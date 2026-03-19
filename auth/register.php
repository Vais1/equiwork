<?php
require_once '../includes/config.php';

if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? '';
    if ($role === 'Admin') {
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
    } elseif ($role === 'Employer') {
        header('Location: ' . BASE_URL . 'employer/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . 'jobs.php');
    }
    exit;
}

$defaultRole = isset($_GET['role']) && in_array($_GET['role'], ['Seeker', 'Employer']) ? $_GET['role'] : 'Seeker';

require_once '../includes/db.php';
require_once '../includes/flash.php';
require_once '../includes/header.php';
?>

<div class="max-w-sm mx-auto mt-8 md:mt-16">
    <div class="card">
        <?php display_flash_messages(); ?>
        <h1 class="heading-2 text-center mb-2">Create Account</h1>
        <p class="text-small text-center mb-8">Join EquiWork as a professional or organization.</p>

        <form action="<?php echo BASE_URL; ?>actions/process_register.php" method="POST" id="registerForm" novalidate>
            <?php echo csrf_input(); ?>
            <fieldset class="mb-6 space-y-4">
                <legend class="sr-only">Account Details</legend>
                
                <div class="form-group">
                    <label for="username" class="form-label">Full Name or Company</label>
                    <input type="text" id="username" name="username" required aria-required="true" aria-describedby="username-error" class="form-input">
                    <p class="mt-1 text-sm text-red-600 hidden" id="username-error" role="alert" aria-live="polite"></p>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" required aria-required="true" autocomplete="email" aria-describedby="email-error" class="form-input">
                    <p class="mt-1 text-sm text-red-600 hidden" id="email-error" role="alert" aria-live="polite"></p>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" required aria-required="true" minlength="8" autocomplete="new-password" aria-describedby="password-hint password-error" class="form-input">
                    <p class="mt-1 text-xs text-muted" id="password-hint">Must be at least 8 characters long.</p>
                    <p class="mt-1 text-sm text-red-600 hidden" id="password-error" role="alert" aria-live="polite"></p>
                </div>

                <div class="form-group">
                    <label for="password_confirm" class="form-label">Confirm Password</label>
                    <input type="password" id="password_confirm" name="password_confirm" required aria-required="true" minlength="8" autocomplete="new-password" aria-describedby="password-confirm-error" class="form-input">
                    <p class="mt-1 text-sm text-red-600 hidden" id="password-confirm-error" role="alert" aria-live="polite"></p>
                </div>

                <div class="form-group">
                    <label class="form-label">Account Type</label>
                    <fieldset role="radiogroup" aria-label="Account type" class="mt-2">
                        <legend class="sr-only">Select account type</legend>
                        <div class="grid grid-cols-2 gap-2 rounded-md border border-border p-1 bg-bg shadow-sm">
                            <div>
                                <input class="sr-only peer" type="radio" name="role_type" id="role-seeker" value="Seeker" <?php echo $defaultRole === 'Seeker' ? 'checked' : ''; ?> required>
                                <label for="role-seeker" class="block cursor-pointer select-none rounded-[4px] px-3 py-1.5 text-center text-sm font-medium text-text transition-all duration-200 peer-checked:bg-surface peer-checked:shadow-sm peer-checked:border peer-checked:border-border peer-focus-visible:ring-2 peer-focus-visible:ring-accent">
                                    Job Seeker
                                </label>
                            </div>

                            <div>
                                <input class="sr-only peer" type="radio" name="role_type" id="role-employer" value="Employer" <?php echo $defaultRole === 'Employer' ? 'checked' : ''; ?> required>
                                <label for="role-employer" class="block cursor-pointer select-none rounded-[4px] px-3 py-1.5 text-center text-sm font-medium text-text transition-all duration-200 peer-checked:bg-surface peer-checked:shadow-sm peer-checked:border peer-checked:border-border peer-focus-visible:ring-2 peer-focus-visible:ring-accent">
                                    Employer
                                </label>
                            </div>
                        </div>
                    </fieldset>
                </div>
            </fieldset>

            <button type="submit" class="w-full btn-primary py-2.5">
                Register Account
            </button>

            <div class="mt-6 text-center">
                <p class="text-small">
                    Already have an account? 
                    <a href="<?php echo BASE_URL; ?>auth/login.php" class="text-text font-medium underline decoration-border focus:outline-none focus-visible:ring-2 focus-visible:ring-accent rounded-sm underline-offset-4">Log in</a>
                </p>
            </div>
        </form>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>assets/js/custom-controls.js"></script>

<script src="<?php echo BASE_URL; ?>assets/js/form-validation.js"></script>

<?php require_once '../includes/footer.php'; ?>

