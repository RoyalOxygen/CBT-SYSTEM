<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method');
if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) jsonResponse(false, 'Invalid token');
if (!isAdminLoggedIn() || $_SESSION['admin_role'] !== 'super_admin') jsonResponse(false, 'Access denied');

try {
    $db = getDB();
    
    $settings = ['institution_name', 'timezone', 'default_duration', 'default_pass_mark', 'default_question_limit', 'session_timeout', 'biometric_threshold'];
    foreach ($settings as $key) {
        if (isset($_POST[$key])) {
            setSetting($key, sanitize($_POST[$key]), 'general');
        }
    }
    
    // Checkboxes
    setSetting('maintenance_mode', !empty($_POST['maintenance_mode']) ? '1' : '0', 'general');
    setSetting('biometric_enabled', !empty($_POST['biometric_enabled']) ? '1' : '0', 'general');
    
    logActivity('admin', $_SESSION['admin_id'], 'save_settings', 'Updated system settings');
    jsonResponse(true, 'Settings saved successfully');
} catch (PDOException $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
