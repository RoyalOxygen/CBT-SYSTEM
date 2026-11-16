<?php
/**
 * CBT System - Restriction enforcement helpers
 *
 * Restrictions are controlled by the online hub and written to
 * storage/sync/policy.json by the sync client.
 */

if (!defined('CBT_SYSTEM')) {
    die('Unauthorized access');
}

function node_policy_message(string $capability): string {
    $messages = [
        'student_edit' => 'Student editing is disabled by the online server.',
        'question_edit' => 'Question editing is disabled by the online server.',
        'exam_edit' => 'Exam management is disabled by the online server.',
        'exam_access' => 'Examinations are disabled by the online server.',
        'results_view' => 'Result viewing is disabled by the online server.',
        'results_edit' => 'Result editing is disabled by the online server.',
        'file_updates' => 'File updates are disabled by the online server.',
    ];
    return $messages[$capability] ?? 'This action is disabled by the online server.';
}

function node_request_expects_json(): bool {
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/ajax/') !== false) {
        return true;
    }
    if (strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '', 'XMLHttpRequest') === 0) {
        return true;
    }
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    return stripos($accept, 'application/json') !== false;
}

/**
 * Block requests while the hub has suspended this node.
 * Sync endpoints and CLI scripts bypass this guard.
 */
function node_guard_request(): void {
    if (PHP_SAPI === 'cli' || defined('CBT_SYNC_CONTEXT')) {
        return;
    }
    if (!node_is_suspended()) {
        return;
    }

    http_response_code(423);
    if (node_request_expects_json()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'This examination server has been suspended by the online server. Please contact the administrator.',
        ]);
        exit;
    }

    header('Content-Type: text/html; charset=utf-8');
    $name = defined('APP_NAME') ? APP_NAME : 'CBT System';
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Server suspended</title>'
        . '<style>body{font-family:system-ui,sans-serif;background:#f8f9fa;color:#212529;display:flex;'
        . 'align-items:center;justify-content:center;min-height:100vh;margin:0}'
        . '.box{max-width:520px;background:#fff;border:1px solid #dee2e6;border-radius:12px;padding:32px;'
        . 'box-shadow:0 8px 24px rgba(0,0,0,.08)}h1{font-size:1.35rem;margin:0 0 12px}'
        . 'p{line-height:1.6;margin:0;color:#495057}</style></head><body><div class="box">'
        . '<h1>Server suspended</h1><p>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
        . ' has been suspended by the online administrator. Local access and examinations are paused '
        . 'until the online server re-enables this node.</p></div></body></html>';
    exit;
}

/**
 * Enforce a capability at an action endpoint.
 * Returns a redirect (pages) or JSON error (AJAX).
 */
function node_require(string $capability, string $redirectTo = ''): void {
    if (node_can($capability)) {
        return;
    }
    $message = node_policy_message($capability);

    if (node_request_expects_json()) {
        if (function_exists('jsonResponse')) {
            jsonResponse(false, $message);
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }

    if (function_exists('redirect')) {
        $target = $redirectTo !== '' ? $redirectTo : (defined('APP_URL') ? APP_URL . '/admin/dashboard.php' : '/');
        redirect($target, $message, 'error');
    }
    echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    exit;
}

/**
 * Guard for result-reading pages. AJAX endpoints should use node_require().
 */
function node_require_results_view(): void {
    node_require('results_view');
}

// Enforce suspension for every normal web request that bootstraps the app.
node_guard_request();
