<?php
/**
 * Prune old synchronisation metadata.
 *
 * Usage: php bin/sync-prune.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

define('CBT_SYSTEM', true);
define('CBT_SYNC_CONTEXT', true);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/functions.php';

$result = sync_prune();
fwrite(STDOUT, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
