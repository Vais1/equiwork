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
    header('Location: ' . BASE_URL . 'login.php');
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

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 pb-8 md:pt-6 md:pb-16">
    
    <div class="mb-6 lg:mb-8 border-b border-border pb-3 mt-2">
        <h1 class="text-3xl md:text-4xl font-bold text-text tracking-tight leading-tight font-heading">Job Board</h1>
    </div>

    <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
        
        <!-- Sidebar Filter System -->
        <aside class="w-full lg:w-1/4">
            <form action="<?php echo BASE_URL; ?>jobs.php" method="GET" class="bg-surface border border-border rounded-xl shadow-sm p-6 transition-all duration-300 ease-in-out sticky top-6">
                
                <div class="mb-6">
                    <label for="search" class="block text-sm font-semibold text-text mb-2">Search Jobs</label>
                    <div class="relative">
                        <input type="text" id="search" name="q" value="<?php echo htmlspecialchars($search_query, ENT_QUOTES); ?>" placeholder="Job title, company..." class="w-full border border-border rounded-lg pl-9 pr-4 py-2 text-sm focus:ring-2 focus:ring-accent focus:border-transparent bg-surface text-text transition-all duration-200">
                        <svg class="w-4 h-4 text-muted absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>
                
                <h2 class="text-lg font-bold text-text mb-4 border-t border-border pt-4">Filter by Accessibility</h2>
                
                <div class="space-y-6">
                    <?php if (empty($sidebar_accommodations)): ?>
                        <p class="text-sm text-muted">No filters available at the moment.</p>
                    <?php else: ?>
                        <?php foreach ($sidebar_accommodations as $category => $items): ?>
                            <fieldset>
                                <legend class="text-sm font-semibold text-text uppercase tracking-wider mb-3 pb-1 border-b border-border">
                                    <?php echo htmlspecialchars($category, ENT_QUOTES); ?>
                                </legend>
                                <div class="space-y-2">
                                    <?php foreach ($items as $item): ?>
                                        <?php 
                                            // Maintain state if checked
                                            $isChecked = in_array($item['accommodation_id'], $active_filters); 
                                        ?>
                                        <div class="flex items-start custom-checkbox-container cursor-pointer focus:outline-none focus:ring-4 focus:ring-accent/50 rounded" role="checkbox" aria-label="Filter by <?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>" aria-checked="<?php echo $isChecked ? 'true' : 'false'; ?>" tabindex="0">
                                            <input type="hidden" name="accommodations[]" value="<?php echo $item['accommodation_id']; ?>" <?php echo $isChecked ? '' : 'disabled'; ?>>
                                            <div class="checkbox-box w-5 h-5 flex-shrink-0 border border-border bg-surface rounded flex items-center justify-center transition-all duration-200 pointer-events-none mt-0.5">
                                                <svg aria-hidden="true" class="w-3 h-3 text-white hidden pointer-events-none" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                                            </div>
                                            <span class="ml-2 text-sm text-text pointer-events-none select-none">
                                                <?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="mt-6 md:mt-8 pt-4 border-t border-border space-y-3">
                    <button type="submit" class="w-full bg-accent text-white px-4 py-2 min-w-[44px] rounded-lg font-semibold active:scale-95 transition-all duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-accent/50">
                        Apply Filters
                    </button>
                    <!-- Provide clear option to remove state -->
                    <?php if (!empty($active_filters)): ?>
                        <a href="<?php echo BASE_URL; ?>jobs.php" class="block w-full text-center text-sm text-muted focus:outline-none focus:underline transition-all duration-200 duration-300">
                            Clear all filters
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </aside>

        <!-- Main Job Board Results -->
        <main class="w-full lg:w-3/4">
            
            <div class="mb-4 text-sm text-muted" aria-live="polite">
                Showing <span class="font-semibold text-text"><?php echo count($jobs); ?></span> of <span class="font-semibold text-text"><?php echo $total_jobs; ?></span> opportunity(s) matching your criteria.
            </div>

            <div class="space-y-6">
                <?php if (count($jobs) === 0): ?>
                    <div class="bg-bg p-6 md:p-10 text-center rounded-xl border border-dashed border-border">
                        <svg class="mx-auto h-12 w-12 text-muted mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-lg font-medium text-text">No jobs found</h3>
                        <p class="mt-1 text-muted">Try removing some accommodation filters to see more results.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($jobs as $job): ?>
                        <article class="bg-surface border border-border rounded-xl shadow-sm p-6 transition-all duration-300 ease-in-out flex flex-col md:flex-row md:items-start md:justify-between focus-within:ring-4 focus-within:ring-blue-300">
                            
                            <div class="flex-grow">
                                <div class="flex items-center space-x-2 text-sm text-muted mb-2">
                                    <span class="font-medium text-accent"><?php echo htmlspecialchars($job['company_name'] ?: $job['employer_name'], ENT_QUOTES); ?></span>
                                    <span>&bull;</span>
                                    <span><?php echo date('M d, Y', strtotime($job['posted_at'])); ?></span>
                                    <span>&bull;</span>
                                    <span class="bg-surface px-2 py-0.5 rounded text-xs font-semibold">
                                        <?php echo htmlspecialchars($job['location_type'], ENT_QUOTES); ?>
                                    </span>
                                    <?php if ($job['state_region']): ?>
                                        <span>&bull;</span>
                                        <span><?php echo htmlspecialchars($job['state_region'], ENT_QUOTES); ?></span>
                                    <?php endif; ?>
                                    <span>&bull;</span>
                                    <span class="bg-surface px-2 py-0.5 rounded text-xs font-semibold">
                                        <?php echo htmlspecialchars($job['employment_type'] ?? 'Full-time', ENT_QUOTES); ?>
                                    </span>
                                    <?php if ($job['salary_min_myr'] || $job['salary_max_myr']): ?>
                                        <span>&bull;</span>
                                        <span class="text-green-600 font-semibold">
                                            MYR <?php echo number_format($job['salary_min_myr'] ?? 0); ?> - <?php echo number_format($job['salary_max_myr'] ?? 0); ?>
                                        </span>
                                    <?php endif; ?>

                                </div>
                                <h2 class="text-xl font-bold text-text mb-3 leading-tight">
                                    <?php echo htmlspecialchars($job['title'], ENT_QUOTES); ?>
                                </h2>
                                
                                <p class="text-text mb-4 line-clamp-3">
                                    <?php echo nl2br(htmlspecialchars($job['description'], ENT_QUOTES)); ?>
                                </p>

                                <div class="flex flex-wrap gap-2 mt-auto" aria-label="Provided Accommodations">
                                    <?php foreach ($job['accommodations'] as $tag): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-accent/10 text-accent">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                            <?php echo htmlspecialchars($tag, ENT_QUOTES); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Application Action Logic -->
                            <div class="mt-5 md:mt-0 md:ml-5 flex shrink-0">
                                <?php if ($user_role === 'Seeker'): ?>
                                    <a href="<?php echo BASE_URL; ?>apply_job.php?job_id=<?php echo $job['job_id']; ?>" class="w-full md:w-auto text-center bg-accent focus:ring-4 focus:ring-accent/50 text-white font-semibold px-4 py-2 min-w-[44px] rounded-lg transition-all duration-300 ease-in-out">
                                        Apply Now
                                    </a>
                                <?php elseif ($user_role === 'Employer'): ?>
                                    <span class="w-full md:w-auto text-center bg-gray-200 text-muted font-medium px-4 py-2 min-w-[44px] rounded-lg cursor-not-allowed" title="Employers cannot apply to jobs">
                                        Employer View
                                    </span>
                                <?php else: ?>
                                    <span class="w-full md:w-auto text-center bg-gray-200 text-muted font-medium px-4 py-2 min-w-[44px] rounded-lg cursor-not-allowed">
                                        Admin View
                                    </span>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            
            </div>
            
            <!-- Pagination Controls -->
            <?php if ($total_pages > 1): ?>
                <div class="mt-8 flex justify-center border-t border-border pt-6">
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php 
                        // Keep current GET params except 'page'
                        $query_params = $_GET;
                        unset($query_params['page']);
                        $base_link = BASE_URL . 'jobs.php?' . http_build_query($query_params);
                        $base_link .= (!empty($query_params) ? '&' : '') . 'page=';
                        ?>
                        
                        <?php if ($page > 1): ?>
                            <a href="<?php echo $base_link . ($page - 1); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-border bg-surface text-sm font-medium text-muted focus:ring-2 focus:ring-accent transition-all duration-200">
                                <span class="sr-only">Previous</span>
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="<?php echo $base_link . $i; ?>" class="relative inline-flex items-center px-4 py-2 border border-border text-sm font-medium <?php echo $i === $page ? 'z-10 bg-accent text-white border-accent' : 'bg-surface text-text hover:bg-bg'; ?> transition-all duration-200">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="<?php echo $base_link . ($page + 1); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-border bg-surface text-sm font-medium text-muted focus:ring-2 focus:ring-accent transition-all duration-200">
                                <span class="sr-only">Next</span>
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
            
        </main>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

