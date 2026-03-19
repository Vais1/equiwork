    </main>

    <footer class="bg-surface border-t border-border mt-auto transition-all duration-200 duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 md:py-4">
            <div class="flex flex-col md:flex-row justify-between items-center text-sm text-muted gap-4 md:gap-5">
                <div class="flex flex-col md:flex-row items-center gap-4 text-center md:text-left">
                    <p class="font-medium">&copy; <?php echo date('Y'); ?> EquiWork.</p>
                    <span class="hidden md:inline text-muted/50" aria-hidden="true">&bull;</span>
                    <p>Advancing SDG 08: Decent Work & Economic Growth.</p>
                </div>
                <div class="flex flex-wrap justify-center gap-4 md:gap-5 font-medium">
                    <a href="<?php echo BASE_URL; ?>register.php?role=Seeker" class="transition-all duration-200 duration-300 focus:outline-none focus:underline">For Job Seekers</a>
                    <a href="<?php echo BASE_URL; ?>register.php?role=Employer" class="transition-all duration-200 duration-300 focus:outline-none focus:underline">For Employers</a>
                    <a href="<?php echo BASE_URL; ?>project-overview.md#6-frontend--accessibility-standards" class="transition-all duration-200 duration-300 focus:outline-none focus:ring-2 focus:ring-accent/50 rounded-md underline decoration-transparent">Accessibility Standards</a>
                    <a href="<?php echo BASE_URL; ?>project-overview.md#5-security-standards" class="transition-all duration-200 duration-300 focus:outline-none focus:ring-2 focus:ring-accent/50 rounded-md underline decoration-transparent">Data Privacy</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Theme Toggle Logic & Custom UI Controls -->
    <script src="<?php echo BASE_URL; ?>assets/js/theme.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/custom-controls.js"></script>
</body>
</html>

