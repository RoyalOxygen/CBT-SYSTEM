<?php
/**
 * Local emergency policy tool for an offline node.
 *
 * Usage:
 *   php bin/policy.php --show
 *   php bin/policy.php --clear-suspension
 *
 * This only changes the local policy file. The hub will overwrite it on the
 * next successful sync unless the hub also removes the restriction.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

define('CBT_SYSTEM', true);
define('CBT_SYNC_CONTEXT', true);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/functions.php';

$options = getopt('', ['show', 'clear-suspension', 'help']);
if (isset($options['help'])) {
    fwrite(STDOUT, "Usage: php bin/policy.php [--show] [--clear-suspension]\n");
    exit(0);
}

if (!node_is_leaf()) {
    fwrite(STDERR, "This tool is for offline (leaf) nodes only.\n");
    exit(1);
}

$policy = node_policy();

if (isset($options['clear-suspension'])) {
    $policy['suspended'] = false;
    $policy['notes'] = 'Suspension cleared locally at ' . date('c');
    $policy['updated_at'] = date('c');
    if (file_put_contents(NODE_POLICY_FILE, json_encode($policy, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) === false) {
        fwrite(STDERR, "Could not write " . NODE_POLICY_FILE . "\n");
        exit(1);
    }
    fwrite(STDOUT, "Local suspension cleared. The next hub policy will overwrite this.\n");
    exit(0);
}

fwrite(STDOUT, json_encode($policy, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
