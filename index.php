<?php
// Project: EquiWork
// Module: Landing Page
require_once 'includes/config.php';
require_once 'includes/db.php';
// If already logged in, bypass the landing page
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') {
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
    } elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'Employer') {
        header('Location: ' . BASE_URL . 'employer/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . 'jobs.php');
    }
    exit;
}

require_once 'includes/header.php';
?>

<div class="container-main flex flex-col items-center justify-center text-center">
    <div class="badge mb-8">
        <svg aria-hidden="true" class="w-3.5 h-3.5 mr-1.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
        <span>SDG 08: Decent Work for All</span>
    </div>

    <h1 class="text-4xl md:text-6xl font-bold text-text leading-tight tracking-tighter mb-6 max-w-3xl">
        Build a resilient career <br class="hidden md:block"/>
        <span class="text-muted">designed for your reality.</span>
    </h1>

    <p class="text-body text-lg md:text-xl text-muted mb-10 max-w-2xl">
        EquiWork connects professionals with adaptable organizations. We match based on actual accessibility accommodations because your environment should adjust to you.
    </p>

    <div class="flex flex-col sm:flex-row justify-center items-center gap-4 w-full sm:w-auto">
        <a href="<?php echo BASE_URL; ?>auth/register.php" class="btn-primary w-full sm:w-auto px-8 py-3 text-base">
            Create Account
        </a>
        <a href="<?php echo BASE_URL; ?>auth/login.php" class="btn-outline w-full sm:w-auto px-8 py-3 text-base">
            Sign In
        </a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

