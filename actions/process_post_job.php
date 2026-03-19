<?php
// actions/process_post_job.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';

// Strict session check for Employer role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employer') {
    set_flash_message('error', 'Unauthorised access. Only employers can post jobs.');
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'employer/post_job.php');
    exit;
}

if (!csrf_validate_request()) {
    csrf_fail_redirect(BASE_URL . 'employer/post_job.php');
}

// 1. Sanitize and collect inputs
$employer_id = $_SESSION['user_id'];
$title = trim($_POST['title'] ?? '');
$location_type = trim($_POST['location_type'] ?? '');
$description = trim($_POST['description'] ?? '');
$accommodations = $_POST['accommodations'] ?? []; // Array of accommodation IDs

$company_name = trim($_POST['company_name'] ?? '');
$employment_type = trim($_POST['employment_type'] ?? 'Full-time');
$state_region = trim($_POST['state_region'] ?? '');
$salary_min = filter_input(INPUT_POST, 'salary_min_myr', FILTER_VALIDATE_FLOAT);
$salary_min = ($salary_min !== false && $salary_min !== null) ? $salary_min : null;

$salary_max = filter_input(INPUT_POST, 'salary_max_myr', FILTER_VALIDATE_FLOAT);
$salary_max = ($salary_max !== false && $salary_max !== null) ? $salary_max : null;


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
    header('Location: ' . BASE_URL . 'employer/post_job.php');
    exit;
}

// 3. Database Transaction
try {
    $conn->begin_transaction();

    
    // Insert the job 
    $stmt = $conn->prepare("INSERT INTO jobs (employer_id, title, company_name, description, location_type, employment_type, salary_min_myr, salary_max_myr, state_region, status, posted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', NOW())");
    $stmt->bind_param("isssssdds", $employer_id, $title, $company_name, $description, $location_type, $employment_type, $salary_min, $salary_max, $state_region);

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
    header('Location: ' . BASE_URL . 'employer/dashboard.php');
    exit;

} catch (Exception $e) {
    $conn->rollback();
    // Log the actual error for the developer and show generic message to user
    error_log("Job Posting Error: " . $e->getMessage());
    set_flash_message('error', 'A system error occurred while creating the job posting. Please try again.');
    header('Location: ' . BASE_URL . 'employer/post_job.php');
    exit;
}

