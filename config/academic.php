<?php
if (!defined('CBT_SYSTEM')) {
    die('Unauthorized access');
}

function academic_make_code(string $name, int $max = 20): string {
    $name = trim($name);
    $words = preg_split('/\s+/', $name) ?: [];
    $skip = ['AND', 'OF', 'THE', 'FOR', '&'];
    $code = '';
    foreach ($words as $word) {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $word));
        if ($clean === '' || in_array($clean, $skip, true)) {
            continue;
        }
        $code .= substr($clean, 0, 1);
    }
    if ($code === '') {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name));
    }
    if ($code === '') {
        $code = 'GEN';
    }
    return substr($code, 0, $max);
}

function academic_unique_code(PDO $db, string $table, string $column, string $base, ?int $ignoreId = null): string {
    $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $base) ?: 'GEN', 0, 20));
    $code = $base;
    $n = 1;
    while (true) {
        $sql = "SELECT id FROM `$table` WHERE `$column` = ?";
        $params = [$code];
        if ($ignoreId) {
            $sql .= ' AND id != ?';
            $params[] = $ignoreId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $code;
        }
        $n++;
        $suffix = (string) $n;
        $code = substr($base, 0, max(1, 20 - strlen($suffix))) . $suffix;
    }
}

function academic_find_or_create_faculty(PDO $db, string $name, string $code = ''): int {
    $name = trim($name);
    if ($name === '') {
        $name = 'General';
    }
    $stmt = $db->prepare('SELECT id FROM faculties WHERE faculty_name = ? LIMIT 1');
    $stmt->execute([$name]);
    $row = $stmt->fetch();
    if ($row) {
        return (int) $row['id'];
    }
    if ($code === '') {
        $code = academic_make_code($name);
    }
    $code = academic_unique_code($db, 'faculties', 'faculty_code', $code);
    $db->prepare('INSERT INTO faculties (faculty_code, faculty_name, status) VALUES (?, ?, 1)')
        ->execute([$code, $name]);
    return (int) $db->lastInsertId();
}

function academic_ensure_schema(PDO $db): void {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `faculties` (
            `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `faculty_code` varchar(20) NOT NULL,
            `faculty_name` varchar(100) NOT NULL,
            `status` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_faculty_code` (`faculty_code`),
            KEY `idx_faculty_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $cols = [];
        foreach ($db->query('SHOW COLUMNS FROM departments') as $row) {
            $cols[$row['Field']] = true;
        }
        if (empty($cols['faculty_id'])) {
            $db->exec('ALTER TABLE departments ADD COLUMN faculty_id int(11) UNSIGNED DEFAULT NULL AFTER dept_name');
        }
        try {
            $db->exec('ALTER TABLE departments ADD KEY idx_faculty (faculty_id)');
        } catch (Throwable $e) {
        }

        $rows = $db->query("SELECT id, faculty FROM departments WHERE faculty IS NOT NULL AND TRIM(faculty) != '' AND (faculty_id IS NULL OR faculty_id = 0)")->fetchAll();
        foreach ($rows as $row) {
            $facultyId = academic_find_or_create_faculty($db, (string) $row['faculty']);
            $db->prepare('UPDATE departments SET faculty_id = ? WHERE id = ?')->execute([$facultyId, $row['id']]);
        }
    } catch (Throwable $e) {
        error_log('academic_ensure_schema: ' . $e->getMessage());
    }
}
