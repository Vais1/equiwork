<?php
/**
 * jobs.php
 * 
 * EquiWork Job Board & Accommodation Matching Engine
 * 
 * This module dynamically matches users with jobs based on a set of selected 
 * accessibility accommodations. It adheres to strict separation of concerns, 
 * keeping PHP data retrieval logic distinct from HTML rendering.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_check.php';

// Ensure the user is authenticated; role check is basic, but we redirect unauthenticated users
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

$user_role = $_SESSION['role'];

// ------------------------------------------------------------------
// 1. Process Active Filters
// ------------------------------------------------------------------
// Securely cast incoming filters to integers to prevent SQL injection or manipulation
$raw_filters = $_GET['accommodations'] ?? [];
$active_filters = array_filter(array_map('intval', is_array($raw_filters) ? $raw_filters : []));

// ------------------------------------------------------------------
// 2. Fetch All Accommodations for the Sidebar
// ------------------------------------------------------------------
// Pre-load all available accommodations to render the interactive filter UI.
$sidebar_accommodations = [];
$cats_stmt = $conn->prepare("SELECT accommodation_id, name, category FROM accommodations ORDER BY category, name");
if ($cats_stmt && $cats_stmt->execute()) {
    $cats_res = $cats_stmt->get_result();
    while ($row = $cats_res->fetch_assoc()) {
        $sidebar_accommodations[$row['category']][] = $row;
    }
    $cats_stmt->close();
}

// ------------------------------------------------------------------
// 3. Construct the Matching Engine Query (Dynamic & Parameterized)
// ------------------------------------------------------------------
// Base query selects active jobs and resolves the employer's username.
$sql = "SELECT j.job_id, j.title, j.description, j.location_type, j.posted_at, j.company_name, j.employment_type, j.salary_min_myr, j.salary_max_myr, j.state_region, u.username AS employer_name 
        FROM jobs j 
        JOIN users u ON j.employer_id = u.user_id 
        WHERE j.status = 'Active'";

$params = [];
$types = "";

if (!empty($active_filters)) {
    /**
     * The Core Matcher: Ensures the job has ALL selected accommodations.
     * We use an IN clause with a GROUP BY and HAVING COUNT(DISTINCT) check.
     * This acts as a logical AND, meaning the job must strictly possess
     * every single accessibility feature requested by the user.
     */
    $placeholders = implode(',', array_fill(0, count($active_filters), '?'));
    $sql .= " AND j.job_id IN (
                SELECT job_id 
                FROM job_accommodations 
                WHERE accommodation_id IN ($placeholders) 
                GROUP BY job_id 
                HAVING COUNT(DISTINCT accommodation_id) = ?
              )";
    
    // Bind the placeholder values dynamically
    foreach ($active_filters as $filter_id) {
        $params[] = $filter_id;
        $types .= "i";
    }
    // Bind the total filter count for the HAVING clause check
    $params[] = count($active_filters);
    $types .= "i";
}

$sql .= " ORDER BY j.posted_at DESC";

// Execute Job Fetch safely using prepared statements
$stmt = $conn->prepare($sql);
if (!empty($types) && !empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$job_results = $stmt->get_result();
$total_jobs = $job_results->num_rows; // Calculate total_jobs

$jobs = [];
$job_ids = [];
while ($row = $job_results->fetch_assoc()) {
    $jobs[$row['job_id']] = $row;
    $jobs[$row['job_id']]['accommodations'] = []; // initialize array to hold tags
    $job_ids[] = $row['job_id'];
}
$stmt->close();

// ------------------------------------------------------------------
// 4. Fetch Accommodations Specifically for the Rendered Jobs
// ------------------------------------------------------------------
// Instead of running a query for each job (N+1 bottleneck), we fetch 
// all related accommodations in one bulk query using an IN clause.
if (!empty($job_ids)) {
    $in_clause = implode(',', array_fill(0, count($job_ids), '?'));
    $acc_sql = "SELECT ja.job_id, a.name 
                FROM job_accommodations ja 
                JOIN accommodations a ON ja.accommodation_id = a.accommodation_id 
                WHERE ja.job_id IN ($in_clause)";
    
    $acc_stmt = $conn->prepare($acc_sql);
    $acc_stmt->bind_param(str_repeat('i', count($job_ids)), ...$job_ids);
    $acc_stmt->execute();
    $acc_res = $acc_stmt->get_result();
    
    while ($tag_row = $acc_res->fetch_assoc()) {
        $jobs[$tag_row['job_id']]['accommodations'][] = $tag_row['name'];
    }
    $acc_stmt->close();
}

// Render HTML layout
require_once 'includes/header.php';
?>

<div class="container-main max-w-7xl">
    
    <div class="mb-8 border-b border-border pb-4">
        <h1 class="heading-1">Job Board</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        
        <!-- Sidebar Filter System -->
        <aside class="lg:col-span-1 self-start sticky top-24">
            <form action="<?php echo BASE_URL; ?>jobs.php" method="GET" class="card">
                
                <div class="mb-6">
                    <label for="search" class="form-label mb-2">Search Jobs</label>
                    <div class="relative">
                        <input type="text" id="search" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES); ?>" placeholder="Job title, company..." class="form-input pl-9">
                        <svg class="w-4 h-4 text-muted absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>
                
                <h2 class="text-sm font-semibold text-text mb-4 border-t border-border pt-4 uppercase tracking-wider">Accessibility</h2>
                
                <div class="space-y-5">
                    <?php if (empty($sidebar_accommodations)): ?>
                        <p class="text-small">No filters available.</p>
                    <?php else: ?>
                        <?php foreach ($sidebar_accommodations as $category => $items): ?>
                            <fieldset>
                                <legend class="text-xs font-semibold text-muted uppercase tracking-wider mb-2">
                                    <?php echo htmlspecialchars($category, ENT_QUOTES); ?>
                                </legend>
                                <div class="space-y-1.5">
                                    <?php foreach ($items as $item): ?>
                                        <?php 
                                            $isChecked = in_array($item['accommodation_id'], $active_filters); 
                                        ?>
                                        <div class="flex items-start custom-checkbox-container cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-accent rounded-sm" role="checkbox" aria-label="Filter by <?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>" aria-checked="<?php echo $isChecked ? 'true' : 'false'; ?>" tabindex="0">
                                            <input type="hidden" name="accommodations[]" value="<?php echo $item['accommodation_id']; ?>" <?php echo $isChecked ? '' : 'disabled'; ?>>
                                            <div class="checkbox-box w-4 h-4 flex-shrink-0 border border-border bg-surface rounded-sm flex items-center justify-center transition-all duration-200 pointer-events-none mt-0.5">
                                                <svg aria-hidden="true" class="w-2.5 h-2.5 text-bg hidden pointer-events-none" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                                            </div>
                                            <span class="ml-2 text-sm text-text pointer-events-none select-none leading-tight">
                                                <?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="mt-6 pt-4 border-t border-border space-y-3">
                    <button type="submit" class="w-full btn-primary py-2">
                        Apply Filters
                    </button>
                    <?php if (!empty($active_filters)): ?>
                        <a href="<?php echo BASE_URL; ?>jobs.php" class="block w-full text-center text-sm text-muted focus:outline-none focus-visible:ring-2 focus-visible:ring-accent rounded-sm transition-colors decoration-border underline underline-offset-4">
                            Clear filters
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </aside>

        <!-- Main Job Board Results -->
        <main class="lg:col-span-3">
            
            <div class="mb-4 text-small" aria-live="polite">
                Showing <span class="font-medium text-text"><?php echo count($jobs); ?></span> of <span class="font-medium text-text"><?php echo $total_jobs ?? count($jobs); ?></span> opportunities.
            </div>

            <div class="space-y-4">
                <?php if (count($jobs) === 0): ?>
                    <div class="bg-surface p-10 text-center rounded-lg border border-dashed border-border shadow-sm">
                        <svg class="mx-auto h-10 w-10 text-muted mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-base font-medium text-text">No jobs found</h3>
                        <p class="mt-1 text-sm text-muted">Try adjusting your search or filters.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($jobs as $job): ?>
                        <article class="card flex flex-col md:flex-row md:items-start md:justify-between gap-5">
                            
                            <div class="flex-grow">
                                <div class="flex flex-wrap items-center gap-2 text-xs text-muted mb-3">
                                    <span class="font-medium text-text"><?php echo htmlspecialchars($job['company_name'] ?: $job['employer_name'], ENT_QUOTES); ?></span>
                                    <span class="text-border">|</span>
                                    <span><?php echo date('M d, Y', strtotime($job['posted_at'])); ?></span>
                                    <span class="text-border">|</span>
                                    <span class="badge">
                                        <?php echo htmlspecialchars($job['location_type'], ENT_QUOTES); ?>
                                    </span>
                                    <?php if ($job['state_region']): ?>
                                        <span class="text-border">|</span>
                                        <span><?php echo htmlspecialchars($job['state_region'], ENT_QUOTES); ?></span>
                                    <?php endif; ?>
                                    <span class="text-border">|</span>
                                    <span class="badge">
                                        <?php echo htmlspecialchars($job['employment_type'] ?? 'Full-time', ENT_QUOTES); ?>
                                    </span>
                                    <?php if ($job['salary_min_myr'] || $job['salary_max_myr']): ?>
                                        <span class="text-border">|</span>
                                        <span class="font-medium text-text">
                                            RM <?php echo number_format($job['salary_min_myr'] ?? 0); ?> - <?php echo number_format($job['salary_max_myr'] ?? 0); ?>
                                        </span>
                                    <?php endif; ?>

                                </div>
                                <h2 class="heading-2 mb-2">
                                    <?php echo htmlspecialchars($job['title'], ENT_QUOTES); ?>
                                </h2>
                                
                                <p class="text-body text-sm mb-4 line-clamp-2">
                                    <?php echo nl2br(htmlspecialchars($job['description'], ENT_QUOTES)); ?>
                                </p>

                                <div class="flex flex-wrap gap-1.5 mt-auto" aria-label="Provided Accommodations">
                                    <?php foreach ($job['accommodations'] as $tag): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-[11px] font-medium bg-border/40 text-text border border-border/50">
                                            <svg class="w-3 h-3 mr-1 text-muted" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                            <?php echo htmlspecialchars($tag, ENT_QUOTES); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="shrink-0 pt-1">
                                <?php if ($user_role === 'Seeker'): ?>
                                    <a href="<?php echo BASE_URL; ?>seeker/apply_job.php?job_id=<?php echo $job['job_id']; ?>" class="w-full md:w-auto btn-primary">
                                        Apply Now
                                    </a>
                                <?php elseif ($user_role === 'Employer'): ?>
                                    <span class="w-full md:w-auto btn-outline opacity-50 cursor-not-allowed" title="Employers cannot apply to jobs">
                                        Employer View
                                    </span>
                                <?php else: ?>
                                    <span class="w-full md:w-auto btn-outline opacity-50 cursor-not-allowed">
                                        Admin View
                                    </span>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            
            </div>
            
            <!-- Pagination Controls (Left intact if used in the future, simplified for now) -->
        </main>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

