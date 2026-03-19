<?php
// profile_update.php
// Dual-Role Profile Update Form - Employer or Seeker

require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Users must be logged in to update profile
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch current user details
$stmt = $conn->prepare("SELECT username, email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

require_once '../includes/header.php';
?>

<div class="max-w-2xl mx-auto mt-8 md:mt-12">
    <div class="card">
        <h1 class="heading-2 mb-2">My Profile</h1>
        <p class="text-small mb-8">Update your <?php echo strtolower($role); ?> account details and preferences.</p>

        <?php require_once '../includes/flash.php'; display_flash_messages(); ?>

        <form action="<?php echo BASE_URL; ?>actions/process_profile.php" method="POST" id="profileForm" novalidate>
            <?php echo csrf_input(); ?>
            
            <fieldset class="mb-8 space-y-4">
                <legend class="text-sm font-semibold text-text uppercase tracking-wider mb-4 border-b border-border pb-2 w-full">Basic Information</legend>
                
                <div class="form-group">
                    <label for="username" class="form-label">
                        <?php echo ($role === 'Employer') ? 'Company Name' : 'Full Name'; ?>
                    </label>
                    <input type="text" id="username" name="username" required aria-required="true"
                        value="<?php echo htmlspecialchars($current_user['username'], ENT_QUOTES); ?>"
                        class="form-input"
                        aria-describedby="usernameError">
                    <p id="usernameError" class="mt-1 text-sm text-red-600 hidden" aria-live="polite"></p>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" required aria-required="true"
                        value="<?php echo htmlspecialchars($current_user['email'], ENT_QUOTES); ?>"
                        class="form-input"
                        aria-describedby="emailError">
                    <p id="emailError" class="mt-1 text-sm text-red-600 hidden" aria-live="polite"></p>
                </div>
            </fieldset>

            <fieldset class="mb-8 space-y-4">
                <legend class="text-sm font-semibold text-text uppercase tracking-wider mb-2 border-b border-border pb-2 w-full">Security</legend>
                <p class="text-xs text-muted mb-4">Leave password blank if you do not wish to change it.</p>
                
                <div class="form-group">
                    <label for="password" class="form-label">New Password</label>
                    <input type="password" id="password" name="password" minlength="8"
                        class="form-input"
                        aria-describedby="passwordError">
                    <p id="passwordError" class="mt-1 text-sm text-red-600 hidden" aria-live="polite"></p>
                </div>

                <div class="form-group">
                    <label for="password_confirm" class="form-label">Confirm New Password</label>
                    <input type="password" id="password_confirm" name="password_confirm" minlength="8"
                        class="form-input"
                        aria-describedby="passwordConfirmError">
                    <p id="passwordConfirmError" class="mt-1 text-sm text-red-600 hidden" aria-live="polite"></p>
                </div>
            </fieldset>

            <div class="flex items-center justify-end border-t border-border pt-6 mt-6">
                <button type="submit" class="btn-primary py-2.5 px-6">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>assets/js/form-validation.js"></script>

<?php require_once '../includes/footer.php'; ?>

