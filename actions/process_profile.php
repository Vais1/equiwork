<?php
// actions/process_profile.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db.php';

// Deep validation setup
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'profile_update.php');
    exit;
}

// 1. Rigorous Data Sanitization
// Utilize ENT_QUOTES to convert single quotes into HTML equivalents avoiding database syntax manipulation
$user_id = $_SESSION['user_id'];
$username_raw = $_POST['username'] ?? '';
$email_raw    = $_POST['email'] ?? '';
$password_raw = $_POST['password'] ?? '';

// Sanitize outputs
$username = htmlspecialchars(trim($username_raw), ENT_QUOTES, 'UTF-8');
$email    = trim($email_raw); 
$errors   = [];

// 2. Strict Server-Side Validation checks (Tier 2)

// A. Name validation
if (empty($username)) {
    $errors[] = "Name field cannot be empty.";
} elseif (strlen($username) > 100) {
    $errors[] = "Name exceeds maximum allowed characters.";
}

// B. Email constraints verification
if (empty($email)) {
    $errors[] = "Email address cannot be empty.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // Fails standard RFC specs
    $errors[] = "A strictly valid email address is required. (e.g. user@domain.com)";
} else {
    // Check if another account took the email
    $chk_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $chk_stmt->bind_param("si", $email, $user_id);
    $chk_stmt->execute();
    if ($chk_stmt->get_result()->num_rows > 0) {
        $errors[] = "That email is already registered to another account.";
    }
    $chk_stmt->close();
}

// C. Password validation
if (!empty($password_raw)) {
    if (strlen($password_raw) < 8) {
        $errors[] = "Password must maintain strict compliance of 8 characters minimum.";
    }
}

// D. Username uniqueness check
if (!empty($username)) {
    $chk_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
    $chk_stmt->bind_param("si", $username, $user_id);
    $chk_stmt->execute();
    if ($chk_stmt->get_result()->num_rows > 0) {
        $errors[] = "That username/company name is already in use.";
    }
    $chk_stmt->close();
}

// 3. Execute DB Action
if (count($errors) > 0) {
    // Return to form with exact reasons
    $_SESSION['flash_errors'] = $errors;
    header('Location: ' . BASE_URL . 'profile_update.php');
    exit;
} else {
    // Logic branch: Update WITH or WITHOUT password 
    if (!empty($password_raw)) {
        // Safe hashing structure
        $hash = password_hash($password_raw, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password_hash = ? WHERE user_id = ?");
        $stmt->bind_param("sssi", $username, $email, $hash, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE user_id = ?");
        $stmt->bind_param("ssi", $username, $email, $user_id);
    }

    if ($stmt->execute()) {
        $_SESSION['flash_success'] = "Your profile has been securely updated.";
    } else {
        $_SESSION['flash_errors'] = ["A severe system error occurred blocking the database update."];
    }
    $stmt->close();
    header('Location: ' . BASE_URL . 'profile_update.php');
    exit;
}
?>