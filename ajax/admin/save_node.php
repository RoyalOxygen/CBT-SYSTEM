<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method');
if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) jsonResponse(false, 'Invalid token');
if (!isAdminLoggedIn() || ($_SESSION['admin_role'] ?? '') !== 'super_admin') jsonResponse(false, 'Access denied');
if (!node_is_hub()) jsonResponse(false, 'Only the online hub can manage nodes');

try {
    $db = getDB();
    $nodeUid = trim((string) ($_POST['node_uid'] ?? ''));
    if ($nodeUid === '') jsonResponse(false, 'Node is required');

    $stmt = $db->prepare("SELECT * FROM sync_node WHERE node_uid = ? AND role = 'leaf' LIMIT 1");
    $stmt->execute([$nodeUid]);
    $node = $stmt->fetch();
    if (!$node) jsonResponse(false, 'Node not found');

    $action = (string) ($_POST['action'] ?? '');
    if ($action !== '') {
        $map = ['suspend' => 'suspended', 'activate' => 'active', 'retire' => 'retired'];
        if (!isset($map[$action])) jsonResponse(false, 'Invalid action');
        $db->prepare("UPDATE sync_node SET status = ?, updated_at = NOW() WHERE node_uid = ?")->execute([$map[$action], $nodeUid]);
        logActivity('admin', $_SESSION['admin_id'], 'sync_node_' . $action, "Set node $nodeUid to " . $map[$action]);
        jsonResponse(true, 'Node updated');
    }

    $name = trim((string) ($_POST['name'] ?? $node['name']));
    $status = (string) ($_POST['status'] ?? $node['status']);
    if (!in_array($status, ['active', 'suspended', 'retired'], true)) jsonResponse(false, 'Invalid status');

    $policy = json_decode((string) ($node['policy_json'] ?? ''), true);
    if (!is_array($policy)) $policy = [];
    $submitted = json_decode((string) ($_POST['policy'] ?? ''), true);
    if (!is_array($submitted)) $submitted = [];

    foreach (['student_edit', 'question_edit', 'exam_edit', 'exam_access', 'results_view', 'results_edit', 'file_updates'] as $key) {
        $policy[$key] = !empty($submitted[$key]);
    }
    $policy['resync_requested'] = !empty($submitted['resync_requested']);
    $policy['updated_at'] = date('c');

    $db->prepare("UPDATE sync_node SET name = ?, status = ?, policy_json = ?, updated_at = NOW() WHERE node_uid = ?")
        ->execute([$name !== '' ? $name : $node['name'], $status, json_encode($policy, JSON_UNESCAPED_SLASHES), $nodeUid]);
    logActivity('admin', $_SESSION['admin_id'], 'sync_node_update', "Updated policy for node $nodeUid");

    jsonResponse(true, 'Node policy saved');
} catch (PDOException $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
