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
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'register.php');
    exit;
}

if (!csrf_validate_request()) {
    csrf_fail_redirect(BASE_URL . 'register.php');
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
$username  = trim($_POST['username'] ?? '');
$email     = trim($_POST['email'] ?? ''); // Wait to run filter_var
$password  = $_POST['password'] ?? '';
$role_type = trim($_POST['role_type'] ?? '');

// 2. Server-Side Validation
$errors = [];

// Name constraint
if (empty($username)) {
    $errors[] = "Username is required.";
} elseif (mb_strlen($username) > 100) {
    $errors[] = "Username must be 100 characters or fewer.";
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
if (!in_array($role_type, $allowed_roles, true)) {
    $errors[] = "Invalid role selected. Administrators must be provisioned manually.";
}

// Check for existing user (to prevent duplicate entry SQL errors)
if (empty($errors)) {
    try {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "An account with that email or username already exists.";
        }
        $stmt->close();
    } catch (Throwable $e) {
        error_log('Registration duplicate check failed: ' . $e->getMessage());
        $errors[] = "We could not validate your registration data. Please try again.";
    }
}

// 3. Process Execution if Valid

if (!empty($errors)) {
    $_SESSION['register_attempts']++;
    // Basic fallback - In a real application, you'd store this in a session flash message
    // and display on the register.php page instead of a raw die().
    set_flash_message('error', implode('<br>', $errors));
    header('Location: ' . BASE_URL . 'register.php');
    exit;
} else {
    try {
        $_SESSION['register_attempts'] = 0;

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $safe_username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role_type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $safe_username, $email, $password_hash, $role_type);

        if ($stmt->execute()) {
            unset($_SESSION['register_errors']);

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$stmt->insert_id;
            $_SESSION['role'] = $role_type;
            $_SESSION['last_action'] = time();

            if ($role_type === 'Employer') {
                header('Location: ' . BASE_URL . 'employer_dashboard.php');
            } else {
                header('Location: ' . BASE_URL . 'jobs.php');
            }
            exit;
        }

        set_flash_message('error', 'A system error occurred. Please try again later.');
        header('Location: ' . BASE_URL . 'register.php');
        exit;
    } catch (Throwable $e) {
        error_log('Registration insert failed: ' . $e->getMessage());
        set_flash_message('error', 'Your account could not be created at this time. Please try again.');
        header('Location: ' . BASE_URL . 'register.php');
        exit;
    }
}
?>
