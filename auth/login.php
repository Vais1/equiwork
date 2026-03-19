<?php
require_once '../includes/config.php';

// Redirect if already logged in
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

require_once '../includes/db.php';
require_once '../includes/flash.php'; // Required to safely handle get-to-flash conversions

// Convert GET errors to flash messages gracefully if any persisted through links
if (isset($_GET['reason']) && $_GET['reason'] === 'timeout') {
    set_flash_message('warning', 'Your session has timed out due to inactivity. Please log in again.');
}

require_once '../includes/header.php';
?>

<div class="max-w-sm mx-auto mt-12 md:mt-24">
    <div class="card">
        <div class="flex justify-center mb-6">
            <div class="w-12 h-12 bg-accent/5 text-accent rounded-full flex items-center justify-center">
                <svg aria-hidden="true" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
            </div>
        </div>
        <h1 class="heading-2 text-center mb-2">Welcome Back</h1>
        <p class="text-small text-center mb-8">Sign in to your EquiWork account</p>

        <form action="<?php echo BASE_URL; ?>actions/process_login.php" method="POST" id="loginForm" novalidate>
            <?php echo csrf_input(); ?>
            <fieldset class="mb-6 space-y-4">
                <legend class="sr-only">Login Credentials</legend>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" required aria-required="true" autocomplete="email" autofocus aria-describedby="emailError"
                        class="form-input">
                    <p id="emailError" class="text-sm text-red-600 mt-1 hidden" role="alert" aria-live="polite"></p>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" required aria-required="true" autocomplete="current-password" aria-describedby="passwordError"
                        class="form-input">
                    <p id="passwordError" class="text-sm text-red-600 mt-1 hidden" role="alert" aria-live="polite"></p>
                </div>
            </fieldset>

            <button type="submit" class="w-full btn-primary py-2.5">
                Sign In
            </button>

            <div class="mt-6 text-center">
                <p class="text-small">
                    Don't have an account? 
                    <a href="<?php echo BASE_URL; ?>auth/register.php" class="text-text font-medium underline decoration-border focus:outline-none focus-visible:ring-2 focus-visible:ring-accent rounded-sm underline-offset-4">Create account</a>
                </p>
            </div>
        </form>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>assets/js/form-validation.js"></script>

<?php require_once '../includes/footer.php'; ?>

