<?php
/**
 * CBT System - Hub sync API
 *
 * Authenticated with a per-node HMAC-SHA256 secret. Only registered nodes can
 * push or pull data. Every request is signed over the raw JSON body and must
 * carry a fresh timestamp and nonce.
 */

define('CBT_SYSTEM', true);
define('CBT_SYNC_CONTEXT', true);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/functions.php';

header('Content-Type: application/json; charset=utf-8');

function hub_response(bool $success, string $message = '', array $data = [], int $status = 200): void {
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function hub_node_policy(array $node): array {
    $defaults = [
        'suspended' => false,
        'student_edit' => true,
        'question_edit' => true,
        'exam_edit' => true,
        'exam_access' => true,
        'results_view' => true,
        'results_edit' => true,
        'file_updates' => true,
        'resync_requested' => false,
        'retired' => false,
        'notes' => '',
    ];
    $policy = json_decode((string) ($node['policy_json'] ?? ''), true);
    if (!is_array($policy)) {
        $policy = [];
    }
    $policy = array_merge($defaults, $policy);
    if ($node['status'] === 'suspended' || $node['status'] === 'retired') {
        $policy['suspended'] = true;
    }
    if ($node['status'] === 'retired') {
        $policy['retired'] = true;
    }
    $policy['updated_at'] = $node['updated_at'] ?? date('c');
    return $policy;
}

function hub_authenticate(string $rawBody, array $payload, ?string $signature, PDO $db): array {
    $nodeUid = (string) ($payload['node_uid'] ?? '');
    $timestamp = (int) ($payload['timestamp'] ?? 0);
    $nonce = (string) ($payload['nonce'] ?? '');

    if ($nodeUid === '' || $signature === null || $nonce === '') {
        throw new RuntimeException('Missing authentication headers');
    }
    if (abs(time() - $timestamp) > 300) {
        throw new RuntimeException('Request timestamp is outside the allowed window');
    }
    if (strlen($nonce) < 16 || strlen($nonce) > 64) {
        throw new RuntimeException('Invalid nonce');
    }

    $stmt = $db->prepare("SELECT * FROM sync_node WHERE node_uid = ? LIMIT 1");
    $stmt->execute([$nodeUid]);
    $node = $stmt->fetch();
    if (!$node) {
        throw new RuntimeException('Unknown node');
    }
    if ($node['status'] === 'retired') {
        throw new RuntimeException('This node has been retired');
    }

    $expected = hash_hmac('sha256', $rawBody, (string) $node['secret_token']);
    if (!hash_equals($expected, $signature)) {
        throw new RuntimeException('Invalid signature');
    }

    $db->prepare("DELETE FROM sync_nonce WHERE created_at < (NOW() - INTERVAL 10 MINUTE)")->execute();
    try {
        $db->prepare("INSERT INTO sync_nonce (node_uid, nonce) VALUES (?, ?)")->execute([$nodeUid, $nonce]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            throw new RuntimeException('Replay detected');
        }
        throw $e;
    }

    $db->prepare("UPDATE sync_node SET last_seen_at = NOW(), last_ip = ? WHERE node_uid = ?")
        ->execute([$_SERVER['REMOTE_ADDR'] ?? null, $nodeUid]);

    return $node;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        hub_response(false, 'POST required', [], 405);
    }

    $rawBody = file_get_contents('php://input');
    if ($rawBody === false || strlen($rawBody) > 8 * 1024 * 1024) {
        hub_response(false, 'Request body too large', [], 413);
    }
    $payload = json_decode($rawBody, true);
    if (!is_array($payload)) {
        hub_response(false, 'Invalid JSON body', [], 400);
    }

    $action = (string) ($payload['action'] ?? '');
    $signature = $_SERVER['HTTP_X_CBT_SIGNATURE'] ?? null;
    $db = getDB();

    $node = hub_authenticate($rawBody, $payload, $signature, $db);
    $policy = hub_node_policy($node);

    switch ($action) {
        case 'hello':
        case 'policy':
            $cursor = (int) $db->query("SELECT COALESCE(MAX(id), 0) FROM sync_change")->fetchColumn();
            hub_response(true, 'ok', [
                'hub_uid' => NODE_UID,
                'server_time' => date('c'),
                'current_cursor' => $cursor,
                'policy' => $policy,
            ]);
            break;

        case 'pull':
            $cursor = max(0, (int) ($payload['cursor'] ?? 0));
            $limit = (int) ($payload['limit'] ?? 500);
            $limit = max(1, min(1000, $limit));

            $stmt = $db->prepare("SELECT id, change_uid, origin_uid, table_name, op, record_uuid, payload, changed_at FROM sync_change WHERE id > ? AND origin_uid <> ? ORDER BY id ASC LIMIT " . $limit);
            $stmt->execute([$cursor, $node['node_uid']]);
            $rows = $stmt->fetchAll();

            $changes = [];
            $nextCursor = $cursor;
            foreach ($rows as $row) {
                $decoded = $row['payload'] !== null ? json_decode((string) $row['payload'], true) : null;
                if (!is_array($decoded)) {
                    $decoded = [];
                }
                $changes[] = [
                    'id' => (int) $row['id'],
                    'change_uid' => $row['change_uid'],
                    'origin_uid' => $row['origin_uid'],
                    'table_name' => $row['table_name'],
                    'op' => $row['op'],
                    'record_uuid' => $row['record_uuid'],
                    'payload' => $decoded,
                    'changed_at' => $row['changed_at'],
                ];
                $nextCursor = (int) $row['id'];
            }

            $pkgStmt = $db->prepare("SELECT id, version, filename, sha256, size, notes, status FROM sync_file_package WHERE status IN ('queued','downloaded') AND (node_uid IS NULL OR node_uid = ?) ORDER BY id ASC LIMIT 50");
            $pkgStmt->execute([$node['node_uid']]);
            $packages = $pkgStmt->fetchAll();

            hub_response(true, 'ok', [
                'changes' => $changes,
                'next_cursor' => $nextCursor,
                'policy' => $policy,
                'has_more' => count($rows) >= $limit,
                'packages' => $packages,
            ]);
            break;

        case 'push':
            $changes = $payload['changes'] ?? null;
            if (!is_array($changes)) {
                hub_response(false, 'changes must be an array', [], 400);
            }
            if (count($changes) > 1000) {
                hub_response(false, 'Too many changes in one batch', [], 413);
            }

            $results = [];
            foreach ($changes as $change) {
                $changeUid = (string) ($change['change_uid'] ?? '');
                $table = (string) ($change['table_name'] ?? '');
                $origin = (string) ($change['origin_uid'] ?? '');

                if ($origin !== $node['node_uid']) {
                    $results[] = ['change_uid' => $changeUid, 'status' => 'error', 'message' => 'Origin does not match authenticated node'];
                    continue;
                }

                $exists = $db->prepare("SELECT id FROM sync_change WHERE change_uid = ? LIMIT 1");
                $exists->execute([$changeUid]);
                if ($exists->fetchColumn() !== false) {
                    $results[] = ['change_uid' => $changeUid, 'status' => 'duplicate'];
                    continue;
                }

                try {
                    $status = sync_apply_change($change, $node['node_uid']);
                    $relay = $db->prepare("INSERT IGNORE INTO sync_change (change_uid, origin_uid, table_name, op, record_uuid, payload, changed_at, synced) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                    $relay->execute([
                        $changeUid,
                        $node['node_uid'],
                        $table,
                        (string) ($change['op'] ?? ''),
                        (string) ($change['record_uuid'] ?? ''),
                        json_encode($change['payload'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        $change['changed_at'] ?? date('Y-m-d H:i:s'),
                    ]);
                    $results[] = ['change_uid' => $changeUid, 'status' => $status];
                } catch (Throwable $e) {
                    sync_log_error($e->getMessage(), $table, $changeUid, is_array($change['payload'] ?? null) ? $change['payload'] : null, 'push');
                    $results[] = ['change_uid' => $changeUid, 'status' => 'error', 'message' => $e->getMessage()];
                }
            }

            hub_response(true, 'ok', ['results' => $results, 'policy' => $policy]);
            break;

        case 'package_meta':
            $packageId = (int) ($payload['package_id'] ?? 0);
            $stmt = $db->prepare("SELECT * FROM sync_file_package WHERE id = ? AND (node_uid IS NULL OR node_uid = ?) LIMIT 1");
            $stmt->execute([$packageId, $node['node_uid']]);
            $pkg = $stmt->fetch();
            if (!$pkg) {
                hub_response(false, 'Package not found', [], 404);
            }
            $signatureValue = hash_hmac('sha256', $pkg['sha256'] . '|' . $pkg['version'], (string) $node['secret_token']);
            hub_response(true, 'ok', ['package' => [
                'id' => (int) $pkg['id'],
                'version' => $pkg['version'],
                'filename' => $pkg['filename'],
                'sha256' => $pkg['sha256'],
                'size' => (int) $pkg['size'],
                'notes' => $pkg['notes'],
                'status' => $pkg['status'],
                'signature' => $signatureValue,
            ]]);
            break;

        case 'package_status':
            if (empty($policy['file_updates'])) {
                hub_response(false, 'File updates are disabled for this node', [], 403);
            }
            $packageId = (int) ($payload['package_id'] ?? 0);
            $status = (string) ($payload['status'] ?? '');
            if (!in_array($status, ['downloaded', 'applied', 'failed'], true)) {
                hub_response(false, 'Invalid package status', [], 400);
            }
            $stmt = $db->prepare("SELECT id FROM sync_file_package WHERE id = ? AND (node_uid IS NULL OR node_uid = ?) LIMIT 1");
            $stmt->execute([$packageId, $node['node_uid']]);
            if (!$stmt->fetchColumn()) {
                hub_response(false, 'Package not found', [], 404);
            }
            $report = null;
            if (isset($payload['report'])) {
                $report = json_encode($payload['report'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            $sql = "UPDATE sync_file_package SET status = ?, report_json = COALESCE(?, report_json)";
            $params = [$status, $report];
            if ($status === 'downloaded') {
                $sql .= ", downloaded_at = NOW()";
            } elseif ($status === 'applied') {
                $sql .= ", applied_at = NOW()";
            }
            $sql .= " WHERE id = ?";
            $params[] = $packageId;
            $db->prepare($sql)->execute($params);
            hub_response(true, 'ok', ['policy' => $policy]);
            break;

        default:
            hub_response(false, 'Unknown action', [], 400);
    }
} catch (Throwable $e) {
    error_log('Sync API error: ' . $e->getMessage());
    hub_response(false, $e->getMessage(), [], 401);
}
