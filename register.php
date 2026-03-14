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

<div class="max-w-md mx-auto bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-8 mt-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2 text-center">Create an Account</h1>
    <p class="text-gray-600 dark:text-gray-400 text-center mb-6">Join EquiWork to find or post accessible opportunities.</p>

    <!-- Semantic form structure -->
    <form action="<?php echo BASE_URL; ?>actions/process_register.php" method="POST" id="registerForm" novalidate>
        <fieldset class="mb-5 space-y-4">
            <legend class="sr-only">Account Details</legend>
            
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Name or Company Name</label>
                <input type="text" id="username" name="username" required aria-required="true"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-500 transition-colors">
                <p class="mt-1 text-sm text-red-600 hidden" id="username-error" role="alert" aria-live="polite"></p>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Address</label>
                <input type="email" id="email" name="email" required aria-required="true" autocomplete="email"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-500 transition-colors">
                <p class="mt-1 text-sm text-red-600 hidden" id="email-error" role="alert" aria-live="polite"></p>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                <input type="password" id="password" name="password" required aria-required="true" minlength="8" autocomplete="new-password"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-500 transition-colors">
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" id="password-hint">Must be at least 8 characters long.</p>
                <p class="mt-1 text-sm text-red-600 hidden" id="password-error" role="alert" aria-live="polite"></p>
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">I am a...</label>
                <select id="role" name="role_type" required aria-required="true"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-500 transition-colors">
                    <option value="Seeker" <?php echo $defaultRole === 'Seeker' ? 'selected' : ''; ?>>Job Seeker</option>
                    <option value="Employer" <?php echo $defaultRole === 'Employer' ? 'selected' : ''; ?>>Employer</option>
                    <option value="Admin">Platform Admin (For Demo)</option>
                </select>
            </div>
        </fieldset>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 text-white font-semibold flex justify-center px-4 py-3 rounded-lg transition-colors">
            Register Account
        </button>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Already have an account? 
                <a href="<?php echo BASE_URL; ?>login.php" class="text-blue-600 dark:text-blue-400 hover:underline font-medium focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">Log in</a>
            </p>
        </div>
    </form>
</div>

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

        // Check if role is valid (though HTML5 select handles most of this)
        const allowedRoles = ['Seeker', 'Employer', 'Admin'];
        if (!allowedRoles.includes(roleInput.value)) {
            isValid = false;
            // Native fallback is fine here since it's a dropdown, but we halt submit
        }

        if (!isValid) {
            e.preventDefault();
        }
    });

    function showError(input, errorElement, message) {
        input.setAttribute('aria-invalid', 'true');
        input.classList.add('border-red-500', 'focus:ring-red-300');
        input.classList.remove('border-gray-300', 'dark:border-gray-600', 'focus:ring-blue-300', 'dark:focus:ring-blue-500');
        errorElement.textContent = message;
        errorElement.classList.remove('hidden');
    }

    function clearError(input, errorElement) {
        input.removeAttribute('aria-invalid');
        input.classList.remove('border-red-500', 'focus:ring-red-300');
        input.classList.add('border-gray-300', 'dark:border-gray-600', 'focus:ring-blue-300', 'dark:focus:ring-blue-500');
        errorElement.textContent = "";
        errorElement.classList.add('hidden');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
