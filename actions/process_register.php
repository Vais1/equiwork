<?php
/**
 * process_register.php
 * 
 * Handles new user registration for the EquiWork platform.
 * Ensures robust data sanitization, password hashing via BCRYPT,
 * role validation, and duplicate account prevention.
 */
// Ensure session starts before ANY output
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'register.php');
    exit;
}

// Session-based Rate Limiting (Throttle after 5 failed attempts)
if (!isset($_SESSION['register_attempts'])) {
    $_SESSION['register_attempts'] = 0;
    $_SESSION['last_register_attempt_time'] = time();
}

if ($_SESSION['register_attempts'] >= 5) {
    $time_passed = time() - $_SESSION['last_register_attempt_time'];
    if ($time_passed < 300) { // Lockout for 5 minutes
        $wait_time = ceil((300 - $time_passed) / 60);
        require_once __DIR__ . '/../includes/flash.php';
        set_flash_message('error', "Too many failed attempts. Please wait $wait_time minute(s).");
        header('Location: ' . BASE_URL . 'register.php');
        exit;
    } else {
        // Reset after lockout expires
        $_SESSION['register_attempts'] = 0;
    }
}

$_SESSION['last_register_attempt_time'] = time();

// 1. Sanitize Strings & Inputs
// htmlspecialchars removes potential XSS before validating
$username  = trim(htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'));
$email     = trim($_POST['email'] ?? ''); // Wait to run filter_var
$password  = $_POST['password'] ?? '';
$role_type = trim($_POST['role_type'] ?? '');

// 2. Server-Side Validation
$errors = [];

// Name constraint
if (empty($username)) {
    $errors[] = "Username is required.";
}

// Email constraint format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "A valid email address is required.";
}

// Password constraint
if (strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters long.";
}

// Role constraints mapping to ENUM
$allowed_roles = ['Employer', 'Seeker'];
if (!in_array($role_type, $allowed_roles)) {
    $errors[] = "Invalid role selected. Administrators must be provisioned manually.";
}

// Check for existing user (to prevent duplicate entry SQL errors)
if (empty($errors)) {
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $errors[] = "An account with that email or username already exists.";
    }
    $stmt->close();
}

// 3. Process Execution if Valid
require_once '../includes/flash.php';

if (!empty($errors)) {
    $_SESSION['register_attempts']++;
    // Basic fallback - In a real application, you'd store this in a session flash message
    // and display on the register.php page instead of a raw die().
    set_flash_message('error', implode('<br>', $errors));
    header('Location: ' . BASE_URL . 'register.php');
    exit;
} else {
    // Check for existing user again is not needed here
    $_SESSION['register_attempts'] = 0;
    
    // Hash password 
    // Uses BCRYPT inherently with PHP 8, never store plain text
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Prepare robust insertion query
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role_type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $password_hash, $role_type);

    if ($stmt->execute()) {
        // Clear errors if any
        unset($_SESSION['register_errors']);
        
        // Auto-login upon registration success (or you could redirect to login)
        session_regenerate_id(true);
        $_SESSION['user_id'] = $stmt->insert_id;
        $_SESSION['role'] = $role_type;
        $_SESSION['last_action'] = time();
        
        // Final routing logic based on assigned role
        switch ($role_type) {
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
        // Failsafe for internal query error
        set_flash_message('error', 'A system error occurred. Please try again later.');
        header('Location: ' . BASE_URL . 'register.php');
        exit;
    }

    $stmt->close();
}
$conn->close();
?>