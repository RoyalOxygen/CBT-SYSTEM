<?php
/**
 * CBT System - Session Keepalive Endpoint
 * Called by client-side heartbeat to keep student sessions alive
 * (only ends on explicit logout or a new login).
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
header('Content-Type: application/json');

if (!isStudentLoggedIn()) {
    jsonResponse(false, 'Not authenticated');
}

// Refresh session activity to keep student session alive on idle pages
refreshStudentSessionActivity();

jsonResponse(true, 'Session refreshed');
