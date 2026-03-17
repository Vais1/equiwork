<?php
// profile_update.php
// Dual-Role Profile Update Form - Employer or Seeker

require_once 'includes/db.php';
require_once 'includes/auth_check.php';

// Users must be logged in to update profile
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'login.php');
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

require_once 'includes/header.php';
?>

<div class="max-w-2xl mx-auto mt-12 bg-surface border border-border rounded-xl shadow-sm p-6 hover:-translate-y-1 hover:shadow-lg transition-all duration-300 ease-in-out">
    <h1 class="text-3xl font-bold text-text mb-2 font-heading">My Profile</h1>
    <p class="text-muted mb-8">Update your <?php echo strtolower($role); ?> account details and preferences.</p>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative shadow-sm" role="alert" aria-live="polite">
            <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES); unset($_SESSION['flash_success']); ?></span>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_errors'])): ?>
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative shadow-sm" role="alert" aria-live="assertive">
            <ul class="list-disc pl-5">
            <?php foreach ($_SESSION['flash_errors'] as $err): ?>
                <li><?php echo htmlspecialchars($err, ENT_QUOTES); ?></li>
            <?php endforeach; unset($_SESSION['flash_errors']); ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?php echo BASE_URL; ?>actions/process_profile.php" method="POST" id="profileForm" novalidate>
        
        <fieldset class="mb-8 space-y-6">
            <legend class="text-lg font-semibold text-text mb-4 border-b border-border pb-2 w-full">Basic Information</legend>
            
            <div class="form-group relative">
                <label for="username" class="block text-sm font-medium text-text mb-1">
                    <?php echo ($role === 'Employer') ? 'Company Name' : 'Full Name'; ?>
                </label>
                <input type="text" id="username" name="username" required aria-required="true"
                    value="<?php echo htmlspecialchars($current_user['username'], ENT_QUOTES); ?>"
                    class="w-full px-4 py-2 border border-border rounded-lg bg-bg text-text focus:outline-none focus:ring-4 focus:ring-accent/50 transition-colors"
                    aria-describedby="usernameError">
                <p id="usernameError" class="mt-1 text-sm text-red-600 hidden" aria-live="polite"></p>
            </div>

            <div class="form-group relative">
                <label for="email" class="block text-sm font-medium text-text mb-1">Email Address</label>
                <input type="email" id="email" name="email" required aria-required="true"
                    value="<?php echo htmlspecialchars($current_user['email'], ENT_QUOTES); ?>"
                    class="w-full px-4 py-2 border border-border rounded-lg bg-bg text-text focus:outline-none focus:ring-4 focus:ring-accent/50 transition-colors"
                    aria-describedby="emailError">
                <p id="emailError" class="mt-1 text-sm text-red-600 hidden" aria-live="polite"></p>
            </div>
        </fieldset>

        <fieldset class="mb-8 space-y-6">
            <legend class="text-lg font-semibold text-text mb-4 border-b border-border pb-2 w-full">Security</legend>
            <p class="text-sm text-muted mb-4">Leave password blank if you do not wish to change it.</p>
            
            <div class="form-group relative">
                <label for="password" class="block text-sm font-medium text-text mb-1">New Password</label>
                <input type="password" id="password" name="password" minlength="8"
                    class="w-full border border-border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-accent focus:border-transparent bg-surface text-text transition-colors duration-150"
                    aria-describedby="passwordError">
                <p id="passwordError" class="mt-1 text-sm text-red-600 hidden" aria-live="polite"></p>
            </div>
        </fieldset>

        <div class="flex items-center justify-end border-t border-border pt-6">
            <button type="submit" class="bg-accent text-white px-5 py-2.5 rounded-lg font-semibold active:scale-95 transition-all duration-300 ease-in-out hover:opacity-80 focus:outline-none focus:ring-2 focus:ring-accent/50">
                Save Profile
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('profileForm');
    
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Fields
        const username = document.getElementById('username');
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        
        // Errors
        const usernameError = document.getElementById('usernameError');
        const emailError = document.getElementById('emailError');
        const passwordError = document.getElementById('passwordError');

        // Helper to show error
        function showError(input, errorElement, msg) {
            isValid = false;
            input.setAttribute('aria-invalid', 'true');
            input.classList.add('border-red-500', '');
            errorElement.textContent = msg;
            errorElement.classList.remove('hidden');
        }

        // Helper to clear error
        function clearError(input, errorElement) {
            input.removeAttribute('aria-invalid');
            input.classList.remove('border-red-500', '');
            errorElement.classList.add('hidden');
            errorElement.textContent = '';
        }

        // Reset state
        [username, email, password].forEach(el => clearError(el, document.getElementById(el.id + 'Error')));

        // 1. Username Regex (prevent pure empty spacing)
        if (username.value.trim() === '') {
            showError(username, usernameError, 'Name field cannot be left entirely blank.');
        }

        // 2. Email Formatting check
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email.value.trim())) {
            showError(email, emailError, 'Ensure the email matches a recognizable format e.g: user@domain.com.');
        }

        // 3. Optional Password check
        if (password.value !== '' && password.value.length < 8) {
            showError(password, passwordError, 'Password must be at least 8 characters if you are attempting to change it.');
        }

        // Stop submission logically without sending bad strings to DB
        if (!isValid) {
            e.preventDefault();
            // Move focus to first invalid element for screen readers
            const firstInvalid = form.querySelector('[aria-invalid="true"]');
            if (firstInvalid) firstInvalid.focus();
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
