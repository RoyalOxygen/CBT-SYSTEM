<?php
/**
 * CBT System - Authenticated file-package download for leaf nodes.
 *
 * A leaf must still ask its local administrator before applying a package.
 */

define('CBT_SYSTEM', true);
define('CBT_SYNC_CONTEXT', true);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/functions.php';

$nodeUid = (string) ($_SERVER['HTTP_X_CBT_NODE'] ?? '');
$timestamp = (int) ($_SERVER['HTTP_X_CBT_TIMESTAMP'] ?? 0);
$nonce = (string) ($_SERVER['HTTP_X_CBT_NONCE'] ?? '');
$signature = (string) ($_SERVER['HTTP_X_CBT_SIGNATURE'] ?? '');
$packageId = (int) ($_GET['id'] ?? 0);

function download_deny(string $message, int $status = 403): void {
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
    exit;
}

if ($nodeUid === '' || $nonce === '' || $signature === '' || $packageId <= 0) {
    download_deny('Missing authentication parameters', 400);
}
if (abs(time() - $timestamp) > 300) {
    download_deny('Request expired', 401);
}
if (strlen($nonce) < 16 || strlen($nonce) > 64) {
    download_deny('Invalid nonce', 401);
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM sync_node WHERE node_uid = ? LIMIT 1");
    $stmt->execute([$nodeUid]);
    $node = $stmt->fetch();
    if (!$node || $node['status'] === 'retired') {
        download_deny('Unknown or retired node', 403);
    }

    $expected = hash_hmac('sha256', $nodeUid . '|' . $timestamp . '|' . $nonce . '|' . $packageId, (string) $node['secret_token']);
    if (!hash_equals($expected, $signature)) {
        download_deny('Invalid signature', 403);
    }

    $db->prepare("DELETE FROM sync_nonce WHERE created_at < (NOW() - INTERVAL 10 MINUTE)")->execute();
    try {
        $db->prepare("INSERT INTO sync_nonce (node_uid, nonce) VALUES (?, ?)")->execute([$nodeUid, $nonce]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            download_deny('Replay detected', 403);
        }
        throw $e;
    }

    $policy = json_decode((string) ($node['policy_json'] ?? ''), true);
    if (is_array($policy) && array_key_exists('file_updates', $policy) && empty($policy['file_updates'])) {
        download_deny('File updates are disabled for this node', 403);
    }

    $stmt = $db->prepare("SELECT * FROM sync_file_package WHERE id = ? AND status IN ('queued','downloaded','applied') AND (node_uid IS NULL OR node_uid = ?) LIMIT 1");
    $stmt->execute([$packageId, $nodeUid]);
    $package = $stmt->fetch();
    if (!$package) {
        download_deny('Package not found', 404);
    }

    $base = realpath(dirname(__DIR__) . '/storage/sync/packages');
    $file = realpath($base . '/' . basename((string) $package['stored_name']));
    if ($base === false || $file === false || strpos($file, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($file)) {
        download_deny('Package file is missing', 404);
    }

    $db->prepare("UPDATE sync_node SET last_seen_at = NOW(), last_ip = ? WHERE node_uid = ?")
        ->execute([$_SERVER['REMOTE_ADDR'] ?? null, $nodeUid]);

    header('Content-Type: application/zip');
    header('Content-Length: ' . filesize($file));
    header('Content-Disposition: attachment; filename="package-' . $packageId . '.zip"');
    header('X-Package-Sha256: ' . $package['sha256']);
    header('Cache-Control: no-store');
    readfile($file);
    exit;
} catch (Throwable $e) {
    error_log('Package download error: ' . $e->getMessage());
    download_deny('Download failed', 500);
}
