<?php
/**
 * CBT System - Manual/cron sync trigger for a leaf node.
 *
 * Web:  /sync/run.php?key=YOUR_KEY
 * CLI:  php sync/run.php [--full]
 *
 * Set SYNC_RUN_KEY in config/node.local.php (or CBT_SYNC_RUN_KEY in the
 * environment) before exposing this endpoint.
 */

define('CBT_SYSTEM', true);
define('CBT_SYNC_CONTEXT', true);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/functions.php';

$isCli = PHP_SAPI === 'cli';
if (!$isCli) {
    header('Content-Type: application/json; charset=utf-8');
    $configuredKey = getenv('CBT_SYNC_RUN_KEY') ?: '';
    if ($configuredKey === '' && defined('NODE_SYNC_RUN_KEY')) {
        $configuredKey = NODE_SYNC_RUN_KEY;
    }
    $provided = (string) ($_GET['key'] ?? ($_SERVER['HTTP_X_SYNC_KEY'] ?? ''));
    if ($configuredKey === '' || !hash_equals($configuredKey, $provided)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }
}

if (!$isCli && !empty($_GET['full'])) {
    sync_full_resync();
}

$result = sync_run(true);
if (!$isCli) {
    echo json_encode(['success' => !empty($result['ok']), 'result' => $result], JSON_UNESCAPED_SLASHES);
} else {
    fwrite(STDOUT, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
}
