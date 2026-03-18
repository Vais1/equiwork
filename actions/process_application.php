<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/auth_check.php';

enforce_role('Seeker');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'jobs.php');
    exit;
}

$seeker_id = (int)$_SESSION['user_id'];
$job_id = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);
$letter = trim($_POST['cover_letter'] ?? '');
$parsed_resume_payload = trim($_POST['parsed_resume_data'] ?? '');

$parsed_resume_json = null;
if ($parsed_resume_payload !== '') {
    $parsed_resume = json_decode($parsed_resume_payload, true);
    if (is_array($parsed_resume)) {
        $parsed_resume_json = json_encode($parsed_resume, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

$letter_safe = htmlspecialchars($letter, ENT_QUOTES, 'UTF-8');
if (!$job_id || $letter_safe === '' || strlen($letter_safe) > 1000 || $parsed_resume_json === null) {
    set_flash_message('error', 'Invalid application parameters provided. Please complete all required fields.');
    header('Location: ' . BASE_URL . 'jobs.php');
    exit;
}

try {
    $conn->begin_transaction();

    $job_check = $conn->prepare("SELECT job_id FROM jobs WHERE job_id = ? AND status = 'Active' LIMIT 1");
    $job_check->bind_param('i', $job_id);
    $job_check->execute();

    if ($job_check->get_result()->num_rows === 0) {
        $job_check->close();
        $conn->rollback();
        set_flash_message('error', 'This job is no longer accepting applications.');
        header('Location: ' . BASE_URL . 'jobs.php');
        exit;
    }
    $job_check->close();

    $dup_check = $conn->prepare("SELECT application_id FROM applications WHERE job_id = ? AND seeker_id = ? LIMIT 1");
    $dup_check->bind_param('ii', $job_id, $seeker_id);
    $dup_check->execute();

    if ($dup_check->get_result()->num_rows > 0) {
        $dup_check->close();
        $conn->rollback();
        set_flash_message('warning', 'You have already applied for this job.');
        header('Location: ' . BASE_URL . 'jobs.php');
        exit;
    }
    $dup_check->close();

    $status = 'Pending';
    $insert = $conn->prepare(
        'INSERT INTO applications (job_id, seeker_id, status, cover_letter, parsed_resume_data) VALUES (?, ?, ?, ?, ?)'
    );
    $insert->bind_param('iisss', $job_id, $seeker_id, $status, $letter_safe, $parsed_resume_json);
    $insert->execute();
    $insert->close();

    $conn->commit();

    $notify_query = $conn->prepare(
        'SELECT j.title, u_emp.email AS employer_email, u_seek.email AS seeker_email, u_seek.username AS seeker_name
         FROM jobs j
         JOIN users u_emp ON j.employer_id = u_emp.user_id
         JOIN users u_seek ON u_seek.user_id = ?
         WHERE j.job_id = ?'
    );
    $notify_query->bind_param('ii', $seeker_id, $job_id);
    $notify_query->execute();
    $notify = $notify_query->get_result()->fetch_assoc();

    if ($notify) {
        $job_title = htmlspecialchars($notify['title'] ?? 'Job Posting', ENT_QUOTES, 'UTF-8');
        $seeker_name = htmlspecialchars($notify['seeker_name'] ?? 'Applicant', ENT_QUOTES, 'UTF-8');

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: EquiWork Notifications <noreply@equiwork.local>\r\n";
        $headers .= "Reply-To: noreply@equiwork.local\r\n";
        $headers .= 'X-Mailer: PHP/' . phpversion();

        $subject_emp = 'New Application Alert: ' . $job_title;
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
        </body>
        </html>
        ";

        $subject_seek = 'Application Received: ' . $job_title;
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
          <p>Thank you for using EquiWork.</p>
        </body>
        </html>
        ";

        @mail($notify['employer_email'], $subject_emp, $body_emp, $headers);
        @mail($notify['seeker_email'], $subject_seek, $body_seek, $headers);
    }

    $notify_query->close();

    set_flash_message('success', 'Your application has been submitted successfully.');
    header('Location: ' . BASE_URL . 'jobs.php');
    exit;
} catch (Throwable $e) {
    $conn->rollback();
    error_log('Application submission failed: ' . $e->getMessage());
    set_flash_message('error', 'A system error occurred while saving your application. Please try again.');
    header('Location: ' . BASE_URL . 'jobs.php');
    exit;
}
