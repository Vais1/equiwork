<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_input(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_validate_request(): bool {
    $token = '';

    if (isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])) {
        $token = $_POST['csrf_token'];
    } elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN']) && is_string($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
    }

    return $token !== ''
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_fail_json(): void {
    http_response_code(419);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Session security token mismatch. Please refresh and try again.']);
    exit;
}

function csrf_fail_redirect(string $path, string $message = 'Invalid request token. Please try again.'): void {
    if (function_exists('set_flash_message')) {
        set_flash_message('error', $message);
    }

    header('Location: ' . $path);
    exit;
}
