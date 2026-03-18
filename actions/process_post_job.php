<?php
// actions/process_post_job.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Strict session check for Employer role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employer') {
    set_flash_message('error', 'Unauthorised access. Only employers can post jobs.');
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'post_job.php');
    exit;
}

// 1. Sanitize and collect inputs
$employer_id = $_SESSION['user_id'];
$title = trim(htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8'));
$location_type = trim($_POST['location_type'] ?? '');
$description = trim(htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8'));
$accommodations = $_POST['accommodations'] ?? []; // Array of accommodation IDs

// 2. Validate inputs
$errors = [];
if (empty($title)) {
    $errors[] = 'Job title is required.';
}
if (empty($description)) {
    $errors[] = 'Job description is required.';
}
$allowed_locations = ['Remote', 'Hybrid', 'On-site'];
if (!in_array($location_type, $allowed_locations)) {
    $errors[] = 'Invalid work arrangement selected.';
}

// Validate that accommodations are provided as an array
if (!is_array($accommodations)) {
    $errors[] = 'Invalid accommodations format.';
}

if (!empty($errors)) {
    foreach ($errors as $error) {
        set_flash_message('error', $error);
    }
    header('Location: ' . BASE_URL . 'post_job.php');
    exit;
}

// 3. Database Transaction
try {
    $conn->begin_transaction();

    // Insert the job 
    $stmt = $conn->prepare("INSERT INTO jobs (employer_id, title, description, location_type, status, posted_at) VALUES (?, ?, ?, ?, 'Active', NOW())");
    $stmt->bind_param("isss", $employer_id, $title, $description, $location_type);
    $stmt->execute();
    
    $job_id = $conn->insert_id;
    
    // Insert accommodations if selected
    if (!empty($accommodations)) {
        $acc_stmt = $conn->prepare("INSERT INTO job_accommodations (job_id, accommodation_id) VALUES (?, ?)");
        
        foreach ($accommodations as $acc_id) {
            $acc_id_int = (int) $acc_id;
            // Additional check to prevent invalid ID insertion
            if ($acc_id_int > 0) {
                $acc_stmt->bind_param("ii", $job_id, $acc_id_int);
                $acc_stmt->execute();
            }
        }
    }

    $conn->commit();

    set_flash_message('success', 'Job posting created successfully!');
    header('Location: ' . BASE_URL . 'employer_dashboard.php');
    exit;

} catch (Exception $e) {
    $conn->rollback();
    // Log the actual error for the developer and show generic message to user
    error_log("Job Posting Error: " . $e->getMessage());
    set_flash_message('error', 'A system error occurred while creating the job posting. Please try again.');
    header('Location: ' . BASE_URL . 'post_job.php');
    exit;
}
