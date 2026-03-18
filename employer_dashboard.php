<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/auth_check.php';

enforce_role('Employer');

$employer_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'update_application_status') {
            $application_id = filter_input(INPUT_POST, 'application_id', FILTER_VALIDATE_INT);
            $next_status = $_POST['status'] ?? '';
            $allowed_statuses = ['Reviewed', 'Accepted', 'Rejected'];

            if (!$application_id || !in_array($next_status, $allowed_statuses, true)) {
                set_flash_message('error', 'Invalid applicant status update request.');
                header('Location: ' . BASE_URL . 'employer_dashboard.php');
                exit;
            }

            $stmt = $conn->prepare(
                "UPDATE applications a
                 JOIN jobs j ON a.job_id = j.job_id
                 SET a.status = ?
                 WHERE a.application_id = ? AND j.employer_id = ?"
            );
            $stmt->bind_param('sii', $next_status, $application_id, $employer_id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                set_flash_message('success', 'Applicant status updated successfully.');
            } else {
                set_flash_message('warning', 'No applicant record was updated. It may not belong to your jobs.');
            }

            $stmt->close();
        } elseif ($action === 'update_job_status') {
            $job_id = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);
            $job_status = $_POST['job_status'] ?? '';
            $allowed_job_statuses = ['Active', 'Closed'];

            if (!$job_id || !in_array($job_status, $allowed_job_statuses, true)) {
                set_flash_message('error', 'Invalid job status update request.');
                header('Location: ' . BASE_URL . 'employer_dashboard.php');
                exit;
            }

            $status_stmt = $conn->prepare('UPDATE jobs SET status = ? WHERE job_id = ? AND employer_id = ?');
            $status_stmt->bind_param('sii', $job_status, $job_id, $employer_id);
            $status_stmt->execute();

            if ($status_stmt->affected_rows > 0) {
                set_flash_message('success', 'Job status updated successfully.');
            } else {
                set_flash_message('warning', 'No job status was updated.');
            }

            $status_stmt->close();
        } else {
            set_flash_message('error', 'Invalid dashboard action.');
        }
    } catch (Throwable $e) {
        error_log('Employer dashboard update failed: ' . $e->getMessage());
        set_flash_message('error', 'Unable to process your request right now. Please try again.');
    }

    header('Location: ' . BASE_URL . 'employer_dashboard.php');
    exit;
}

$jobs = [];
$applications = [];
$total_jobs = 0;
$total_applications = 0;

try {
    $jobs_stmt = $conn->prepare(
        "SELECT j.job_id, j.title, j.location_type, j.status, j.posted_at,
                COUNT(a.application_id) AS application_count
         FROM jobs j
         LEFT JOIN applications a ON a.job_id = j.job_id
         WHERE j.employer_id = ?
         GROUP BY j.job_id
         ORDER BY j.posted_at DESC"
    );
    $jobs_stmt->bind_param('i', $employer_id);
    $jobs_stmt->execute();
    $jobs_result = $jobs_stmt->get_result();

    while ($row = $jobs_result->fetch_assoc()) {
        $jobs[] = $row;
        $total_applications += (int)$row['application_count'];
    }
    $total_jobs = count($jobs);
    $jobs_stmt->close();

    $apps_stmt = $conn->prepare(
        "SELECT a.application_id, a.status, a.submitted_at, a.cover_letter, a.parsed_resume_data,
                j.title AS job_title,
                u.username AS seeker_name,
                u.email AS seeker_email
         FROM applications a
         JOIN jobs j ON a.job_id = j.job_id
         JOIN users u ON a.seeker_id = u.user_id
         WHERE j.employer_id = ?
         ORDER BY a.submitted_at DESC"
    );
    $apps_stmt->bind_param('i', $employer_id);
    $apps_stmt->execute();
    $apps_result = $apps_stmt->get_result();

    while ($row = $apps_result->fetch_assoc()) {
        $applications[] = $row;
    }
    $apps_stmt->close();
} catch (Throwable $e) {
    error_log('Employer dashboard query failed: ' . $e->getMessage());
    set_flash_message('error', 'We could not fully load your employer dashboard data.');
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-10">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-text font-heading">Employer Dashboard</h1>
            <p class="text-muted mt-1">Post inclusive roles and manage incoming applications in one place.</p>
        </div>
        <a href="<?php echo BASE_URL; ?>post_job.php" class="w-full md:w-auto text-center bg-accent text-white px-4 py-2 min-w-[44px] rounded-lg font-semibold transition-all duration-300 ease-in-out hover:opacity-80 focus:outline-none focus:ring-2 focus:ring-accent/50">
            Post New Job
        </a>
    </div>

    <section class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8" aria-label="Employer summary">
        <article class="bg-surface border border-border rounded-xl p-5 shadow-sm">
            <p class="text-sm text-muted">Active and historical job posts</p>
            <p class="text-3xl font-bold text-text mt-1"><?php echo (int)$total_jobs; ?></p>
        </article>
        <article class="bg-surface border border-border rounded-xl p-5 shadow-sm">
            <p class="text-sm text-muted">Total applications received</p>
            <p class="text-3xl font-bold text-text mt-1"><?php echo (int)$total_applications; ?></p>
        </article>
    </section>

    <section class="mb-10" aria-labelledby="jobs-heading">
        <h2 id="jobs-heading" class="text-xl font-bold text-text mb-4">Your Job Listings</h2>
        <div class="bg-surface border border-border rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-bg">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-text">Title</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-text">Arrangement</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-text">Status</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-text">Applications</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-text">Posted</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <?php if (empty($jobs)): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-sm text-muted">No jobs posted yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($jobs as $job): ?>
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-text"><?php echo htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="px-4 py-3 text-sm text-text"><?php echo htmlspecialchars($job['location_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="px-4 py-3 text-sm text-text">
                                        <form action="<?php echo BASE_URL; ?>employer_dashboard.php" method="POST" class="flex gap-2 items-center">
                                            <input type="hidden" name="action" value="update_job_status">
                                            <input type="hidden" name="job_id" value="<?php echo (int)$job['job_id']; ?>">
                                            <label for="job-status-<?php echo (int)$job['job_id']; ?>" class="sr-only">Update job status</label>
                                            <select id="job-status-<?php echo (int)$job['job_id']; ?>" name="job_status" class="border border-border rounded-lg bg-surface text-text px-2 py-1 text-sm">
                                                <option value="Active" <?php echo $job['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="Closed" <?php echo $job['status'] === 'Closed' ? 'selected' : ''; ?>>Closed</option>
                                            </select>
                                            <button type="submit" class="bg-accent text-white px-3 py-1 rounded-lg text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-accent/50">Save</button>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-text"><?php echo (int)$job['application_count']; ?></td>
                                    <td class="px-4 py-3 text-sm text-text"><?php echo date('M d, Y', strtotime($job['posted_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section aria-labelledby="apps-heading">
        <h2 id="apps-heading" class="text-xl font-bold text-text mb-4">Recent Applicants</h2>
        <div class="space-y-4">
            <?php if (empty($applications)): ?>
                <div class="bg-surface border border-border rounded-xl p-6 text-sm text-muted">No applications received yet.</div>
            <?php else: ?>
                <?php foreach ($applications as $application): ?>
                    <article class="bg-surface border border-border rounded-xl p-5 shadow-sm">
                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                            <div>
                                <p class="text-sm text-muted"><?php echo htmlspecialchars($application['job_title'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <h3 class="text-lg font-semibold text-text"><?php echo htmlspecialchars($application['seeker_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p class="text-sm text-muted"><?php echo htmlspecialchars($application['seeker_email'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="text-sm text-muted mt-1">Submitted <?php echo date('M d, Y H:i', strtotime($application['submitted_at'])); ?></p>
                            </div>
                            <form action="<?php echo BASE_URL; ?>employer_dashboard.php" method="POST" class="w-full md:w-auto">
                                <input type="hidden" name="action" value="update_application_status">
                                <input type="hidden" name="application_id" value="<?php echo (int)$application['application_id']; ?>">
                                <label for="status-<?php echo (int)$application['application_id']; ?>" class="block text-sm font-medium text-text mb-1">Application Status</label>
                                <div class="flex gap-2">
                                    <select id="status-<?php echo (int)$application['application_id']; ?>" name="status" class="border border-border rounded-lg bg-surface text-text px-3 py-2 text-sm">
                                        <?php foreach (['Reviewed', 'Accepted', 'Rejected'] as $status): ?>
                                            <option value="<?php echo $status; ?>" <?php echo $application['status'] === $status ? 'selected' : ''; ?>><?php echo $status; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="bg-accent text-white px-4 py-2 rounded-lg text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-accent/50">Save</button>
                                </div>
                            </form>
                        </div>
                        <div class="mt-4 border-t border-border pt-4">
                            <p class="text-sm font-semibold text-text mb-1">Cover Letter</p>
                            <p class="text-sm text-text whitespace-pre-line"><?php echo htmlspecialchars($application['cover_letter'] ?? 'No cover letter provided.', ENT_QUOTES, 'UTF-8'); ?></p>

                            <?php
                                $parsedResume = [];
                                if (!empty($application['parsed_resume_data'])) {
                                    $decoded = json_decode($application['parsed_resume_data'], true);
                                    if (is_array($decoded)) {
                                        $parsedResume = $decoded;
                                    }
                                }
                            ?>

                            <?php if (!empty($parsedResume)): ?>
                                <details class="mt-4">
                                    <summary class="cursor-pointer text-sm font-semibold text-accent">View Parsed Resume Snapshot</summary>
                                    <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                        <div>
                                            <p class="text-muted">Parsed Email</p>
                                            <p class="text-text"><?php echo htmlspecialchars($parsedResume['email'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-muted">Parsed Phone</p>
                                            <p class="text-text"><?php echo htmlspecialchars($parsedResume['phone'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></p>
                                        </div>
                                        <div class="md:col-span-2">
                                            <p class="text-muted">Skills</p>
                                            <p class="text-text whitespace-pre-line"><?php echo htmlspecialchars($parsedResume['skills'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></p>
                                        </div>
                                        <div class="md:col-span-2">
                                            <p class="text-muted">Work Experience</p>
                                            <p class="text-text whitespace-pre-line"><?php echo htmlspecialchars($parsedResume['work_experience'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></p>
                                        </div>
                                    </div>
                                </details>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
