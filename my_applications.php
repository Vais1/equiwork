<?php
// my_applications.php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth_check.php';

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

require_once 'includes/header.php';
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-text mb-2 font-heading">My Applications</h1>
        <p class="text-muted">Track the status of roles you have applied for.</p>
    </div>

    <?php if (empty($applications)): ?>
        <div class="bg-surface border border-dashed border-border rounded-xl p-10 text-center">
            <svg class="mx-auto h-12 w-12 text-muted mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h2 class="text-lg font-medium text-text mb-2">No applications yet</h2>
            <p class="text-muted mb-6">You haven't applied to any roles. Browse the job board to find your next opportunity.</p>
            <a href="<?php echo BASE_URL; ?>jobs.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-accent focus:outline-none focus:ring-2 focus:ring-accent/50 active:scale-95 transition-all duration-200">
                Browse Jobs
            </a>
        </div>
    <?php else: ?>
        <div class="bg-surface border border-border rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-bg text-text text-sm uppercase tracking-wider border-b border-border">
                            <th class="px-6 py-4 font-semibold">Role</th>
                            <th class="px-6 py-4 font-semibold">Employer</th>
                            <th class="px-6 py-4 font-semibold">Location Type</th>
                            <th class="px-6 py-4 font-semibold">Date Applied</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border text-sm">
                        <?php foreach ($applications as $app): ?>
                            <tr class="hover:bg-bg/50 transition-colors duration-200">
                                <td class="px-6 py-4 font-medium text-text">
                                    <?php echo htmlspecialchars($app['title'], ENT_QUOTES); ?>
                                </td>
                                <td class="px-6 py-4 text-muted">
                                    <?php echo htmlspecialchars($app['company_name'] ?: $app['employer_name'], ENT_QUOTES); ?>
                                </td>
                                <td class="px-6 py-4 text-muted">
                                    <?php echo htmlspecialchars($app['location_type'], ENT_QUOTES); ?>
                                </td>
                                <td class="px-6 py-4 text-muted whitespace-nowrap">
                                    <?php echo date('M d, Y', strtotime($app['submitted_at'])); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php 
                                        $statusClass = 'bg-gray-100 text-gray-800 border-gray-200';
                                        if ($app['status'] === 'Accepted') $statusClass = 'bg-green-100 text-green-800 border-green-200';
                                        if ($app['status'] === 'Rejected') $statusClass = 'bg-red-100 text-red-800 border-red-200';
                                        if ($app['status'] === 'Reviewed') $statusClass = 'bg-blue-100 text-blue-800 border-blue-200';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border <?php echo $statusClass; ?>">
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

<?php require_once 'includes/footer.php'; ?>
