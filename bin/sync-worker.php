<?php
/**
 * Long-running background synchronisation worker for a leaf node.
 *
 * Usage: php bin/sync-worker.php
 *
 * Prefer a process supervisor (systemd/supervisord) so the worker restarts
 * automatically. The normal web-request shutdown hook also syncs in the
 * background, so this worker is optional but recommended for busy exam days.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

define('CBT_SYSTEM', true);
define('CBT_SYNC_CONTEXT', true);

$root = dirname(__DIR__);
require_once $root . '/config/config.php';
require_once $root . '/config/functions.php';

if (!node_is_leaf()) {
    fwrite(STDERR, "This worker is only needed on a leaf (offline) node.\n");
    exit(1);
}

$running = true;
if (function_exists('pcntl_signal')) {
    pcntl_async_signals(true);
    pcntl_signal(SIGTERM, function () use (&$running) { $running = false; });
    pcntl_signal(SIGINT, function () use (&$running) { $running = false; });
}

fwrite(STDOUT, "CBT sync worker started for node " . NODE_UID . "\n");
$lastPrune = date('Y-m-d');
while ($running) {
    if (date('Y-m-d') !== $lastPrune) {
        try {
            $pruned = sync_prune();
            fwrite(STDOUT, date('c') . ' prune ' . json_encode($pruned, JSON_UNESCAPED_SLASHES) . "\n");
        } catch (Throwable $e) {
            fwrite(STDERR, 'prune failed: ' . $e->getMessage() . "\n");
        }
        $lastPrune = date('Y-m-d');
    }
    $result = sync_run(false);
    fwrite(STDOUT, date('c') . ' ' . json_encode($result, JSON_UNESCAPED_SLASHES) . "\n");
    sleep(NODE_SYNC_INTERVAL);
}
fwrite(STDOUT, "CBT sync worker stopped\n");
