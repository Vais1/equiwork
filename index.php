<?php
// Project: EquiWork
// Module: Landing Page
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/header.php';
?>

<section class="max-w-5xl mx-auto py-12 md:py-20 text-center px-4 sm:px-6 lg:px-8">
    <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-blue-50/50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 text-sm font-semibold mb-10 ring-1 ring-blue-600/10 dark:ring-blue-400/20 shadow-sm backdrop-blur-sm">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
        <span>Advancing SDG 08: Decent Work for All</span>
    </div>

    <h1 class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white leading-[1.1] mb-6 tracking-tight">
        Build a resilient career <br class="hidden md:block"/>
        <span class="text-transparent bg-clip-text bg-gradient-to-br from-blue-700 to-indigo-600 dark:from-blue-400 dark:to-indigo-400">designed for your reality.</span>
    </h1>

    <p class="text-lg md:text-xl text-gray-600 dark:text-gray-300 mb-10 max-w-2xl mx-auto leading-relaxed font-medium">
        EquiWork connects uniquely skilled professionals with adaptable, remote-first organizations. We match based on actual accessibility accommodations—because your environment should adjust to you, not the other way around.
    </p>

    <div class="flex flex-col sm:flex-row justify-center items-center gap-5 sm:gap-6">
        <a href="<?php echo BASE_URL; ?>register.php?role=Seeker" class="w-full sm:w-auto bg-blue-700 hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-400/50 text-white font-bold text-lg px-8 py-4 rounded-xl transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5 mt-2">
            Explore Opportunities
        </a>
        <a href="<?php echo BASE_URL; ?>register.php?role=Employer" class="w-full sm:w-auto bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-900 dark:text-gray-100 font-bold text-lg px-8 py-4 rounded-xl transition-all border border-gray-200/80 dark:border-gray-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 mt-2">
            Partner with Us
        </a>
    </div>
</section>



<?php require_once 'includes/footer.php'; ?>
