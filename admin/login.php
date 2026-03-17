<?php
require_once '../includes/config.php';

// If already logged in
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') {
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
        exit;
    } else {
        // Standard user shouldn't be here, kick them out to their area
        header('Location: ' . BASE_URL . 'jobs.php');
        exit;
    }
}

require_once '../includes/db.php';
require_once '../includes/flash.php';

// Support gentle session-timeout redirection message explicitly for Admins
if (isset($_GET['reason']) && $_GET['reason'] === 'timeout') {
    set_flash_message('warning', 'Your secure session has expired. Please re-authenticate.');
}

// Handle login logic right here to isolate Admin traffic, enforcing security
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $conn->prepare("SELECT user_id, password_hash, role_type FROM users WHERE email = ? AND role_type = 'Admin' LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password_hash'])) {
                // Securely set session state
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role_type'];
                $_SESSION['last_action'] = time();

                // Regenerate session ID to prevent fixation attacks
                session_regenerate_id(true);

                header('Location: ' . BASE_URL . 'admin/dashboard.php');
                exit;
            } else {
                set_flash_message('error', 'Invalid email or password.');
            }
        } else {
            // Do not reveal if the account exists but isn't an admin
            set_flash_message('error', 'Invalid email or password.');
        }
    } else {
        set_flash_message('error', 'Please provide a valid email and password.');
    }
    
    // Redirect back to GET to show flash message properly
    header('Location: ' . BASE_URL . 'admin/login.php');
    exit;
}

require_once '../includes/header.php';
?>

<div class="max-w-md mx-auto mt-8 md:mt-12 bg-surface border border-red-200 rounded-xl shadow-sm p-4 md:p-6 hover:-translate-y-1 hover:shadow-lg transition-all duration-300 ease-in-out text-white relative overflow-hidden">
    <div class="absolute top-0 left-0 w-full h-2 bg-red-600"></div>

    <div class="text-center mb-6 md:mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-600/20 text-red-500 mb-4">
            <svg aria-hidden="true" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"></path></svg>
        </div>
        <h1 class="text-2xl font-bold mb-2">Restricted Area</h1>
        <p class="text-muted">EquiWork Administrator Access</p>
    </div>

    <form action="<?php echo BASE_URL; ?>admin/login.php" method="POST" id="adminLoginForm" novalidate>
        <fieldset class="mb-5 md:mb-6 space-y-4">
            <legend class="sr-only">Administrator Login</legend>

            <div>
                <label for="email" class="block text-sm font-medium text-muted mb-1">Admin Email</label>
                <input type="email" id="email" name="email" required aria-required="true" autocomplete="email" autofocus aria-describedby="emailError"
                    class="w-full px-4 py-2 border border-border bg-surface text-white rounded-lg focus:outline-none focus:ring-4 focus:ring-red-500/50 transition-colors placeholder-gray-500">
                <p id="emailError" class="text-sm text-red-400 mt-1 hidden" role="alert" aria-live="polite"></p>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-muted mb-1">Passphrase</label>
                <input type="password" id="password" name="password" required aria-required="true" autocomplete="current-password" aria-describedby="passwordError"
                    class="w-full px-4 py-2 border border-border bg-surface text-white rounded-lg focus:outline-none focus:ring-4 focus:ring-red-500/50 transition-colors placeholder-gray-500">
                <p id="passwordError" class="text-sm text-red-400 mt-1 hidden" role="alert" aria-live="polite"></p>
            </div>
        </fieldset>

        <button type="submit" class="w-full bg-red-600 focus:ring-4 focus:ring-red-500/50 text-white font-semibold flex justify-center px-4 py-3 rounded-lg transition-colors">
            Authenticate
        </button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('adminLoginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const emailError = document.getElementById('emailError');
    const passwordError = document.getElementById('passwordError');

    // Secure, standalone DOM validation for Admin
    form.addEventListener('submit', (e) => {
        let isValid = true;

        const emailValue = emailInput.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailValue) {
            showError(emailInput, emailError, "Email address is required.");
            isValid = false;
        } else if (!emailRegex.test(emailValue)) {
            showError(emailInput, emailError, "Please enter a valid email format.");
            isValid = false;
        } else {
            clearError(emailInput, emailError);
        }

        if (!passwordInput.value) {
            showError(passwordInput, passwordError, "Passphrase is required.");
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
        input.classList.add('border-red-500', 'focus:ring-red-500/50');
        input.classList.remove('border-border');
        errorElement.textContent = message;
        errorElement.classList.remove('hidden');
    }

    function clearError(input, errorElement) {
        input.removeAttribute('aria-invalid');
        input.classList.remove('border-red-500', 'focus:ring-red-500/50');
        input.classList.add('border-border');
        errorElement.textContent = "";
        errorElement.classList.add('hidden');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>