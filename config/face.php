<?php
if (!defined('CBT_SYSTEM')) {
    die('Unauthorized access');
}

function face_api_url(): string {
    $url = trim((string) getSetting('face_api_url', 'http://127.0.0.1:9000'));
    if ($url === '') {
        $url = 'http://127.0.0.1:9000';
    }
    return rtrim($url, '/');
}

function face_timeout(): int {
    $n = (int) getSetting('face_timeout', '60');
    return $n > 0 ? $n : 60;
}

function face_match_threshold(): float {
    $n = (float) getSetting('face_match_threshold', '0.50');
    if ($n > 1 && $n <= 100) {
        $n = $n / 100;
    }
    if ($n <= 0) {
        $n = 0.50;
    }
    if ($n > 1) {
        $n = 1.0;
    }
    return $n;
}

function face_login_enabled(): bool {
    return getSetting('face_login_enabled', '0') === '1';
}

function student_login_url(): string {
    return APP_URL . (face_login_enabled() ? '/student/face_login.php' : '/student/login.php');
}

function face_healthy(): bool {
    $res = face_http('GET', '/health');
    return !empty($res['ok']) && (($res['json']['status'] ?? '') === 'healthy');
}

function face_extract(string $imageBinary): array {
    $res = face_http('POST', '/extract', [
        'files' => [['name' => 'image', 'filename' => 'face.jpg', 'body' => $imageBinary]],
    ]);
    if (!$res['ok']) {
        return ['ok' => false, 'message' => $res['error'] ?? 'Face service unavailable.'];
    }
    $json = $res['json'] ?? [];
    if (empty($json['success']) || empty($json['descriptor'])) {
        return ['ok' => false, 'message' => (string) ($json['message'] ?? 'No face detected.')];
    }
    return [
        'ok' => true,
        'descriptor' => $json['descriptor'],
        'confidence' => (float) ($json['confidence'] ?? 0),
        'message' => (string) $json['message'],
    ];
}

function face_compare(array $a, array $b): array {
    $res = face_http('POST', '/compare', [
        'json' => ['descriptor1' => array_map('floatval', $a), 'descriptor2' => array_map('floatval', $b)],
    ]);
    if (!$res['ok']) {
        $local = face_cosine_local($a, $b);
        $matched = $local >= face_match_threshold();
        return [
            'ok' => $matched,
            'score' => $local,
            'confidence' => max(0.0, min(1.0, $local)),
            'reason' => $matched ? 'Face verified' : 'Face does not match enrolled template.',
            'offline' => true,
        ];
    }
    $json = $res['json'] ?? [];
    $matched = !empty($json['matched']);
    return [
        'ok' => $matched,
        'score' => (float) ($json['cosine_similarity'] ?? 0),
        'confidence' => (float) ($json['confidence'] ?? 0),
        'reason' => $matched ? 'Face verified' : 'Face does not match enrolled template.',
    ];
}

function face_cosine_local(array $a, array $b): float {
    $n = min(count($a), count($b));
    if ($n < 128) {
        return 0.0;
    }
    $dot = 0.0;
    $na = 0.0;
    $nb = 0.0;
    for ($i = 0; $i < $n; $i++) {
        $x = (float) $a[$i];
        $y = (float) $b[$i];
        $dot += $x * $y;
        $na += $x * $x;
        $nb += $y * $y;
    }
    if ($na <= 0 || $nb <= 0) {
        return 0.0;
    }
    return $dot / (sqrt($na) * sqrt($nb));
}

function face_load_descriptor(int $studentId): ?array {
    try {
        $stmt = getDB()->prepare('SELECT descriptor_json FROM face_profiles WHERE student_id = ?');
        $stmt->execute([$studentId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $data = json_decode((string) $row['descriptor_json'], true);
        return is_array($data) ? $data : null;
    } catch (Throwable $e) {
        return null;
    }
}

function face_store_descriptor(int $studentId, array $descriptor, ?int $samsStudentId = null, ?string $samplePath = null): void {
    $json = json_encode(array_map('floatval', $descriptor));
    if ($json === false) {
        return;
    }
    $hash = hash('sha256', $json);
    $db = getDB();
    $existing = $db->prepare('SELECT id FROM face_profiles WHERE student_id = ?');
    $existing->execute([$studentId]);
    if ($existing->fetch()) {
        $db->prepare('UPDATE face_profiles SET descriptor_json = ?, descriptor_hash = ?, sams_student_id = COALESCE(?, sams_student_id), sample_path = COALESCE(?, sample_path), updated_at = NOW() WHERE student_id = ?')
            ->execute([$json, $hash, $samsStudentId, $samplePath, $studentId]);
    } else {
        $db->prepare('INSERT INTO face_profiles (student_id, sams_student_id, descriptor_json, descriptor_hash, sample_path, enrolled_at) VALUES (?, ?, ?, ?, ?, NOW())')
            ->execute([$studentId, $samsStudentId, $json, $hash, $samplePath]);
    }
    $db->prepare('UPDATE students SET face_enrolled = 1 WHERE id = ?')->execute([$studentId]);
}

function face_match_student(int $studentId, string $imageBinary): array {
    $stored = face_load_descriptor($studentId);
    $extract = face_extract($imageBinary);
    if (!$extract['ok']) {
        return ['ok' => false, 'score' => 0, 'reason' => $extract['message']];
    }
    if ($stored && count($stored) >= 128) {
        $cmp = face_compare($stored, $extract['descriptor']);
        if (!empty($cmp['ok'])) {
            return $cmp;
        }
        return [
            'ok' => false,
            'score' => $cmp['score'] ?? 0,
            'reason' => $cmp['reason'] ?? 'Face does not match enrolled template.',
        ];
    }
    return ['ok' => false, 'score' => 0, 'reason' => 'No face template enrolled for this student.'];
}

function face_verify_one_n(string $imageBinary): array {
    $extract = face_extract($imageBinary);
    if (!$extract['ok']) {
        return ['ok' => false, 'score' => 0, 'reason' => $extract['message']];
    }
    $live = $extract['descriptor'];
    $threshold = face_match_threshold();
    try {
        $rows = getDB()->query('SELECT student_id, descriptor_json FROM face_profiles')->fetchAll();
    } catch (Throwable $e) {
        return ['ok' => false, 'score' => 0, 'reason' => 'Face profiles are not available.'];
    }
    $bestId = 0;
    $best = -1.0;
    foreach ($rows as $row) {
        $stored = json_decode((string) $row['descriptor_json'], true);
        if (!is_array($stored) || count($stored) < 128) {
            continue;
        }
        $score = face_cosine_local($live, $stored);
        if ($score > $best) {
            $best = $score;
            $bestId = (int) $row['student_id'];
        }
    }
    if ($bestId > 0 && $best >= $threshold) {
        return [
            'ok' => true,
            'student_id' => $bestId,
            'score' => $best,
            'confidence' => max(0.0, min(1.0, $best)),
            'reason' => 'Face verified',
        ];
    }
    return [
        'ok' => false,
        'score' => $best > 0 ? $best : 0,
        'reason' => 'Face not recognized. No matching enrollment found.',
    ];
}

function face_decode_image(string $payload): ?string {
    $payload = trim($payload);
    if ($payload === '') {
        return null;
    }
    if (preg_match('#^data:image/(jpeg|jpg|png|webp);base64,(.+)$#i', $payload, $m)) {
        $bin = base64_decode($m[2], true);
        return ($bin !== false && strlen($bin) > 32) ? $bin : null;
    }
    $bin = base64_decode($payload, true);
    if ($bin !== false && strlen($bin) > 32 && strncmp($bin, "\xFF\xD8", 2) === 0) {
        return $bin;
    }
    return null;
}

function face_images_from_request(): array {
    $out = [];
    foreach (['face_images', 'snapshot', 'image'] as $key) {
        if (empty($_FILES[$key])) {
            continue;
        }
        $names = $_FILES[$key]['name'] ?? [];
        if (!is_array($names)) {
            if (($_FILES[$key]['error'] ?? 1) === UPLOAD_ERR_OK && is_readable($_FILES[$key]['tmp_name'])) {
                $out[] = (string) file_get_contents($_FILES[$key]['tmp_name']);
            }
            continue;
        }
        foreach ($_FILES[$key]['tmp_name'] as $i => $tmp) {
            if (($_FILES[$key]['error'][$i] ?? 1) === UPLOAD_ERR_OK && is_readable($tmp)) {
                $out[] = (string) file_get_contents($tmp);
            }
        }
    }
    $snaps = $_POST['snapshots'] ?? $_POST['snapshot'] ?? [];
    if (is_string($snaps)) {
        $snaps = [$snaps];
    }
    if (is_array($snaps)) {
        foreach ($snaps as $s) {
            $bin = face_decode_image((string) $s);
            if ($bin !== null) {
                $out[] = $bin;
            }
        }
    }
    return array_values(array_filter($out, static fn($b) => is_string($b) && strlen($b) > 32));
}

function face_http(string $method, string $path, ?array $opts = null): array {
    $url = face_api_url() . $path;
    $timeout = face_timeout();
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'PHP cURL is required to reach the face service.'];
    }
    $ch = curl_init($url);
    $headers = ['Accept: application/json'];
    $method = strtoupper($method);
    if ($method === 'POST' && isset($opts['json'])) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($opts['json']));
    } elseif ($method === 'POST') {
        $boundary = '----CBTFace' . bin2hex(random_bytes(8));
        $headers[] = 'Content-Type: multipart/form-data; boundary=' . $boundary;
        curl_setopt($ch, CURLOPT_POSTFIELDS, face_build_multipart($boundary, $opts['fields'] ?? [], $opts['files'] ?? []));
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false) {
        return ['ok' => false, 'error' => 'Face service unreachable: ' . $err, 'status' => $status];
    }
    $json = json_decode((string) $raw, true);
    if ($status >= 400) {
        $msg = is_array($json) ? (string) ($json['detail'] ?? $json['message'] ?? 'Face service error') : 'Face service error';
        if (is_array($json) && isset($json['detail']) && is_array($json['detail'])) {
            $msg = json_encode($json['detail']);
        }
        return ['ok' => false, 'error' => $msg, 'status' => $status, 'json' => is_array($json) ? $json : []];
    }
    return ['ok' => true, 'json' => is_array($json) ? $json : [], 'status' => $status];
}

function face_build_multipart(string $boundary, array $fields, array $files): string {
    $eol = "\r\n";
    $body = '';
    foreach ($fields as $name => $value) {
        $body .= '--' . $boundary . $eol;
        $body .= 'Content-Disposition: form-data; name="' . $name . '"' . $eol . $eol;
        $body .= $value . $eol;
    }
    foreach ($files as $f) {
        $body .= '--' . $boundary . $eol;
        $body .= 'Content-Disposition: form-data; name="' . $f['name'] . '"; filename="' . $f['filename'] . '"' . $eol;
        $body .= 'Content-Type: image/jpeg' . $eol . $eol;
        $body .= $f['body'] . $eol;
    }
    $body .= '--' . $boundary . '--' . $eol;
    return $body;
}
