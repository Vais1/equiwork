<?php
// my_applications.php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Ensure the user is a Seeker
enforce_role('Seeker');

$seeker_id = $_SESSION['user_id'];

// Fetch applications for this user
$stmt = $conn->prepare("
    SELECT a.application_id, a.status, a.submitted_at, 
           j.title, j.company_name, j.location_type, u.username as employer_name
    FROM applications a
    JOIN jobs j ON a.job_id = j.job_id
    JOIN users u ON j.employer_id = u.user_id
    WHERE a.seeker_id = ?
    ORDER BY a.submitted_at DESC
");
$stmt->bind_param("i", $seeker_id);
$stmt->execute();
$result = $stmt->get_result();

$applications = [];
while ($row = $result->fetch_assoc()) {
    $applications[] = $row;
}
$stmt->close();

require_once '../includes/header.php';
?>

<div class="container-main">
    <div class="mb-8">
        <h1 class="heading-1 mb-2">My Applications</h1>
        <p class="text-small">Track the status of roles you have applied for.</p>
    </div>

    <?php if (empty($applications)): ?>
        <div class="card flex flex-col items-center justify-center text-center py-16">
            <svg class="h-10 w-10 text-muted mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h2 class="heading-3 mb-2">No applications yet</h2>
            <p class="text-small mb-6 max-w-md">You haven't applied to any roles. Browse the job board to find your next opportunity.</p>
            <a href="<?php echo BASE_URL; ?>jobs.php" class="btn-primary">
                Browse Jobs
            </a>
        </div>
    <?php else: ?>
        <div class="bg-surface border border-border rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-bg text-muted text-xs uppercase tracking-wider border-b border-border">
                            <th class="px-5 py-3 font-medium">Role</th>
                            <th class="px-5 py-3 font-medium">Employer</th>
                            <th class="px-5 py-3 font-medium">Location Type</th>
                            <th class="px-5 py-3 font-medium">Date Applied</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border text-sm">
                        <?php foreach ($applications as $app): ?>
                            <tr class="transition-colors duration-200">
                                <td class="px-5 py-4 font-medium text-text">
                                    <?php echo htmlspecialchars($app['title'], ENT_QUOTES); ?>
                                </td>
                                <td class="px-5 py-4 text-muted">
                                    <?php echo htmlspecialchars($app['company_name'] ?: $app['employer_name'], ENT_QUOTES); ?>
                                </td>
                                <td class="px-5 py-4 text-muted">
                                    <?php echo htmlspecialchars($app['location_type'], ENT_QUOTES); ?>
                                </td>
                                <td class="px-5 py-4 text-muted whitespace-nowrap">
                                    <?php echo date('M d, Y', strtotime($app['submitted_at'])); ?>
                                </td>
                                <td class="px-5 py-4">
                                    <?php 
                                        $statusClass = 'bg-border/50 text-text';
                                        if ($app['status'] === 'Accepted') $statusClass = 'bg-green-100 text-green-800 border border-green-200 dark:bg-green-900/30 dark:text-green-400';
                                        if ($app['status'] === 'Rejected') $statusClass = 'bg-red-100 text-red-800 border border-red-200 dark:bg-red-900/30 dark:text-red-400';
                                        if ($app['status'] === 'Reviewed') $statusClass = 'bg-blue-100 text-blue-800 border border-blue-200 dark:bg-blue-900/30 dark:text-blue-400';
                                    ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-[11px] font-medium <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($app['status'], ENT_QUOTES); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
