<?php
// Project: EquiWork
// Module: Landing Page
session_start();
require_once 'includes/db.php';
require_once 'includes/header.php';
?>

<section class="max-w-4xl mx-auto py-12 md:py-20 text-center">
    <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 dark:text-white leading-tight mb-6">
        Bridging the Gap to <br/>
        <span class="text-blue-600 dark:text-blue-400">Accessible Employment</span>
    </h1>
    
    <p class="text-lg md:text-xl text-gray-700 dark:text-gray-300 mb-10 max-w-2xl mx-auto">
        EquiWork connects individuals with physical disabilities to quality, adaptive remote employment opportunities. Discover employers who genuinely support specialized work environments.
    </p>

    <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
        <a href="<?php echo BASE_URL; ?>register.php?role=Seeker" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 text-white font-semibold px-8 py-3 rounded-lg transition-colors shadow-lg">
            I'm a Job Seeker
        </a>
        <a href="<?php echo BASE_URL; ?>register.php?role=Employer" class="w-full sm:w-auto bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-blue-600 dark:text-blue-400 font-semibold px-8 py-3 rounded-lg transition-colors border-2 border-blue-600 dark:border-blue-400 focus:ring-4 focus:ring-blue-300 shadow-lg">
            I'm an Employer
        </a>
    </div>
</section>

<section class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8">
    <div class="bg-white dark:bg-gray-800 p-8 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="w-12 h-12 bg-blue-100 hidden md:flex text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg items-center justify-center mb-6">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
        </div>
        <h3 class="text-xl font-bold mb-3 dark:text-white">Transparent Opportunities</h3>
        <p class="text-gray-600 dark:text-gray-400">Filter jobs by the specific accommodations and physical requirements you need to succeed.</p>
    </div>

    <div class="bg-white dark:bg-gray-800 p-8 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="w-12 h-12 bg-blue-100 hidden md:flex text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg items-center justify-center mb-6">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
        </div>
        <h3 class="text-xl font-bold mb-3 dark:text-white">Inclusive Employers</h3>
        <p class="text-gray-600 dark:text-gray-400">Connect with partners committed to creating adaptive and genuinely supportive workspaces.</p>
    </div>

    <div class="bg-white dark:bg-gray-800 p-8 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="w-12 h-12 bg-blue-100 hidden md:flex text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg items-center justify-center mb-6">
             <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <h3 class="text-xl font-bold mb-3 dark:text-white">SDG 08 Alignment</h3>
        <p class="text-gray-600 dark:text-gray-400">Contributing to sustained, inclusive, and sustainable economic growth and decent work for all.</p>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
