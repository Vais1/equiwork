<?php
// jobs.php
// EquiWork Job Board & Accommodation Matching Engine

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';
require_once 'includes/auth_check.php';

// Must be authenticated to view the job board
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user_role = $_SESSION['role'];

// 1. Process Active Filters
$raw_filters = $_GET['accommodations'] ?? [];
$active_filters = array_filter(array_map('intval', is_array($raw_filters) ? $raw_filters : []));

// 2. Fetch All Accommodations for the Sidebar
$sidebar_accommodations = [];
$cats_stmt = $conn->prepare("SELECT accommodation_id, name, category FROM accommodations ORDER BY category, name");
if ($cats_stmt && $cats_stmt->execute()) {
    $cats_res = $cats_stmt->get_result();
    while ($row = $cats_res->fetch_assoc()) {
        $sidebar_accommodations[$row['category']][] = $row;
    }
    $cats_stmt->close();
}

// 3. Construct the Matching Engine Query (Dynamic & Parameterized)
$sql = "SELECT j.job_id, j.title, j.description, j.location_type, j.posted_at, u.username AS employer_name 
        FROM jobs j 
        JOIN users u ON j.employer_id = u.user_id 
        WHERE j.status = 'Active'";

$params = [];
$types = "";

if (!empty($active_filters)) {
    // The core matcher: Ensures the job has ALL selected accommodations
    $placeholders = implode(',', array_fill(0, count($active_filters), '?'));
    $sql .= " AND j.job_id IN (
                SELECT job_id 
                FROM job_accommodations 
                WHERE accommodation_id IN ($placeholders) 
                GROUP BY job_id 
                HAVING COUNT(DISTINCT accommodation_id) = ?
              )";
    
    // Bind the placeholder values
    foreach ($active_filters as $filter_id) {
        $params[] = $filter_id;
        $types .= "i";
    }
    // Bind the COUNT requirement
    $params[] = count($active_filters);
    $types .= "i";
}

$sql .= " ORDER BY j.posted_at DESC";

// Execute Job Fetch
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

// 4. Fetch Accommodations Specifically for the Rendered Jobs (to display as tags in the UI)
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

require_once 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <div class="mb-8 border-b border-gray-200 dark:border-gray-700 pb-5">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Accessible Job Board</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Discover employment opportunities tailored to your physical and communicative requirements.</p>
    </div>

    <!-- Application Feedback Messages removed: Now handled globally by flash messages in header.php -->

    <div class="flex flex-col lg:flex-row gap-8">
        
        <!-- Sidebar Filter System -->
        <aside class="w-full lg:w-1/4">
            <form action="<?php echo BASE_URL; ?>jobs.php" method="GET" class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 sticky top-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Filter by Accessibility</h2>
                
                <div class="space-y-6">
                    <?php if (empty($sidebar_accommodations)): ?>
                        <p class="text-sm text-gray-500 dark:text-gray-400">No filters available at the moment.</p>
                    <?php else: ?>
                        <?php foreach ($sidebar_accommodations as $category => $items): ?>
                            <fieldset>
                                <legend class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-3 pb-1 border-b border-gray-100 dark:border-gray-700">
                                    <?php echo htmlspecialchars($category, ENT_QUOTES); ?>
                                </legend>
                                <div class="space-y-2">
                                    <?php foreach ($items as $item): ?>
                                        <?php 
                                            // Maintain state if checked
                                            $isChecked = in_array($item['accommodation_id'], $active_filters) ? 'checked' : ''; 
                                        ?>
                                        <div class="flex items-center">
                                            <input type="checkbox" 
                                                   id="acc_<?php echo $item['accommodation_id']; ?>" 
                                                   name="accommodations[]" 
                                                   value="<?php echo $item['accommodation_id']; ?>"
                                                   <?php echo $isChecked; ?>
                                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                            <label for="acc_<?php echo $item['accommodation_id']; ?>" class="ml-2 text-sm text-gray-700 dark:text-gray-300 select-none cursor-pointer">
                                                <?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="mt-8 pt-4 border-t border-gray-100 dark:border-gray-700 space-y-3">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 text-white font-semibold px-4 py-2 rounded-lg transition-colors">
                        Apply Filters
                    </button>
                    <!-- Provide clear option to remove state -->
                    <?php if (!empty($active_filters)): ?>
                        <a href="<?php echo BASE_URL; ?>jobs.php" class="block w-full text-center text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none focus:underline">
                            Clear all filters
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </aside>

        <!-- Main Job Board Results -->
        <main class="w-full lg:w-3/4">
            
            <div class="mb-4 text-sm text-gray-600 dark:text-gray-400" aria-live="polite">
                Showing <span class="font-semibold text-gray-900 dark:text-white"><?php echo count($jobs); ?></span> opportunity(s) matching your criteria.
            </div>

            <div class="space-y-6">
                <?php if (count($jobs) === 0): ?>
                    <div class="bg-gray-50 dark:bg-gray-800/50 p-10 text-center rounded-xl border border-dashed border-gray-300 dark:border-gray-600">
                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">No jobs found</h3>
                        <p class="mt-1 text-gray-500 dark:text-gray-400">Try removing some accommodation filters to see more results.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($jobs as $job): ?>
                        <article class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col md:flex-row md:items-start md:justify-between transition-transform hover:-translate-y-1 hover:shadow-md focus-within:ring-4 focus-within:ring-blue-300">
                            
                            <div class="flex-grow">
                                <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
                                    <span class="font-medium text-blue-600 dark:text-blue-400"><?php echo htmlspecialchars($job['employer_name'], ENT_QUOTES); ?></span>
                                    <span>&bull;</span>
                                    <span><?php echo date('M d, Y', strtotime($job['posted_at'])); ?></span>
                                    <span>&bull;</span>
                                    <span class="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-xs font-semibold">
                                        <?php echo htmlspecialchars($job['location_type'], ENT_QUOTES); ?>
                                    </span>
                                </div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 leading-tight">
                                    <?php echo htmlspecialchars($job['title'], ENT_QUOTES); ?>
                                </h2>
                                
                                <p class="text-gray-700 dark:text-gray-300 mb-4 line-clamp-3">
                                    <?php echo nl2br(htmlspecialchars($job['description'], ENT_QUOTES)); ?>
                                </p>

                                <div class="flex flex-wrap gap-2 mt-auto" aria-label="Provided Accommodations">
                                    <?php foreach ($job['accommodations'] as $tag): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                            <?php echo htmlspecialchars($tag, ENT_QUOTES); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Application Action Logic -->
                            <div class="mt-6 md:mt-0 md:ml-6 flex shrink-0">
                                <?php if ($user_role === 'Seeker'): ?>
                                    <a href="<?php echo BASE_URL; ?>apply_job.php?job_id=<?php echo $job['job_id']; ?>" class="w-full md:w-auto text-center bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 text-white font-semibold px-6 py-2.5 rounded-lg transition-colors">
                                        Apply Now
                                    </a>
                                <?php elseif ($user_role === 'Employer'): ?>
                                    <span class="w-full md:w-auto text-center bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-medium px-6 py-2.5 rounded-lg cursor-not-allowed" title="Employers cannot apply to jobs">
                                        Employer View
                                    </span>
                                <?php else: ?>
                                    <span class="w-full md:w-auto text-center bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-medium px-6 py-2.5 rounded-lg cursor-not-allowed">
                                        Admin View
                                    </span>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
        </main>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
