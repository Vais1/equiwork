<?php
// actions/process_application.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Strict Role Gate: Only Seekers can submit applications
enforce_role('Seeker');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'jobs.php');
    exit;
}

$seeker_id = $_SESSION['user_id'];
$job_id    = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);
$letter    = trim($_POST['cover_letter'] ?? '');

// Sanitization & Security stripping
$letter_safe = htmlspecialchars($letter, ENT_QUOTES, 'UTF-8');

// Tier 2 Validation
if (!$job_id || empty($letter_safe) || strlen($letter_safe) > 1000) {
    // Malicious or manipulated payload sent via POST directly
    set_flash_message('error', 'Invalid application parameters provided. Action blocked for security.');
    header('Location: ' . BASE_URL . 'jobs.php');
    exit;
}

// Ensure application isn't strictly duplicate
$chk = $conn->prepare("SELECT application_id FROM applications WHERE job_id = ? AND seeker_id = ?");
$chk->bind_param("ii", $job_id, $seeker_id);
$chk->execute();
if ($chk->get_result()->num_rows > 0) {
    // Already applied
    $chk->close();
    set_flash_message('warning', 'You have already applied for this job.');
    header('Location: ' . BASE_URL . 'jobs.php');
    exit;
}
$chk->close();

// DB Insertion - Secure query mapping to the `applications` structure
$status = 'Pending';
$stmt = $conn->prepare("INSERT INTO applications (job_id, seeker_id, status, cover_letter) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiss", $job_id, $seeker_id, $status, $letter_safe);

if ($stmt->execute()) {
    
    // ==========================================
    // Phase 5 Module E: Automated Email System (Stub)
    // ==========================================
    
    // Simulate fetching emails for notification triggers
    $q = $conn->prepare("
        SELECT j.title, u_emp.email AS employer_email, u_seek.email AS seeker_email, u_seek.username AS seeker_name 
        FROM jobs j 
        JOIN users u_emp ON j.employer_id = u_emp.user_id 
        JOIN users u_seek ON u_seek.user_id = ? 
        WHERE j.job_id = ?
    ");
    $q->bind_param("ii", $seeker_id, $job_id);
    $q->execute();
    $notify = $q->get_result()->fetch_assoc();
    
    if ($notify) {
        $job_title = htmlspecialchars($notify['title'] ?? 'Job Posting');
        $seeker_name = htmlspecialchars($notify['seeker_name'] ?? 'Applicant');
        
        // Define common headers for robust HTML emails
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: EquiWork Notifications <noreply@equiwork.local>\r\n";
        $headers .= "Reply-To: noreply@equiwork.local\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        // Employer Email Payload
        $subject_emp = "New Application Alert: " . $job_title;
        $body_emp = "
        <html>
        <head>
          <title>New Candidate Application</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
          <h2 style='color: #2c9394;'>New Application Received</h2>
          <p>Hello,</p>
          <p>The candidate <strong>{$seeker_name}</strong> has submitted a new application for your posting: <em>{$job_title}</em>.</p>
          <p>Please log in to your Employer Dashboard to review the application details.</p>
          <hr style='border: 1px solid #ddd;'>
          <small>This is an automated message from <a href='http://localhost/equiwork/'>EquiWork</a>. Please do not reply.</small>
        </body>
        </html>
        ";
        
        // Seeker Email Payload
        $subject_seek = "Application Received: " . $job_title;
        $body_seek = "
        <html>
        <head>
          <title>Application Successfully Submitted</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
          <h2 style='color: #2c9394;'>Application Confirmation</h2>
          <p>Hello {$seeker_name},</p>
          <p>Your application for the position <strong>{$job_title}</strong> has been received safely.</p>
          <p>The employer has been notified and will review your profile shortly.</p>
          <p>Thank you for using EquiWork!</p>
          <hr style='border: 1px solid #ddd;'>
          <small>This is an automated message from <a href='http://localhost/equiwork/'>EquiWork</a>. Please do not reply.</small>
        </body>
        </html>
        ";
        
        // Execute mail functions using error suppression (@) to prevent UI breakage if SMTP is not configured locally
        @mail($notify['employer_email'], $subject_emp, $body_emp, $headers);
        @mail($notify['seeker_email'], $subject_seek, $body_seek, $headers);
    }
    
    $q->close();
    $stmt->close();
    
    // Redirect success
    set_flash_message('success', 'Your application has been submitted successfully.');
    header('Location: ' . BASE_URL . 'jobs.php');
    exit;

} else {
    // Hard error fallback
    set_flash_message('error', 'A database error occurred saving your application. Please try again or contact support.');
    header('Location: ' . BASE_URL . 'jobs.php');
    exit;
}
?>