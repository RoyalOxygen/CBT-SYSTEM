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
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'register') {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') jsonResponse(false, 'Enter a name for the exam hall');

        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $nodeUid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
        $secret = bin2hex(random_bytes(32));
        $policy = [
            'suspended' => false,
            'student_edit' => true,
            'question_edit' => true,
            'exam_edit' => true,
            'exam_access' => true,
            'results_view' => true,
            'results_edit' => true,
            'file_updates' => true,
            'resync_requested' => false,
            'notes' => 'Registered from web installer ' . date('c'),
        ];
        $db->prepare("INSERT INTO sync_node (node_uid, name, role, secret_token, status, policy_json) VALUES (?, ?, 'leaf', ?, 'active', ?)")
            ->execute([$nodeUid, $name, $secret, json_encode($policy, JSON_UNESCAPED_SLASHES)]);

        $hubUrl = NODE_APP_URL ?: '';
        if ($hubUrl === '') {
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $hubUrl = ($https ? 'https' : 'http') . '://' . $host;
        }
        $hubUrl = rtrim($hubUrl, '/');
        $pairing = [
            'uid' => $nodeUid,
            'name' => $name,
            'secret' => $secret,
            'hub_url' => $hubUrl,
        ];
        logActivity('admin', $_SESSION['admin_id'], 'sync_node_register', "Registered offline node $name");
        jsonResponse(true, 'Exam hall registered. Copy the pairing code onto the offline installer.', [
            'node_uid' => $nodeUid,
            'pairing' => base64_encode(json_encode($pairing, JSON_UNESCAPED_SLASHES)),
            'pairing_json' => $pairing,
        ]);
    }

    $nodeUid = trim((string) ($_POST['node_uid'] ?? ''));
    if ($nodeUid === '') jsonResponse(false, 'Node is required');

    $stmt = $db->prepare("SELECT * FROM sync_node WHERE node_uid = ? AND role = 'leaf' LIMIT 1");
    $stmt->execute([$nodeUid]);
    $node = $stmt->fetch();
    if (!$node) jsonResponse(false, 'Node not found');

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
