<?php
/**
 * CBT System - Synchronisation installer
 *
 * Usage (from the project root):
 *   php sync/install.php --role=hub --name="Online CBT"
 *   php sync/install.php --role=leaf --name="Offline CBT"
 *
 * Creates the sync tables, adds sync_uuid columns and installs the change-log
 * triggers. Safe to re-run after upgrading the application schema.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This installer can only be run from the command line.\n");
}

define('CBT_SYSTEM', true);
define('CBT_SYNC_CONTEXT', true);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/functions.php';

$options = getopt('', ['role::', 'name::', 'uid::', 'app-url::', 'write-config', 'help']);
if (isset($options['help'])) {
    fwrite(STDOUT, "Usage: php sync/install.php [--role=hub|leaf] [--name=...] [--uid=...] [--app-url=...] [--write-config]\n");
    exit(0);
}

$root = dirname(__DIR__);
$role = isset($options['role']) ? strtolower((string) $options['role']) : NODE_ROLE;
if (!in_array($role, ['hub', 'leaf'], true)) {
    fwrite(STDERR, "Role must be hub or leaf.\n");
    exit(1);
}
$name = isset($options['name']) ? (string) $options['name'] : NODE_NAME;
$uid = isset($options['uid']) ? (string) $options['uid'] : NODE_UID;
$appUrl = isset($options['app-url']) ? rtrim((string) $options['app-url'], '/') : (NODE_APP_URL ?: '');

if (isset($options['write-config']) && !is_file($root . '/config/node.local.php')) {
    $php = "<?php\nreturn [\n"
        . "    'role' => " . var_export($role, true) . ",\n"
        . "    'uid' => " . var_export($uid, true) . ",\n"
        . "    'name' => " . var_export($name, true) . ",\n"
        . "    'app_url' => " . var_export($appUrl, true) . ",\n"
        . "];\n";
    file_put_contents($root . '/config/node.local.php', $php, LOCK_EX);
    fwrite(STDOUT, "Wrote config/node.local.php\n");
    // Reload so the rest of the installer uses the written identity.
    $env = [];
    foreach (['CBT_NODE_ROLE', 'CBT_NODE_UID', 'CBT_NODE_NAME', 'CBT_APP_URL', 'CBT_DB_HOST', 'CBT_DB_NAME', 'CBT_DB_USER', 'CBT_DB_PASS'] as $key) {
        $value = getenv($key);
        if ($value !== false) {
            $env[$key] = $value;
        }
    }
    fwrite(STDOUT, "Restart the installer to apply the new identity.\n");
    exit(0);
}

if ($uid === '' || $uid === 'unknown') {
    fwrite(STDERR, "Node UID is empty. Set it in config/node.local.php or pass --uid.\n");
    exit(1);
}

$db = getDB();
$tables = require $root . '/config/sync_tables.php';

function sync_install_run_sql_file(PDO $db, string $file): void {
    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException("Cannot read $file");
    }
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }
        $db->exec($statement);
    }
}

function sync_install_table_exists(PDO $db, string $table): bool {
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmt->execute([$table]);
    return (int) $stmt->fetchColumn() > 0;
}

function sync_install_column_exists(PDO $db, string $table, string $column): bool {
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function sync_install_index_exists(PDO $db, string $table, string $index): bool {
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?");
    $stmt->execute([$table, $index]);
    return (int) $stmt->fetchColumn() > 0;
}

function sync_install_columns(PDO $db, string $table): array {
    $stmt = $db->query("SHOW COLUMNS FROM `" . str_replace('`', '``', $table) . "`");
    $columns = [];
    foreach ($stmt->fetchAll() as $row) {
        if (stripos($row['Extra'] ?? '', 'GENERATED') !== false) {
            continue;
        }
        $columns[] = $row['Field'];
    }
    return $columns;
}

fwrite(STDOUT, "Installing sync schema on database " . NODE_DB_NAME . " (node " . $uid . ", role " . $role . ")\n");
sync_install_run_sql_file($db, $root . '/sql/sync_schema.sql');

// Trigger fallback identity, also used by scripts that bypass PDO.
$db->prepare("INSERT INTO sync_state (state_key, state_value) VALUES ('node_uid', ?) ON DUPLICATE KEY UPDATE state_value = VALUES(state_value)")
    ->execute([json_encode($uid)]);

$created = 0;
$skipped = 0;
foreach ($tables as $table => $config) {
    if (!sync_install_table_exists($db, $table)) {
        fwrite(STDERR, "  skip: table `$table` does not exist\n");
        $skipped++;
        continue;
    }

    if (!sync_install_column_exists($db, $table, 'sync_uuid')) {
        $db->exec("ALTER TABLE `$table` ADD COLUMN `sync_uuid` CHAR(36) NULL DEFAULT NULL");
    }
    // Deterministic UUIDs make a cloned database line up with the hub even
    // when each side installed the sync schema independently.
    $db->exec("UPDATE `$table` SET `sync_uuid` = CONCAT(
        SUBSTR(MD5(CONCAT('cbt-sync|', '$table', '|', `id`)), 1, 8), '-',
        SUBSTR(MD5(CONCAT('cbt-sync|', '$table', '|', `id`)), 9, 4), '-',
        '4', SUBSTR(MD5(CONCAT('cbt-sync|', '$table', '|', `id`)), 14, 3), '-',
        '8', SUBSTR(MD5(CONCAT('cbt-sync|', '$table', '|', `id`)), 18, 3), '-',
        SUBSTR(MD5(CONCAT('cbt-sync|', '$table', '|', `id`)), 21, 12)
    ) WHERE `sync_uuid` IS NULL OR `sync_uuid` = ''");
    if (!sync_install_index_exists($db, $table, 'uq_sync_uuid')) {
        $db->exec("ALTER TABLE `$table` ADD UNIQUE KEY `uq_sync_uuid` (`sync_uuid`)");
    }

    $columns = sync_install_columns($db, $table);
    $jsonArgs = [];
    $selectJsonArgs = [];
    foreach ($columns as $column) {
        if ($column === 'sync_uuid') {
            continue;
        }
        $escaped = "'" . str_replace("'", "''", $column) . "'";
        $jsonArgs[] = $escaped . ", NEW.`" . $column . "`";
        $selectJsonArgs[] = $escaped . ", `" . $column . "`";
    }
    $jsonExpr = 'JSON_OBJECT(' . implode(', ', $jsonArgs) . ')';
    $selectJsonExpr = 'JSON_OBJECT(' . implode(', ', $selectJsonArgs) . ')';

    foreach (['bi' => 'BEFORE INSERT', 'ai' => 'AFTER INSERT', 'au' => 'AFTER UPDATE', 'ad' => 'AFTER DELETE'] as $suffix => $timing) {
        $trigger = 'trg_sync_' . substr($table, 0, 40) . '_' . $suffix;
        $db->exec("DROP TRIGGER IF EXISTS `$trigger`");

        if ($suffix === 'bi') {
            // Do not use COALESCE(NEW.sync_uuid, UUID()) here: MariaDB types the
            // COALESCE result as UUID and then rejects non-v4 values that
            // MySQL/older MariaDB accept. An explicit IF avoids the coercion.
            $sql = "CREATE TRIGGER `$trigger` BEFORE INSERT ON `$table` FOR EACH ROW "
                . "BEGIN "
                . "IF NEW.`sync_uuid` IS NULL OR NEW.`sync_uuid` = '' THEN "
                . "SET NEW.`sync_uuid` = UUID(); "
                . "END IF; "
                . "END";
        } else {
            $op = $suffix === 'ai' ? 'insert' : ($suffix === 'au' ? 'update' : 'delete');
            $row = $op === 'delete' ? 'OLD' : 'NEW';
            $deleteArgs = [];
            foreach ($columns as $column) {
                if ($column === 'sync_uuid') {
                    continue;
                }
                $deleteArgs[] = "'" . str_replace("'", "''", $column) . "', OLD.`" . $column . "`";
            }
            $payloadExpr = $op === 'delete' ? 'JSON_OBJECT(' . implode(', ', $deleteArgs) . ')' : $jsonExpr;
            $sql = "CREATE TRIGGER `$trigger` $timing ON `$table` FOR EACH ROW "
                . "INSERT INTO `sync_change` (`change_uid`, `origin_uid`, `table_name`, `op`, `record_uuid`, `payload`, `changed_at`) "
                . "SELECT UUID(), COALESCE(@cbt_node_uid, (SELECT JSON_UNQUOTE(state_value) FROM `sync_state` WHERE `state_key` = 'node_uid' LIMIT 1), 'unknown'), '$table', '$op', $row.`sync_uuid`, $payloadExpr, NOW() "
                . "FROM DUAL WHERE COALESCE(@cbt_sync_applying, 0) = 0";
        }
        $db->exec($sql);
    }

    if ($role === 'hub') {
        // Snapshot the existing rows as changes so a brand-new leaf can build a
        // complete database from the hub, not just changes made after install.
        $changeUidExpr = "CONCAT(
            SUBSTR(MD5(CONCAT('cbt-sync-change|', '$table', '|', `id`)), 1, 8), '-',
            SUBSTR(MD5(CONCAT('cbt-sync-change|', '$table', '|', `id`)), 9, 4), '-',
            '4', SUBSTR(MD5(CONCAT('cbt-sync-change|', '$table', '|', `id`)), 14, 3), '-',
            '8', SUBSTR(MD5(CONCAT('cbt-sync-change|', '$table', '|', `id`)), 18, 3), '-',
            SUBSTR(MD5(CONCAT('cbt-sync-change|', '$table', '|', `id`)), 21, 12)
        )";
        $db->prepare("INSERT IGNORE INTO `sync_change` (`change_uid`, `origin_uid`, `table_name`, `op`, `record_uuid`, `payload`, `changed_at`)
            SELECT $changeUidExpr, ?, '$table', 'insert', `sync_uuid`, $selectJsonExpr, NOW() FROM `$table` WHERE `sync_uuid` IS NOT NULL")
            ->execute([$uid]);
    }

    fwrite(STDOUT, "  ok: $table\n");
    $created++;
}

fwrite(STDOUT, "\nDone. $created tables prepared, $skipped skipped.\n");
fwrite(STDOUT, "Node UID: $uid\n");
if ($role === 'leaf') {
    fwrite(STDOUT, "Next: register this node on the hub and put hub_url + secret in config/node.local.php.\n");
}
