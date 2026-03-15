<?php
// post_job.php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth_check.php';
require_once 'includes/flash.php';

// Strict session check for Employer role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employer') {
    set_flash_message('error', 'Unauthorised access. Only employers can post jobs.');
    header('Location: ' . BASE_URL . 'login.php');
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

require_once 'includes/header.php';
?>

<div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-8 mt-8 mb-12">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">Post a New Job</h1>
    
    <form action="<?php echo BASE_URL; ?>actions/process_post_job.php" method="POST" id="postJobForm" novalidate>
        <fieldset class="mb-6 space-y-4">
            <legend class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4 border-b pb-2">Job Details</legend>
            
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Job Title <span class="text-red-500">*</span></label>
                <input type="text" id="title" name="title" required
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800 transition-colors"
                       aria-describedby="titleError">
                <p id="titleError" class="mt-1 text-sm text-red-600 dark:text-red-400 hidden" aria-live="polite">Please enter a job title.</p>
            </div>
            
            <div>
                <label for="location_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Work Arrangement <span class="text-red-500">*</span></label>
                
                <div class="custom-select-container relative w-full" data-name="location_type">
                    <input type="hidden" name="location_type" id="location_type" required>
                    <button type="button" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-500 transition-colors flex justify-between items-center" aria-haspopup="listbox" aria-expanded="false" aria-describedby="locationError">
                        <span class="custom-select-text">Select an arrangement...</span>
                        <svg class="w-4 h-4 ml-2 text-gray-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <ul class="custom-select-list absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden" role="listbox" tabindex="-1">
                        <li class="px-4 py-2 hover:bg-blue-50 dark:hover:bg-gray-700 cursor-pointer text-gray-900 dark:text-gray-100" role="option" aria-selected="true" data-value="">Select an arrangement...</li>
                        <li class="px-4 py-2 hover:bg-blue-50 dark:hover:bg-gray-700 cursor-pointer text-gray-900 dark:text-gray-100" role="option" aria-selected="false" data-value="Remote">Remote</li>
                        <li class="px-4 py-2 hover:bg-blue-50 dark:hover:bg-gray-700 cursor-pointer text-gray-900 dark:text-gray-100" role="option" aria-selected="false" data-value="Hybrid">Hybrid</li>
                        <li class="px-4 py-2 hover:bg-blue-50 dark:hover:bg-gray-700 cursor-pointer text-gray-900 dark:text-gray-100" role="option" aria-selected="false" data-value="On-site">On-site</li>
                    </ul>
                </div>
                
                <p id="locationError" class="mt-1 text-sm text-red-600 dark:text-red-400 hidden" aria-live="polite">Please select a work arrangement.</p>
            </div>
            
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Job Description <span class="text-red-500">*</span></label>
                <textarea id="description" name="description" rows="6" required
                          class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800 transition-colors"
                          aria-describedby="descriptionError"></textarea>
                <p id="descriptionError" class="mt-1 text-sm text-red-600 dark:text-red-400 hidden" aria-live="polite">Please provide a detailed job description.</p>
            </div>
        </fieldset>

        <fieldset class="mb-8">
            <legend class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4 border-b pb-2">Accessibility Accommodations</legend>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Select the specific accommodations your organisation provides for this role.</p>
            
            <?php if (!empty($accommodations)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($accommodations as $category => $items): ?>
                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                        <h3 class="font-medium text-gray-800 dark:text-gray-200 mb-3"><?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></h3>
                        <div class="space-y-2">
                        <?php foreach ($items as $item): ?>
                            <div class="flex items-start custom-checkbox-container cursor-pointer focus:outline-none focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800 rounded" role="checkbox" aria-checked="false" tabindex="0">
                                <input type="hidden" name="accommodations[]" value="<?php echo (int)$item['accommodation_id']; ?>" disabled>
                                <div class="checkbox-box w-5 h-5 flex-shrink-0 border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center transition-colors pointer-events-none mt-0.5">
                                    <svg class="w-3 h-3 text-white hidden pointer-events-none" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300 pointer-events-none select-none">
                                    <?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 italic">No accommodations configured in the system yet.</p>
            <?php endif; ?>
        </fieldset>

        <div class="flex justify-end gap-4 mt-8 pt-4 border-t border-gray-200 dark:border-gray-700">
            <a href="<?php echo BASE_URL; ?>jobs.php" class="px-6 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-medium">
                Cancel
            </a>
            <button type="submit" 
                    class="bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800 text-white font-semibold px-8 py-2.5 rounded-lg transition-colors shadow-sm">
                Post Job
            </button>
        </div>
    </form>
</div>

<!-- Client-side validation logic -->
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

<?php require_once 'includes/footer.php'; ?>