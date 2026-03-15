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
                        // Global override: Mapping 'blue' to a premium deep 'indigo' and violet blend
                        blue: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5', // Primary Brand (Indigo 600)
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                            950: '#1e1b4b',
                        },
                        // Global override: Mapping 'gray' to sophisticated 'slate'
                        gray: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                            950: '#020617',
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
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:p-4 focus:bg-white dark:focus:bg-gray-900 focus:text-blue-600 dark:focus:text-blue-400 focus:z-50 focus:ring-4 focus:ring-blue-300 focus:outline-none rounded-br-lg shadow-lg font-medium">
        Skip to main content
    </a>

    <!-- Sticky, Premium Navbar -->
    <header class="sticky top-0 z-40 w-full backdrop-blur-xl bg-white/80 dark:bg-gray-900/80 border-b border-gray-200 dark:border-gray-800 shadow-sm transition-colors">
        <nav class="container mx-auto px-4 lg:px-8 py-3.5 flex flex-wrap justify-between items-center" aria-label="Main Navigation">
            
            <!-- Brand Logo -->
            <a href="<?php echo BASE_URL; ?>index.php" class="flex items-center gap-2 text-xl md:text-2xl font-extrabold text-gray-900 dark:text-white focus:outline-none focus:ring-4 focus:ring-blue-300 rounded-lg p-1 transition-transform hover:scale-105 active:scale-95 group">
                <svg class="w-7 h-7 text-blue-600 dark:text-blue-500 group-hover:text-blue-500 transition-colors" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                <span class="tracking-tight">Equi<span class="text-blue-600 dark:text-blue-500">Work</span></span>
            </a>

            <div class="flex items-center space-x-3 md:space-x-5">
                <!-- Theme Toggle Button -->
                <button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:ring-4 focus:ring-blue-300/50 rounded-full text-sm p-2.5 transition-all" aria-label="Toggle Dark Mode">
                    <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                    <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                </button>

                <?php if (isset(['user_id'])): ?>
                    
                    <?php if (isset(['role']) && ['role'] === 'Admin'): ?>
                        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="text-sm font-semibold text-gray-700 dark:text-gray-200 hover:text-red-500 dark:hover:text-red-400 focus:outline-none focus:ring-4 focus:ring-red-300 rounded-lg px-3 py-2 transition-colors">Admin Panel</a>
                    <?php elseif (isset(['role']) && ['role'] === 'Employer'): ?>
                        <a href="<?php echo BASE_URL; ?>employer_dashboard.php" class="text-sm font-semibold text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-300 rounded-lg px-3 py-2 transition-colors">Dashboard</a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>jobs.php" class="text-sm font-semibold text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-300 rounded-lg px-3 py-2 transition-colors">Find Work</a>
                    <?php endif; ?>

                    <div class="h-6 border-l border-gray-300 dark:border-gray-700 hidden sm:block"></div>

                    <a href="<?php echo BASE_URL; ?>actions/logout.php" class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-lg px-3 py-2 transition-colors">Sign Out</a>

                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>login.php" class="text-sm font-semibold text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-300 rounded-lg px-4 py-2 transition-colors">Log In</a>
                    <a href="<?php echo BASE_URL; ?>register.php" class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500 focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-500/50 text-white font-semibold px-5 py-2.5 rounded-full transition-all shadow-sm hover:shadow-md text-sm">Join EquiWork</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main id="main-content" class="flex-grow container mx-auto px-4 lg:px-8 py-8 lg:py-12">
        <?php display_flash_messages(); ?>
