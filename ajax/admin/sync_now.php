<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method');
if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) jsonResponse(false, 'Invalid token');
if (!isAdminLoggedIn()) jsonResponse(false, 'Access denied');
if (!node_is_leaf()) jsonResponse(false, 'Only an offline node can sync');

if (NODE_HUB_URL === '' || NODE_SECRET === '') {
    jsonResponse(false, 'Hub URL or node secret is not configured');
}

$result = sync_run(true);
if (empty($result['ok'])) {
    jsonResponse(false, $result['error'] ?? 'Sync failed');
}
$push = (int) ($result['push']['pushed'] ?? 0);
$pull = (int) ($result['pull']['applied'] ?? 0);
jsonResponse(true, "Sync complete: $push uploaded, $pull downloaded", ['result' => $result]);
