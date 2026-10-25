<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method');
if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) jsonResponse(false, 'Invalid token');
if (!isAdminLoggedIn() || $_SESSION['admin_role'] !== 'super_admin') jsonResponse(false, 'Access denied');

try {
    $db = getDB();
    
    $settings = ['institution_name', 'timezone', 'default_duration', 'default_pass_mark', 'default_question_limit', 'session_timeout', 'biometric_threshold', 'face_api_url', 'face_timeout', 'face_match_threshold', 'attendance_threshold'];
    foreach ($settings as $key) {
        if (isset($_POST[$key])) {
            setSetting($key, sanitize($_POST[$key]), 'general');
        }
    }

    if (isset($_POST['face_api_url'])) {
        setSetting('face_api_url', rtrim(sanitize($_POST['face_api_url']), '/'), 'general');
    }
    
    // Checkboxes
    setSetting('maintenance_mode', !empty($_POST['maintenance_mode']) ? '1' : '0', 'general');
    setSetting('biometric_enabled', !empty($_POST['biometric_enabled']) ? '1' : '0', 'general');
    setSetting('face_login_enabled', !empty($_POST['face_login_enabled']) ? '1' : '0', 'general');
    setSetting('attendance_check_enabled', !empty($_POST['attendance_check_enabled']) ? '1' : '0', 'general');
    if (isset($_POST['attendance_threshold'])) {
        $threshold = (float) $_POST['attendance_threshold'];
        if ($threshold < 0) {
            $threshold = 0;
        }
        if ($threshold > 100) {
            $threshold = 100;
        }
        setSetting('attendance_threshold', (string) $threshold, 'general');
    }

    if (node_is_hub()) {
        if (isset($_POST['sams_url'])) {
            setSetting('sams_url', rtrim(sanitize($_POST['sams_url']), '/'), 'general');
        }
        if (isset($_POST['sams_sync_token'])) {
            setSetting('sams_sync_token', trim((string) $_POST['sams_sync_token']), 'general');
        }
        setSetting('sams_sync_enabled', !empty($_POST['sams_sync_enabled']) ? '1' : '0', 'general');
    }
    
    logActivity('admin', $_SESSION['admin_id'], 'save_settings', 'Updated system settings');
    jsonResponse(true, 'Settings saved successfully');
} catch (PDOException $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
