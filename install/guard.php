<?php
/**
 * Redirect a fresh copy to the web installer.
 */

function cbt_needs_install(string $root): bool {
    if (is_file($root . '/storage/sync/installed.lock')) {
        return false;
    }
    if (is_file($root . '/config/node.local.php')) {
        return false;
    }
    $envUid = getenv('CBT_NODE_UID');
    if ($envUid !== false && $envUid !== '') {
        return false;
    }
    $envDb = getenv('CBT_DB_NAME');
    if ($envDb !== false && $envDb !== '') {
        return false;
    }
    return true;
}

function cbt_redirect_to_installer(string $root, string $to = 'install/'): void {
    if (!cbt_needs_install($root)) {
        return;
    }
    header('Location: ' . $to);
    exit;
}
