<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method');
if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) jsonResponse(false, 'Invalid token');
if (!isAdminLoggedIn() || $_SESSION['admin_role'] !== 'super_admin') jsonResponse(false, 'Access denied');
if (!node_is_hub()) jsonResponse(false, 'SAMS pull runs on the online CBT hub only');

if (!empty($_POST['full'])) {
    sync_state_set('sams_since', '');
}

$result = sams_pull_once(true);
if (empty($result['ok'])) {
    jsonResponse(false, $result['error'] ?? 'SAMS sync failed', ['result' => $result]);
}
$imported = (int) ($result['imported'] ?? 0);
jsonResponse(true, "SAMS sync complete: $imported student(s) imported/updated", ['result' => $result]);
