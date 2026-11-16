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
 *
 * Prefer the web wizard at /install/ for new deployments.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This installer can only be run from the command line. Use /install/ in a browser.\n");
}

define('CBT_SYSTEM', true);
define('CBT_SYNC_CONTEXT', true);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/functions.php';
require_once dirname(__DIR__) . '/config/sync_install.php';

$options = getopt('', ['role::', 'name::', 'uid::', 'app-url::', 'write-config', 'help']);
if (isset($options['help'])) {
    fwrite(STDOUT, "Usage: php sync/install.php [--role=hub|leaf] [--name=...] [--uid=...] [--app-url=...] [--write-config]\n");
    fwrite(STDOUT, "Prefer the web wizard at /install/ for new deployments.\n");
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
    fwrite(STDOUT, "Restart the installer to apply the new identity.\n");
    exit(0);
}

if ($uid === '' || $uid === 'unknown') {
    fwrite(STDERR, "Node UID is empty. Set it in config/node.local.php or pass --uid.\n");
    exit(1);
}

fwrite(STDOUT, "Installing sync schema on database " . NODE_DB_NAME . " (node " . $uid . ", role " . $role . ")\n");
$result = sync_install_schema(getDB(), $root, $uid, $role);
foreach ($result['log'] as $line) {
    fwrite(STDOUT, "  $line\n");
}
fwrite(STDOUT, "\nDone. {$result['prepared']} tables prepared, {$result['skipped']} skipped.\n");
fwrite(STDOUT, "Node UID: {$result['uid']}\n");
if ($role === 'leaf') {
    fwrite(STDOUT, "Next: register this node on the hub and put hub_url + secret in config/node.local.php.\n");
}
