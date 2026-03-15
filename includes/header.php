<?php
// Start session strictly before any HTML output, as per security standards
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/flash.php';
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EquiWork | Adaptive Careers & Inclusive Workspaces</title>

    <!-- Bespoke Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                    colors: {
                        // High-contrast, WCAG-compliant Brand color mapped to 'blue'
                        blue: {
                            50: '#f0fdfa',
                            100: '#cffafe',
                            200: '#a5f3fc',
                            300: '#67e8f9',
                            400: '#22d3ee',
                            500: '#06b6d4',
                            600: '#0891b2', // Minimum contrast standard for links
                            700: '#0e7490', // Primary high-contrast CTAs
                            800: '#155e75',
                            900: '#164e63',
                            950: '#083344',
                        },
                        // Global override: Mapping 'gray' to deep sophisticated carbon
                        gray: {
                            50: '#fafafa',
                            100: '#f4f4f5',
                            200: '#e4e4e7',
                            300: '#d4d4d8',
                            400: '#a1a1aa',
                            500: '#71717a',
                            600: '#52525b',
                            700: '#3f3f46',
                            800: '#27272a',
                            900: '#18181b',
                            950: '#09090b',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Prevents FOUC (Flash of Unstyled Content) for Dark Mode -->
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100 transition-colors duration-200 min-h-screen flex flex-col font-sans tracking-tight">
    
    <!-- Accessible Skip Link -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:p-4 focus:bg-white dark:focus:bg-gray-900 focus:text-blue-700 dark:focus:text-blue-400 focus:z-50 focus:ring-4 focus:ring-blue-400 focus:outline-none rounded-br-lg shadow-lg font-medium transition-all">
        Skip to main content
    </a>

    <!-- Sticky, Premium Navbar -->
    <header class="sticky top-0 z-50 w-full backdrop-blur-xl bg-white/75 dark:bg-gray-950/75 border-b border-gray-200/80 dark:border-white/10 shadow-sm transition-colors duration-300">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex flex-wrap justify-between items-center" aria-label="Main Navigation">
            
            <!-- Brand Logo -->
            <a href="<?php echo BASE_URL; ?>index.php" class="flex items-center gap-2 text-xl font-bold text-gray-900 dark:text-white focus:outline-none focus-visible:ring-4 focus-visible:ring-blue-400 rounded-lg p-1 transition-all group">
                <span class="tracking-tight hidden sm:block">Equi<span class="text-blue-700 dark:text-blue-400">Work</span></span>
            </a>

            <div class="flex items-center gap-2 sm:gap-4">
                <!-- Theme Toggle Button -->
                <button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus-visible:ring-4 focus-visible:ring-blue-400/50 rounded-xl text-sm p-2 transition-all border border-transparent dark:hover:border-gray-700" aria-label="Toggle Dark Mode">
                    <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                    <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                </button>

                <?php if (isset($_SESSION['user_id'])): ?>
                    
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
                        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="text-sm font-semibold text-gray-700 dark:text-gray-300 hover:text-red-600 dark:hover:text-red-400 focus:outline-none focus:ring-4 focus:ring-red-300 rounded-lg px-4 py-2 transition-colors">Admin Panel</a>
                    <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'Employer'): ?>
                        <a href="<?php echo BASE_URL; ?>employer_dashboard.php" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-700 dark:hover:text-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-400 rounded-lg px-4 py-2 transition-colors">Dashboard</a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>jobs.php" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-700 dark:hover:text-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-400 rounded-lg px-4 py-2 transition-colors">Available Roles</a>
                    <?php endif; ?>

                    <div class="h-6 w-px bg-gray-300 dark:bg-gray-700 hidden sm:block"></div>

                    <a href="<?php echo BASE_URL; ?>actions/logout.php" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white focus:outline-none focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-700 rounded-lg px-4 py-2 transition-colors">Log Out</a>

                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>login.php" class="text-sm font-semibold text-gray-700 dark:text-gray-200 hover:text-blue-700 dark:hover:text-blue-400 focus:outline-none focus-visible:ring-4 focus-visible:ring-blue-400 rounded-lg px-4 py-2 transition-all">Sign In</a>
                    <a href="<?php echo BASE_URL; ?>register.php" class="bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-400 dark:bg-blue-600 dark:hover:bg-blue-500 dark:focus:ring-blue-500/50 text-white font-semibold px-5 py-2.5 rounded-xl transition-all shadow-sm hover:shadow text-sm border border-transparent">Join Platform</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main id="main-content" class="flex-grow w-full">
        <?php display_flash_messages(); ?>
