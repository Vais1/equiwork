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

<div class="max-w-md mx-auto mt-8 md:mt-12 bg-surface border border-border rounded-xl shadow-sm p-4 md:p-6 hover:-translate-y-1 hover:shadow-lg transition-all duration-300 ease-in-out">
    <div class="flex justify-center mb-5 md:mb-6">
        <div class="w-16 h-16 bg-accent/10 text-accent rounded-full flex items-center justify-center">
            <svg aria-hidden="true" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
        </div>
    </div>
    <h1 class="text-2xl md:text-3xl font-extrabold text-text mb-2 md:mb-3 text-center tracking-tight font-heading">Access Account</h1>
    <p class="text-muted text-center mb-6 md:mb-8 leading-relaxed font-medium">Log into your EquiWork platform securely to manage your profile and connections.</p>

    <form action="<?php echo BASE_URL; ?>actions/process_login.php" method="POST" id="loginForm" novalidate>
        <fieldset class="mb-5 md:mb-6 space-y-4 md:space-y-5">
            <legend class="sr-only">Login Credentials</legend>
            
            <div>
                <label for="email" class="block text-sm font-medium text-text mb-1">Email Address</label>
                <input type="email" id="email" name="email" required aria-required="true" autocomplete="email" autofocus aria-describedby="emailError"
                    class="w-full border border-border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-accent focus:border-transparent bg-surface text-text transition-colors duration-150">
                <p id="emailError" class="text-sm text-red-600 mt-1 hidden" role="alert" aria-live="polite"></p>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-text mb-1">Password</label>
                <input type="password" id="password" name="password" required aria-required="true" autocomplete="current-password" aria-describedby="passwordError"
                    class="w-full border border-border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-accent focus:border-transparent bg-surface text-text transition-colors duration-150">
                <p id="passwordError" class="text-sm text-red-600 mt-1 hidden" role="alert" aria-live="polite"></p>
            </div>
        </fieldset>

        <button type="submit" class="w-full bg-accent text-white px-4 py-2.5 min-w-[44px] rounded-lg font-semibold active:scale-95 transition-all duration-300 ease-in-out hover:opacity-80 focus:outline-none focus:ring-2 focus:ring-accent/50">
            Log In
        </button>

        <div class="mt-5 md:mt-6 text-center">
            <p class="text-sm text-muted">
                Don't have an account yet? 
                <a href="<?php echo BASE_URL; ?>register.php" class="text-accent font-medium focus:outline-none focus:ring-2 focus:ring-accent/50 rounded transition-all duration-300 ease-in-out hover:opacity-80">Sign up</a>
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
