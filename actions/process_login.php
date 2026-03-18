<?php
/**
 * process_login.php
 * 
 * Handles the secure authentication of users.
 * Implements strict input validation, prepared statements for database queries, 
 * and password verification against BCRYPT hashes. Also handles role-based routing.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/flash.php';

// Prevent GET requests from processing script
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

// Session-based Rate Limiting (Throttle after 5 failed attempts)
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

if ($_SESSION['login_attempts'] >= 5) {
    $time_passed = time() - $_SESSION['last_attempt_time'];
    if ($time_passed < 300) { // Lockout for 5 minutes
        $wait_time = ceil((300 - $time_passed) / 60);
        set_flash_message('error', "Too many failed attempts. Please wait $wait_time minute(s).");
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    } else {
        // Reset after lockout expires
        $_SESSION['login_attempts'] = 0;
    }
}

$_SESSION['last_attempt_time'] = time();

// Capture variables & trim
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? ''; // Don't modify raw incoming PW structure

// Validation checks
if (empty($email) || empty($password)) {
    // Missing inputs
    set_flash_message('error', 'Please provide both email and password.');
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // Invalid email format - generalized error to prevent fishing 
    $_SESSION['login_attempts']++;
    set_flash_message('error', 'Invalid email or password.');
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

// Query DB using prepared statement to prevent SQL Injection
// Note: We pull `user_id`, `password_hash`, and `role_type`
$stmt = $conn->prepare("SELECT user_id, password_hash, role_type FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    // Verify password hash against the stored hash
    if (password_verify($password, $user['password_hash'])) {
        // Successful login, reset attempts
        $_SESSION['login_attempts'] = 0;
        
        // Ensure Admins are routed exclusively through the dedicated admin gateway
        if ($user['role_type'] === 'Admin') {
            set_flash_message('warning', 'Administrators must log in through the secure admin portal.');
            header('Location: ' . BASE_URL . 'admin/login.php');
            exit;
        }

        // Authenticated! Update Session securely
        session_regenerate_id(true); // Mitigate session fixation
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role_type'];
        $_SESSION['last_action'] = time(); // Track for timeout purposes

        // Role Routing as defined in Module A: Dual-Role Authentication
        switch ($user['role_type']) {
            case 'Employer':
                header('Location: ' . BASE_URL . 'employer_dashboard.php');
                break;
            case 'Seeker':
                header('Location: ' . BASE_URL . 'jobs.php');
                break;
            default:
                header('Location: ' . BASE_URL . 'index.php');
                break;
        }
        exit;
    } else {
        // Password did not match
        $_SESSION['login_attempts']++;
        set_flash_message('error', 'Invalid email or password.');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
} else {
    // User not found
    $_SESSION['login_attempts']++;
    set_flash_message('error', 'Invalid email or password.');
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

// Close resources
$stmt->close();
$conn->close();
?>