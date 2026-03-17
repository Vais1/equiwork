<?php
// Ensure session starts before ANY output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Enforce Role: Admin only
enforce_role('Admin');

// Set up validation & flash message handling
$errors = [];
$success_msg = '';

if (isset($_SESSION['flash_success'])) {
    $success_msg = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
if (isset($_SESSION['flash_error'])) {
    $errors[] = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

// ---------------------------------------------------------
// POST Handlers (Update & Delete)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // -- DELETE USER --
    if ($action === 'delete') {
        $delete_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        
        // Prevent deleting oneself
        if ($delete_id === $_SESSION['user_id']) {
            $_SESSION['flash_error'] = "You cannot delete your own admin account.";
        } else if ($delete_id) {
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $delete_id);
            if ($stmt->execute()) {
                $_SESSION['flash_success'] = "User successfully deleted.";
            } else {
                $_SESSION['flash_error'] = "Failed to delete user due to database error.";
            }
            $stmt->close();
        }
        header("Location: " . BASE_URL . "admin/dashboard.php");
        exit;
    }

    // -- UPDATE USER --
    if ($action === 'edit') {
        $edit_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $username = trim(htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'));
        $email = trim($_POST['email'] ?? '');
        $role_type = trim($_POST['role_type'] ?? '');
        $allowed_roles = ['Admin', 'Employer', 'Seeker'];

        if (!$edit_id || empty($username) || empty($email) || !in_array($role_type, $allowed_roles)) {
            $_SESSION['flash_error'] = "Invalid input for updating user.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = "A valid email is required.";
        } else {
            // Check for duplicate emails/usernames excluding the current user being edited
            $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE (email = ? OR username = ?) AND user_id != ?");
            $check_stmt->bind_param("ssi", $email, $username, $edit_id);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                $_SESSION['flash_error'] = "Another user already has that email or username.";
            } else {
                // Perform Update
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role_type = ? WHERE user_id = ?");
                $stmt->bind_param("sssi", $username, $email, $role_type, $edit_id);
                if ($stmt->execute()) {
                    $_SESSION['flash_success'] = "User profile updated successfully.";
                } else {
                    $_SESSION['flash_error'] = "Failed to update user profile.";
                }
                $stmt->close();
            }
            $check_stmt->close();
        }
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

require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20">
    
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-text">Admin Dashboard</h1>
            <p class="mt-2 text-sm text-muted">Manage registered users and platform parameters.</p>
        </div>
        <div class="mt-4 md:mt-0">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-accent/10 text-accent">
                Total Users: <?php echo htmlspecialchars($total_users); ?>
            </span>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if ($success_msg): ?>
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative shadow-sm" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($success_msg); ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative shadow-sm" role="alert">
            <?php foreach ($errors as $err): ?>
                <span class="block sm:inline"><?php echo htmlspecialchars($err); ?><br></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Data Table Container -->
    <div class="bg-surface shadow-md rounded-lg overflow-hidden border border-border">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border text-left text-sm text-text">
                <thead class="bg-bg">
                    <tr>
                        <th scope="col" class="px-6 py-4 font-semibold text-text">ID</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-text">Username</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-text">Email</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-text">Role</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-text">Registered</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-text text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <?php while($row = $users_result->fetch_assoc()): ?>
                        <tr class="transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['user_id']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap font-medium"><?php echo htmlspecialchars($row['username']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['email']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
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
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-2">
                                
                                <!-- Edit Button triggers Modal via JS -->
                                <button type="button" 
                                        class="open-edit-modal font-medium text-accent focus:outline-none focus:underline"
                                        data-id="<?php echo htmlspecialchars($row['user_id']); ?>"
                                        data-username="<?php echo htmlspecialchars($row['username']); ?>"
                                        data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                        data-role="<?php echo htmlspecialchars($row['role_type']); ?>">
                                    Edit
                                </button>

                                <!-- Delete Form with JS Confirmation -->
                                <form action="<?php echo BASE_URL; ?>admin/dashboard.php" method="POST" class="inline-block" onsubmit="return confirm('WARNING: Are you sure you want to completely delete record for <?php echo htmlspecialchars($row['username'], ENT_QUOTES); ?>? This cannot be undone.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($row['user_id']); ?>">
                                    <button type="submit" class="bg-accent text-white px-5 py-2.5 rounded-lg font-semibold active:scale-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-accent/50">
                                        Delete
                                    </button>
                                </form>
                                
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    
                    <?php if($users_result->num_rows === 0): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-muted">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Component -->
        <?php if($total_pages > 1): ?>
        <div class="px-6 py-4 border-t border-border bg-bg flex items-center justify-between">
            <span class="text-sm text-text">
                Page <span class="font-medium"><?php echo $page; ?></span> of <span class="font-medium"><?php echo $total_pages; ?></span>
            </span>
            <div class="space-x-1">
                <?php if($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="px-3 py-1 border border-border rounded bg-surface text-text focus:ring-2 focus:ring-accent">Previous</a>
                <?php endif; ?>
                <?php if($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="px-3 py-1 border border-border rounded bg-surface text-text focus:ring-2 focus:ring-accent">Next</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
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
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="bg-surface px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-text" id="modal-title">
                                Edit User Account
                            </h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="edit_username" class="block text-sm font-medium text-text">Username</label>
                                    <input type="text" name="username" id="edit_username" required
                                        class="w-full border border-border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-accent focus:border-transparent bg-surface text-text transition-colors duration-150">
                                </div>
                                <div>
                                    <label for="edit_email" class="block text-sm font-medium text-text">Email Address</label>
                                    <input type="email" name="email" id="edit_email" required
                                        class="w-full border border-border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-accent focus:border-transparent bg-surface text-text transition-colors duration-150">
                                </div>
                                <div>
                                    <label id="edit_role_label" class="block text-sm font-medium text-text mb-1">Role</label>
                                    <div class="custom-select-container relative w-full" data-name="role_type">
                                        <input type="hidden" name="role_type" id="edit_role_type" required>
                                        <button type="button" class="w-full px-4 py-2 border border-border rounded-md bg-surface text-text focus:outline-none focus:ring-4 focus:ring-accent/50 flex justify-between items-center text-left" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="edit_role_label">
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
                <div class="bg-bg px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-border">
                    <button type="submit" class="bg-accent text-white px-5 py-2.5 rounded-lg font-semibold active:scale-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-accent/50">
                        Save Changes
                    </button>
                    <button type="button" id="closeModalBtn" class="border border-accent text-accent px-5 py-2.5 rounded-lg font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-accent/50 active:scale-95">
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
$conn->close();
require_once '../includes/footer.php';
?>
