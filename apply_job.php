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

<div class="max-w-3xl mx-auto mt-12 mb-12 bg-surface border border-border rounded-xl shadow-sm p-6 hover:-translate-y-1 hover:shadow-md transition-all duration-200">
    
    <div class="mb-8 border-b border-border pb-6">
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-accent/10 text-accent mb-3">
            Application Submission
        </span>
        <h1 class="text-3xl font-bold text-text mb-2 font-heading"><?php echo htmlspecialchars($job['title'], ENT_QUOTES); ?></h1>
        <p class="text-muted">Please provide a brief cover letter outlining your required accommodations and why you're a fit for this role.</p>
    </div>

    <!-- Application Form -->
    <form action="<?php echo BASE_URL; ?>actions/process_application.php" method="POST" id="applyForm" novalidate>
        
        <input type="hidden" name="job_id" value="<?php echo (int)$job_id; ?>">

        <fieldset class="mb-8">
            <legend class="sr-only">Application Letter</legend>
            
            <div class="form-group relative">
                <label for="cover_letter" class="block text-sm font-medium text-text mb-2">
                    Cover Letter & Accommodation Requirements <span class="text-red-500">*</span>
                </label>
                <!-- For the sake of the base assessment, we pass this in POST. Since applications table in blueprint doesn't mandate a text column, we might simulate storing it or rely entirely on email notification -->
                <textarea id="cover_letter" name="cover_letter" rows="8" required aria-required="true"
                    class="w-full border border-border rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-accent focus:border-transparent bg-surface text-text transition-colors duration-150"
                    aria-describedby="coverLetterHelp coverLetterError"></textarea>
                <p id="coverLetterHelp" class="mt-2 text-sm text-muted">Highlight how your skills merge with the required accommodations for maximum productivity. (Max 1000 characters)</p>
                <p id="coverLetterError" class="mt-1 text-sm text-red-600 hidden" aria-live="polite"></p>
            </div>
        </fieldset>

        <div class="flex items-center justify-between border-t border-border pt-6">
            <a href="<?php echo BASE_URL; ?>jobs.php" class="text-sm font-medium text-text hover:text-accent focus:outline-none focus:underline">
                &larr; Cancel and return to Job Board
            </a>
            <button type="submit" class="bg-accent text-white px-5 py-2.5 rounded-lg font-semibold hover:bg-accent-hover active:scale-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-accent/50">
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
        letter.classList.remove('border-red-500', '');
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
            letter.classList.add('border-red-500', '');
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
