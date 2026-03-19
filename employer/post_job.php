<?php
// post_job.php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/flash.php';

// Strict session check for Employer role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employer') {
    set_flash_message('error', 'Unauthorised access. Only employers can post jobs.');
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

// Fetch all available accommodations grouped by category
$accQuery = "SELECT * FROM accommodations ORDER BY category, name";
$accResult = $conn->query($accQuery);
$accommodations = [];
if ($accResult && $accResult->num_rows > 0) {
    while ($row = $accResult->fetch_assoc()) {
        $accommodations[$row['category']][] = $row;
    }
}

require_once '../includes/header.php';
?>

<main class="container-main max-w-3xl">
    <div class="card">
        <h1 class="heading-1 mb-6">Post a New Job</h1>
        
        <form action="<?php echo BASE_URL; ?>actions/process_post_job.php" method="POST" id="postJobForm" novalidate>
            <?php echo csrf_input(); ?>
            <fieldset class="mb-8 space-y-5">
                <legend class="heading-2 mb-4 border-b border-border pb-2 w-full">Job Details</legend>
                
                <div class="form-group">
                    <label for="title" class="form-label">Job Title <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" required
                           class="form-input"
                           aria-describedby="titleError">
                    <p id="titleError" class="text-small text-red-600 hidden mt-1" aria-live="polite">Please enter a job title.</p>
                </div>
                
                <div class="form-group">
                    <label for="location_type" class="form-label">Work Arrangement <span class="text-red-500">*</span></label>
                    <div class="custom-select-container relative w-full" data-name="location_type">
                        <input type="hidden" name="location_type" id="location_type" required>
                        <button type="button" class="form-input text-left flex justify-between items-center" aria-haspopup="listbox" aria-expanded="false" aria-describedby="locationError">
                            <span class="custom-select-text">Select an arrangement...</span>
                            <svg aria-hidden="true" class="w-4 h-4 text-muted pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <ul class="custom-select-list absolute z-10 w-full mt-1 bg-surface border border-border rounded-md shadow-lg max-h-60 overflow-y-auto hidden" role="listbox" tabindex="-1">
                            <li class="px-3 py-2 cursor-pointer text-sm text-text" role="option" aria-selected="true" data-value="">Select an arrangement...</li>
                            <li class="px-3 py-2 cursor-pointer text-sm text-text" role="option" aria-selected="false" data-value="Remote">Remote</li>
                            <li class="px-3 py-2 cursor-pointer text-sm text-text" role="option" aria-selected="false" data-value="Hybrid">Hybrid</li>
                            <li class="px-3 py-2 cursor-pointer text-sm text-text" role="option" aria-selected="false" data-value="On-site">On-site</li>
                        </ul>
                    </div>
                    <p id="locationError" class="text-small text-red-600 hidden mt-1" aria-live="polite">Please select a work arrangement.</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="form-group">
                        <label for="company_name" class="form-label">Company Name <span class="text-xs text-muted font-normal">(Optional, defaults to your profile name)</span></label>
                        <input type="text" id="company_name" name="company_name" class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="employment_type" class="form-label">Employment Type</label>
                        <select id="employment_type" name="employment_type" class="form-input">
                            <option value="Full-time">Full-time</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Contract">Contract</option>
                            <option value="Freelance">Freelance</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="form-group">
                        <label for="state_region" class="form-label">State / Region <span class="text-xs text-muted font-normal">(Optional)</span></label>
                        <input type="text" id="state_region" name="state_region" placeholder="e.g. Kuala Lumpur" class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="salary_min_myr" class="form-label">Min Salary (MYR) <span class="text-xs text-muted font-normal">(Optional)</span></label>
                        <input type="number" step="0.01" min="0" id="salary_min_myr" name="salary_min_myr" placeholder="e.g. 3000" class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="salary_max_myr" class="form-label">Max Salary (MYR) <span class="text-xs text-muted font-normal">(Optional)</span></label>
                        <input type="number" step="0.01" min="0" id="salary_max_myr" name="salary_max_myr" placeholder="e.g. 5000" class="form-input">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Job Description <span class="text-red-500">*</span></label>
                    <textarea id="description" name="description" rows="6" required class="form-input" aria-describedby="descriptionError"></textarea>
                    <p id="descriptionError" class="text-small text-red-600 hidden mt-1" aria-live="polite">Please provide a detailed job description.</p>
                </div>
            </fieldset>

            <fieldset class="mb-8">
                <legend class="heading-2 mb-2 border-b border-border pb-2 w-full">Accessibility Accommodations</legend>
                <p class="text-small mb-4">Select the specific accommodations your organisation provides for this role.</p>
                
                <?php if (!empty($accommodations)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <?php foreach ($accommodations as $category => $items): ?>
                        <div class="card-compact">
                            <h3 class="heading-3 mb-3"><?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></h3>
                            <div class="space-y-3">
                            <?php foreach ($items as $item): ?>
                                <div class="flex items-start custom-checkbox-container cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-accent rounded" role="checkbox" aria-label="Provide <?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?> accommodation" aria-checked="false" tabindex="0">
                                    <input type="hidden" name="accommodations[]" value="<?php echo (int)$item['accommodation_id']; ?>" disabled>
                                    <div class="checkbox-box w-5 h-5 flex-shrink-0 border border-border bg-surface rounded flex items-center justify-center transition-all duration-200 pointer-events-none mt-0.5">
                                        <svg aria-hidden="true" class="w-3 h-3 text-bg hidden pointer-events-none" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                    <span class="ml-3 text-sm text-text pointer-events-none select-none">
                                        <?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted italic">No accommodations configured in the system yet.</p>
                <?php endif; ?>
            </fieldset>

            <div class="flex justify-end gap-3 pt-6 border-t border-border">
                <a href="<?php echo BASE_URL; ?>employer/dashboard.php" class="btn-outline">Cancel</a>
                <button type="submit" class="btn-primary">Post Job</button>
            </div>
        </form>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('postJobForm');
    
    // Elements
    const title = document.getElementById('title');
    const locationType = document.getElementById('location_type');
    const description = document.getElementById('description');
    
    // Errors
    const titleError = document.getElementById('titleError');
    const locationError = document.getElementById('locationError');
    const descriptionError = document.getElementById('descriptionError');

    const validateField = (input, errorElement, condition) => {
        if (condition) {
            input.setAttribute('aria-invalid', 'true');
            input.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-300');
            errorElement.classList.remove('hidden');
            return false;
        } else {
            input.setAttribute('aria-invalid', 'false');
            input.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-300');
            errorElement.classList.add('hidden');
            return true;
        }
    };

    form.addEventListener('submit', (e) => {
        let isValid = true;
        
        // Validate individual fields
        const isTitleValid = validateField(title, titleError, title.value.trim() === '');
        const isLocationValid = validateField(locationType, locationError, locationType.value === '');
        const isDescValid = validateField(description, descriptionError, description.value.trim() === '');
        
        isValid = isTitleValid && isLocationValid && isDescValid;

        if (!isValid) {
            e.preventDefault();
            
            // Set focus to first invalid element
            if (!isTitleValid) title.focus();
            else if (!isLocationValid) locationType.focus();
            else if (!isDescValid) description.focus();
        }
    });

    // Real-time clearing of errors
    [title, locationType, description].forEach(input => {
        input.addEventListener('input', () => {
            const errorElement = document.getElementById(input.id + 'Error');
            if (input.value.trim() !== '') {
                validateField(input, errorElement, false);
            }
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
