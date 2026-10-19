<?php
/**
 * CBT System - Register an offline node on the online hub
 *
 * Usage:
 *   php sync/register_node.php --name="Offline CBT - Campus A"
 *
 * Prints a config snippet for the leaf server. The secret is shown only once.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This command can only be run from the command line.\n");
}

define('CBT_SYSTEM', true);
define('CBT_SYNC_CONTEXT', true);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/functions.php';

$options = getopt('', ['name::', 'help']);
if (isset($options['help'])) {
    fwrite(STDOUT, "Usage: php sync/register_node.php --name=\"Offline CBT - Campus A\"\n");
    exit(0);
}

if (!node_is_hub()) {
    fwrite(STDERR, "This command must be run on the online hub (role=hub).\n");
    exit(1);
}

$name = trim((string) ($options['name'] ?? ''));
if ($name === '') {
    fwrite(STDERR, "A node name is required. Use --name=\"...\"\n");
    exit(1);
}

$uidBytes = random_bytes(16);
$uidBytes[6] = chr((ord($uidBytes[6]) & 0x0f) | 0x40);
$uidBytes[8] = chr((ord($uidBytes[8]) & 0x3f) | 0x80);
$nodeUid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($uidBytes), 4));
$secret = bin2hex(random_bytes(32));

$defaultPolicy = [
    'suspended' => false,
    'student_edit' => true,
    'question_edit' => true,
    'exam_edit' => true,
    'exam_access' => true,
    'results_view' => true,
    'results_edit' => true,
    'file_updates' => true,
    'resync_requested' => false,
    'notes' => 'Registered ' . date('c'),
];

$db = getDB();
$stmt = $db->prepare("INSERT INTO sync_node (node_uid, name, role, secret_token, status, policy_json) VALUES (?, ?, 'leaf', ?, 'active', ?)");
$stmt->execute([$nodeUid, $name, $secret, json_encode($defaultPolicy, JSON_UNESCAPED_SLASHES)]);

$hubUrl = NODE_APP_URL ?: 'https://your-online-server.example/cbt-system';

fwrite(STDOUT, "\nNode registered successfully.\n\n");
fwrite(STDOUT, "Add this to config/node.local.php on the OFFLINE server:\n\n");
fwrite(STDOUT, "<?php\nreturn [\n");
fwrite(STDOUT, "    'role' => 'leaf',\n");
fwrite(STDOUT, "    'uid' => " . var_export($nodeUid, true) . ",\n");
fwrite(STDOUT, "    'name' => " . var_export($name, true) . ",\n");
fwrite(STDOUT, "    'hub_url' => " . var_export($hubUrl, true) . ",\n");
fwrite(STDOUT, "    'secret' => " . var_export($secret, true) . ",\n");
fwrite(STDOUT, "    'app_url' => 'http://offline-server/cbt-system',\n");
fwrite(STDOUT, "    'db_host' => 'localhost',\n");
fwrite(STDOUT, "    'db_name' => 'cbt_system',\n");
fwrite(STDOUT, "    'db_user' => 'cbt_user',\n");
fwrite(STDOUT, "    'db_pass' => 'change-me',\n");
fwrite(STDOUT, "];\n\n");
fwrite(STDOUT, "Keep the secret private. It is not stored anywhere else on the hub.\n");
