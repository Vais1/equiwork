    </main>

    <footer class="bg-surface border-t border-border mt-auto transition-colors duration-200">
        <div class="max-w-5xl mx-auto px-6 py-8">
            <div class="flex flex-col md:flex-row justify-between items-center text-sm text-muted gap-6">
                <div class="flex flex-col md:flex-row items-center gap-3 text-center md:text-left">
                    <p class="font-medium text-text">&copy; <?php echo date('Y'); ?> EquiWork.</p>
                    <span class="hidden md:inline text-border" aria-hidden="true">|</span>
                    <p>Advancing SDG 08: Decent Work & Economic Growth.</p>
                </div>
                <div class="flex flex-wrap justify-center gap-6 font-medium">
                    <a href="<?php echo BASE_URL; ?>auth/register.php?role=Seeker" class="transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-accent rounded-sm">For Job Seekers</a>
                    <a href="<?php echo BASE_URL; ?>auth/register.php?role=Employer" class="transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-accent rounded-sm">For Employers</a>
                    <a href="<?php echo BASE_URL; ?>project-overview.md" class="transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-accent rounded-sm">Privacy & Access</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Theme Toggle Logic & Custom UI Controls -->
    <script src="<?php echo BASE_URL; ?>assets/js/theme.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/custom-controls.js"></script>
</body>
</html>

