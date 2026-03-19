<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db.php';
require_once '../includes/flash.php';
require_once '../includes/auth_check.php';
require_once '../includes/csrf.php';

// Enforce Role: Admin only
enforce_role('Admin');

// Validate and process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate_request()) {
        set_flash_message('error', 'Invalid request token. Please refresh and try again.');
        header('Location: add_job.php');
        exit;
    }

    // Collect and sanitize inputs
    $employer_id = filter_input(INPUT_POST, 'employer_id', FILTER_VALIDATE_INT);
    $title = trim($_POST['title'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location_type = trim($_POST['location_type'] ?? '');
    $employment_type = trim($_POST['employment_type'] ?? '');
    
    $salary_min = filter_input(INPUT_POST, 'salary_min_myr', FILTER_VALIDATE_FLOAT);
    $salary_min = ($salary_min !== false && $salary_min !== null) ? $salary_min : null;
    
    $salary_max = filter_input(INPUT_POST, 'salary_max_myr', FILTER_VALIDATE_FLOAT);
    $salary_max = ($salary_max !== false && $salary_max !== null) ? $salary_max : null;
    $state_region = trim($_POST['state_region'] ?? '');
    $status = trim($_POST['status'] ?? 'Active');
    
    // Array of accommodations submitted
    $selected_accommodations = $_POST['accommodations'] ?? [];

    $errors = [];

    // Validations
    if (!$employer_id) $errors[] = "A valid Employer must be selected.";
    if (empty($title)) $errors[] = "Job Title is required.";
    if (empty($description)) $errors[] = "Job Description is required.";
    if (!in_array($location_type, ['Remote', 'Hybrid', 'On-site'])) $errors[] = "Invalid location type selected.";
    if (!in_array($employment_type, ['Full-time', 'Part-time', 'Contract', 'Freelance'])) $errors[] = "Invalid employment type selected.";
    if (!in_array($status, ['Active', 'Closed'])) $errors[] = "Invalid status selected.";
    if ($salary_min && $salary_max && $salary_min > $salary_max) {
        $errors[] = "Minimum salary cannot be greater than maximum salary.";
    }

    if (empty($errors)) {
        try {
            $conn->begin_transaction();

            // Insert core listing
            $stmt = $conn->prepare("
                INSERT INTO jobs 
                (employer_id, title, company_name, description, location_type, employment_type, salary_min_myr, salary_max_myr, state_region, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                "isssssddss", 
                $employer_id, $title, $company_name, $description, 
                $location_type, $employment_type, $salary_min, $salary_max, 
                $state_region, $status
            );
            $stmt->execute();
            
            $job_id = $stmt->insert_id;
            $stmt->close();

            // Insert accommodations mapped to intersection table
            if (!empty($selected_accommodations)) {
                $acc_stmt = $conn->prepare("INSERT INTO job_accommodations (job_id, accommodation_id) VALUES (?, ?)");
                foreach ($selected_accommodations as $acc_id) {
                    $val_acc_id = (int)$acc_id;
                    if ($val_acc_id > 0) {
                        $acc_stmt->bind_param("ii", $job_id, $val_acc_id);
                        $acc_stmt->execute();
                    }
                }
                $acc_stmt->close();
            }

            $conn->commit();
            set_flash_message('success', "Job posting successfully added.");
            header("Location: dashboard.php");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            set_flash_message('error', "Failed to add job posting. Please try again.");
        }
    } else {
        set_flash_message('error', implode("<br>", $errors));
    }
}

// Fetch employers for standard dropdowns
$emp_stmt = $conn->prepare("SELECT user_id, username FROM users WHERE role_type = 'Employer' ORDER BY username ASC");
$emp_stmt->execute();
$employers = $emp_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$emp_stmt->close();

// Fetch specific accommodations, grouped by category
$acc_stmt = $conn->prepare("SELECT accommodation_id, name, category FROM accommodations ORDER BY category ASC, name ASC");
$acc_stmt->execute();
$all_accs = $acc_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$acc_stmt->close();

// Group accommodations
$grouped_accommodations = [];
foreach ($all_accs as $acc) {
    $grouped_accommodations[$acc['category']][] = $acc;
}

require_once '../includes/header.php';
?>

<main class="container-main max-w-4xl">
    <div class="flex items-center justify-between mb-8 gap-4">
        <div>
            <h1 class="heading-1">Publish New Job</h1>
            <p class="text-body mt-1">Create a secure administrative record for accessible employment opportunities.</p>
        </div>
        <a href="dashboard.php" class="btn-ghost shrink-0">
            &larr; Back to Dashboard
        </a>
    </div>

    <form method="POST" action="add_job.php" class="card p-0 overflow-hidden" novalidate onsubmit="return validateForm(this);">
        <?php echo csrf_input(); ?>
        <div class="p-6 md:p-8 space-y-8">
            
            <fieldset>
                <legend class="heading-2 mb-4 pb-2 border-b border-border w-full">Core Reference Details</legend>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="form-group relative z-40">
                        <label class="form-label">Assigned Employer <span class="text-red-500">*</span></label>
                        <div class="custom-select-container relative">
                            <input type="hidden" name="employer_id" required>
                            <button type="button" aria-haspopup="listbox" aria-expanded="false" class="form-input text-left flex justify-between items-center">
                                <span class="custom-select-text">Select an employer...</span>
                                <svg class="w-4 h-4 text-muted pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <ul role="listbox" class="absolute mt-1 w-full bg-surface border border-border rounded-md shadow-lg hidden z-50 max-h-60 overflow-y-auto outline-none transition-all py-1">
                                <?php foreach ($employers as $emp): ?>
                                    <li role="option" data-value="<?= (int)$emp['user_id'] ?>" class="px-3 py-2 text-sm text-text cursor-pointer transition-colors relative">
                                        <?= htmlspecialchars($emp['username'], ENT_QUOTES) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="company_name" class="form-label">Company Alias <span class="text-xs text-muted font-normal">(Optional)</span></label>
                        <input type="text" id="company_name" name="company_name" placeholder="e.g. Inclusivity Inc" class="form-input">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 mt-5">
                    <div class="form-group">
                        <label for="title" class="form-label">Job Title <span class="text-red-500">*</span></label>
                        <input type="text" id="title" name="title" required placeholder="Frontend Developer, Accessible Workflows" class="form-input">
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Detailed Opportunity Description <span class="text-red-500">*</span></label>
                        <textarea id="description" name="description" rows="5" required placeholder="Describe responsibilities, expectations, and culture..." class="form-input resize-y"></textarea>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend class="heading-2 mb-4 pb-2 border-b border-border w-full">Arrangement Configuration</legend>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
                    
                    <div class="form-group relative z-30">
                        <label class="form-label">Location Environment <span class="text-red-500">*</span></label>
                        <div class="custom-select-container relative">
                            <input type="hidden" name="location_type" required>
                            <button type="button" aria-haspopup="listbox" aria-expanded="false" class="form-input text-left flex justify-between items-center">
                                <span class="custom-select-text">Select paradigm...</span>
                                <svg class="w-4 h-4 text-muted pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <ul role="listbox" class="absolute mt-1 w-full bg-surface border border-border rounded-md shadow-lg hidden z-50 max-h-60 overflow-y-auto outline-none transition-all py-1">
                                <?php foreach (['Remote', 'Hybrid', 'On-site'] as $loc): ?>
                                    <li role="option" data-value="<?= $loc ?>" class="px-3 py-2 text-sm text-text cursor-pointer transition-colors relative"><?= $loc ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="form-group relative z-20">
                        <label class="form-label">Contract Scope <span class="text-red-500">*</span></label>
                        <div class="custom-select-container relative">
                            <input type="hidden" name="employment_type" required>
                            <button type="button" aria-haspopup="listbox" aria-expanded="false" class="form-input text-left flex justify-between items-center">
                                <span class="custom-select-text">Select commitment...</span>
                                <svg class="w-4 h-4 text-muted pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <ul role="listbox" class="absolute mt-1 w-full bg-surface border border-border rounded-md shadow-lg hidden z-50 max-h-60 overflow-y-auto outline-none transition-all py-1">
                                <?php foreach (['Full-time', 'Part-time', 'Contract', 'Freelance'] as $empType): ?>
                                    <li role="option" data-value="<?= $empType ?>" class="px-3 py-2 text-sm text-text cursor-pointer transition-colors relative"><?= $empType ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="form-group relative z-10">
                        <label class="form-label">Operational Status <span class="text-red-500">*</span></label>
                        <div class="custom-select-container relative">
                            <input type="hidden" name="status" value="Active" required>
                            <button type="button" aria-haspopup="listbox" aria-expanded="false" class="form-input text-left flex justify-between items-center">
                                <span class="custom-select-text">Active</span>
                                <svg class="w-4 h-4 text-muted pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <ul role="listbox" class="absolute mt-1 w-full bg-surface border border-border rounded-md shadow-lg hidden z-50 max-h-60 overflow-y-auto outline-none transition-all py-1">
                                <li role="option" aria-selected="true" data-value="Active" class="px-3 py-2 text-sm text-text cursor-pointer transition-colors relative">Active</li>
                                <li role="option" aria-selected="false" data-value="Closed" class="px-3 py-2 text-sm text-text cursor-pointer transition-colors relative">Closed</li>
                            </ul>
                        </div>
                    </div>

                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-5">
                    <div class="form-group">
                        <label for="state_region" class="form-label">State / Region</label>
                        <input type="text" id="state_region" name="state_region" placeholder="e.g. Kuala Lumpur" class="form-input">
                    </div>

                    <div class="form-group">
                        <label for="salary_min_myr" class="form-label">Base Salary (MYR)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-muted pointer-events-none text-sm">RM</span>
                            <input type="number" id="salary_min_myr" name="salary_min_myr" step="0.01" min="0" placeholder="0.00" class="form-input pl-10">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="salary_max_myr" class="form-label">Ceiling Salary (MYR)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-muted pointer-events-none text-sm">RM</span>
                            <input type="number" id="salary_max_myr" name="salary_max_myr" step="0.01" min="0" placeholder="0.00" class="form-input pl-10">
                        </div>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend class="heading-2 mb-2 pb-2 border-b border-border w-full">Verified Work Accommodations</legend>
                <p class="text-small mb-6">Select specific, verified accommodations natively provided for this role. This ensures accurate candidate filtering alignment.</p>
                
                <div class="space-y-6">
                    <?php foreach ($grouped_accommodations as $category => $acc_list): ?>
                        <div class="card-compact">
                            <h4 class="heading-3 mb-3"><?= htmlspecialchars($category, ENT_QUOTES) ?></h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <?php foreach ($acc_list as $acc): ?>
                                    <div class="custom-checkbox-container flex items-center justify-between p-3 border border-border rounded-md bg-surface cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-accent group" role="checkbox" aria-label="Assign accommodation <?php echo htmlspecialchars($acc['name'], ENT_QUOTES); ?>" aria-checked="false" tabindex="0">
                                        <input type="hidden" name="accommodations[]" value="<?= (int)$acc['accommodation_id'] ?>" disabled>
                                        <div class="flex-grow select-none">
                                            <span class="text-sm font-medium text-text block">
                                                <?= htmlspecialchars($acc['name'], ENT_QUOTES) ?>
                                            </span>
                                        </div>
                                        <div class="checkbox-box flex items-center justify-center w-5 h-5 rounded border border-border bg-surface flex-shrink-0 ml-3">
                                            <svg class="w-3.5 h-3.5 text-bg hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </fieldset>
            
        </div>
        
        <div class="px-6 md:px-8 py-4 bg-bg border-t border-border flex items-center justify-end gap-3">
            <a href="dashboard.php" class="btn-outline">Discard Draft</a>
            <button type="submit" class="btn-primary flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Publish Job Record
            </button>
        </div>
    </form>
</main>

<script src="<?php echo BASE_URL; ?>assets/js/custom-controls.js"></script>

<script>
    /**
     * Tier 1: Client-Side Structural Validation (matches HTML5 semantics on Custom Controls)
     */
    function validateForm(form) {
        let valid = true;
        
        // Remove prior inline custom errors if needed...
        
        // Check hidden custom select inputs marked as required
        const requiredHidden = form.querySelectorAll('input[type="hidden"][required]');
        requiredHidden.forEach(inp => {
            if (!inp.value.trim()) {
                valid = false;
                // Visually highlight error on parent custom select
                const container = inp.closest('.custom-select-container');
                const btn = container.querySelector('button');
                btn.classList.add('border-red-500', 'ring-1', 'ring-red-500');
                
                // Remove visual cue once an option is selected
                inp.addEventListener('change', function handler() {
                    if (this.value.trim()) {
                        btn.classList.remove('border-red-500', 'ring-1', 'ring-red-500');
                        inp.removeEventListener('change', handler);
                    }
                });
            }
        });
        
        // Check text/textarea fields marked natively as required
        const requiredNative = form.querySelectorAll('input:not([type="hidden"])[required], textarea[required]');
        requiredNative.forEach(inp => {
            if (!inp.value.trim()) {
                valid = false;
                inp.classList.add('border-red-500', 'ring-1', 'ring-red-500');
                inp.addEventListener('input', function handler() {
                    inp.classList.remove('border-red-500', 'ring-1', 'ring-red-500');
                    inp.removeEventListener('input', handler);
                });
            }
        });

        if (!valid) {
            // Scroll to the first error gracefully for accessibility
            const firstError = form.querySelector('.border-red-500');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            alert("Please complete all required fields accurately."); // Standard quick-feedback
        }
        
        return valid;
    }
</script>

<?php require_once '../includes/footer.php'; ?>

