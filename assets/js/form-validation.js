// assets/js/form-validation.js

const FormValidator = {
        getErrorElement(field) {
            if (!field || !field.id) return null;
            return document.getElementById(`${field.id}Error`) || document.getElementById(`${field.id}-error`);
        },

    patterns: {
        email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
        password: { min: 8 },
        username: { min: 1 }
    },
    
    validate(field, type, errorElem) {
        if (!field) return true;
        
        const value = field.value.trim();
        const rules = this.patterns[type] || { min: 1 };
        
        let isValid = true;
        let errorMsg = '';
        
        if (type === 'email') {
            if (!value) {
                errorMsg = 'Email is required.';
                isValid = false;
            } else if (!this.patterns.email.test(value)) {
                errorMsg = 'Invalid email format.';
                isValid = false;
            }
        } else if (type === 'password') {
            if (!value) {
                if (field.hasAttribute('required')) {
                    errorMsg = 'Password is required.';
                    isValid = false;
                }
            } else if (value.length < rules.min) {
                errorMsg = `Password must be at least ${rules.min} characters.`;
                isValid = false;
            }
        } else if (type === 'username') {
            if (!value) {
                errorMsg = 'This field is required.';
                isValid = false;
            }
        }
        
        if (errorElem) {
            this.showError(field, errorElem, errorMsg, isValid);
        }
        return isValid;
    },
    
    showError(field, errorElem, message, isValid) {
        if (!isValid) {
            field.setAttribute('aria-invalid', 'true');
            field.classList.add('border-red-500');
            field.classList.remove('border-border');
            errorElem.textContent = message;
            errorElem.classList.remove('hidden');
        } else {
            field.removeAttribute('aria-invalid');
            field.classList.remove('border-red-500');
            field.classList.add('border-border');
            errorElem.classList.add('hidden');
        }
    },
    
    attachValidation(formId) {
        const form = document.getElementById(formId);
        if (!form) return;
        
        form.addEventListener('submit', (e) => {
            let formIsValid = true;
            
            // Check email
            const emailInput = form.querySelector('input[type="email"]');
            if (emailInput && emailInput.id) {
                const emailError = this.getErrorElement(emailInput);
                if (!this.validate(emailInput, 'email', emailError)) {
                    formIsValid = false;
                }
            }
            
            // Check password
            const passwordInput = form.querySelector('input[type="password"]');
            if (passwordInput && passwordInput.id) {
                const passwordError = this.getErrorElement(passwordInput);
                if (!this.validate(passwordInput, 'password', passwordError)) {
                    formIsValid = false;
                }
            }
            
            
            // Check confirm password if it exists
            const confirmInput = form.querySelector('input[name="password_confirm"]');
            if (confirmInput && passwordInput) {
                const confirmError = this.getErrorElement(confirmInput);
                if (confirmInput.value !== passwordInput.value) {
                    formIsValid = false;
                    this.showError(confirmInput, confirmError, "Passwords do not match.", false);
                } else if (!confirmInput.value) {
                    formIsValid = false;
                    this.showError(confirmInput, confirmError, "Please confirm your password.", false);
                } else {
                    this.showError(confirmInput, confirmError, "", true);
                }
            }

            // Check text inputs (like username)
            const textInputs = form.querySelectorAll('input[type="text"][required]');
            textInputs.forEach(input => {
                if (input.id) {
                    const textError = this.getErrorElement(input);
                    if (!this.validate(input, 'username', textError)) {
                        formIsValid = false;
                    }
                }
            });

            const requiredHiddenInputs = form.querySelectorAll('input[type="hidden"][required]');
            requiredHiddenInputs.forEach(input => {
                if (input.value.trim() === '') {
                    formIsValid = false;
                    const customContainer = input.closest('.custom-select-container');
                    if (customContainer) {
                        const trigger = customContainer.querySelector('button[aria-haspopup="listbox"]');
                        if (trigger) {
                            trigger.setAttribute('aria-invalid', 'true');
                            trigger.classList.add('border-red-500');
                        }
                    }
                }
            });
            
            
            if (formIsValid) {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    // Create a spinner or loading state
                    const originalText = submitBtn.innerHTML;
                    submitBtn.setAttribute('data-original-text', originalText);
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
                    submitBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Processing...';
                }
            }

            if (!formIsValid) {
                e.preventDefault();
            }
        });
        
        // Add real-time validation on blur
        form.querySelectorAll('input').forEach(input => {
            input.addEventListener('blur', () => {
                let type = input.type;
                if (type === 'text') type = 'username';
                if (input.id) {
                    const errorElem = this.getErrorElement(input);
                    this.validate(input, type, errorElem);
                }
            });
        });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    // Only initialized if explicitly requested, or auto initialized for main forms
    FormValidator.attachValidation('loginForm');
    FormValidator.attachValidation('registerForm');
    FormValidator.attachValidation('profileForm');
    FormValidator.attachValidation('postJobForm');
    FormValidator.attachValidation('applyForm');
});