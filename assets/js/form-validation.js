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
});