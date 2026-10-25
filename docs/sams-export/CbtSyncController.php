<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Security;

final class CbtSyncController
{
    public function students(): void
    {
        $this->requireToken();

        $since = trim((string)($_GET['since'] ?? ''));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $where = '1=1';
        $params = [];
        if ($since !== '') {
            $where = '(COALESCE(s.updated_at, s.created_at) >= ? OR COALESCE(fp.updated_at, fp.enrolled_at) >= ?)';
            $params[] = $since;
            $params[] = $since;
        }

        $totalRow = Database::one(
            "SELECT COUNT(*) AS c
             FROM students s
             LEFT JOIN face_profiles fp ON fp.student_id = s.id
             WHERE $where",
            $params
        );
        $total = (int)($totalRow['c'] ?? 0);

        $rows = Database::all(
            "SELECT s.id, s.matric_no, s.first_name, s.last_name, s.other_name, s.gender,
                    s.level, s.phone, s.email, s.passport_path, s.enrollment_status,
                    s.updated_at, s.created_at,
                    d.code AS department_code, d.name AS department_name,
                    f.name AS faculty_name,
                    fp.descriptor_enc
             FROM students s
             LEFT JOIN departments d ON d.id = s.department_id
             LEFT JOIN faculties f ON f.id = s.faculty_id
             LEFT JOIN face_profiles fp ON fp.student_id = s.id
             WHERE $where
             ORDER BY COALESCE(s.updated_at, s.created_at) ASC, s.id ASC
             LIMIT $limit OFFSET $offset",
            $params
        );

        $students = [];
        foreach ($rows as $row) {
            $students[] = $this->exportStudent($row);
        }

        json_response([
            'ok' => true,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'students' => $students,
        ]);
    }

    private function requireToken(): void
    {
        $expected = trim(setting('cbt_sync_token', ''));
        if ($expected === '') {
            json_response(['ok' => false, 'message' => 'CBT sync token is not configured on SAMS.'], 403);
        }
        $provided = trim((string)($_GET['token'] ?? $_SERVER['HTTP_X_CBT_SYNC_TOKEN'] ?? ''));
        if ($provided === '' || !hash_equals($expected, $provided)) {
            json_response(['ok' => false, 'message' => 'Invalid CBT sync token.'], 403);
        }
    }

    private function exportStudent(array $row): array
    {
        $status = (string)($row['enrollment_status'] ?? 'preloaded');
        $active = in_array($status, ['paid', 'registered', 'active', 'face', 'photo'], true) ? 'active' : $status;
        $updated = (string)($row['updated_at'] ?: $row['created_at'] ?: date('Y-m-d H:i:s'));

        $item = [
            'sams_student_id' => (int)$row['id'],
            'matric_no' => (string)$row['matric_no'],
            'first_name' => (string)$row['first_name'],
            'last_name' => (string)$row['last_name'],
            'other_name' => (string)($row['other_name'] ?? ''),
            'gender' => (string)($row['gender'] ?? ''),
            'email' => (string)($row['email'] ?? ''),
            'phone' => (string)($row['phone'] ?? ''),
            'level' => (int)($row['level'] ?? 100),
            'department_code' => (string)($row['department_code'] ?? 'GEN'),
            'department_name' => (string)($row['department_name'] ?? 'General'),
            'faculty_name' => (string)($row['faculty_name'] ?? ''),
            'status' => $active === 'active' ? 'active' : 'inactive',
            'enrollment_status' => $status,
            'updated_at' => $updated,
        ];

        $photo = $this->encodePassport((string)($row['passport_path'] ?? ''));
        if ($photo) {
            $item['photo_b64'] = $photo['b64'];
            $item['photo_mime'] = $photo['mime'];
        }

        $descriptor = $this->decodeDescriptor((string)($row['descriptor_enc'] ?? ''));
        if ($descriptor) {
            $item['face_descriptor'] = $descriptor;
        }

        return $item;
    }

    /**
     * @return array{b64:string,mime:string}|null
     */
    private function encodePassport(string $path): ?array
    {
        $path = trim($path);
        if ($path === '') {
            return null;
        }
        $file = rtrim((string)config('paths.public', ''), '/') . '/' . ltrim($path, '/');
        if (!is_file($file) || filesize($file) < 32) {
            return null;
        }
        $bin = file_get_contents($file);
        if ($bin === false) {
            return null;
        }
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = 'image/jpeg';
        if ($ext === 'png') {
            $mime = 'image/png';
        } elseif ($ext === 'webp') {
            $mime = 'image/webp';
        } elseif ($ext === 'gif') {
            $mime = 'image/gif';
        }
        return ['b64' => base64_encode($bin), 'mime' => $mime];
    }

    private function decodeDescriptor(string $enc): ?array
    {
        if ($enc === '') {
            return null;
        }
        $json = Security::decrypt($enc);
        $data = json_decode($json, true);
        if (!is_array($data) || count($data) < 128) {
            return null;
        }
        return array_map('floatval', $data);
    }
}
