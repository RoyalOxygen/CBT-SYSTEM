<?php
if (!defined('CBT_SYSTEM')) {
    die('Unauthorized access');
}

function sams_url(): string {
    return rtrim(trim((string) getSetting('sams_url', '')), '/');
}

function sams_token(): string {
    return trim((string) getSetting('sams_sync_token', ''));
}

function sams_sync_enabled(): bool {
    return getSetting('sams_sync_enabled', '0') === '1' && sams_url() !== '' && sams_token() !== '';
}

function sams_ensure_schema(PDO $db): void {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `face_profiles` (
            `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `student_id` int(11) UNSIGNED NOT NULL,
            `sams_student_id` int(11) UNSIGNED DEFAULT NULL,
            `descriptor_json` longtext NOT NULL,
            `descriptor_hash` char(64) NOT NULL,
            `sample_path` varchar(255) DEFAULT NULL,
            `enrolled_at` datetime NOT NULL DEFAULT current_timestamp(),
            `updated_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_face_student` (`student_id`),
            KEY `idx_face_sams` (`sams_student_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $db->exec("CREATE TABLE IF NOT EXISTS `student_files` (
            `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `student_id` int(11) UNSIGNED NOT NULL,
            `file_kind` varchar(20) NOT NULL DEFAULT 'photo',
            `filename` varchar(255) NOT NULL,
            `mime` varchar(80) DEFAULT 'image/jpeg',
            `content_b64` longtext NOT NULL,
            `content_hash` char(64) NOT NULL,
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_student_file_kind` (`student_id`,`file_kind`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $cols = [];
        foreach ($db->query('SHOW COLUMNS FROM students') as $row) {
            $cols[$row['Field']] = true;
        }
        if (empty($cols['sams_student_id'])) {
            $db->exec('ALTER TABLE students ADD COLUMN sams_student_id int(11) UNSIGNED DEFAULT NULL AFTER matric_number');
        }
        if (empty($cols['sams_updated_at'])) {
            $db->exec('ALTER TABLE students ADD COLUMN sams_updated_at datetime DEFAULT NULL AFTER updated_at');
        }
        if (empty($cols['face_enrolled'])) {
            $db->exec('ALTER TABLE students ADD COLUMN face_enrolled tinyint(1) NOT NULL DEFAULT 0 AFTER fingerprint_enrolled');
        }
        try {
            $db->exec('ALTER TABLE students ADD UNIQUE KEY uk_sams_student (sams_student_id)');
        } catch (Throwable $e) {
        }
    } catch (Throwable $e) {
        error_log('sams_ensure_schema: ' . $e->getMessage());
    }
}

function sams_http(string $path, array $query = [], int $timeout = 45): array {
    $base = sams_url();
    if ($base === '') {
        return ['ok' => false, 'error' => 'SAMS URL is not configured'];
    }
    $query['token'] = sams_token();
    $url = $base . $path . (strpos($path, '?') === false ? '?' : '&') . http_build_query($query);
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'PHP cURL is required'];
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'X-CBT-Sync-Token: ' . sams_token(),
            'User-Agent: CBT-SAMS-Sync/1.0',
        ],
    ]);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false) {
        return ['ok' => false, 'error' => 'SAMS unreachable: ' . $err, 'status' => $status];
    }
    $json = json_decode((string) $raw, true);
    if ($status >= 400 || !is_array($json) || empty($json['ok'])) {
        $msg = is_array($json) ? (string) ($json['message'] ?? 'SAMS rejected the request') : 'Invalid SAMS response';
        return ['ok' => false, 'error' => $msg, 'status' => $status, 'json' => is_array($json) ? $json : []];
    }
    return ['ok' => true, 'json' => $json, 'status' => $status];
}

function sams_photo_dir(): string {
    $dir = dirname(__DIR__) . '/assets/uploads/student_photos/';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

function sams_save_photo(string $matric, string $b64, string $mime = 'image/jpeg'): ?string {
    $bin = base64_decode($b64, true);
    if ($bin === false || strlen($bin) < 32) {
        return null;
    }
    $ext = 'jpg';
    if (strpos($mime, 'png') !== false) {
        $ext = 'png';
    } elseif (strpos($mime, 'webp') !== false) {
        $ext = 'webp';
    } elseif (strpos($mime, 'gif') !== false) {
        $ext = 'gif';
    }
    $safe = preg_replace('/[^a-zA-Z0-9]/', '_', $matric);
    $filename = 'sams_' . $safe . '.' . $ext;
    if (file_put_contents(sams_photo_dir() . $filename, $bin) === false) {
        return null;
    }
    return $filename;
}

function sams_store_file(int $studentId, string $kind, string $filename, string $mime, string $b64): void {
    $hash = hash('sha256', $b64);
    $db = getDB();
    $db->prepare('INSERT INTO student_files (student_id, file_kind, filename, mime, content_b64, content_hash)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE filename = VALUES(filename), mime = VALUES(mime), content_b64 = VALUES(content_b64), content_hash = VALUES(content_hash)')
        ->execute([$studentId, $kind, $filename, $mime, $b64, $hash]);
}

function sams_restore_photos_from_files(PDO $db): int {
    $restored = 0;
    try {
        $rows = $db->query("SELECT sf.*, s.matric_number FROM student_files sf JOIN students s ON s.id = sf.student_id WHERE sf.file_kind = 'photo'")->fetchAll();
    } catch (Throwable $e) {
        return 0;
    }
    foreach ($rows as $row) {
        $path = sams_photo_dir() . $row['filename'];
        if (is_file($path) && filesize($path) > 32) {
            continue;
        }
        $bin = base64_decode((string) $row['content_b64'], true);
        if ($bin === false || strlen($bin) < 32) {
            continue;
        }
        if (file_put_contents($path, $bin) !== false) {
            $db->prepare('UPDATE students SET photo = ? WHERE id = ? AND (photo IS NULL OR photo = "")')
                ->execute([$row['filename'], $row['student_id']]);
            $restored++;
        }
    }
    return $restored;
}

function sams_find_or_create_department(PDO $db, string $code, string $name, string $faculty): int {
    $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $code) ?: 'GEN', 0, 20));
    $name = trim($name) !== '' ? trim($name) : 'General';
    $stmt = $db->prepare('SELECT id FROM departments WHERE dept_code = ? LIMIT 1');
    $stmt->execute([$code]);
    $row = $stmt->fetch();
    if ($row) {
        if ($faculty !== '') {
            $db->prepare('UPDATE departments SET dept_name = ?, faculty = ?, status = 1 WHERE id = ?')
                ->execute([$name, $faculty, $row['id']]);
        }
        return (int) $row['id'];
    }
    $stmt = $db->prepare('SELECT id FROM departments WHERE dept_name = ? LIMIT 1');
    $stmt->execute([$name]);
    $row = $stmt->fetch();
    if ($row) {
        return (int) $row['id'];
    }
    $db->prepare('INSERT INTO departments (dept_code, dept_name, faculty, status) VALUES (?, ?, ?, 1)')
        ->execute([$code, $name, $faculty !== '' ? $faculty : $name]);
    return (int) $db->lastInsertId();
}

function sams_find_or_create_level(PDO $db, $level): int {
    $num = (int) preg_replace('/[^0-9]/', '', (string) $level);
    if ($num <= 0) {
        $num = 100;
    }
    if ($num < 10) {
        $num *= 100;
    }
    $name = $num . ' Level';
    $stmt = $db->prepare('SELECT id FROM levels WHERE level_name = ? OR level_name LIKE ? LIMIT 1');
    $stmt->execute([$name, '%' . $num . '%']);
    $row = $stmt->fetch();
    if ($row) {
        return (int) $row['id'];
    }
    $order = (int) floor($num / 100);
    $db->prepare('INSERT INTO levels (level_name, level_order, status) VALUES (?, ?, 1)')
        ->execute([$name, max(1, $order)]);
    return (int) $db->lastInsertId();
}

function sams_active_semester_id(PDO $db): int {
    $row = $db->query('SELECT id FROM semesters WHERE status = 1 ORDER BY semester_order LIMIT 1')->fetch();
    return $row ? (int) $row['id'] : 1;
}

function sams_active_session_id(PDO $db): int {
    $row = $db->query('SELECT id FROM sessions WHERE is_current = 1 AND status = 1 LIMIT 1')->fetch();
    if ($row) {
        return (int) $row['id'];
    }
    $row = $db->query('SELECT id FROM sessions WHERE status = 1 ORDER BY id DESC LIMIT 1')->fetch();
    return $row ? (int) $row['id'] : 1;
}

function sams_upsert_student(PDO $db, array $item): ?int {
    $matric = strtoupper(trim((string) ($item['matric_no'] ?? '')));
    if ($matric === '') {
        return null;
    }
    $deptId = sams_find_or_create_department(
        $db,
        (string) ($item['department_code'] ?? 'GEN'),
        (string) ($item['department_name'] ?? 'General'),
        (string) ($item['faculty_name'] ?? '')
    );
    $levelId = sams_find_or_create_level($db, $item['level'] ?? 100);
    $semesterId = sams_active_semester_id($db);
    $sessionId = sams_active_session_id($db);
    $status = (($item['status'] ?? 'active') === 'active') ? 1 : 0;
    $samsId = (int) ($item['sams_student_id'] ?? 0);
    $photoName = null;
    if (!empty($item['photo_b64'])) {
        $photoName = sams_save_photo($matric, (string) $item['photo_b64'], (string) ($item['photo_mime'] ?? 'image/jpeg'));
    }

    $stmt = $db->prepare('SELECT id, photo, password FROM students WHERE matric_number = ? LIMIT 1');
    $stmt->execute([$matric]);
    $existing = $stmt->fetch();

    $first = substr((string) ($item['first_name'] ?? ''), 0, 50);
    $last = substr((string) ($item['last_name'] ?? ''), 0, 50);
    $other = substr((string) ($item['other_name'] ?? ''), 0, 50);
    $email = substr((string) ($item['email'] ?? ''), 0, 100);
    $phone = substr((string) ($item['phone'] ?? ''), 0, 20);
    $gender = in_array($item['gender'] ?? '', ['Male', 'Female'], true) ? $item['gender'] : null;
    $updatedAt = $item['updated_at'] ?? date('Y-m-d H:i:s');

    if ($existing) {
        $id = (int) $existing['id'];
        $photo = $photoName ?: $existing['photo'];
        $db->prepare('UPDATE students SET first_name=?, last_name=?, other_name=?, email=?, phone=?, gender=?, department_id=?, level_id=?, semester_id=?, session_id=?, photo=?, sams_student_id=?, sams_updated_at=?, status=?, updated_at=NOW() WHERE id=?')
            ->execute([$first, $last, $other !== '' ? $other : null, $email !== '' ? $email : null, $phone !== '' ? $phone : null, $gender, $deptId, $levelId, $semesterId, $sessionId, $photo, $samsId ?: null, $updatedAt, $status, $id]);
    } else {
        $password = hashPassword($matric);
        $db->prepare('INSERT INTO students (matric_number, password, first_name, last_name, other_name, email, phone, gender, department_id, level_id, semester_id, session_id, photo, sams_student_id, sams_updated_at, status, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())')
            ->execute([$matric, $password, $first, $last, $other !== '' ? $other : null, $email !== '' ? $email : null, $phone !== '' ? $phone : null, $gender, $deptId, $levelId, $semesterId, $sessionId, $photoName, $samsId ?: null, $updatedAt, $status]);
        $id = (int) $db->lastInsertId();
    }

    if ($photoName && !empty($item['photo_b64'])) {
        sams_store_file($id, 'photo', $photoName, (string) ($item['photo_mime'] ?? 'image/jpeg'), (string) $item['photo_b64']);
    }

    if (!empty($item['face_descriptor']) && is_array($item['face_descriptor']) && count($item['face_descriptor']) >= 128) {
        face_store_descriptor($id, $item['face_descriptor'], $samsId ?: null);
    }

    return $id;
}

function sams_pull_once(bool $force = false): array {
    if (!node_is_hub()) {
        return ['ok' => true, 'skipped' => true, 'reason' => 'leaf'];
    }
    if (!sams_sync_enabled()) {
        return ['ok' => true, 'skipped' => true, 'reason' => 'disabled'];
    }

    $db = getDB();
    sams_ensure_schema($db);

    $lockFile = NODE_STORAGE_DIR . '/sams.lock';
    $lock = @fopen($lockFile, 'c');
    if (!$lock) {
        return ['ok' => false, 'error' => 'Cannot open SAMS lock'];
    }
    if (!flock($lock, LOCK_EX | LOCK_NB)) {
        fclose($lock);
        return ['ok' => true, 'skipped' => true, 'reason' => 'busy'];
    }

    try {
        $lastAttempt = (int) sync_state_get('sams_last_attempt_at', 0);
        if (!$force && $lastAttempt > 0 && (time() - $lastAttempt) < 60) {
            return ['ok' => true, 'skipped' => true, 'reason' => 'interval'];
        }
        sync_state_set('sams_last_attempt_at', time());

        $since = (string) sync_state_get('sams_since', '');
        $imported = 0;
        $pages = 0;
        $offset = 0;
        $limit = 50;
        $maxSince = $since;

        do {
            $res = sams_http('/api/cbt-sync/students', [
                'since' => $since,
                'limit' => $limit,
                'offset' => $offset,
            ], 60);
            if (!$res['ok']) {
                sync_state_set('sams_last_error', $res['error'] ?? 'SAMS pull failed');
                return ['ok' => false, 'error' => $res['error'] ?? 'SAMS pull failed', 'imported' => $imported];
            }
            $students = $res['json']['students'] ?? [];
            $total = (int) ($res['json']['total'] ?? count($students));
            foreach ($students as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $id = sams_upsert_student($db, $item);
                if ($id) {
                    $imported++;
                    $u = (string) ($item['updated_at'] ?? '');
                    if ($u !== '' && ($maxSince === '' || strcmp($u, $maxSince) > 0)) {
                        $maxSince = $u;
                    }
                }
            }
            $offset += $limit;
            $pages++;
        } while (count($students) >= $limit && $offset < $total && $pages < 40);

        if ($maxSince !== '') {
            sync_state_set('sams_since', $maxSince);
        }
        sync_state_set('sams_last_success_at', time());
        sync_state_set('sams_last_error', '');
        sync_state_set('sams_last_imported', $imported);
        return ['ok' => true, 'imported' => $imported, 'pages' => $pages];
    } catch (Throwable $e) {
        sync_state_set('sams_last_error', $e->getMessage());
        return ['ok' => false, 'error' => $e->getMessage()];
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function sams_maybe_background(): void {
    if (!node_is_hub() || PHP_SAPI === 'cli' || !sams_sync_enabled()) {
        return;
    }
    if (function_exists('fastcgi_finish_request')) {
        @fastcgi_finish_request();
    }
    @set_time_limit(40);
    try {
        sams_pull_once(false);
    } catch (Throwable $e) {
        error_log('Background SAMS sync failed: ' . $e->getMessage());
    }
}

function sams_register_background(): void {
    if (!node_is_hub() || PHP_SAPI === 'cli') {
        return;
    }
    static $registered = false;
    if ($registered) {
        return;
    }
    $registered = true;
    register_shutdown_function('sams_maybe_background');
}
