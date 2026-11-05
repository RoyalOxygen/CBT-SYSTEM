<?php
/**
 * Run one synchronisation cycle on a leaf node.
 *
 * Usage: php bin/sync-once.php [--full]
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

$options = getopt('', ['full']);
if (isset($options['full'])) {
    sync_full_resync();
}

$result = sync_run(true);
fwrite(STDOUT, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
exit(!empty($result['ok']) ? 0 : 1);
