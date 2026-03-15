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

<div class="max-w-md mx-auto bg-surface border border-border rounded-xl shadow-sm p-6 hover:-translate-y-1 hover:shadow-md transition-all duration-200">
    <h1 class="text-3xl font-extrabold text-text mb-3 text-center tracking-tight font-heading">Create Account</h1>
    <p class="text-muted text-center mb-8 leading-relaxed font-medium">Join EquiWork as a professional seeking inclusive roles or as an organization committed to accessible workflows.</p>

    <!-- Semantic form structure -->
    <form action="<?php echo BASE_URL; ?>actions/process_register.php" method="POST" id="registerForm" novalidate>
        <fieldset class="mb-5 space-y-4">
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
                    <button type="button" class="w-full bg-accent text-white px-5 py-2.5 rounded-lg font-semibold hover:bg-accent-hover active:scale-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-accent/50" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="role-label">
                        <span class="custom-select-text"><?php echo $defaultRole === 'Employer' ? 'Employer' : 'Job Seeker'; ?></span>
                        <svg aria-hidden="true" class="w-4 h-4 ml-2 text-muted pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <ul class="custom-select-list absolute z-10 w-full mt-1 bg-surface border border-border rounded-lg shadow-lg max-h-60 overflow-y-auto hidden" role="listbox" tabindex="-1">
                        <li class="px-4 py-2 hover:bg-accent/10 cursor-pointer text-text" role="option" aria-selected="<?php echo $defaultRole === 'Seeker' ? 'true' : 'false'; ?>" data-value="Seeker">Job Seeker</li>
                        <li class="px-4 py-2 hover:bg-accent/10 cursor-pointer text-text" role="option" aria-selected="<?php echo $defaultRole === 'Employer' ? 'true' : 'false'; ?>" data-value="Employer">Employer</li>
                        <li class="px-4 py-2 hover:bg-accent/10 cursor-pointer text-text" role="option" aria-selected="false" data-value="Admin">Platform Admin (For Demo)</li>
                    </ul>
                </div>

            </div>
        </fieldset>

        <button type="submit" class="w-full bg-accent text-white px-5 py-2.5 rounded-lg font-semibold hover:bg-accent-hover active:scale-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-accent/50">
            Register Account
        </button>

        <div class="mt-6 text-center">
            <p class="text-sm text-muted">
                Already have an account? 
                <a href="<?php echo BASE_URL; ?>login.php" class="text-accent hover:underline font-medium focus:outline-none focus:ring-2 focus:ring-accent/50 rounded">Log in</a>
            </p>
        </div>
    </form>
</div>

<!-- Inject custom script inline to bypass includes restriction if any -->
<script src="<?php echo BASE_URL; ?>assets/js/custom-controls.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registerForm');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const roleInput = document.getElementById('role');
    
    const elements = [
        { input: usernameInput, error: document.getElementById('username-error') },
        { input: emailInput, error: document.getElementById('email-error') },
        { input: passwordInput, error: document.getElementById('password-error') }
    ];

    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Validate Username
        if (!usernameInput.value.trim()) {
            showError(usernameInput, elements[0].error, "Full Name or Company Name is required.");
            isValid = false;
        } else {
            clearError(usernameInput, elements[0].error);
        }

        // Validate Email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailInput.value.trim()) {
            showError(emailInput, elements[1].error, "Email address is required.");
            isValid = false;
        } else if (!emailRegex.test(emailInput.value.trim())) {
            showError(emailInput, elements[1].error, "Please enter a valid email address.");
            isValid = false;
        } else {
            clearError(emailInput, elements[1].error);
        }

        // Validate Password Length
        if (!passwordInput.value) {
            showError(passwordInput, elements[2].error, "Password is required.");
            isValid = false;
        } else if (passwordInput.value.length < 8) {
            showError(passwordInput, elements[2].error, "Password must be at least 8 characters long.");
            isValid = false;
        } else {
            clearError(passwordInput, elements[2].error);
        }

        // Check if role is valid
        const allowedRoles = ['Seeker', 'Employer', 'Admin'];
        if (!allowedRoles.includes(roleInput.value)) {
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
        }
    });

    function showError(input, errorElement, message) {
        input.setAttribute('aria-invalid', 'true');
        input.classList.add('border-red-500', 'focus:ring-red-300');
        input.classList.remove('border-border', '', 'focus:ring-accent/50', '');
        errorElement.textContent = message;
        errorElement.classList.remove('hidden');
    }

    function clearError(input, errorElement) {
        input.removeAttribute('aria-invalid');
        input.classList.remove('border-red-500', 'focus:ring-red-300');
        input.classList.add('border-border', '', 'focus:ring-accent/50', '');
        errorElement.textContent = "";
        errorElement.classList.add('hidden');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
