<?php
// Project: EquiWork
// Module: Landing Page
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/header.php';
?>

<section class="max-w-5xl mx-auto py-16 md:py-24 text-center px-4 lg:px-0">
    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-sm font-semibold mb-8 ring-1 ring-blue-600/20 dark:ring-blue-400/20">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
        <span>Promoting SDG 08: Decent Work & Economic Growth</span>
    </div>

    <h1 class="text-5xl md:text-7xl font-extrabold text-gray-900 dark:text-white leading-tight mb-8 tracking-tight">
        Build a career without <br class="hidden md:block"/>
        <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400">architectural barriers.</span>
    </h1>

    <p class="text-lg md:text-2xl text-gray-600 dark:text-gray-300 mb-12 max-w-3xl mx-auto leading-relaxed">
        EquiWork bridges the gap between talented individuals with physical disabilities and forward-thinking organizations that prioritize accessible, remote-first workspaces. 
    </p>

    <div class="flex flex-col sm:flex-row justify-center items-center gap-4 sm:gap-6">
        <a href="<?php echo BASE_URL; ?>register.php?role=Seeker" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500 focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-500/50 text-white font-bold text-lg px-8 py-4 rounded-full transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5">
            Find an Accessible Role
        </a>
        <a href="<?php echo BASE_URL; ?>register.php?role=Employer" class="w-full sm:w-auto bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-900 dark:text-white font-bold text-lg px-8 py-4 rounded-full transition-all border border-gray-200 dark:border-gray-700 focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5">
            Hire Diverse Talent
        </a>
    </div>
</section>

<section class="mt-8 mb-20 grid grid-cols-1 md:grid-cols-3 gap-8">
    <div class="bg-white dark:bg-gray-800/50 p-8 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 hover:border-blue-200 dark:hover:border-blue-900/50 transition-colors group">
        <div class="w-14 h-14 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
        </div>
        <h3 class="text-xl font-bold mb-3 text-gray-900 dark:text-white tracking-tight">Granular Matching</h3>
        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">Don't guess if a role suits your needs. Filter positions precisely by the adaptive technologies and asynchronous workflows they support.</p>
    </div>

    <div class="bg-white dark:bg-gray-800/50 p-8 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 hover:border-blue-200 dark:hover:border-blue-900/50 transition-colors group">
        <div class="w-14 h-14 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
        </div>
        <h3 class="text-xl font-bold mb-3 text-gray-900 dark:text-white tracking-tight">Verified Inclusivity</h3>
        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">We carefully onboard employers who demonstrate authentic, robust commitments to establishing universally accessible team environments.</p>
    </div>

    <div class="bg-white dark:bg-gray-800/50 p-8 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 hover:border-blue-200 dark:hover:border-blue-900/50 transition-colors group">
        <div class="w-14 h-14 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <h3 class="text-xl font-bold mb-3 text-gray-900 dark:text-white tracking-tight">Equitable Growth</h3>
        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">Directly contributing to UN Sustainable Development Goal 08 by expanding pathways to full, productive, and decent employment for everyone.</p>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
