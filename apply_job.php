<?php
// apply_job.php
// Job application submission form

require_once 'includes/db.php';
require_once 'includes/auth_check.php';

// Only logged-in 'Seekers' can apply to jobs
enforce_role('Seeker');

// Safe fetch the job ID
$job_id = filter_input(INPUT_GET, 'job_id', FILTER_VALIDATE_INT);

if (!$job_id) {
    // Fallback if no valid job ID is passed
    header("Location: " . BASE_URL . "jobs.php");
    exit;
}

// Fetch Job Details just for Context (assumes jobs table is structured as per blueprint)
$stmt = $conn->prepare("SELECT title, description FROM jobs WHERE job_id = ?");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$job_result = $stmt->get_result();

if ($job_result->num_rows === 0) {
    header("Location: " . BASE_URL . "jobs.php");
    exit;
}
$job = $job_result->fetch_assoc();
$stmt->close();

require_once 'includes/header.php';
?>

<div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-8 mt-12 mb-12">
    
    <div class="mb-8 border-b border-gray-200 dark:border-gray-700 pb-6">
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200 mb-3">
            Application Submission
        </span>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2"><?php echo htmlspecialchars($job['title'], ENT_QUOTES); ?></h1>
        <p class="text-gray-600 dark:text-gray-400">Please provide a brief cover letter outlining your required accommodations and why you're a fit for this role.</p>
    </div>

    <!-- Application Form -->
    <form action="<?php echo BASE_URL; ?>actions/process_application.php" method="POST" id="applyForm" novalidate>
        
        <input type="hidden" name="job_id" value="<?php echo (int)$job_id; ?>">

        <fieldset class="mb-8">
            <legend class="sr-only">Application Letter</legend>
            
            <div class="form-group relative">
                <label for="cover_letter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Cover Letter & Accommodation Requirements <span class="text-red-500">*</span>
                </label>
                <!-- For the sake of the base assessment, we pass this in POST. Since applications table in blueprint doesn't mandate a text column, we might simulate storing it or rely entirely on email notification -->
                <textarea id="cover_letter" name="cover_letter" rows="8" required aria-required="true"
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-500 transition-colors"
                    aria-describedby="coverLetterHelp coverLetterError"></textarea>
                <p id="coverLetterHelp" class="mt-2 text-sm text-gray-500 dark:text-gray-400">Highlight how your skills merge with the required accommodations for maximum productivity. (Max 1000 characters)</p>
                <p id="coverLetterError" class="mt-1 text-sm text-red-600 dark:text-red-400 hidden" aria-live="polite"></p>
            </div>
        </fieldset>

        <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-6">
            <a href="<?php echo BASE_URL; ?>jobs.php" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 focus:outline-none focus:underline">
                &larr; Cancel and return to Job Board
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 text-white font-semibold px-8 py-3 rounded-lg transition-colors shadow-sm">
                Submit Application
            </button>
        </div>
    </form>
</div>

<!-- Validation Script: Tier 1 Client-Side HTML5/JS -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('applyForm');
    const letter = document.getElementById('cover_letter');
    const letterError = document.getElementById('coverLetterError');

    form.addEventListener('submit', function(e) {
        let valid = true;
        
        // Reset state
        letter.removeAttribute('aria-invalid');
        letter.classList.remove('border-red-500', 'dark:border-red-500');
        letterError.classList.add('hidden');
        letterError.textContent = '';

        // Length & Presence Check
        const textValue = letter.value.trim();
        if (textValue.length === 0) {
            valid = false;
            letterError.textContent = 'Please enter a cover letter. This field cannot be left entirely blank.';
        } else if (textValue.length > 1000) {
            valid = false;
            letterError.textContent = 'Your cover letter exceeds the maximum 1,000 character limit.';
        }

        if (!valid) {
            e.preventDefault();
            letter.setAttribute('aria-invalid', 'true');
            letter.classList.add('border-red-500', 'dark:border-red-500');
            letterError.classList.remove('hidden');
            letter.focus();
        }
    });

    // Real-time counter tracker
    letter.addEventListener('input', function() {
        const remaining = 1000 - this.value.length;
        const helpText = document.getElementById('coverLetterHelp');
        if (remaining < 0) {
            helpText.classList.add('text-red-500');
            helpText.textContent = `Length exceeded by ${Math.abs(remaining)} character(s).`;
        } else {
            helpText.classList.remove('text-red-500');
            helpText.textContent = `Highlight how your skills merge with the required accommodations for maximum productivity. (${remaining} characters remaining)`;
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
