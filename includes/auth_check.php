<?php
// includes/auth_check.php
// Strict session timeout & security mechanism

// 1. Core Security Headers
// Prevent browsers from guessing the MIME type 
header('X-Content-Type-Options: nosniff');
// Enhance clickjacking and framing defenses
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// 2. Safely initialize session 
if (session_status() === PHP_SESSION_NONE) {
    // Attempt secure cookie configurations before start
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    // If using HTTPS, force secure flag: ini_set('session.cookie_secure', 1);
    
    session_start();
}

// 3. Enforce Strict Session Timeout (30 minutes / 1800 seconds)
$timeout = 1800; 

// Track time-to-live
if (isset($_SESSION['last_action']) && (time() - $_SESSION['last_action']) > $timeout) {
    // Purge session explicitly to counter edge-case hangs
    $_SESSION = [];
    
    // Nuke the session cookie on the client side
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy file store
    session_destroy();
    
    // Bounce gracefully to login with reason parameter
    header('Location: ' . BASE_URL . 'login.php?reason=timeout');
    exit;
}

// 4. Session Fixation Mitigation (Regenerate ID automatically every 15 minutes)
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 900) {
    session_regenerate_id(true);    // Generate new ID and delete old physical file
    $_SESSION['created'] = time();  // Reset regeneration timer
}

// 5. Extend active lifecycle
$_SESSION['last_action'] = time();

// Optional Helper: Function to protect pages based on required role
function enforce_role($required_role) {
    // Check if the user is completely unauthenticated
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }

    // Role does not match destination requirement
    if ($_SESSION['role'] !== $required_role) {
        // Evaluate their actual role to redirect to safe zones natively
        switch ($_SESSION['role']) {
            case 'Admin': 
                header('Location: ' . BASE_URL . 'admin/dashboard.php'); 
                break;
            case 'Employer': 
                header('Location: ' . BASE_URL . 'employer_dashboard.php'); 
                break;
            case 'Seeker': 
                header('Location: ' . BASE_URL . 'jobs.php'); 
                break;
            default: 
                // Catch-all
                header('Location: ' . BASE_URL . 'login.php'); 
                break;
        }
        exit;
    }
}
?>