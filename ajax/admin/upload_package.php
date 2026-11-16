<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method');
if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) jsonResponse(false, 'Invalid token');
if (!isAdminLoggedIn() || ($_SESSION['admin_role'] ?? '') !== 'super_admin') jsonResponse(false, 'Access denied');
if (!node_is_hub()) jsonResponse(false, 'Only the online hub can queue updates');

try {
    if (empty($_FILES['package']) || $_FILES['package']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(false, 'Please upload a valid ZIP package');
    }
    if ($_FILES['package']['size'] > 25 * 1024 * 1024) {
        jsonResponse(false, 'Package is larger than 25 MB');
    }
    $original = (string) $_FILES['package']['name'];
    if (strtolower(pathinfo($original, PATHINFO_EXTENSION)) !== 'zip') {
        jsonResponse(false, 'Only .zip packages are supported');
    }
    if (!class_exists('ZipArchive')) {
        jsonResponse(false, 'PHP zip extension is required on the hub');
    }

    $zip = new ZipArchive();
    if ($zip->open($_FILES['package']['tmp_name']) !== true) {
        jsonResponse(false, 'Cannot open the ZIP package');
    }
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = str_replace('\\', '/', (string) $zip->getNameIndex($i));
        if ($name === '' || strpos($name, '..') !== false || $name[0] === '/' || preg_match('#^(\.git|storage|config)/#', $name)) {
            $zip->close();
            jsonResponse(false, 'Package contains an unsafe path: ' . $name);
        }
    }
    $zip->close();

    $version = trim((string) ($_POST['version'] ?? ''));
    if ($version === '') jsonResponse(false, 'Version is required');
    $nodeUid = trim((string) ($_POST['node_uid'] ?? ''));
    if ($nodeUid !== '') {
        $stmt = getDB()->prepare("SELECT id FROM sync_node WHERE node_uid = ? AND role = 'leaf'");
        $stmt->execute([$nodeUid]);
        if (!$stmt->fetchColumn()) jsonResponse(false, 'Target node not found');
    }

    $dir = dirname(__DIR__, 2) . '/storage/sync/packages';
    if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) {
        jsonResponse(false, 'Cannot create package storage directory');
    }
    $stored = bin2hex(random_bytes(12)) . '.zip';
    $target = $dir . '/' . $stored;
    if (!move_uploaded_file($_FILES['package']['tmp_name'], $target)) {
        jsonResponse(false, 'Could not store the package');
    }

    $sha = hash_file('sha256', $target);
    $size = filesize($target);
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $stmt = getDB()->prepare("INSERT INTO sync_file_package (node_uid, version, filename, stored_name, sha256, size, notes, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, 'queued', ?)");
    $stmt->execute([$nodeUid !== '' ? $nodeUid : null, $version, $original, $stored, $sha, $size, $notes, $_SESSION['admin_id']]);

    logActivity('admin', $_SESSION['admin_id'], 'sync_package_upload', "Queued package $version for " . ($nodeUid ?: 'all nodes'));
    jsonResponse(true, 'Package queued', ['id' => (int) getDB()->lastInsertId()]);
} catch (PDOException $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
