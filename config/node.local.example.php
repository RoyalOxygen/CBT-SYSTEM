<?php
/**
 * Copy this file to config/node.local.php and fill in the values.
 * Do not commit node.local.php.
 */

return [
    // hub = online server, leaf = offline examination server
    'role' => 'hub',
    'uid' => '',
    'name' => 'CBT Node',
    'secret' => '',
    'hub_url' => '',
    'sync_enabled' => true,
    'sync_interval' => 20,
    'sync_batch_limit' => 500,
    'sync_retention_days' => 180,
    'sync_run_key' => '',
    'app_url' => 'http://localhost/cbt-system',
    'db_host' => 'localhost',
    'db_name' => 'cbt_system',
    'db_user' => 'root',
    'db_pass' => '',
];
