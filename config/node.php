<?php
/**
 * CBT System - Node identity and online/offline policy
 *
 * Every installation is either a "hub" (the online server) or a "leaf"
 * (an offline examination server). A leaf synchronises its database with
 * the hub and receives policy/restrictions from it.
 *
 * Configuration lives in config/node.local.php (not committed) and can be
 * overridden by environment variables for container/automated deployments.
 */

if (!defined('CBT_SYSTEM')) {
    die('Unauthorized access');
}

$nodeRoot = dirname(__DIR__);

$nodeDefaults = [
    'role' => 'hub',
    'uid' => null,
    'name' => 'CBT Node',
    'secret' => '',
    'hub_url' => '',
    'sync_enabled' => true,
    'sync_interval' => 20,
    'sync_batch_limit' => 500,
    'sync_retention_days' => 180,
    'sync_run_key' => '',
    'app_url' => null,
    'db_host' => null,
    'db_name' => null,
    'db_user' => null,
    'db_pass' => null,
];

$nodeConfig = $nodeDefaults;
$nodeLocalFile = __DIR__ . '/node.local.php';
if (is_file($nodeLocalFile)) {
    $loaded = require $nodeLocalFile;
    if (is_array($loaded)) {
        $nodeConfig = array_merge($nodeConfig, $loaded);
    }
}

$nodeEnvMap = [
    'role' => 'CBT_NODE_ROLE',
    'uid' => 'CBT_NODE_UID',
    'sync_run_key' => 'CBT_SYNC_RUN_KEY',
    'name' => 'CBT_NODE_NAME',
    'secret' => 'CBT_NODE_SECRET',
    'hub_url' => 'CBT_HUB_URL',
    'app_url' => 'CBT_APP_URL',
    'db_host' => 'CBT_DB_HOST',
    'db_name' => 'CBT_DB_NAME',
    'db_user' => 'CBT_DB_USER',
    'db_pass' => 'CBT_DB_PASS',
];
foreach ($nodeEnvMap as $key => $envName) {
    $value = getenv($envName);
    if ($value !== false && $value !== '') {
        $nodeConfig[$key] = $value;
    }
}
$syncEnabledEnv = getenv('CBT_SYNC_ENABLED');
if ($syncEnabledEnv !== false && $syncEnabledEnv !== '') {
    $nodeConfig['sync_enabled'] = !in_array(strtolower((string) $syncEnabledEnv), ['0', 'false', 'no', 'off'], true);
}

// A stable identity is required so change-log rows are attributed correctly.
if (empty($nodeConfig['uid'])) {
    $uidFile = $nodeRoot . '/storage/sync/node_uid';
    if (is_file($uidFile)) {
        $nodeConfig['uid'] = trim((string) file_get_contents($uidFile));
    }
    if (empty($nodeConfig['uid'])) {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $nodeConfig['uid'] = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
        if (!is_dir(dirname($uidFile))) {
            @mkdir(dirname($uidFile), 0770, true);
        }
        @file_put_contents($uidFile, $nodeConfig['uid'], LOCK_EX);
    }
}

$nodeRole = strtolower((string) $nodeConfig['role']) === 'leaf' ? 'leaf' : 'hub';

define('NODE_ROLE', $nodeRole);
define('NODE_UID', (string) $nodeConfig['uid']);
define('NODE_NAME', (string) $nodeConfig['name']);
define('NODE_SECRET', (string) $nodeConfig['secret']);
define('NODE_HUB_URL', rtrim((string) $nodeConfig['hub_url'], '/'));
define('NODE_SYNC_ENABLED', (bool) $nodeConfig['sync_enabled']);
define('NODE_SYNC_INTERVAL', max(5, (int) $nodeConfig['sync_interval']));
define('NODE_SYNC_BATCH_LIMIT', max(50, (int) $nodeConfig['sync_batch_limit']));
define('NODE_SYNC_RUN_KEY', (string) $nodeConfig['sync_run_key']);
define('NODE_SYNC_RETENTION_DAYS', max(7, (int) $nodeConfig['sync_retention_days']));
define('NODE_STORAGE_DIR', $nodeRoot . '/storage/sync');
define('NODE_POLICY_FILE', NODE_STORAGE_DIR . '/policy.json');
define('NODE_DB_HOST', $nodeConfig['db_host'] ?: 'localhost');
define('NODE_DB_NAME', $nodeConfig['db_name'] ?: 'cbt_system');
define('NODE_DB_USER', $nodeConfig['db_user'] ?: 'root');
define('NODE_DB_PASS', $nodeConfig['db_pass'] !== null ? (string) $nodeConfig['db_pass'] : '');
define('NODE_APP_URL', $nodeConfig['app_url'] ? rtrim((string) $nodeConfig['app_url'], '/') : null);

if (!is_dir(NODE_STORAGE_DIR)) {
    @mkdir(NODE_STORAGE_DIR, 0770, true);
}

/**
 * Default restrictions. Hub always has every capability.
 */
$nodeDefaultPolicy = [
    'suspended' => false,
    'student_edit' => true,
    'question_edit' => true,
    'exam_edit' => true,
    'exam_access' => true,
    'results_view' => true,
    'results_edit' => true,
    'file_updates' => true,
    'resync_requested' => false,
    'notes' => '',
    'updated_at' => null,
];

/**
 * Current effective policy for this node.
 * On a hub every capability is always permitted.
 */
function node_policy(): array {
    global $nodeDefaultPolicy;

    if (defined('NODE_ROLE') && NODE_ROLE === 'hub') {
        $hub = $nodeDefaultPolicy;
        $hub['notes'] = 'Hub node';
        return $hub;
    }

    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $cached = $nodeDefaultPolicy;
    if (defined('NODE_POLICY_FILE') && is_file(NODE_POLICY_FILE)) {
        $decoded = json_decode((string) file_get_contents(NODE_POLICY_FILE), true);
        if (is_array($decoded)) {
            $cached = array_merge($cached, $decoded);
        }
    }
    return $cached;
}

function node_is_hub(): bool {
    return defined('NODE_ROLE') && NODE_ROLE === 'hub';
}

function node_is_leaf(): bool {
    return defined('NODE_ROLE') && NODE_ROLE === 'leaf';
}

function node_is_suspended(): bool {
    if (node_is_hub()) {
        return false;
    }
    $policy = node_policy();
    return !empty($policy['suspended']);
}

/**
 * Check whether this node is allowed to perform a restricted action.
 */
function node_can(string $capability): bool {
    if (node_is_hub()) {
        return true;
    }
    $policy = node_policy();
    return !empty($policy[$capability]);
}
