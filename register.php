<?php
require_once 'includes/config.php';

if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? '';
    if ($role === 'Admin') {
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
    } elseif ($role === 'Employer') {
        header('Location: ' . BASE_URL . 'employer_dashboard.php');
    } else {
        header('Location: ' . BASE_URL . 'jobs.php');
    }
    exit;
}

$defaultRole = isset($_GET['role']) && in_array($_GET['role'], ['Seeker', 'Employer']) ? $_GET['role'] : 'Seeker';

require_once 'includes/db.php';
require_once 'includes/flash.php';
require_once 'includes/header.php';
?>

<div class="max-w-md mx-auto bg-surface border border-border rounded-xl shadow-sm p-6 transition-all duration-300 ease-in-out">
    <?php display_flash_messages(); ?>
    <h1 class="text-2xl md:text-3xl font-extrabold text-text mb-2 md:mb-3 text-center tracking-tight font-heading">Create Account</h1>
    <p class="text-muted text-center mb-6 md:mb-8 leading-relaxed font-medium">Join EquiWork as a professional seeking inclusive roles or as an organization committed to accessible workflows.</p>

    <form action="<?php echo BASE_URL; ?>actions/process_register.php" method="POST" id="registerForm" novalidate>
        <?php echo csrf_input(); ?>
        <fieldset class="mb-4 md:mb-5 space-y-3 md:space-y-4">
            <legend class="sr-only">Account Details</legend>
            
            <div>
                <label for="username" class="block text-sm font-medium text-text mb-1">Full Name or Company Name</label>
                <input type="text" id="username" name="username" required aria-required="true" aria-describedby="username-error"
                    class="w-full border border-border rounded-lg px-4 py-2 focus:ring-2 focus:ring-accent focus:border-transparent bg-surface text-text transition-all duration-200 duration-150">
                <p class="mt-1 text-sm text-red-600 hidden" id="username-error" role="alert" aria-live="polite"></p>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-text mb-1">Email Address</label>
                <input type="email" id="email" name="email" required aria-required="true" autocomplete="email" aria-describedby="email-error"
                    class="w-full border border-border rounded-lg px-4 py-2 focus:ring-2 focus:ring-accent focus:border-transparent bg-surface text-text transition-all duration-200 duration-150">
                <p class="mt-1 text-sm text-red-600 hidden" id="email-error" role="alert" aria-live="polite"></p>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-text mb-1">Password</label>
                <input type="password" id="password" name="password" required aria-required="true" minlength="8" autocomplete="new-password" aria-describedby="password-hint password-error"
                    class="w-full border border-border rounded-lg px-4 py-2 focus:ring-2 focus:ring-accent focus:border-transparent bg-surface text-text transition-all duration-200 duration-150">
                <p class="mt-1 text-sm text-muted" id="password-hint">Must be at least 8 characters long.</p>
                <p class="mt-1 text-sm text-red-600 hidden" id="password-error" role="alert" aria-live="polite"></p>
            </div>

            <div>
                <label class="block text-sm font-medium text-text mb-1">I am a...</label>

                <fieldset role="radiogroup" aria-label="Account type" aria-describedby="role-help" class="mt-1">
                    <legend class="sr-only">Select account type</legend>
                    <div class="grid grid-cols-2 gap-2 rounded-lg border border-border p-1 bg-bg">
                        <div>
                            <input class="sr-only peer" type="radio" name="role_type" id="role-seeker" value="Seeker" <?php echo $defaultRole === 'Seeker' ? 'checked' : ''; ?> required>
                            <label
                                for="role-seeker"
                                class="block cursor-pointer select-none rounded-md px-3 py-2 text-center text-sm font-semibold text-text transition-all duration-200 peer-checked:bg-accent peer-checked:text-white"
                            >
                                Job Seeker
                            </label>
                        </div>

                        <div>
                            <input class="sr-only peer" type="radio" name="role_type" id="role-employer" value="Employer" <?php echo $defaultRole === 'Employer' ? 'checked' : ''; ?> required>
                            <label
                                for="role-employer"
                                class="block cursor-pointer select-none rounded-md px-3 py-2 text-center text-sm font-semibold text-text transition-all duration-200 peer-checked:bg-accent peer-checked:text-white"
                            >
                                Employer
                            </label>
                        </div>
                    </div>
                    <p id="role-help" class="mt-1 text-xs text-muted">Choose one account type. You can update profile details later.</p>
                </fieldset>

            </div>
        </fieldset>

        <button type="submit" class="w-full bg-accent text-white px-4 py-2 min-w-[44px] rounded-lg font-semibold active:scale-95 transition-all duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-accent/50">
            Register Account
        </button>

        <div class="mt-5 md:mt-6 text-center">
            <p class="text-sm text-muted">
                Already have an account? 
                <a href="<?php echo BASE_URL; ?>login.php" class="text-accent font-medium focus:outline-none focus:ring-2 focus:ring-accent/50 rounded transition-all duration-300 ease-in-out">Log in</a>
            </p>
        </div>
    </form>
</div>

<script src="<?php echo BASE_URL; ?>assets/js/custom-controls.js"></script>

<script src="<?php echo BASE_URL; ?>assets/js/form-validation.js"></script>

<?php require_once 'includes/footer.php'; ?>

