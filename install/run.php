<?php
/**
 * CBT System - Web installer endpoint
 */

define('CBT_SYSTEM', true);
define('CBT_SYNC_CONTEXT', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    install_json(false, 'POST required', [], 405);
}

if (!install_csrf_check((string) ($_POST['csrf'] ?? ''))) {
    install_json(false, 'Invalid security token. Refresh the installer and try again.', [], 400);
}

try {
    $result = install_run($_POST);
    install_json(true, 'Installation complete', $result);
} catch (Throwable $e) {
    install_json(false, $e->getMessage(), [], 400);
}
