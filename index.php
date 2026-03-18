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
        header('Location: ' . BASE_URL . 'employer_dashboard.php');
    } else {
        header('Location: ' . BASE_URL . 'jobs.php');
    }
    exit;
}

require_once 'includes/header.php';
?>

<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-16 text-center">
    <div class="inline-flex items-center gap-2.5 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-bg text-text border border-border mb-10">
        <svg aria-hidden="true" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
        <span>Advancing SDG 08: Decent Work for All</span>
    </div>

    <h1 class="text-4xl md:text-5xl font-bold text-text leading-[1.1] mb-6 tracking-tight font-heading">
        Build a resilient career <br class="hidden md:block"/>
        <span class="text-transparent bg-clip-text bg-gradient-to-br from-blue-700 to-indigo-600">designed for your reality.</span>
    </h1>

    <p class="text-lg md:text-xl text-muted mb-10 max-w-2xl mx-auto leading-relaxed font-medium">
        EquiWork connects uniquely skilled professionals with adaptable, remote-first organizations. We match based on actual accessibility accommodations—because your environment should adjust to you, not the other way around.
    </p>

    <div class="flex flex-col sm:flex-row justify-center items-center gap-4 sm:gap-5">
        <a href="<?php echo BASE_URL; ?>register.php" class="w-full sm:w-auto bg-accent text-white px-4 py-2 min-w-[44px] rounded-lg font-semibold active:scale-95 transition-all duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-accent/50 text-center">
            Register
        </a>
        <a href="<?php echo BASE_URL; ?>login.php" class="w-full sm:w-auto border border-accent text-accent px-4 py-2 min-w-[44px] rounded-lg font-semibold transition-all duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-accent/50 active:scale-95 text-center">
            Sign In
        </a>
    </div>
</section>



<?php require_once 'includes/footer.php'; ?>

