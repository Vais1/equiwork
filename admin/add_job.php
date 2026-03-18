<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/csrf.php';

// Enforce Role: Admin only
enforce_role('Admin');

// Validate and process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate_request()) {
        $_SESSION['flash_error'] = 'Invalid request token. Please refresh and try again.';
        header('Location: add_job.php');
        exit;
    }

    // Collect and sanitize inputs
    $employer_id = filter_input(INPUT_POST, 'employer_id', FILTER_VALIDATE_INT);
    $title = trim(htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8'));
    $company_name = trim(htmlspecialchars($_POST['company_name'] ?? '', ENT_QUOTES, 'UTF-8'));
    $description = trim(htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8'));
    $location_type = trim($_POST['location_type'] ?? '');
    $employment_type = trim($_POST['employment_type'] ?? '');
    $salary_min = filter_input(INPUT_POST, 'salary_min_myr', FILTER_VALIDATE_FLOAT) ?: null;
    $salary_max = filter_input(INPUT_POST, 'salary_max_myr', FILTER_VALIDATE_FLOAT) ?: null;
    $state_region = trim(htmlspecialchars($_POST['state_region'] ?? '', ENT_QUOTES, 'UTF-8'));
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
            $_SESSION['flash_success'] = "Job posting successfully added.";
            header("Location: dashboard.php");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_error'] = "Failed to add job posting. Please try again.";
        }
    } else {
        $_SESSION['flash_error'] = implode("<br>", $errors);
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

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-heading font-bold text-text mb-2">Publish New Job</h1>
            <p class="text-muted">Create a secure administrative record for accessible employment opportunities.</p>
        </div>
        <a href="dashboard.php" class="text-sm font-medium text-muted transition-colors">
            &larr; Back to Dashboard
        </a>
    </div>

    <form method="POST" action="add_job.php" class="bg-surface border border-border rounded-xl shadow-sm overflow-hidden" novalidate onsubmit="return validateForm(this);">
        <?php echo csrf_input(); ?>
        <div class="p-6 md:p-8 space-y-8">
            
            <!-- Section: Primary Information -->
            <fieldset>
                <legend class="text-lg font-heading font-semibold text-text mb-4 pb-2 border-b border-border w-full">Core Reference Details</legend>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Custom Select: Employer -->
                    <div class="space-y-1 relative z-40">
                        <label class="block text-sm font-medium text-text">Assigned Employer <span class="text-accent">*</span></label>
                        <div class="custom-select-container relative">
                            <input type="hidden" name="employer_id" required>
                            <button type="button" aria-haspopup="listbox" aria-expanded="false" class="w-full flex items-center justify-between px-3 py-2 bg-surface border border-border text-text text-sm rounded-lg focus:outline-none focus:ring-2 focus:ring-accent transition-all text-left">
                                <span class="custom-select-text text-muted">Select an employer...</span>
                                <svg class="w-4 h-4 text-muted pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <ul role="listbox" class="absolute mt-1 w-full bg-surface border border-border rounded-lg shadow-lg hidden z-50 max-h-60 overflow-y-auto outline-none transition-all py-1">
                                <?php foreach ($employers as $emp): ?>
                                    <li role="option" data-value="<?= (int)$emp['user_id'] ?>" class="px-4 py-2 text-sm text-text cursor-pointer transition-colors duration-150 relative">
                                        <?= htmlspecialchars($emp['username'], ENT_QUOTES) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Company Name -->
                    <div class="space-y-1">
                        <label for="company_name" class="block text-sm font-medium text-text">Company Alias (Optional)</label>
                        <input type="text" id="company_name" name="company_name" placeholder="e.g. Inclusivity Inc" class="w-full px-3 py-2 bg-surface text-text border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent transition-all text-sm placeholder:text-muted/60">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 mt-6">
                    <!-- Job Title -->
                    <div class="space-y-1">
                        <label for="title" class="block text-sm font-medium text-text">Job Title <span class="text-accent">*</span></label>
                        <input type="text" id="title" name="title" required placeholder="Frontend Developer, Accessible Workflows" class="w-full px-3 py-2 bg-surface text-text border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent transition-all text-sm placeholder:text-muted/60">
                    </div>

                    <!-- Description -->
                    <div class="space-y-1">
                        <label for="description" class="block text-sm font-medium text-text">Detailed Opportunity Description <span class="text-accent">*</span></label>
                        <textarea id="description" name="description" rows="5" required placeholder="Describe responsibilities, expectations, and culture..." class="w-full px-3 py-2 bg-surface text-text border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent transition-all text-sm resize-y placeholder:text-muted/60"></textarea>
                    </div>
                </div>
            </fieldset>

            <!-- Section: Demographics & Type -->
            <fieldset>
                <legend class="text-lg font-heading font-semibold text-text mb-4 pb-2 border-b border-border w-full">Arrangement Configuration</legend>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                    
                    <!-- Custom Select: Location Type -->
                    <div class="space-y-1 relative z-30">
                        <label class="block text-sm font-medium text-text">Location Environment <span class="text-accent">*</span></label>
                        <div class="custom-select-container relative">
                            <input type="hidden" name="location_type" required>
                            <button type="button" aria-haspopup="listbox" aria-expanded="false" class="w-full flex items-center justify-between px-3 py-2 bg-surface border border-border text-text text-sm rounded-lg focus:outline-none focus:ring-2 focus:ring-accent transition-all text-left">
                                <span class="custom-select-text text-muted">Select paradigm...</span>
                                <svg class="w-4 h-4 text-muted pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <ul role="listbox" class="absolute mt-1 w-full bg-surface border border-border rounded-lg shadow-lg hidden z-50 max-h-60 overflow-y-auto outline-none transition-all py-1">
                                <?php foreach (['Remote', 'Hybrid', 'On-site'] as $loc): ?>
                                    <li role="option" data-value="<?= $loc ?>" class="px-4 py-2 text-sm text-text cursor-pointer transition-colors duration-150 relative"><?= $loc ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Custom Select: Employment Type -->
                    <div class="space-y-1 relative z-20">
                        <label class="block text-sm font-medium text-text">Contract Scope <span class="text-accent">*</span></label>
                        <div class="custom-select-container relative">
                            <input type="hidden" name="employment_type" required>
                            <button type="button" aria-haspopup="listbox" aria-expanded="false" class="w-full flex items-center justify-between px-3 py-2 bg-surface border border-border text-text text-sm rounded-lg focus:outline-none focus:ring-2 focus:ring-accent transition-all text-left">
                                <span class="custom-select-text text-muted">Select commitment...</span>
                                <svg class="w-4 h-4 text-muted pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <ul role="listbox" class="absolute mt-1 w-full bg-surface border border-border rounded-lg shadow-lg hidden z-50 max-h-60 overflow-y-auto outline-none transition-all py-1">
                                <?php foreach (['Full-time', 'Part-time', 'Contract', 'Freelance'] as $empType): ?>
                                    <li role="option" data-value="<?= $empType ?>" class="px-4 py-2 text-sm text-text cursor-pointer transition-colors duration-150 relative"><?= $empType ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Custom Select: Status -->
                    <div class="space-y-1 relative z-10">
                        <label class="block text-sm font-medium text-text">Operational Status <span class="text-accent">*</span></label>
                        <div class="custom-select-container relative">
                            <input type="hidden" name="status" value="Active" required>
                            <button type="button" aria-haspopup="listbox" aria-expanded="false" class="w-full flex items-center justify-between px-3 py-2 bg-surface border border-border text-text text-sm rounded-lg focus:outline-none focus:ring-2 focus:ring-accent transition-all text-left">
                                <span class="custom-select-text">Active</span>
                                <svg class="w-4 h-4 text-muted pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <ul role="listbox" class="absolute mt-1 w-full bg-surface border border-border rounded-lg shadow-lg hidden z-50 max-h-60 overflow-y-auto outline-none transition-all py-1">
                                <li role="option" aria-selected="true" data-value="Active" class="px-4 py-2 text-sm text-text cursor-pointer transition-colors duration-150 relative">Active</li>
                                <li role="option" aria-selected="false" data-value="Closed" class="px-4 py-2 text-sm text-text cursor-pointer transition-colors duration-150 relative">Closed</li>
                            </ul>
                        </div>
                    </div>

                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                    <!-- State/Region -->
                    <div class="space-y-1">
                        <label for="state_region" class="block text-sm font-medium text-text">State / Region</label>
                        <input type="text" id="state_region" name="state_region" placeholder="e.g. Kuala Lumpur" class="w-full px-3 py-2 bg-surface text-text border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent transition-all text-sm placeholder:text-muted/60">
                    </div>

                    <!-- Minimum Salary -->
                    <div class="space-y-1">
                        <label for="salary_min_myr" class="block text-sm font-medium text-text">Base Salary (MYR)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-muted pointer-events-none text-sm">RM</span>
                            <input type="number" id="salary_min_myr" name="salary_min_myr" step="0.01" min="0" placeholder="0.00" class="w-full pl-10 pr-3 py-2 bg-surface text-text border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent transition-all text-sm">
                        </div>
                    </div>
                    
                    <!-- Maximum Salary -->
                    <div class="space-y-1">
                        <label for="salary_max_myr" class="block text-sm font-medium text-text">Ceiling Salary (MYR)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-muted pointer-events-none text-sm">RM</span>
                            <input type="number" id="salary_max_myr" name="salary_max_myr" step="0.01" min="0" placeholder="0.00" class="w-full pl-10 pr-3 py-2 bg-surface text-text border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent transition-all text-sm">
                        </div>
                    </div>
                </div>
            </fieldset>

            <!-- Section: Accessibility Integration (SDG 08 Alignment) -->
            <fieldset>
                <legend class="text-lg font-heading font-semibold text-text mb-4 pb-2 border-b border-border w-full">Verified Work Accommodations</legend>
                <p class="text-sm text-muted mb-6">Select specific, verified accommodations natively provided for this role. This ensures accurate candidate filtering alignment.</p>
                
                <div class="space-y-6">
                    <?php foreach ($grouped_accommodations as $category => $acc_list): ?>
                        <div>
                            <h4 class="text-sm font-semibold text-text uppercase tracking-wider mb-3"><?= htmlspecialchars($category, ENT_QUOTES) ?></h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 mt-2 gap-4">
                                <?php foreach ($acc_list as $acc): ?>
                                    
                                    <!-- Custom Checkbox Implementation -->
                                    <div class="custom-checkbox-container flex items-center justify-between p-3 border border-border rounded-lg bg-surface/50 cursor-pointer transition-all focus:outline-none focus:ring-2 focus:ring-accent group" role="checkbox" aria-label="Assign accommodation <?php echo htmlspecialchars($acc['name'], ENT_QUOTES); ?>" aria-checked="false" tabindex="0">
                                        <input type="hidden" name="accommodations[]" value="<?= (int)$acc['accommodation_id'] ?>" disabled>
                                        <div class="flex-grow select-none">
                                            <span class="text-sm font-medium text-text transition-colors block">
                                                <?= htmlspecialchars($acc['name'], ENT_QUOTES) ?>
                                            </span>
                                        </div>
                                        <div class="checkbox-box flex items-center justify-center w-5 h-5 rounded border border-border bg-surface transition-all flex-shrink-0 ml-3">
                                            <svg class="w-3.5 h-3.5 text-white hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                        </div>
                                    </div>
                                    
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </fieldset>
            
        </div>
        
        <!-- Form Actions -->
        <div class="px-6 md:px-8 py-5 bg-surface border-t border-border flex items-center justify-end gap-3">
            <a href="dashboard.php" class="px-4 py-2 border border-border text-text rounded-lg text-sm font-medium focus:outline-none focus:ring-2 focus:ring-border transition-colors">Discard Draft</a>
            <button type="submit" class="px-6 py-2 bg-accent text-white rounded-lg text-sm font-medium focus:outline-none focus:ring-4 focus:ring-accent/50 transition-all shadow-sm active:scale-95 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Publish Job Record
            </button>
        </div>
    </form>
</div>

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

