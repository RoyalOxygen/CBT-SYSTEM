<?php
/**
 * Shared synchronisation schema installer used by the CLI and the web wizard.
 */

if (!defined('CBT_SYSTEM')) {
    die('Unauthorized access');
}

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

function sync_install_schema(PDO $db, string $root, string $uid, string $role): array {
    if ($uid === '' || $uid === 'unknown') {
        throw new RuntimeException('Node UID is empty.');
    }
    if (!in_array($role, ['hub', 'leaf'], true)) {
        throw new RuntimeException('Role must be hub or leaf.');
    }

    $tables = require $root . '/config/sync_tables.php';
    sync_install_run_sql_file($db, $root . '/sql/sync_schema.sql');

    $db->prepare("INSERT INTO sync_state (state_key, state_value) VALUES ('node_uid', ?) ON DUPLICATE KEY UPDATE state_value = VALUES(state_value)")
        ->execute([json_encode($uid)]);

    $prepared = 0;
    $skipped = 0;
    $log = [];

    foreach ($tables as $table => $config) {
        if (!sync_install_table_exists($db, $table)) {
            $skipped++;
            $log[] = "skip: table `$table` does not exist";
            continue;
        }

        if (!sync_install_column_exists($db, $table, 'sync_uuid')) {
            $db->exec("ALTER TABLE `$table` ADD COLUMN `sync_uuid` CHAR(36) NULL DEFAULT NULL");
        }

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

        $prepared++;
        $log[] = "ok: $table";
    }

    return [
        'prepared' => $prepared,
        'skipped' => $skipped,
        'uid' => $uid,
        'role' => $role,
        'log' => $log,
    ];
}
