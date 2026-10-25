<?php
/**
 * Pull students, photos and face templates from the attendance (SAMS) server.
 *
 * Usage: php bin/sams-sync.php [--full]
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

if (!node_is_hub()) {
    fwrite(STDERR, "SAMS pull runs on the online CBT hub only. Offline halls receive students via hub/leaf sync.\n");
    exit(1);
}

$options = getopt('', ['full']);
if (isset($options['full'])) {
    sync_state_set('sams_since', '');
}

$result = sams_pull_once(true);
fwrite(STDOUT, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
exit(!empty($result['ok']) ? 0 : 1);
