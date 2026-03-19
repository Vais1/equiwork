<?php
// Ensure session starts before ANY output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db.php';
require_once '../includes/flash.php';
require_once '../includes/auth_check.php';
require_once '../includes/csrf.php';

// Enforce Role: Admin only
enforce_role('Admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate_request()) {
        set_flash_message('error', 'Invalid request token. Please refresh and try again.');
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
        exit;
    }
    $action = $_POST['action'] ?? '';

    try {

    // -- DELETE USER --
    if ($action === 'delete') {
        $delete_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        
        // Prevent deleting oneself
        if ($delete_id === $_SESSION['user_id']) {
            set_flash_message('error', "You cannot delete your own admin account.");
        } else if ($delete_id) {
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $delete_id);
            if ($stmt->execute()) {
                set_flash_message('success', "User successfully deleted.");
            } else {
                set_flash_message('error', "Failed to delete user due to database error.");
            }
            $stmt->close();
        }
        header("Location: " . BASE_URL . "admin/dashboard.php");
        exit;
    }

    // -- UPDATE USER --
    if ($action === 'edit') {
        $edit_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role_type = trim($_POST['role_type'] ?? '');
        $allowed_roles = ['Admin', 'Employer', 'Seeker'];

        if (!$edit_id || empty($username) || empty($email) || !in_array($role_type, $allowed_roles)) {
            set_flash_message('error', "Invalid input for updating user.");
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash_message('error', "A valid email is required.");
        } else {
            // Check for duplicate emails/usernames excluding the current user being edited
            $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE (email = ? OR username = ?) AND user_id != ?");
            $check_stmt->bind_param("ssi", $email, $username, $edit_id);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                set_flash_message('error', "Another user already has that email or username.");
            } else {
                // Perform Update
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role_type = ? WHERE user_id = ?");
                $stmt->bind_param("sssi", $username, $email, $role_type, $edit_id);
                if ($stmt->execute()) {
                    set_flash_message('success', "User profile updated successfully.");
                } else {
                    set_flash_message('error', "Failed to update user profile.");
                }
                $stmt->close();
            }
            $check_stmt->close();
        }
        header("Location: " . BASE_URL . "admin/dashboard.php");
        exit;
    }

    // -- DELETE JOB --
    if ($action === 'delete_job') {
        $job_id = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);

        if (!$job_id) {
            set_flash_message('error', 'Invalid job selected for deletion.');
        } else {
            $stmt = $conn->prepare("DELETE FROM jobs WHERE job_id = ?");
            $stmt->bind_param("i", $job_id);
            if ($stmt->execute()) {
                set_flash_message('success', 'Job posting deleted successfully.');
            } else {
                set_flash_message('error', 'Unable to delete the selected job posting.');
            }
            $stmt->close();
        }

        header("Location: " . BASE_URL . "admin/dashboard.php");
        exit;
    }

    // -- UPDATE JOB --
    if ($action === 'edit_job') {
        $job_id = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);
        $title = trim($_POST['job_title'] ?? '');
        $location_type = trim($_POST['location_type'] ?? '');
        $status = trim($_POST['status'] ?? '');

        $allowed_locations = ['Remote', 'Hybrid', 'On-site'];
        $allowed_statuses = ['Active', 'Closed'];

        if (!$job_id || $title === '' || !in_array($location_type, $allowed_locations, true) || !in_array($status, $allowed_statuses, true)) {
            set_flash_message('error', 'Invalid job update request.');
        } else {
            $stmt = $conn->prepare("UPDATE jobs SET title = ?, location_type = ?, status = ? WHERE job_id = ?");
            $stmt->bind_param("sssi", $title, $location_type, $status, $job_id);
            if ($stmt->execute()) {
                set_flash_message('success', 'Job posting updated successfully.');
            } else {
                set_flash_message('error', 'Unable to update the selected job posting.');
            }
            $stmt->close();
        }

        header("Location: " . BASE_URL . "admin/dashboard.php");
        exit;
    }

    // -- UPDATE ACCOMMODATION --
    if ($action === 'edit_accommodation') {
        $accommodation_id = filter_input(INPUT_POST, 'accommodation_id', FILTER_VALIDATE_INT);
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');

        if (!$accommodation_id || $name === '' || $category === '') {
            set_flash_message('error', 'Invalid accommodation update request.');
        } else {
            $stmt = $conn->prepare("UPDATE accommodations SET name = ?, category = ? WHERE accommodation_id = ?");
            $stmt->bind_param("ssi", $name, $category, $accommodation_id);

            if ($stmt->execute()) {
                set_flash_message('success', 'Accommodation updated successfully.');
            } else {
                set_flash_message('error', 'Unable to update accommodation.');
            }

            $stmt->close();
        }

        header("Location: " . BASE_URL . "admin/dashboard.php");
        exit;
    }
    } catch (mysqli_sql_exception $e) {
        error_log("Admin Action DB Error: " . $e->getMessage());
        set_flash_message('error', "A database error occurred while performing this action.");
        header("Location: " . BASE_URL . "admin/dashboard.php");
        exit;
    } catch (Throwable $e) {
        error_log("Admin Action General Error: " . $e->getMessage());
        set_flash_message('error', "An unexpected error occurred.");
        header("Location: " . BASE_URL . "admin/dashboard.php");
        exit;
    }
}

// ---------------------------------------------------------
// GET Handlers (Read with Pagination)
// ---------------------------------------------------------

$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $limit;

// Total Count for Pagination
$count_query = $conn->query("SELECT COUNT(user_id) AS total_users FROM users");
$total_users = $count_query->fetch_assoc()['total_users'];
$total_pages = ceil($total_users / $limit);

// Fetch Data Using Parameters
$stmt = $conn->prepare("SELECT user_id, username, email, role_type, created_at FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$users_result = $stmt->get_result();

$job_limit = 10;
$job_page = isset($_GET['job_page']) && is_numeric($_GET['job_page']) ? (int)$_GET['job_page'] : 1;
$job_page = max(1, $job_page);
$job_offset = ($job_page - 1) * $job_limit;

$job_count_query = $conn->query("SELECT COUNT(job_id) AS total_jobs FROM jobs");
$total_jobs = (int)($job_count_query->fetch_assoc()['total_jobs'] ?? 0);
$job_total_pages = max(1, (int)ceil($total_jobs / $job_limit));

$jobs_stmt = $conn->prepare(
    "SELECT j.job_id, j.title, j.location_type, j.status, j.posted_at, u.username AS employer_name
     FROM jobs j
     JOIN users u ON j.employer_id = u.user_id
     ORDER BY j.posted_at DESC
     LIMIT ? OFFSET ?"
);
$jobs_stmt->bind_param("ii", $job_limit, $job_offset);
$jobs_stmt->execute();
$jobs_result = $jobs_stmt->get_result();

$accommodations_stmt = $conn->prepare(
    "SELECT accommodation_id, name, category
     FROM accommodations
     ORDER BY category ASC, name ASC"
);
$accommodations_stmt->execute();
$accommodations_result = $accommodations_stmt->get_result();

require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-16">
    
    <div class="mb-6 md:mb-8 flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-text">Admin Dashboard</h1>
            <p class="mt-2 text-sm text-muted">Manage registered users and platform parameters.</p>
        </div>
        <div class="mt-4 md:mt-0 flex items-center gap-2">
            <a href="<?php echo BASE_URL; ?>admin/add_job.php" class="btn-primary">
                Add Job
            </a>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-accent/10 text-accent">
                Total Users: <?php echo htmlspecialchars($total_users); ?>
            </span>
        </div>
    </div>

    <!-- Data Table Container -->
    <div class="bg-surface shadow-md rounded-lg overflow-hidden border border-border">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border text-left text-sm text-text">
                <thead class="bg-bg">
                    <tr>
                        <th scope="col" class="px-4 md:px-6 py-3 md:py-4 font-semibold text-text">ID</th>
                        <th scope="col" class="px-4 md:px-6 py-3 md:py-4 font-semibold text-text">Username</th>
                        <th scope="col" class="px-4 md:px-6 py-3 md:py-4 font-semibold text-text">Email</th>
                        <th scope="col" class="px-4 md:px-6 py-3 md:py-4 font-semibold text-text">Role</th>
                        <th scope="col" class="px-4 md:px-6 py-3 md:py-4 font-semibold text-text">Registered</th>
                        <th scope="col" class="px-4 md:px-6 py-3 md:py-4 font-semibold text-text text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <?php while($row = $users_result->fetch_assoc()): ?>
                        <tr class="transition-all duration-200 duration-300 ease-in-out">
                            <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['user_id']); ?></td>
                            <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap font-medium"><?php echo htmlspecialchars($row['username']); ?></td>
                            <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['email']); ?></td>
                            <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap">
                                <?php 
                                    $role_color = match($row['role_type']) {
                                        'Admin' => 'bg-purple-100 text-purple-800  ',
                                        'Employer' => 'bg-indigo-100 text-indigo-800  ',
                                        'Seeker' => 'bg-emerald-100 text-emerald-800  ',
                                        default => 'bg-bg text-text'
                                    };
                                ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $role_color; ?>">
                                    <?php echo htmlspecialchars($row['role_type']); ?>
                                </span>
                            </td>
                            <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-right text-sm space-x-2">
                                
                                <!-- Edit Button triggers Modal via JS -->
                                <button type="button" 
                                        class="open-edit-modal font-medium text-accent transition-all duration-200 duration-300 focus:outline-none focus:underline transition-all duration-200 active:scale-95"
                                        data-id="<?php echo htmlspecialchars($row['user_id']); ?>"
                                        data-username="<?php echo htmlspecialchars($row['username']); ?>"
                                        data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                        data-role="<?php echo htmlspecialchars($row['role_type']); ?>">
                                    Edit
                                </button>

                                <!-- Delete Form with JS Confirmation -->
                                <form action="<?php echo BASE_URL; ?>admin/dashboard.php" method="POST" class="inline-block" onsubmit="return confirm('WARNING: Are you sure you want to completely delete record for <?php echo htmlspecialchars($row['username'], ENT_QUOTES); ?>? This cannot be undone.');">
                                    <?php echo csrf_input(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($row['user_id']); ?>">
                                    <button type="submit" class="btn-primary">
                                        Delete
                                    </button>
                                </form>
                                
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    
                    <?php if($users_result->num_rows === 0): ?>
                        <tr>
                            <td colspan="6" class="px-4 md:px-6 py-6 text-center text-muted">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Component -->
        <?php if($total_pages > 1): ?>
        <div class="px-4 md:px-6 py-3 border-t border-border bg-bg flex items-center justify-between">
            <span class="text-sm text-text">
                Page <span class="font-medium"><?php echo $page; ?></span> of <span class="font-medium"><?php echo $total_pages; ?></span>
            </span>
            <div class="space-x-1">
                <?php if($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="px-3 py-1 border border-border rounded bg-surface text-text transition-all duration-200 duration-300 focus:ring-2 focus:ring-accent">Previous</a>
                <?php endif; ?>
                <?php if($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="px-3 py-1 border border-border rounded bg-surface text-text transition-all duration-200 duration-300 focus:ring-2 focus:ring-accent">Next</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="mt-8 bg-surface shadow-md rounded-lg overflow-hidden border border-border">
        <div class="px-4 md:px-6 py-4 border-b border-border bg-bg flex items-center justify-between">
            <h2 class="text-lg font-semibold text-text">Job Postings Management</h2>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-accent/10 text-accent">
                Total Jobs: <?php echo (int)$total_jobs; ?>
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border text-left text-sm text-text">
                <thead class="bg-bg">
                    <tr>
                        <th scope="col" class="px-4 md:px-6 py-3 md:py-4 font-semibold text-text">ID</th>
                        <th scope="col" class="px-4 md:px-6 py-3 md:py-4 font-semibold text-text">Title</th>
                        <th scope="col" class="px-4 md:px-6 py-3 md:py-4 font-semibold text-text">Employer</th>
                        <th scope="col" class="px-4 md:px-6 py-3 md:py-4 font-semibold text-text">Location</th>
                        <th scope="col" class="px-4 md:px-6 py-3 md:py-4 font-semibold text-text">Status</th>
                        <th scope="col" class="px-4 md:px-6 py-3 md:py-4 font-semibold text-text">Posted</th>
                        <th scope="col" class="px-4 md:px-6 py-3 md:py-4 font-semibold text-text text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <?php while ($job = $jobs_result->fetch_assoc()): ?>
                        <tr class="transition-all duration-200 duration-300 ease-in-out">
                            <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap"><?php echo (int)$job['job_id']; ?></td>
                            <td class="px-4 md:px-6 py-3 md:py-4"><?php echo htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap"><?php echo htmlspecialchars($job['employer_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-4 md:px-6 py-3 md:py-4"><?php echo htmlspecialchars($job['location_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-4 md:px-6 py-3 md:py-4"><?php echo htmlspecialchars($job['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap"><?php echo date('M d, Y', strtotime($job['posted_at'])); ?></td>
                            <td class="px-4 md:px-6 py-3 md:py-4">
                                <form action="<?php echo BASE_URL; ?>admin/dashboard.php" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-2 items-center">
                                    <?php echo csrf_input(); ?>
                                    <input type="hidden" name="action" value="edit_job">
                                    <input type="hidden" name="job_id" value="<?php echo (int)$job['job_id']; ?>">

                                    <label for="job-title-<?php echo (int)$job['job_id']; ?>" class="sr-only">Job title</label>
                                    <input id="job-title-<?php echo (int)$job['job_id']; ?>" type="text" name="job_title" value="<?php echo htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8'); ?>" class="border border-border rounded-lg px-3 py-2 bg-surface text-text">

                                    <label for="location-<?php echo (int)$job['job_id']; ?>" class="sr-only">Location type</label>
                                    <select id="location-<?php echo (int)$job['job_id']; ?>" name="location_type" class="border border-border rounded-lg px-3 py-2 bg-surface text-text">
                                        <option value="Remote" <?php echo $job['location_type'] === 'Remote' ? 'selected' : ''; ?>>Remote</option>
                                        <option value="Hybrid" <?php echo $job['location_type'] === 'Hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                                        <option value="On-site" <?php echo $job['location_type'] === 'On-site' ? 'selected' : ''; ?>>On-site</option>
                                    </select>

                                    <label for="status-<?php echo (int)$job['job_id']; ?>" class="sr-only">Job status</label>
                                    <select id="status-<?php echo (int)$job['job_id']; ?>" name="status" class="border border-border rounded-lg px-3 py-2 bg-surface text-text">
                                        <option value="Active" <?php echo $job['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="Closed" <?php echo $job['status'] === 'Closed' ? 'selected' : ''; ?>>Closed</option>
                                    </select>

                                    <button type="submit" class="bg-accent text-white px-3 py-2 rounded-lg text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-accent/50 transition-all duration-200 active:scale-95">Save</button>
                                </form>
                                <form action="<?php echo BASE_URL; ?>admin/dashboard.php" method="POST" class="inline-block mt-2" onsubmit="return confirm('Are you sure you want to delete this job posting? This action cannot be undone.');">
                                    <?php echo csrf_input(); ?>
                                    <input type="hidden" name="action" value="delete_job">
                                    <input type="hidden" name="job_id" value="<?php echo (int)$job['job_id']; ?>">
                                    <button type="submit" class="bg-red-600 text-white px-3 py-2 rounded-lg text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-red-300 transition-all duration-200 active:scale-95">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                    <?php if ($jobs_result->num_rows === 0): ?>
                        <tr>
                            <td colspan="7" class="px-4 md:px-6 py-6 text-center text-muted">No job postings found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($job_total_pages > 1): ?>
            <div class="px-4 md:px-6 py-3 border-t border-border bg-bg flex items-center justify-between">
                <span class="text-sm text-text">
                    Jobs Page <span class="font-medium"><?php echo $job_page; ?></span> of <span class="font-medium"><?php echo $job_total_pages; ?></span>
                </span>
                <div class="space-x-1">
                    <?php if ($job_page > 1): ?>
                        <a href="?page=<?php echo $page; ?>&job_page=<?php echo $job_page - 1; ?>" class="px-3 py-1 border border-border rounded bg-surface text-text transition-all duration-200 duration-300 focus:ring-2 focus:ring-accent">Previous</a>
                    <?php endif; ?>
                    <?php if ($job_page < $job_total_pages): ?>
                        <a href="?page=<?php echo $page; ?>&job_page=<?php echo $job_page + 1; ?>" class="px-3 py-1 border border-border rounded bg-surface text-text transition-all duration-200 duration-300 focus:ring-2 focus:ring-accent">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="mt-8 bg-surface shadow-md rounded-lg overflow-hidden border border-border">
        <div class="px-4 md:px-6 py-4 border-b border-border bg-bg">
            <h2 class="text-lg font-semibold text-text">Accommodation Categories</h2>
            <p class="text-sm text-muted mt-1">Update accommodation names and categories used by the matching engine.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border text-left text-sm text-text">
                <thead class="bg-bg">
                    <tr>
                        <th scope="col" class="px-4 md:px-6 py-3 md:py-4 font-semibold text-text">ID</th>
                        <th scope="col" class="px-4 md:px-6 py-3 md:py-4 font-semibold text-text">Name</th>
                        <th scope="col" class="px-4 md:px-6 py-3 md:py-4 font-semibold text-text">Category</th>
                        <th scope="col" class="px-4 md:px-6 py-3 md:py-4 font-semibold text-text text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <?php while ($acc = $accommodations_result->fetch_assoc()): ?>
                        <tr class="transition-all duration-200 duration-300 ease-in-out">
                            <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap"><?php echo (int)$acc['accommodation_id']; ?></td>
                            <td class="px-4 md:px-6 py-3 md:py-4"><?php echo htmlspecialchars($acc['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-4 md:px-6 py-3 md:py-4"><?php echo htmlspecialchars($acc['category'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-right">
                                <form action="<?php echo BASE_URL; ?>admin/dashboard.php" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-2 items-center">
                                    <?php echo csrf_input(); ?>
                                    <input type="hidden" name="action" value="edit_accommodation">
                                    <input type="hidden" name="accommodation_id" value="<?php echo (int)$acc['accommodation_id']; ?>">

                                    <label for="acc-name-<?php echo (int)$acc['accommodation_id']; ?>" class="sr-only">Accommodation name</label>
                                    <input id="acc-name-<?php echo (int)$acc['accommodation_id']; ?>" type="text" name="name" value="<?php echo htmlspecialchars($acc['name'], ENT_QUOTES, 'UTF-8'); ?>" class="border border-border rounded-lg px-3 py-2 bg-surface text-text">

                                    <label for="acc-category-<?php echo (int)$acc['accommodation_id']; ?>" class="sr-only">Accommodation category</label>
                                    <input id="acc-category-<?php echo (int)$acc['accommodation_id']; ?>" type="text" name="category" value="<?php echo htmlspecialchars($acc['category'], ENT_QUOTES, 'UTF-8'); ?>" class="border border-border rounded-lg px-3 py-2 bg-surface text-text">

                                    <button type="submit" class="bg-accent text-white px-3 py-2 rounded-lg text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-accent/50 transition-all duration-200 active:scale-95">Save</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                    <?php if ($accommodations_result->num_rows === 0): ?>
                        <tr>
                            <td colspan="4" class="px-4 md:px-6 py-6 text-center text-muted">No accommodations found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Accessible Edit User Modal -->
<div id="editUserModal" class="fixed z-50 inset-0 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-bg0 bg-opacity-75 transition-opacity" aria-hidden="true" id="modalOverlay"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-surface rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-border">
            <form action="<?php echo BASE_URL; ?>admin/dashboard.php" method="POST" id="editForm" novalidate>
                <?php echo csrf_input(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="bg-surface px-4 pt-5 pb-4 sm:p-5 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-text" id="modal-title">
                                Edit User Account
                            </h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="edit_username" class="block text-sm font-medium text-text">Username</label>
                                    <input type="text" name="username" id="edit_username" required
                                        class="form-input">
                                </div>
                                <div>
                                    <label for="edit_email" class="block text-sm font-medium text-text">Email Address</label>
                                    <input type="email" name="email" id="edit_email" required
                                        class="form-input">
                                </div>
                                <div>
                                    <label id="edit_role_label" class="block text-sm font-medium text-text mb-1">Role</label>
                                    <div class="custom-select-container relative w-full" data-name="role_type">
                                        <input type="hidden" name="role_type" id="edit_role_type" required>
                                        <button type="button" class="w-full px-4 py-2 border border-border rounded-md bg-surface text-text focus:outline-none focus:ring-4 focus:ring-accent/50 flex justify-between items-center text-left transition-all duration-200 active:scale-95" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="edit_role_label">
                                            <span class="custom-select-text">Select Role</span>
                                            <svg aria-hidden="true" class="w-4 h-4 text-muted pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </button>
                                        <ul class="custom-select-list absolute z-50 w-full mt-1 bg-surface border border-border rounded-md shadow-lg max-h-60 overflow-y-auto hidden" role="listbox" tabindex="-1">
                                            <li class="px-4 py-2 cursor-pointer text-text" role="option" aria-selected="false" data-value="Seeker">Seeker</li>
                                            <li class="px-4 py-2 cursor-pointer text-text" role="option" aria-selected="false" data-value="Employer">Employer</li>
                                            <li class="px-4 py-2 cursor-pointer text-text" role="option" aria-selected="false" data-value="Admin">Admin</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Modal Footer -->
                <div class="bg-bg px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-border sm:space-x-reverse sm:space-x-3">
                    <button type="submit" class="w-full sm:w-auto mb-3 sm:mb-0 btn-primary">
                        Save Changes
                    </button>
                    <button type="button" id="closeModalBtn" class="w-full sm:w-auto btn-outline">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('editUserModal');
    const closeBtn = document.getElementById('closeModalBtn');
    const overlay = document.getElementById('modalOverlay');
    const editButtons = document.querySelectorAll('.open-edit-modal');
    
    // Form Inputs
    const inputId = document.getElementById('edit_user_id');
    const inputUsername = document.getElementById('edit_username');
    const inputEmail = document.getElementById('edit_email');
    const inputRole = document.getElementById('edit_role_type');

    function openModal() {
        modal.classList.remove('hidden');
        // Shift focus to the modal for accessibility
        inputUsername.focus();
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    // Attach click events to all "Edit" buttons inside table
    editButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            const btn = e.currentTarget;
            // Map data-attributes to modal fields
            inputId.value = btn.getAttribute('data-id');
            inputUsername.value = btn.getAttribute('data-username');
            inputEmail.value = btn.getAttribute('data-email');
            inputRole.value = btn.getAttribute('data-role');
            inputRole.dispatchEvent(new CustomEvent('customUpdate', { detail: { value: btn.getAttribute('data-role') } }));

            openModal();
        });
    });

    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);
    
    // Allow ESC key to close modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
});
</script>

<?php
// Clean up connection
$stmt->close();
$jobs_stmt->close();
$accommodations_stmt->close();
$conn->close();
require_once '../includes/footer.php';
?>

