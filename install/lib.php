<?php
/**
 * Web installer helpers. Do not include config.php from here.
 */

if (!defined('CBT_SYSTEM')) {
    die('Unauthorized access');
}

function install_root(): string {
    return dirname(__DIR__);
}

function install_lock_file(): string {
    return install_root() . '/storage/sync/installed.lock';
}

function install_is_complete(): bool {
    if (is_file(install_lock_file()) || is_file(install_root() . '/config/node.local.php')) {
        return true;
    }
    $envUid = getenv('CBT_NODE_UID');
    if ($envUid !== false && $envUid !== '') {
        return true;
    }
    $envDb = getenv('CBT_DB_NAME');
    return $envDb !== false && $envDb !== '';
}

function install_json(bool $success, string $message = '', array $data = [], int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function install_detected_app_url(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $dir = preg_replace('#/(admin|student|ajax|sync|install)(/.*)?$#', '', $script);
    $dir = preg_replace('#/index\.php$#', '', (string) $dir);
    $dir = rtrim((string) $dir, '/');
    return ($https ? 'https' : 'http') . '://' . $host . $dir;
}

function install_uuid(): string {
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
}

function install_csrf_token(): string {
    if (empty($_SESSION['install_csrf'])) {
        $_SESSION['install_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['install_csrf'];
}

function install_csrf_check(string $token): bool {
    return isset($_SESSION['install_csrf']) && hash_equals($_SESSION['install_csrf'], $token);
}

function install_requirements(): array {
    $root = install_root();
    $phpOk = version_compare(PHP_VERSION, '8.0.0', '>=');
    $ext = [];
    foreach (['pdo', 'pdo_mysql', 'json', 'session', 'fileinfo'] as $name) {
        $ext[$name] = extension_loaded($name);
    }
    $ext['zip'] = extension_loaded('zip');
    $writable = [];
    foreach (['config', 'storage', 'storage/sync', 'logs', 'assets/uploads', 'assets/uploads/student_photos'] as $rel) {
        $path = $root . '/' . $rel;
        if (!is_dir($path)) {
            @mkdir($path, 0770, true);
        }
        $writable[$rel] = is_dir($path) && is_writable($path);
    }
    $ok = $phpOk && $ext['pdo'] && $ext['pdo_mysql'] && $ext['json'] && $ext['session'] && $ext['fileinfo']
        && $writable['config'] && $writable['storage'] && $writable['storage/sync'] && $writable['logs'] && $writable['assets/uploads'] && $writable['assets/uploads/student_photos'];
    return [
        'ok' => $ok,
        'php_version' => PHP_VERSION,
        'php_ok' => $phpOk,
        'extensions' => $ext,
        'writable' => $writable,
        'app_url' => install_detected_app_url(),
    ];
}

function install_ident(string $name): string {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
        throw new RuntimeException('Invalid database name.');
    }
    return $name;
}

function install_pdo(string $host, string $user, string $pass, ?string $name = null): PDO {
    $dsn = 'mysql:host=' . $host . ($name ? ';dbname=' . install_ident($name) : '') . ';charset=utf8mb4';
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function install_run_sql_file(PDO $db, string $file): void {
    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException('Cannot read ' . $file);
    }
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $statements = preg_split('/;\s*(?:\r?\n|$)/', (string) $sql);
    foreach ($statements as $statement) {
        $statement = trim((string) $statement);
        if ($statement === '') {
            continue;
        }
        $db->exec($statement);
    }
}

function install_table_exists(PDO $db, string $table): bool {
    $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $stmt->execute([$table]);
    return (int) $stmt->fetchColumn() > 0;
}

function install_write_config(array $config): string {
    $file = install_root() . '/config/node.local.php';
    $php = "<?php\nreturn [\n";
    foreach ($config as $key => $value) {
        if (is_bool($value)) {
            $exported = $value ? 'true' : 'false';
        } else {
            $exported = var_export($value, true);
        }
        $php .= '    ' . var_export((string) $key, true) . ' => ' . $exported . ",\n";
    }
    $php .= "];\n";
    if (file_put_contents($file, $php, LOCK_EX) === false) {
        throw new RuntimeException('Could not write config/node.local.php. Check folder permissions.');
    }
    @chmod($file, 0640);
    return $file;
}

function install_academic_session(): string {
    $year = (int) date('Y');
    $month = (int) date('n');
    if ($month >= 9) {
        return $year . '/' . ($year + 1);
    }
    return ($year - 1) . '/' . $year;
}

function install_seed(PDO $db, array $opts): void {
    $adminCount = (int) $db->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    if ($adminCount === 0) {
        $hash = password_hash((string) $opts['admin_password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare('INSERT INTO admins (username, password, full_name, email, role, status) VALUES (?, ?, ?, ?, ?, 1)');
        $stmt->execute([
            $opts['admin_username'],
            $hash,
            $opts['admin_name'],
            $opts['admin_email'] !== '' ? $opts['admin_email'] : null,
            'super_admin',
        ]);
    } else {
        $hash = password_hash((string) $opts['admin_password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare('UPDATE admins SET password = ?, full_name = ?, email = COALESCE(?, email) WHERE username = ?');
        $stmt->execute([
            $hash,
            $opts['admin_name'],
            $opts['admin_email'] !== '' ? $opts['admin_email'] : null,
            $opts['admin_username'],
        ]);
        if ($stmt->rowCount() === 0) {
            $stmt = $db->prepare('INSERT INTO admins (username, password, full_name, email, role, status) VALUES (?, ?, ?, ?, ?, 1)');
            $stmt->execute([
                $opts['admin_username'],
                $hash,
                $opts['admin_name'],
                $opts['admin_email'] !== '' ? $opts['admin_email'] : null,
                'super_admin',
            ]);
        }
    }

    $db->exec("INSERT IGNORE INTO levels (id, level_name, level_order, status) VALUES
        (1, '100 Level', 1, 1), (2, '200 Level', 2, 1), (3, '300 Level', 3, 1), (4, '400 Level', 4, 1), (5, '500 Level', 5, 1)");
    $db->exec("INSERT IGNORE INTO semesters (id, semester_name, semester_order, status) VALUES
        (1, 'First Semester', 1, 1), (2, 'Second Semester', 2, 1)");

    $sessionName = install_academic_session();
    $start = date('Y') . '-09-01';
    $end = ((int) date('Y') + 1) . '-08-31';
    $exists = $db->prepare('SELECT id FROM sessions WHERE session_name = ?');
    $exists->execute([$sessionName]);
    if (!$exists->fetch()) {
        $db->prepare('INSERT INTO sessions (session_name, session_start, session_end, is_current, status) VALUES (?, ?, ?, 1, 1)')
            ->execute([$sessionName, $start, $end]);
    } else {
        $db->exec('UPDATE sessions SET is_current = 0');
        $db->prepare('UPDATE sessions SET is_current = 1, status = 1 WHERE session_name = ?')->execute([$sessionName]);
    }

    $dept = $db->query("SELECT id FROM departments WHERE dept_code = 'GEN' LIMIT 1")->fetch();
    if (!$dept) {
        $db->prepare('INSERT INTO departments (dept_code, dept_name, faculty, status) VALUES (?, ?, ?, 1)')
            ->execute(['GEN', 'General', 'General']);
    }

    $settings = [
        'institution_name' => $opts['site_name'],
        'timezone' => 'Africa/Lagos',
        'default_duration' => '60',
        'default_pass_mark' => '40',
        'default_question_limit' => '50',
        'session_timeout' => '30',
        'biometric_threshold' => '90',
        'maintenance_mode' => '0',
        'biometric_enabled' => '0',
        'face_api_url' => 'http://127.0.0.1:9000',
        'face_timeout' => '60',
        'face_match_threshold' => '0.50',
        'face_login_enabled' => '0',
        'sams_url' => '',
        'sams_sync_token' => '',
        'sams_sync_enabled' => '0',
    ];
    $stmt = $db->prepare('INSERT INTO settings (setting_key, setting_value, setting_group) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    foreach ($settings as $key => $value) {
        $stmt->execute([$key, $value, 'general']);
    }
}

function install_decode_pairing(string $raw): array {
    $raw = trim($raw);
    if ($raw === '') {
        throw new RuntimeException('Paste the pairing details from the online server.');
    }
    $json = $raw;
    if ($raw[0] !== '{') {
        $decoded = base64_decode($raw, true);
        if ($decoded !== false) {
            $json = $decoded;
        }
    }
    $data = json_decode($json, true);
    if (!is_array($data)) {
        throw new RuntimeException('Pairing details are not valid. Copy them again from the online server.');
    }
    $uid = trim((string) ($data['uid'] ?? $data['node_uid'] ?? ''));
    $secret = trim((string) ($data['secret'] ?? ''));
    $hubUrl = rtrim(trim((string) ($data['hub_url'] ?? '')), '/');
    $name = trim((string) ($data['name'] ?? ''));
    if ($uid === '' || $secret === '' || $hubUrl === '') {
        throw new RuntimeException('Pairing details must include uid, secret and hub_url.');
    }
    return [
        'uid' => $uid,
        'secret' => $secret,
        'hub_url' => $hubUrl,
        'name' => $name,
    ];
}

function install_hello_hub(string $hubUrl, string $uid, string $secret): array {
    $payload = [
        'action' => 'hello',
        'node_uid' => $uid,
        'timestamp' => time(),
        'nonce' => bin2hex(random_bytes(16)),
    ];
    $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
    $signature = hash_hmac('sha256', $body, $secret);
    $url = $hubUrl . '/sync/api.php';
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nX-CBT-Signature: {$signature}\r\n",
            'content' => $body,
            'timeout' => 8,
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        throw new RuntimeException('Could not reach the online server at ' . $url);
    }
    $decoded = json_decode($response, true);
    if (!is_array($decoded) || empty($decoded['success'])) {
        $message = is_array($decoded) ? (string) ($decoded['message'] ?? 'Hub rejected the pairing') : 'Hub rejected the pairing';
        throw new RuntimeException($message);
    }
    return $decoded;
}

function install_mark_complete(array $meta): void {
    $dir = dirname(install_lock_file());
    if (!is_dir($dir)) {
        @mkdir($dir, 0770, true);
    }
    $meta['installed_at'] = date('c');
    file_put_contents(install_lock_file(), json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
}

function install_run(array $input): array {
    if (install_is_complete()) {
        throw new RuntimeException('This copy is already installed.');
    }

    $req = install_requirements();
    if (!$req['ok']) {
        throw new RuntimeException('Server requirements are not met. Fix the checklist and try again.');
    }

    $role = strtolower(trim((string) ($input['role'] ?? ''))) === 'leaf' ? 'leaf' : 'hub';
    $siteName = trim((string) ($input['site_name'] ?? ''));
    $nodeName = trim((string) ($input['node_name'] ?? ''));
    $appUrl = rtrim(trim((string) ($input['app_url'] ?? '')), '/');
    $dbHost = trim((string) ($input['db_host'] ?? 'localhost'));
    $dbName = trim((string) ($input['db_name'] ?? 'cbt_system'));
    $dbUser = trim((string) ($input['db_user'] ?? ''));
    $dbPass = (string) ($input['db_pass'] ?? '');
    $adminUsername = trim((string) ($input['admin_username'] ?? 'admin'));
    $adminPassword = (string) ($input['admin_password'] ?? '');
    $adminName = trim((string) ($input['admin_name'] ?? 'System Administrator'));
    $adminEmail = trim((string) ($input['admin_email'] ?? ''));

    if ($siteName === '') {
        throw new RuntimeException('Enter the institution or site name.');
    }
    if ($nodeName === '') {
        $nodeName = $role === 'hub' ? $siteName . ' (Online)' : $siteName . ' (Exam hall)';
    }
    if ($appUrl === '') {
        $appUrl = install_detected_app_url();
    }
    if ($dbUser === '') {
        throw new RuntimeException('Enter the database username.');
    }
    if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $adminUsername)) {
        throw new RuntimeException('Admin username must be 3-50 letters, numbers or underscores.');
    }
    if (strlen($adminPassword) < 8) {
        throw new RuntimeException('Admin password must be at least 8 characters.');
    }

    $uid = install_uuid();
    $secret = '';
    $hubUrl = '';
    $pairingNote = '';

    if ($role === 'leaf') {
        $pairing = install_decode_pairing((string) ($input['pairing'] ?? ''));
        $uid = $pairing['uid'];
        $secret = $pairing['secret'];
        $hubUrl = $pairing['hub_url'];
        if ($pairing['name'] !== '') {
            $nodeName = $pairing['name'];
        }
    }

    $server = install_pdo($dbHost, $dbUser, $dbPass, null);
    $dbName = install_ident($dbName);
    try {
        $server->exec('CREATE DATABASE IF NOT EXISTS `' . $dbName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    } catch (PDOException $e) {
        try {
            install_pdo($dbHost, $dbUser, $dbPass, $dbName);
        } catch (PDOException $inner) {
            throw new RuntimeException('Could not create or open the database. Create `' . $dbName . '` in MySQL, then try again.');
        }
    }

    $db = install_pdo($dbHost, $dbUser, $dbPass, $dbName);
    $db->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '+01:00'");

    $schemaImported = false;
    if (!install_table_exists($db, 'admins')) {
        install_run_sql_file($db, install_root() . '/sql/schema_fresh.sql');
        $schemaImported = true;
    }

    install_seed($db, [
        'admin_username' => $adminUsername,
        'admin_password' => $adminPassword,
        'admin_name' => $adminName,
        'admin_email' => $adminEmail,
        'site_name' => $siteName,
    ]);

    $syncRunKey = bin2hex(random_bytes(16));
    $config = [
        'role' => $role,
        'uid' => $uid,
        'name' => $nodeName,
        'secret' => $secret,
        'hub_url' => $hubUrl,
        'sync_enabled' => true,
        'sync_interval' => 20,
        'sync_batch_limit' => 500,
        'sync_retention_days' => 180,
        'sync_run_key' => $syncRunKey,
        'app_url' => $appUrl,
        'db_host' => $dbHost,
        'db_name' => $dbName,
        'db_user' => $dbUser,
        'db_pass' => $dbPass,
    ];

    require_once install_root() . '/config/sync_install.php';
    $sync = sync_install_schema($db, install_root(), $uid, $role);
    install_write_config($config);

    $hubOk = null;
    if ($role === 'leaf') {
        try {
            install_hello_hub($hubUrl, $uid, $secret);
            $hubOk = true;
            $pairingNote = 'Connected to the online server.';
        } catch (Throwable $e) {
            $hubOk = false;
            $pairingNote = 'Saved locally. Connection to the online server will retry automatically when the internet is available. (' . $e->getMessage() . ')';
        }
    }

    install_mark_complete([
        'role' => $role,
        'uid' => $uid,
        'name' => $nodeName,
        'app_url' => $appUrl,
    ]);

    return [
        'role' => $role,
        'uid' => $uid,
        'name' => $nodeName,
        'app_url' => $appUrl,
        'admin_username' => $adminUsername,
        'schema_imported' => $schemaImported,
        'sync' => ['prepared' => $sync['prepared'], 'skipped' => $sync['skipped']],
        'hub_ok' => $hubOk,
        'note' => $pairingNote,
        'admin_url' => $appUrl . '/admin/',
        'student_url' => $appUrl . '/student/login.php',
    ];
}
