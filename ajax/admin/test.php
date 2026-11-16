<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

header('Content-Type: application/json');

// Simple test response
echo json_encode([
    'success' => true,
    'message' => 'AJAX test endpoint is working!',
    'timestamp' => date('Y-m-d H:i:s')
]);