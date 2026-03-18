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

<div class="max-w-3xl mx-auto mt-8 md:mt-12 mb-8 md:mb-12 bg-surface border border-border rounded-xl shadow-sm p-6 transition-all duration-300 ease-in-out">
    
    <div class="mb-6 md:mb-8 border-b border-border pb-4 md:pb-6">
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-accent/10 text-accent mb-3">
            Application Submission
        </span>
        <h1 class="text-3xl font-bold text-text mb-2 font-heading"><?php echo htmlspecialchars($job['title'], ENT_QUOTES); ?></h1>
        <p class="text-muted">Please provide a brief cover letter outlining your required accommodations and why you're a fit for this role.</p>
    </div>

    <!-- Application Form -->
    <form action="<?php echo BASE_URL; ?>actions/process_application.php" method="POST" id="applyForm" novalidate>
        <?php echo csrf_input(); ?>
        
        <input type="hidden" name="job_id" value="<?php echo (int)$job_id; ?>">
        <input type="hidden" id="parsed_resume_data" name="parsed_resume_data" value="">

        <fieldset class="mb-6 md:mb-8">
            <legend class="sr-only">Application Letter</legend>
            
            <div class="form-group relative">
                <label for="cover_letter" class="block text-sm font-medium text-text mb-2">
                    Cover Letter & Accommodation Requirements <span class="text-red-500">*</span>
                </label>
                <!-- For the sake of the base assessment, we pass this in POST. Since applications table in blueprint doesn't mandate a text column, we might simulate storing it or rely entirely on email notification -->
                <textarea id="cover_letter" name="cover_letter" rows="8" required aria-required="true"
                    class="w-full border border-border rounded-lg px-4 py-2 focus:ring-2 focus:ring-accent focus:border-transparent bg-surface text-text transition-colors duration-150"
                    aria-describedby="coverLetterHelp coverLetterError"></textarea>
                <p id="coverLetterHelp" class="mt-2 text-sm text-muted">Highlight how your skills merge with the required accommodations for maximum productivity. (Max 1000 characters)</p>
                <p id="coverLetterError" class="mt-1 text-sm text-red-600 hidden" aria-live="polite"></p>
            </div>
        </fieldset>

        <fieldset class="mb-6 md:mb-8 border-t border-border pt-4 md:pt-6">
            <legend class="sr-only">Resume Upload & Parsing</legend>
            <div class="form-group relative">
                <label for="resume" class="block text-sm font-medium text-text mb-2">
                    Upload Resume (PDF, DOCX, DOC, JPG, PNG) <span class="text-red-500">*</span>
                </label>
                <div id="drop_zone" class="mt-1 flex justify-center px-4 md:px-6 pt-4 pb-5 border-2 border-border border-dashed rounded-md bg-surface transition-all duration-300 ease-in-out">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-muted" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-text justify-center">
                            <label for="resume" class="relative cursor-pointer bg-surface rounded-md font-medium text-accent transition-all duration-300 ease-in-out focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-accent">
                                <span>Upload a file</span>
                                <input id="resume" name="resume" type="file" class="sr-only" accept=".pdf,.docx,.doc,.jpg,.jpeg,.png,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png" aria-describedby="resumeHelp resumeError" aria-required="true" required>
                            </label>
                            <p class="pl-1">or drag and drop</p>
                        </div>
                        <p id="resumeHelp" class="text-xs text-muted">PDF, DOCX, DOC, JPG, or PNG up to 5MB</p>
                    </div>
                </div>
                
                <!-- Loading State (ARIA Announced) -->
                <div id="parseLoading" class="hidden mt-4 flex items-center space-x-3 text-accent" aria-live="assertive">
                    <svg class="animate-spin h-5 w-5 text-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm font-medium">Extracting data from your resume... Please wait.</span>
                </div>
                
                <p id="resumeError" class="mt-2 text-sm text-red-600 hidden" aria-live="assertive" role="alert"></p>

                <!-- Parsed Resume Preview -->
                <div id="resumePreview" class="hidden mt-4 md:mt-6 bg-surface border border-border rounded-lg shadow-sm p-6" aria-live="polite">
                    <h3 class="text-xl font-bold text-text border-b border-border pb-2 mb-4">Verify Extracted Profile Information</h3>
                    <p class="text-sm text-muted mb-4 md:mb-6">Please review, correct, and expand upon the extracted information below before submitting your application.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                        <!-- Email -->
                        <div>
                            <label for="parsed_email" class="block text-sm font-semibold text-text mb-1">Email Address</label>
                            <input type="email" id="parsed_email" name="parsed_email" 
                                class="w-full border border-border rounded-lg px-4 py-2 focus:ring-2 focus:ring-accent focus:border-transparent bg-surface text-text transition-colors"
                                aria-label="Extracted Email Address">
                        </div>
                        
                        <!-- Phone -->
                        <div>
                            <label for="parsed_phone" class="block text-sm font-semibold text-text mb-1">Phone Number</label>
                            <input type="text" id="parsed_phone" name="parsed_phone" 
                                class="w-full border border-border rounded-lg px-4 py-2 focus:ring-2 focus:ring-accent focus:border-transparent bg-surface text-text transition-colors"
                                aria-label="Extracted Phone Number">
                        </div>

                        <!-- Skills -->
                        <div class="md:col-span-2">
                            <label for="parsed_skills" class="block text-sm font-semibold text-text mb-1">Identified Skills</label>
                            <input type="text" id="parsed_skills" name="parsed_skills" 
                                class="w-full border border-border rounded-lg px-4 py-2 focus:ring-2 focus:ring-accent focus:border-transparent bg-surface text-text transition-colors"
                                aria-label="Extracted Skills" aria-describedby="skillsHelp">
                            <p id="skillsHelp" class="text-xs text-muted mt-1">Comma-separated list of your technical and soft skills.</p>
                        </div>
                        
                        <!-- Education -->
                        <div class="md:col-span-2">
                            <label for="parsed_education" class="block text-sm font-semibold text-text mb-1">Education History</label>
                            <textarea id="parsed_education" name="parsed_education" rows="4"
                                class="w-full border border-border rounded-lg px-4 py-2 focus:ring-2 focus:ring-accent focus:border-transparent bg-surface text-text transition-colors resize-y"
                                aria-label="Extracted Education History"></textarea>
                        </div>

                        <!-- Work Experience -->
                        <div class="md:col-span-2">
                            <label for="parsed_work" class="block text-sm font-semibold text-text mb-1">Work Experience</label>
                            <textarea id="parsed_work" name="parsed_work" rows="6"
                                class="w-full border border-border rounded-lg px-4 py-2 focus:ring-2 focus:ring-accent focus:border-transparent bg-surface text-text transition-colors resize-y"
                                aria-label="Extracted Work Experience"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>

        <div class="flex items-center justify-between border-t border-border pt-4 md:pt-6">
            <a href="<?php echo BASE_URL; ?>jobs.php" class="text-sm font-medium text-text transition-colors duration-300 focus:outline-none focus:underline">
                &larr; Cancel and return to Job Board
            </a>
            <button type="submit" class="bg-accent text-white px-4 py-2 min-w-[44px] rounded-lg font-semibold active:scale-95 transition-all duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-accent/50">
                Submit Application
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('applyForm');
    const letter = document.getElementById('cover_letter');
    const letterError = document.getElementById('coverLetterError');
    
    // Resume Elements
    const resumeInput = document.getElementById('resume');
    const resumeError = document.getElementById('resumeError');
    const parseLoading = document.getElementById('parseLoading');
    const resumePreview = document.getElementById('resumePreview');
    const dropZone = document.getElementById('drop_zone');
    
    // Form Elements for parsed data
    const parsedEmail = document.getElementById('parsed_email');
    const parsedPhone = document.getElementById('parsed_phone');
    const parsedSkills = document.getElementById('parsed_skills');
    const parsedEducation = document.getElementById('parsed_education');
    const parsedWork = document.getElementById('parsed_work');
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');

    // Drag and Drop functionality
    if (dropZone) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('border-accent', 'bg-accent/5');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('border-accent', 'bg-accent/5');
            }, false);
        });

        dropZone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length) {
                resumeInput.files = files;
                handleFileUpload();
            }
        });
    }

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    resumeInput.addEventListener('change', handleFileUpload);

    async function handleFileUpload() {
        const file = resumeInput.files[0];
        if (!file) return;

        // Reset UI
        resumeError.classList.add('hidden');
        resumePreview.classList.add('hidden');
        parseLoading.classList.remove('hidden');
        resumeInput.setAttribute('aria-invalid', 'false');

        // Client-side validation
        const validTypes = [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/msword',
            'image/jpeg',
            'image/png'
        ];
        const extension = (file.name.split('.').pop() || '').toLowerCase();
        const isAllowedByExtension = ['pdf', 'docx', 'doc', 'jpg', 'jpeg', 'png'].includes(extension);
        const isAllowedByMime = file.type === '' || validTypes.includes(file.type);
        if (!isAllowedByExtension || !isAllowedByMime) {
            showError('Please upload a valid PDF, DOCX, DOC, JPG, or PNG file.');
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            showError('File exceeds the 5MB size limit.');
            return;
        }

        const formData = new FormData();
        formData.append('resume', file);
        if (csrfMeta) {
            formData.append('csrf_token', csrfMeta.getAttribute('content') || '');
        }

        try {
            const response = await fetch('<?php echo BASE_URL; ?>actions/parse_resume.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-Token': csrfMeta ? csrfMeta.getAttribute('content') : ''
                }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Server error occurred during parsing.');
            }

            // Populate Form Fields
            parsedEmail.value = data.data.email;
            parsedPhone.value = data.data.phone;
            parsedEducation.value = data.data.education;
            parsedSkills.value = data.data.skills;
            parsedWork.value = data.data.work_experience;
            document.getElementById('parsed_resume_data').value = JSON.stringify(data.data);

            // Show Preview Area
            parseLoading.classList.add('hidden');
            resumePreview.classList.remove('hidden');
            
            // Announce to screen readers
            const announcement = document.createElement('div');
            announcement.setAttribute('role', 'status');
            announcement.setAttribute('aria-live', 'polite');
            announcement.classList.add('sr-only');
            announcement.textContent = 'Resume parsed successfully. Please review the extracted information.';
            document.body.appendChild(announcement);
            setTimeout(() => document.body.removeChild(announcement), 3000);

        } catch (err) {
            showError(err.message);
        }
    }

    function showError(message) {
        parseLoading.classList.add('hidden');
        resumeError.textContent = message;
        resumeError.classList.remove('hidden');
        resumeInput.setAttribute('aria-invalid', 'true');
        resumeInput.value = ''; // Clear input
    }

    form.addEventListener('submit', function(e) {
        let valid = true;
        
        // Reset state
        letter.removeAttribute('aria-invalid');
        letter.classList.remove('border-red-500');
        letterError.classList.add('hidden');
        letterError.textContent = '';

        // Length & Presence Check
        const textValue = letter.value.trim();
        const parsedResumeInput = document.getElementById('parsed_resume_data');

        if (textValue.length === 0) {
            valid = false;
            letterError.textContent = 'Please enter a cover letter. This field cannot be left entirely blank.';
        } else if (textValue.length > 1000) {
            valid = false;
            letterError.textContent = 'Your cover letter exceeds the maximum 1,000 character limit.';
        } else if (!parsedResumeInput.value) {
            valid = false;
            letterError.textContent = 'Please upload and parse your resume before submitting your application.';
        }

        if (!valid) {
            e.preventDefault();
            letter.setAttribute('aria-invalid', 'true');
            letter.classList.add('border-red-500');
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

