<?php
require_once 'includes/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php'); // or redirect based on role
    exit;
}

$defaultRole = isset($_GET['role']) && in_array($_GET['role'], ['Seeker', 'Employer']) ? $_GET['role'] : 'Seeker';

require_once 'includes/db.php';
require_once 'includes/header.php';
?>

<div class="max-w-md mx-auto bg-surface border border-border rounded-xl shadow-sm p-4 md:p-6 transition-all duration-300 ease-in-out">
    <h1 class="text-2xl md:text-3xl font-extrabold text-text mb-2 md:mb-3 text-center tracking-tight font-heading">Create Account</h1>
    <p class="text-muted text-center mb-6 md:mb-8 leading-relaxed font-medium">Join EquiWork as a professional seeking inclusive roles or as an organization committed to accessible workflows.</p>

    <form action="<?php echo BASE_URL; ?>actions/process_register.php" method="POST" id="registerForm" novalidate>
        <fieldset class="mb-4 md:mb-5 space-y-3 md:space-y-4">
            <legend class="sr-only">Account Details</legend>
            
            <div>
                <label for="username" class="block text-sm font-medium text-text mb-1">Full Name or Company Name</label>
                <input type="text" id="username" name="username" required aria-required="true" aria-describedby="username-error"
                    class="w-full border border-border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-accent focus:border-transparent bg-surface text-text transition-colors duration-150">
                <p class="mt-1 text-sm text-red-600 hidden" id="username-error" role="alert" aria-live="polite"></p>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-text mb-1">Email Address</label>
                <input type="email" id="email" name="email" required aria-required="true" autocomplete="email" aria-describedby="email-error"
                    class="w-full border border-border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-accent focus:border-transparent bg-surface text-text transition-colors duration-150">
                <p class="mt-1 text-sm text-red-600 hidden" id="email-error" role="alert" aria-live="polite"></p>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-text mb-1">Password</label>
                <input type="password" id="password" name="password" required aria-required="true" minlength="8" autocomplete="new-password" aria-describedby="password-hint password-error"
                    class="w-full border border-border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-accent focus:border-transparent bg-surface text-text transition-colors duration-150">
                <p class="mt-1 text-sm text-muted" id="password-hint">Must be at least 8 characters long.</p>
                <p class="mt-1 text-sm text-red-600 hidden" id="password-error" role="alert" aria-live="polite"></p>
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-text mb-1">I am a...</label>
                
                <div class="custom-select-container relative w-full" data-name="role_type">
                    <input type="hidden" name="role_type" id="role" value="<?php echo $defaultRole; ?>" required>
                    <button type="button" class="w-full bg-accent text-white px-4 py-2.5 min-w-[44px] rounded-lg font-semibold active:scale-95 transition-all duration-300 ease-in-out hover:opacity-80 focus:outline-none focus:ring-2 focus:ring-accent/50" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="role-label">
                        <span class="custom-select-text"><?php echo $defaultRole === 'Employer' ? 'Employer' : 'Job Seeker'; ?></span>
                        <svg aria-hidden="true" class="w-4 h-4 ml-2 text-muted pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <ul class="custom-select-list absolute z-10 w-full mt-1 bg-surface border border-border rounded-lg shadow-lg max-h-60 overflow-y-auto hidden" role="listbox" tabindex="-1">
                        <li class="px-4 py-2 cursor-pointer text-text" role="option" aria-selected="<?php echo $defaultRole === 'Seeker' ? 'true' : 'false'; ?>" data-value="Seeker">Job Seeker</li>
                        <li class="px-4 py-2 cursor-pointer text-text" role="option" aria-selected="<?php echo $defaultRole === 'Employer' ? 'true' : 'false'; ?>" data-value="Employer">Employer</li>
                        <li class="px-4 py-2 cursor-pointer text-text" role="option" aria-selected="false" data-value="Admin">Platform Admin (For Demo)</li>
                    </ul>
                </div>

            </div>
        </fieldset>

        <button type="submit" class="w-full bg-accent text-white px-4 py-2.5 min-w-[44px] rounded-lg font-semibold active:scale-95 transition-all duration-300 ease-in-out hover:opacity-80 focus:outline-none focus:ring-2 focus:ring-accent/50">
            Register Account
        </button>

        <div class="mt-5 md:mt-6 text-center">
            <p class="text-sm text-muted">
                Already have an account? 
                <a href="<?php echo BASE_URL; ?>login.php" class="text-accent font-medium focus:outline-none focus:ring-2 focus:ring-accent/50 rounded transition-all duration-300 ease-in-out hover:opacity-80">Log in</a>
            </p>
        </div>
    </form>
</div>

<script src="<?php echo BASE_URL; ?>assets/js/custom-controls.js"></script>

<script src="<?php echo BASE_URL; ?>assets/js/form-validation.js"></script>

<?php require_once 'includes/footer.php'; ?>
