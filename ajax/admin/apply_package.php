<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method');
if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) jsonResponse(false, 'Invalid token');
if (!isAdminLoggedIn() || ($_SESSION['admin_role'] ?? '') !== 'super_admin') jsonResponse(false, 'Access denied');
if (!node_is_leaf()) jsonResponse(false, 'Only an offline node can apply updates');
node_require('file_updates');

try {
    $packageId = (int) ($_POST['package_id'] ?? 0);
    if ($packageId <= 0) jsonResponse(false, 'Invalid package');
    $report = sync_apply_package($packageId);
    logActivity('admin', $_SESSION['admin_id'], 'apply_package', 'Applied package ' . $packageId . ' (' . count($report['applied']) . ' files)');
    jsonResponse(true, 'Update applied. ' . count($report['applied']) . ' file(s) written, ' . count($report['skipped']) . ' skipped.', ['report' => $report]);
} catch (Throwable $e) {
    jsonResponse(false, $e->getMessage());
}
