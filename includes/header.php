<?php
// Start session strictly before any HTML output, as per security standards
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/flash.php';
require_once __DIR__ . '/csrf.php';
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <title>EquiWork</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/tailwind.css">

    <!-- Prevents FOUC (Flash of Unstyled Content) for Dark Mode -->
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="bg-bg text-text min-h-screen flex flex-col font-sans tracking-tight antialiased selection:bg-accent selection:text-bg">
    
    <!-- Accessible Skip Link -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:p-4 focus:bg-surface focus:text-accent focus:z-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent rounded-br-lg shadow-sm font-medium transition-all">
        Skip to main content
    </a>

    <header class="sticky top-0 z-50 w-full bg-surface/90 backdrop-blur-md border-b border-border shadow-sm transition-colors duration-200">
        <nav class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between" aria-label="Main Navigation">
            
            <a href="<?php echo BASE_URL; ?>index.php" class="flex items-center gap-1 text-xl font-semibold tracking-tight text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-accent rounded-md transition-all">
                Equi<span class="text-muted font-light">Work</span>
            </a>

            <div class="flex items-center gap-4 flex-wrap justify-end">
                <button id="theme-toggle" type="button" class="btn-icon text-muted" aria-label="Toggle Dark Mode">
                    <svg id="theme-toggle-dark-icon" class="hidden w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                    <svg id="theme-toggle-light-icon" class="hidden w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                </button>

                <?php if (isset($_SESSION['user_id'])): ?>

                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
                        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="text-sm font-medium text-text rounded-md px-2 py-1 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">Admin</a>
                    <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'Employer'): ?>
                        <a href="<?php echo BASE_URL; ?>employer/dashboard.php" class="text-sm font-medium text-text rounded-md px-2 py-1 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">Dashboard</a>
                        <a href="<?php echo BASE_URL; ?>employer/post_job.php" class="text-sm font-medium text-text rounded-md px-2 py-1 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">Post Job</a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>jobs.php" class="text-sm font-medium text-text rounded-md px-2 py-1 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">Jobs</a>
                        <a href="<?php echo BASE_URL; ?>seeker/dashboard.php" class="text-sm font-medium text-text rounded-md px-2 py-1 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">Applications</a>
                        <a href="<?php echo BASE_URL; ?>user/profile.php" class="text-sm font-medium text-text rounded-md px-2 py-1 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">Profile</a>
                    <?php endif; ?>

                    <div class="h-4 w-px bg-border hidden sm:block"></div>

                    <a href="<?php echo BASE_URL; ?>actions/logout.php" class="text-sm font-medium text-muted rounded-md px-2 py-1 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">Log Out</a>

                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>auth/login.php" class="text-sm font-medium text-text rounded-md px-2 py-1 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">Sign In</a>
                    <a href="<?php echo BASE_URL; ?>auth/register.php" class="btn-primary btn-sm">Get Started</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main id="main-content" class="flex-grow w-full page-shell">
        <?php display_flash_messages(); ?>

