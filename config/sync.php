<?php
/**
 * CBT System - Online/offline synchronisation engine
 *
 * Hub  : the online server. Receives pushes from leaves, relays changes and
 *        publishes restrictions.
 * Leaf : an offline examination server. Pushes local data and pulls hub data
 *        in the background whenever the internet is reachable.
 *
 * The transport is HMAC-SHA256 signed JSON over HTTP(S). Changes are captured
 * by database triggers into sync_change, so every existing code path is
 * covered without modifying individual forms.
 */

if (!defined('CBT_SYSTEM')) {
    die('Unauthorized access');
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/node.php';

class SyncDeferredException extends RuntimeException {}
class SyncSkipException extends RuntimeException {}

function sync_tables(): array {
    static $tables = null;
    if ($tables === null) {
        $tables = require __DIR__ . '/sync_tables.php';
    }
    return $tables;
}

function sync_state_get(string $key, $default = null) {
    try {
        $stmt = getDB()->prepare("SELECT state_value FROM sync_state WHERE state_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        if (!$row) {
            return $default;
        }
        $decoded = json_decode($row['state_value'], true);
        return $decoded === null && trim((string) $row['state_value']) !== 'null' ? $row['state_value'] : $decoded;
    } catch (Throwable $e) {
        return $default;
    }
}

function sync_state_set(string $key, $value): void {
    $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $stmt = getDB()->prepare("INSERT INTO sync_state (state_key, state_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE state_value = VALUES(state_value)");
    $stmt->execute([$key, $encoded]);
}

function sync_log_error(string $message, ?string $table = null, ?string $changeUid = null, ?array $payload = null, string $direction = 'pull'): void {
    try {
        $stmt = getDB()->prepare("INSERT INTO sync_error (node_uid, change_uid, table_name, direction, message, payload) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            defined('NODE_UID') ? NODE_UID : null,
            $changeUid,
            $table,
            $direction,
            substr($message, 0, 4000),
            $payload !== null ? json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
        ]);
    } catch (Throwable $e) {
        error_log('sync_log_error failed: ' . $e->getMessage());
    }
}

function sync_table_columns(string $table): array {
    static $cache = [];
    if (isset($cache[$table])) {
        return $cache[$table];
    }
    $columns = [];
    try {
        $stmt = getDB()->query("SHOW COLUMNS FROM `" . str_replace('`', '``', $table) . "`");
        foreach ($stmt->fetchAll() as $row) {
            $columns[$row['Field']] = $row;
        }
    } catch (Throwable $e) {
        throw new RuntimeException("Cannot inspect table $table: " . $e->getMessage());
    }
    $cache[$table] = $columns;
    return $columns;
}

function sync_hub_url(): string {
    $base = defined('NODE_HUB_URL') ? NODE_HUB_URL : '';
    if ($base === '') {
        return '';
    }
    return rtrim($base, '/') . '/sync/api.php';
}

function sync_http_post(string $url, string $body, string $signature, int $timeout = 15): string {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-CBT-Node: ' . NODE_UID,
                'X-CBT-Signature: ' . $signature,
                'User-Agent: CBT-Sync/1.0',
            ],
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($response === false) {
            throw new RuntimeException('Hub unreachable: ' . $error);
        }
        if ($status >= 400) {
            throw new RuntimeException('Hub error HTTP ' . $status . ': ' . substr((string) $response, 0, 500));
        }
        return (string) $response;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n"
                . 'X-CBT-Node: ' . NODE_UID . "\r\n"
                . 'X-CBT-Signature: ' . $signature . "\r\n"
                . "User-Agent: CBT-Sync/1.0\r\n",
            'content' => $body,
            'timeout' => $timeout,
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        throw new RuntimeException('Hub unreachable');
    }
    return $response;
}

/**
 * Send a signed request to the hub.
 */
function sync_request(string $action, array $data = [], int $timeout = 15): array {
    if (!node_is_leaf()) {
        throw new RuntimeException('Only leaf nodes can call the hub');
    }
    if (NODE_HUB_URL === '' || NODE_SECRET === '') {
        throw new RuntimeException('Hub URL or node secret is not configured');
    }
    $url = sync_hub_url();
    if ($url === '') {
        throw new RuntimeException('Hub URL is not configured');
    }

    $payload = array_merge([
        'node_uid' => NODE_UID,
        'timestamp' => time(),
        'nonce' => bin2hex(random_bytes(16)),
        'action' => $action,
    ], $data);
    $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($body === false) {
        throw new RuntimeException('Cannot encode sync request');
    }
    $signature = hash_hmac('sha256', $body, NODE_SECRET);

    $raw = sync_http_post($url, $body, $signature, $timeout);
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid response from hub');
    }
    if (empty($decoded['success'])) {
        throw new RuntimeException((string) ($decoded['message'] ?? 'Hub rejected the request'));
    }
    return $decoded['data'] ?? [];
}

/**
 * Persist the policy received from the hub.
 */
function sync_apply_policy(array $policy): void {
    if (!node_is_leaf()) {
        return;
    }
    $current = node_policy();
    $incoming = array_merge($current, $policy);
    $incoming['updated_at'] = $policy['updated_at'] ?? date('c');

    $tmp = NODE_POLICY_FILE . '.tmp';
    $dir = dirname(NODE_POLICY_FILE);
    if (!is_dir($dir)) {
        @mkdir($dir, 0770, true);
    }
    if (file_put_contents($tmp, json_encode($incoming, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false) {
        @rename($tmp, NODE_POLICY_FILE);
    }

    if (!empty($incoming['resync_requested'])) {
        $marker = (string) ($incoming['updated_at'] ?? '');
        if (sync_state_get('resync_applied_at') !== $marker) {
            sync_state_set('cursor', 0);
            sync_state_set('bootstrap_done', 1);
            sync_state_set('resync_applied_at', $marker);
        }
    }
}

function sync_hello(): array {
    $data = sync_request('hello', [], 8);
    if (!empty($data['policy']) && is_array($data['policy'])) {
        sync_apply_policy($data['policy']);
    }
    return $data;
}

/**
 * First contact: start from the hub's current cursor so an existing database
 * clone is not replayed. Use sync_full_resync() to replay all history.
 */
function sync_bootstrap(): void {
    if (sync_state_get('bootstrap_done', false)) {
        return;
    }
    $data = sync_hello();
    $cursor = (int) ($data['current_cursor'] ?? 0);

    // A leaf that already holds data is a clone of the hub and must not replay
    // old history. An empty leaf needs the full history to become usable.
    $hasData = false;
    try {
        $hasData = (int) getDB()->query("SELECT COUNT(*) FROM students")->fetchColumn() > 0;
    } catch (Throwable $e) {
        $hasData = false;
    }
    sync_state_set('cursor', $hasData ? $cursor : 0);
    sync_state_set('bootstrap_done', true);
}

function sync_full_resync(): void {
    sync_state_set('cursor', 0);
    sync_state_set('bootstrap_done', true);
}

function sync_map_lookup(string $table, string $originUid, int $sourceId): ?int {
    $stmt = getDB()->prepare("SELECT local_id FROM sync_id_map WHERE table_name = ? AND origin_uid = ? AND source_id = ? LIMIT 1");
    $stmt->execute([$table, $originUid, $sourceId]);
    $value = $stmt->fetchColumn();
    return $value === false ? null : (int) $value;
}

function sync_map_store(string $table, string $originUid, int $sourceId, int $localId, string $recordUuid): void {
    if ($sourceId <= 0) {
        return;
    }
    $stmt = getDB()->prepare("INSERT INTO sync_id_map (table_name, origin_uid, source_id, local_id, record_uuid) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE local_id = VALUES(local_id), record_uuid = VALUES(record_uuid)");
    $stmt->execute([$table, $originUid, $sourceId, $localId, $recordUuid]);
}

function sync_find_local_id(string $table, string $uuid, array $naturalKey = [], array $payload = []): ?int {
    $stmt = getDB()->prepare("SELECT id FROM `$table` WHERE sync_uuid = ? LIMIT 1");
    $stmt->execute([$uuid]);
    $id = $stmt->fetchColumn();
    if ($id !== false) {
        return (int) $id;
    }
    if ($naturalKey) {
        $where = [];
        $values = [];
        foreach ($naturalKey as $column) {
            if (!array_key_exists($column, $payload)) {
                return null;
            }
            $where[] = "`$column` = ?";
            $values[] = $payload[$column];
        }
        $stmt = getDB()->prepare("SELECT id FROM `$table` WHERE " . implode(' AND ', $where) . " LIMIT 1");
        $stmt->execute($values);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }
    }
    return null;
}

/**
 * Apply one change received from another node.
 */
function sync_apply_change(array $change, string $originUid): string {
    $tables = sync_tables();
    $table = (string) ($change['table_name'] ?? '');
    $op = (string) ($change['op'] ?? '');
    $uuid = (string) ($change['record_uuid'] ?? '');
    $payload = $change['payload'] ?? null;

    if (!isset($tables[$table])) {
        throw new RuntimeException("Table not allowed: $table");
    }
    if (!in_array($op, ['insert', 'update', 'delete'], true)) {
        throw new RuntimeException("Invalid operation: $op");
    }
    if (!preg_match('/^[0-9a-f-]{36}$/i', $uuid)) {
        throw new RuntimeException('Invalid record UUID');
    }
    if (is_string($payload)) {
        $payload = json_decode($payload, true);
    }
    if (!is_array($payload)) {
        $payload = [];
    }

    $cfg = $tables[$table];
    $columns = sync_table_columns($table);
    $db = getDB();

    foreach (($cfg['fks'] ?? []) as $column => $refTable) {
        if (!array_key_exists($column, $payload)) {
            continue;
        }
        $value = $payload[$column];
        if ($value === null || $value === '' || (int) $value <= 0) {
            $payload[$column] = null;
            continue;
        }
        $mapped = sync_map_lookup($refTable, $originUid, (int) $value);
        if ($mapped !== null) {
            $payload[$column] = $mapped;
            continue;
        }
        // Both sides often start from the same database clone, so the local
        // primary key is usually identical. Adopt it when it exists.
        try {
            $probe = getDB()->prepare("SELECT id, sync_uuid FROM `$refTable` WHERE id = ? LIMIT 1");
            $probe->execute([(int) $value]);
            $refRow = $probe->fetch();
            if ($refRow) {
                sync_map_store($refTable, $originUid, (int) $value, (int) $refRow['id'], (string) $refRow['sync_uuid']);
                $payload[$column] = (int) $refRow['id'];
                continue;
            }
        } catch (Throwable $e) {
            // Fall through to the nullable check below.
        }
        $nullable = strtoupper((string) ($columns[$column]['Null'] ?? 'YES')) === 'YES';
        if ($nullable) {
            $payload[$column] = null;
        } else {
            throw new SyncDeferredException("Missing parent $refTable#$value for $table.$column");
        }
    }

    $db->exec("SET @cbt_sync_applying = 1");
    try {
        $localId = sync_find_local_id($table, $uuid, $cfg['natural_key'] ?? [], $payload);

        if ($op === 'delete') {
            if ($localId !== null) {
                $stmt = $db->prepare("DELETE FROM `$table` WHERE id = ?");
                $stmt->execute([$localId]);
            }
            return 'ok';
        }

        $sourceId = (int) ($payload['id'] ?? 0);
        $data = [];
        foreach ($payload as $column => $value) {
            if ($column === 'id' || $column === 'sync_uuid' || !isset($columns[$column])) {
                continue;
            }
            $data[$column] = $value;
        }

        if ($localId !== null) {
            if ($data) {
                $set = [];
                $values = [];
                foreach ($data as $column => $value) {
                    $set[] = "`$column` = ?";
                    $values[] = $value;
                }
                $values[] = $localId;
                $stmt = $db->prepare("UPDATE `$table` SET " . implode(', ', $set) . " WHERE id = ?");
                $stmt->execute($values);
            }
            $stmt = $db->prepare("UPDATE `$table` SET sync_uuid = ? WHERE id = ? AND (sync_uuid IS NULL OR sync_uuid = '')");
            $stmt->execute([$uuid, $localId]);
            sync_map_store($table, $originUid, $sourceId, $localId, $uuid);
            return 'updated';
        }

        $insertColumns = array_keys($data);
        $insertValues = array_values($data);
        if ($sourceId > 0) {
            $exists = $db->prepare("SELECT COUNT(*) FROM `$table` WHERE id = ?");
            $exists->execute([$sourceId]);
            if ((int) $exists->fetchColumn() === 0) {
                array_unshift($insertColumns, 'id');
                array_unshift($insertValues, $sourceId);
            }
        }
        array_unshift($insertColumns, 'sync_uuid');
        array_unshift($insertValues, $uuid);

        $quoted = [];
        foreach ($insertColumns as $column) {
            $quoted[] = "`$column`";
        }
        $placeholders = implode(', ', array_fill(0, count($insertColumns), '?'));

        try {
            $stmt = $db->prepare("INSERT INTO `$table` (" . implode(', ', $quoted) . ") VALUES ($placeholders)");
            $stmt->execute($insertValues);
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            // A row with the same natural key may already exist under a different UUID.
            $retry = sync_find_local_id($table, $uuid, $cfg['natural_key'] ?? [], $payload);
            if ($retry === null) {
                throw $e;
            }
            if ($data) {
                $set = [];
                $values = [];
                foreach ($data as $column => $value) {
                    $set[] = "`$column` = ?";
                    $values[] = $value;
                }
                $values[] = $retry;
                $db->prepare("UPDATE `$table` SET " . implode(', ', $set) . " WHERE id = ?")->execute($values);
            }
            $db->prepare("UPDATE `$table` SET sync_uuid = ? WHERE id = ?")->execute([$uuid, $retry]);
            sync_map_store($table, $originUid, $sourceId, $retry, $uuid);
            return 'updated';
        }

        $newId = (int) $db->lastInsertId();
        if ($newId <= 0 && $sourceId > 0) {
            $newId = $sourceId;
        }
        sync_map_store($table, $originUid, $sourceId, $newId, $uuid);
        return 'inserted';
    } finally {
        $db->exec("SET @cbt_sync_applying = 0");
    }
}

function sync_store_packages(array $packages): void {
    if (!node_is_leaf() || !$packages) {
        return;
    }
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO sync_file_package (id, node_uid, version, filename, stored_name, sha256, size, notes, status) VALUES (?, ?, ?, ?, '', ?, ?, ?, 'queued') ON DUPLICATE KEY UPDATE version = VALUES(version), filename = VALUES(filename), sha256 = VALUES(sha256), size = VALUES(size), notes = VALUES(notes)");
    foreach ($packages as $package) {
        if (empty($package['id'])) {
            continue;
        }
        $stmt->execute([
            (int) $package['id'],
            NODE_UID,
            (string) ($package['version'] ?? ''),
            (string) ($package['filename'] ?? ''),
            (string) ($package['sha256'] ?? ''),
            (int) ($package['size'] ?? 0),
            (string) ($package['notes'] ?? ''),
        ]);
    }
}

function sync_pull_once(int $limit = 0): array {
    if (!node_is_leaf()) {
        return ['applied' => 0, 'failed' => 0, 'skipped' => true];
    }
    $limit = $limit > 0 ? $limit : NODE_SYNC_BATCH_LIMIT;
    $cursor = (int) sync_state_get('cursor', 0);
    $data = sync_request('pull', ['cursor' => $cursor, 'limit' => $limit], 20);
    if (!empty($data['policy']) && is_array($data['policy'])) {
        sync_apply_policy($data['policy']);
    }
    if (!empty($data['packages']) && is_array($data['packages'])) {
        sync_store_packages($data['packages']);
    }

    $changes = $data['changes'] ?? [];
    $tables = sync_tables();
    usort($changes, function ($a, $b) use ($tables) {
        $pa = (int) ($tables[$a['table_name']]['priority'] ?? 99);
        $pb = (int) ($tables[$b['table_name']]['priority'] ?? 99);
        if ($pa === $pb) {
            return (int) ($a['id'] ?? 0) <=> (int) ($b['id'] ?? 0);
        }
        return $pa <=> $pb;
    });

    $applied = 0;
    $failed = 0;
    $deferred = [];
    foreach ($changes as $change) {
        try {
            sync_apply_change($change, (string) ($change['origin_uid'] ?? ''));
            $applied++;
        } catch (SyncDeferredException $e) {
            $deferred[] = $change;
        } catch (Throwable $e) {
            $failed++;
            sync_log_error($e->getMessage(), $change['table_name'] ?? null, $change['change_uid'] ?? null, $change['payload'] ?? null, 'pull');
        }
    }
    $skipped = 0;
    foreach ($deferred as $change) {
        try {
            sync_apply_change($change, (string) ($change['origin_uid'] ?? ''));
            $applied++;
        } catch (SyncDeferredException $e) {
            // The source row references a parent that no longer exists there.
            // Do not block the whole stream on orphaned history.
            $skipped++;
            sync_log_error('Skipped orphaned change: ' . $e->getMessage(), $change['table_name'] ?? null, $change['change_uid'] ?? null, is_array($change['payload'] ?? null) ? $change['payload'] : null, 'pull');
        } catch (Throwable $e) {
            $failed++;
            sync_log_error($e->getMessage(), $change['table_name'] ?? null, $change['change_uid'] ?? null, is_array($change['payload'] ?? null) ? $change['payload'] : null, 'pull');
        }
    }

    if (isset($data['next_cursor'])) {
        sync_state_set('cursor', (int) $data['next_cursor']);
    }
    sync_state_set('last_pull_at', time());

    return ['applied' => $applied, 'failed' => $failed, 'skipped' => $skipped, 'cursor' => (int) sync_state_get('cursor', 0)];
}

function sync_push_once(int $limit = 0): array {
    if (!node_is_leaf()) {
        return ['pushed' => 0, 'skipped' => true];
    }
    $limit = $limit > 0 ? $limit : NODE_SYNC_BATCH_LIMIT;
    $db = getDB();

    $stmt = $db->prepare("SELECT * FROM sync_change WHERE synced = 0 AND origin_uid = ? ORDER BY id ASC LIMIT " . (int) $limit);
    $stmt->execute([NODE_UID]);
    $rows = $stmt->fetchAll();
    if (!$rows) {
        return ['pushed' => 0];
    }

    $changes = [];
    foreach ($rows as $row) {
        $payload = json_decode((string) $row['payload'], true);
        $changes[] = [
            'change_uid' => $row['change_uid'],
            'origin_uid' => $row['origin_uid'],
            'table_name' => $row['table_name'],
            'op' => $row['op'],
            'record_uuid' => $row['record_uuid'],
            'changed_at' => $row['changed_at'],
            'payload' => is_array($payload) ? $payload : null,
        ];
    }

    $data = sync_request('push', ['changes' => $changes], 30);
    if (!empty($data['policy']) && is_array($data['policy'])) {
        sync_apply_policy($data['policy']);
    }

    $results = $data['results'] ?? [];
    $ok = [];
    foreach ($results as $result) {
        if (in_array($result['status'] ?? '', ['ok', 'duplicate', 'updated', 'inserted'], true)) {
            $ok[] = $result['change_uid'];
        }
    }
    if ($ok) {
        $placeholders = implode(',', array_fill(0, count($ok), '?'));
        $update = $db->prepare("UPDATE sync_change SET synced = 1 WHERE change_uid IN ($placeholders)");
        $update->execute($ok);
    }
    sync_state_set('last_push_at', time());

    return ['pushed' => count($ok), 'sent' => count($changes)];
}

/**
 * Push then pull. Returns a summary array.
 */
function sync_run(bool $force = false): array {
    if (!node_is_leaf()) {
        return ['ok' => true, 'skipped' => true, 'reason' => 'hub'];
    }
    if (!NODE_SYNC_ENABLED) {
        return ['ok' => false, 'skipped' => true, 'reason' => 'disabled'];
    }

    $lockFile = NODE_STORAGE_DIR . '/sync.lock';
    $lock = @fopen($lockFile, 'c');
    if (!$lock) {
        return ['ok' => false, 'error' => 'Cannot open sync lock'];
    }
    if (!flock($lock, LOCK_EX | LOCK_NB)) {
        fclose($lock);
        return ['ok' => true, 'skipped' => true, 'reason' => 'busy'];
    }

    try {
        $lastAttempt = (int) sync_state_get('last_attempt_at', 0);
        if (!$force && $lastAttempt > 0 && (time() - $lastAttempt) < NODE_SYNC_INTERVAL) {
            return ['ok' => true, 'skipped' => true, 'reason' => 'interval'];
        }
        sync_state_set('last_attempt_at', time());

        if (NODE_HUB_URL === '' || NODE_SECRET === '') {
            $message = 'Hub URL or node secret is not configured';
            sync_state_set('last_error', $message);
            return ['ok' => false, 'error' => $message];
        }

        sync_bootstrap();

        $push = sync_push_once();
        while (($push['pushed'] ?? 0) > 0 && ($push['sent'] ?? 0) >= NODE_SYNC_BATCH_LIMIT) {
            $push = sync_push_once();
        }

        $pull = sync_pull_once();
        sync_state_set('last_sync_at', time());
        sync_state_set('last_success_at', time());
        sync_state_set('last_error', '');

        return ['ok' => true, 'push' => $push, 'pull' => $pull];
    } catch (Throwable $e) {
        sync_state_set('last_error', $e->getMessage());
        sync_log_error($e->getMessage(), null, null, null, 'push');
        return ['ok' => false, 'error' => $e->getMessage()];
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

/**
 * Called at the end of a normal web request on a leaf. Runs after the response
 * has been flushed so users do not wait for synchronisation.
 */
function sync_maybe_background(): void {
    if (!node_is_leaf() || PHP_SAPI === 'cli' || !NODE_SYNC_ENABLED) {
        return;
    }
    if (NODE_HUB_URL === '' || NODE_SECRET === '') {
        return;
    }

    if (function_exists('fastcgi_finish_request')) {
        @fastcgi_finish_request();
    } else {
        @ignore_user_abort(true);
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
        @flush();
    }
    @set_time_limit(25);
    try {
        sync_run(false);
    } catch (Throwable $e) {
        error_log('Background sync failed: ' . $e->getMessage());
    }
}

function sync_register_background(): void {
    if (!node_is_leaf() || PHP_SAPI === 'cli') {
        return;
    }
    static $registered = false;
    if ($registered) {
        return;
    }
    $registered = true;
    register_shutdown_function('sync_maybe_background');
}

// ---------------------------------------------------------------------------
// Signed file update packages (local administrator approval is required)
// ---------------------------------------------------------------------------

function sync_package_dir(): string {
    return NODE_STORAGE_DIR . '/packages';
}

function sync_pending_packages(): array {
    if (!node_is_leaf()) {
        return [];
    }
    try {
        $stmt = getDB()->query("SELECT id, version, filename, sha256, size, notes, status, created_at FROM sync_file_package WHERE status IN ('queued','downloaded') AND (node_uid IS NULL OR node_uid = " . getDB()->quote(NODE_UID) . ") ORDER BY id ASC");
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function sync_download_package(int $packageId): string {
    if (!node_is_leaf()) {
        throw new RuntimeException('Only leaf nodes can download packages');
    }

    $data = sync_request('package_meta', ['package_id' => $packageId], 10);
    $pkg = $data['package'] ?? null;
    if (!is_array($pkg)) {
        throw new RuntimeException('Package metadata missing');
    }

    $dir = sync_package_dir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0770, true);
    }
    $target = $dir . '/' . (int) $packageId . '.zip';

    $url = rtrim(NODE_HUB_URL, '/') . '/sync/download.php';
    $timestamp = time();
    $nonce = bin2hex(random_bytes(16));
    $signature = hash_hmac('sha256', NODE_UID . '|' . $timestamp . '|' . $nonce . '|' . $packageId, NODE_SECRET);

    if (function_exists('curl_init')) {
        $fp = fopen($target, 'wb');
        $ch = curl_init($url . '?id=' . $packageId);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_HTTPHEADER => [
                'X-CBT-Node: ' . NODE_UID,
                'X-CBT-Timestamp: ' . $timestamp,
                'X-CBT-Nonce: ' . $nonce,
                'X-CBT-Signature: ' . $signature,
            ],
        ]);
        $ok = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        fclose($fp);
        if (!$ok || $http !== 200) {
            @unlink($target);
            throw new RuntimeException('Package download failed (HTTP ' . $http . ')');
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'X-CBT-Node: ' . NODE_UID . "\r\n"
                    . 'X-CBT-Timestamp: ' . $timestamp . "\r\n"
                    . 'X-CBT-Nonce: ' . $nonce . "\r\n"
                    . 'X-CBT-Signature: ' . $signature . "\r\n",
                'timeout' => 120,
            ],
        ]);
        $contents = @file_get_contents($url . '?id=' . $packageId, false, $context);
        if ($contents === false) {
            throw new RuntimeException('Package download failed');
        }
        file_put_contents($target, $contents, LOCK_EX);
    }

    $sha = hash_file('sha256', $target);
    if (!hash_equals(strtolower((string) $pkg['sha256']), strtolower((string) $sha))) {
        @unlink($target);
        throw new RuntimeException('Package checksum mismatch');
    }
    $expectedSig = hash_hmac('sha256', (string) $pkg['sha256'] . '|' . (string) $pkg['version'], NODE_SECRET);
    if (!empty($pkg['signature']) && !hash_equals($expectedSig, (string) $pkg['signature'])) {
        @unlink($target);
        throw new RuntimeException('Package signature mismatch');
    }

    sync_state_set('package_' . $packageId, [
        'id' => $packageId,
        'version' => $pkg['version'],
        'sha256' => $sha,
        'downloaded_at' => time(),
        'status' => 'downloaded',
    ]);

    try {
        sync_request('package_status', ['package_id' => $packageId, 'status' => 'downloaded'], 8);
    } catch (Throwable $e) {
        // The local copy is still usable; status will be retried on next sync.
        sync_log_error($e->getMessage(), 'sync_file_package', null, null, 'push');
    }

    return $target;
}

/**
 * Apply a downloaded package after local administrator approval.
 * Files are backed up first and deletions are never performed.
 */
function sync_apply_package(int $packageId): array {
    if (!node_is_leaf()) {
        throw new RuntimeException('Only leaf nodes can apply packages');
    }
    $meta = sync_state_get('package_' . $packageId, []);
    if (!is_array($meta) || empty($meta['sha256'])) {
        throw new RuntimeException('Package has not been downloaded');
    }

    $zipPath = sync_package_dir() . '/' . $packageId . '.zip';
    if (!is_file($zipPath)) {
        throw new RuntimeException('Package file is missing');
    }
    if (!hash_equals((string) $meta['sha256'], hash_file('sha256', $zipPath))) {
        throw new RuntimeException('Package checksum mismatch');
    }
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('PHP zip extension is required to apply packages');
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        throw new RuntimeException('Cannot open package archive');
    }

    $root = realpath(dirname(__DIR__));
    $backupDir = NODE_STORAGE_DIR . '/backups/' . $packageId . '_' . date('Ymd_His');
    if (!is_dir($backupDir)) {
        @mkdir($backupDir, 0770, true);
    }

    $allowed = '/\.(php|html|css|js|json|txt|md|png|jpg|jpeg|gif|svg|webp|woff|woff2|ttf|eot|ico|sql)$/i';
    $applied = [];
    $skipped = [];

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if ($name === false || substr($name, -1) === '/') {
            continue;
        }
        $name = str_replace('\\', '/', $name);
        if (strpos($name, '..') !== false || strpos($name, "\0") !== false || $name[0] === '/' || preg_match('#^(\.git|storage|config)/#', $name) || !preg_match($allowed, $name)) {
            $skipped[] = $name;
            continue;
        }

        $target = $root . '/' . $name;
        $targetDir = dirname($target);
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }
        if (is_link($target)) {
            $skipped[] = $name . ' (symlink)';
            continue;
        }
        if (is_file($target)) {
            $backup = $backupDir . '/' . $name;
            if (!is_dir(dirname($backup))) {
                @mkdir(dirname($backup), 0770, true);
            }
            @copy($target, $backup);
        }
        $stream = $zip->getStream($name);
        if ($stream === false) {
            $skipped[] = $name . ' (unreadable)';
            continue;
        }
        $out = fopen($target, 'wb');
        if ($out === false) {
            fclose($stream);
            $skipped[] = $name . ' (not writable)';
            continue;
        }
        stream_copy_to_stream($stream, $out);
        fclose($stream);
        fclose($out);
        $applied[] = $name;
    }
    $zip->close();

    $report = ['applied' => $applied, 'skipped' => $skipped, 'backup' => $backupDir, 'applied_at' => date('c')];
    sync_state_set('package_' . $packageId, array_merge($meta, ['status' => 'applied', 'report' => $report]));

    try {
        sync_request('package_status', ['package_id' => $packageId, 'status' => 'applied', 'report' => $report], 8);
    } catch (Throwable $e) {
        sync_log_error($e->getMessage(), 'sync_file_package', null, null, 'push');
    }

    return $report;
}

// Automatically schedule background sync after each web request on a leaf.
if (defined('CBT_SYNC_CONTEXT') === false) {
    sync_register_background();
}

/**
 * Remove old sync metadata so tables do not grow forever.
 * Called by the worker once per day and by bin/sync-prune.php.
 */
function sync_prune(): array {
    $days = defined('NODE_SYNC_RETENTION_DAYS') ? NODE_SYNC_RETENTION_DAYS : 180;
    $days = max(7, (int) $days);
    $db = getDB();
    $cutoff = date('Y-m-d H:i:s', time() - ($days * 86400));

    $changes = $db->prepare("DELETE FROM sync_change WHERE changed_at < ? AND (synced = 1 OR origin_uid <> ?)");
    $changes->execute([$cutoff, defined('NODE_UID') ? NODE_UID : 'unknown']);

    $errors = $db->prepare("DELETE FROM sync_error WHERE created_at < ?");
    $errors->execute([$cutoff]);

    $db->exec("DELETE FROM sync_nonce WHERE created_at < (NOW() - INTERVAL 1 DAY)");

    return [
        'changes' => $changes->rowCount(),
        'errors' => $errors->rowCount(),
        'retention_days' => $days,
    ];
}
