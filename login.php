<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    // Determine redirect based on role
    header('Location: ' . BASE_URL . 'index.php'); 
    exit;
}

require_once 'includes/db.php';
require_once 'includes/flash.php'; // Required to safely handle get-to-flash conversions

// Convert GET errors to flash messages gracefully if any persisted through links
if (isset($_GET['reason']) && $_GET['reason'] === 'timeout') {
    set_flash_message('warning', 'Your session has timed out due to inactivity. Please log in again.');
}

require_once 'includes/header.php';
?>

<div class="max-w-md mx-auto bg-white dark:bg-gray-800/80 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-8 md:p-10 mt-12 backdrop-blur-sm">
    <div class="flex justify-center mb-6">
        <div class="w-16 h-16 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
        </div>
    </div>
    <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white mb-3 text-center tracking-tight">Welcome Back</h1>
    <p class="text-gray-600 dark:text-gray-400 text-center mb-8 leading-relaxed">Sign in to your EquiWork account to continue.</p>

    <form action="<?php echo BASE_URL; ?>actions/process_login.php" method="POST" id="loginForm" novalidate>
        <fieldset class="mb-6 space-y-5">
            <legend class="sr-only">Login Credentials</legend>
            
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Address</label>
                <input type="email" id="email" name="email" required aria-required="true" autocomplete="email" autofocus aria-describedby="emailError"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-500 transition-colors">
                <p id="emailError" class="text-sm text-red-600 dark:text-red-400 mt-1 hidden" role="alert" aria-live="polite"></p>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                <input type="password" id="password" name="password" required aria-required="true" autocomplete="current-password" aria-describedby="passwordError"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-500 transition-colors">
                <p id="passwordError" class="text-sm text-red-600 dark:text-red-400 mt-1 hidden" role="alert" aria-live="polite"></p>
            </div>
        </fieldset>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 text-white font-semibold flex justify-center px-4 py-3 rounded-lg transition-colors">
            Log In
        </button>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Don't have an account yet? 
                <a href="<?php echo BASE_URL; ?>register.php" class="text-blue-600 dark:text-blue-400 hover:underline font-medium focus:outline-none focus:ring-2 focus:ring-blue-300 rounded">Sign up</a>
            </p>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const emailError = document.getElementById('emailError');
    const passwordError = document.getElementById('passwordError');

    form.addEventListener('submit', (e) => {
        let isValid = true;
        
        // Email validation
        const emailValue = emailInput.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailValue) {
            showError(emailInput, emailError, "Email address is required.");
            isValid = false;
        } else if (!emailRegex.test(emailValue)) {
            showError(emailInput, emailError, "Please enter a valid email address.");
            isValid = false;
        } else {
            clearError(emailInput, emailError);
        }

        // Password validation
        if (!passwordInput.value) {
            showError(passwordInput, passwordError, "Password is required.");
            isValid = false;
        } else {
            clearError(passwordInput, passwordError);
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
