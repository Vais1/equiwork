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
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"DM Sans"', 'ui-sans-serif', 'sans-serif'],
                        heading: ['Outfit', 'ui-sans-serif', 'sans-serif'],
                    },
                    colors: {
                        bg: 'rgb(var(--color-bg) / <alpha-value>)',
                        surface: 'rgb(var(--color-surface) / <alpha-value>)',
                        border: 'rgb(var(--color-border) / <alpha-value>)',
                        text: 'rgb(var(--color-text) / <alpha-value>)',
                        muted: 'rgb(var(--color-muted) / <alpha-value>)',
                        accent: 'rgb(var(--color-accent) / <alpha-value>)',
                        'accent-hover': 'rgb(var(--color-accent-hover) / <alpha-value>)',
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

    <style>
        :root {
            --color-bg: 248 250 252;
            --color-surface: 255 255 255;
            --color-border: 226 232 240;
            --color-text: 15 23 42;
            --color-muted: 100 116 139;
            --color-accent: 37 99 235;
            --color-accent-hover: 29 78 216;
        }
        .dark {
            --color-bg: 15 23 42;
            --color-surface: 30 41 59;
            --color-border: 51 65 85;
            --color-text: 248 250 252;
            --color-muted: 148 163 184;
            --color-accent: 59 130 246;
            --color-accent-hover: 96 165 250;
        }
    </style>
</head>
<body class="bg-bg text-text transition-colors duration-200 min-h-screen flex flex-col font-sans tracking-tight">
    
    <!-- Accessible Skip Link -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:p-4 focus:bg-surface focus:text-accent focus:z-50 focus:ring-4 focus:ring-accent focus:outline-none rounded-br-lg shadow-lg font-medium transition-all">
        Skip to main content
    </a>

    <header class="sticky top-0 z-50 w-full backdrop-blur-md bg-bg/80 border-b border-border shadow-sm transition-colors duration-300">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between py-4" aria-label="Main Navigation">
            
            <a href="<?php echo BASE_URL; ?>index.php" class="flex items-center gap-1 text-lg font-bold text-text focus:outline-none focus-visible:ring-4 focus-visible:ring-accent rounded-lg transition-all group leading-none">
                <span class="tracking-tight hidden sm:block">Equi<span class="text-accent">Work</span></span>
            </a>

            <div class="flex items-center gap-2 sm:gap-3">
                <button id="theme-toggle" type="button" class="text-muted focus:outline-none focus-visible:ring-4 focus-visible:ring-accent/50 rounded-lg p-0.5 transition-all border border-transparent flex items-center justify-center" aria-label="Toggle Dark Mode">
                    <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                    <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                </button>

                <?php if (isset($_SESSION['user_id'])): ?>

                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
                        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="text-sm font-semibold text-text focus:outline-none focus:ring-4 focus:ring-red-300 rounded px-2 py-0.5 transition-colors whitespace-nowrap leading-none flex items-center">Admin Panel</a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>jobs.php" class="text-sm font-medium text-text focus:outline-none focus:ring-4 focus:ring-accent rounded px-2 py-0.5 transition-colors whitespace-nowrap leading-none flex items-center">Available Roles</a>
                    <?php endif; ?>

                    <div class="h-4 w-px bg-border hidden sm:block"></div>

                    <a href="<?php echo BASE_URL; ?>actions/logout.php" class="text-sm font-medium text-muted focus:outline-none focus:ring-4 focus:ring-border rounded px-2 py-0.5 transition-colors whitespace-nowrap leading-none flex items-center">Log Out</a>

                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>login.php" class="text-sm font-semibold text-text focus:outline-none focus-visible:ring-4 focus-visible:ring-accent rounded px-2 py-0.5 transition-all whitespace-nowrap leading-none flex items-center">Sign In</a>
                    <a href="<?php echo BASE_URL; ?>register.php" class="bg-accent text-white px-5 py-2.5 rounded-lg font-semibold active:scale-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-accent/50 whitespace-nowrap leading-none flex items-center">Join Platform</a>
            </div>
        </nav>
    </header>

    <main id="main-content" class="flex-grow w-full">
        <?php display_flash_messages(); ?>
