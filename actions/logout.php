<?php
// session_start must be called before using session_destroy/session_unset
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nullify the session super global properties
$_SESSION = array();

// If you want to kill the session cookie entirely 
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy all session registrations physically
session_destroy();

// Redirect back to login explicitly avoiding cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../includes/config.php';
header('Location: ' . BASE_URL . 'login.php');
exit;
?>