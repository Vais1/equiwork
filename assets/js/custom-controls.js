/**
 * custom-controls.js
 * 
 * Provides accessible, JS-powered custom UI components that replace native 
 * default browser dropdown menus, select inputs, and checkboxes across EquiWork.
 * 
 * Key Features:
 * - 100% Keyboard Navigable (Arrow keys, Space, Enter, Escape)
 * - Comprehensive ARIA attribute management (aria-expanded, aria-checked, role="listbox")
 * - Focus management and seamless integration with standard form submissions
 */

document.addEventListener('DOMContentLoaded', () => {
    
    // ==========================================
    // CUSTOM SELECT COMPONENTS
    // ==========================================
    const customSelects = document.querySelectorAll('.custom-select-container');
    
    customSelects.forEach((container, sIdx) => {
        const btn = container.querySelector('button[aria-haspopup="listbox"]');
        const list = container.querySelector('ul[role="listbox"]');
        const options = list.querySelectorAll('li[role="option"]');
        const hiddenInput = container.querySelector('input[type="hidden"]');
        const textSpan = btn.querySelector('.custom-select-text');
        
        let isOpen = false;
        let focusedIndex = -1;

        // Ensure ARIA IDs are present for active-descendant
        const listId = list.id || `custom-select-list-${sIdx}`;
        list.id = listId;
        btn.setAttribute('aria-controls', listId);

        options.forEach((opt, idx) => {
            if (!opt.id) opt.id = `${listId}-opt-${idx}`;
            opt.setAttribute('aria-selected', 'false'); // default state
        });

        // Sync initial state if value exists
        const initValue = hiddenInput.value;
        if (initValue) {
            const initialOpt = Array.from(options).find(o => o.getAttribute('data-value') === initValue);
            if (initialOpt) {
                initialOpt.setAttribute('aria-selected', 'true');
                textSpan.textContent = initialOpt.textContent;
            }
        }

        // Listener for programmatic updates
        hiddenInput.addEventListener('customUpdate', (e) => {
            const val = e.detail.value;
            const optToSelect = Array.from(options).find(o => o.getAttribute('data-value') === val);
            if (optToSelect) {
                options.forEach(opt => opt.setAttribute('aria-selected', 'false'));
                optToSelect.setAttribute('aria-selected', 'true');
                textSpan.textContent = optToSelect.textContent;
                hiddenInput.value = val;
            }
        });

        const toggleList = (show) => {
            isOpen = show;
            btn.setAttribute('aria-expanded', isOpen);
            if (isOpen) {
                list.classList.remove('hidden');
                // Scroll to selected
                const selectedOptIndex = Array.from(options).findIndex(o => o.getAttribute('aria-selected') === 'true');
                focusedIndex = selectedOptIndex > -1 ? selectedOptIndex : 0;
                updateFocus();
                list.focus();
            } else {
                list.classList.add('hidden');
                list.removeAttribute('aria-activedescendant');
                btn.focus();
            }
        };

        const updateFocus = () => {
            options.forEach((opt, idx) => {
                if (idx === focusedIndex) {
                    opt.classList.add('bg-blue-100', 'dark:bg-blue-900', 'text-blue-900', 'dark:text-blue-100');
                    opt.scrollIntoView({ block: 'nearest' });
                    list.setAttribute('aria-activedescendant', opt.id);
                } else {
                    opt.classList.remove('bg-blue-100', 'dark:bg-blue-900', 'text-blue-900', 'dark:text-blue-100');
                }
            });
        };

        const selectOption = (index) => {
            if (index < 0 || index >= options.length) return;
            options.forEach(opt => opt.setAttribute('aria-selected', 'false'));
            const selected = options[index];
            selected.setAttribute('aria-selected', 'true');
            textSpan.textContent = selected.textContent;
            hiddenInput.value = selected.getAttribute('data-value');
            
            // Dispatch input/change event on hidden input so other scripts (like validation) can catch it
            hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            toggleList(false);
        };

        btn.addEventListener('click', (e) => {
            e.preventDefault();
            toggleList(!isOpen);
        });

        btn.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                toggleList(true);
            }
        });

        list.addEventListener('keydown', (e) => {
            if (!isOpen) return;
            switch(e.key) {
                case 'Escape':
                    e.preventDefault();
                    toggleList(false);
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    focusedIndex = (focusedIndex + 1) % options.length;
                    updateFocus();
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    focusedIndex = (focusedIndex - 1 + options.length) % options.length;
                    updateFocus();
                    break;
                case 'Home':
                    e.preventDefault();
                    focusedIndex = 0;
                    updateFocus();
                    break;
                case 'End':
                    e.preventDefault();
                    focusedIndex = options.length - 1;
                    updateFocus();
                    break;
                case 'Enter':
                case ' ':
                    e.preventDefault();
                    selectOption(focusedIndex);
                    break;
            }
        });
        
        // Handle mouse interactions on options
        options.forEach((opt, idx) => {
            opt.addEventListener('click', () => {
                selectOption(idx);
            });
            opt.addEventListener('mouseenter', () => {
                focusedIndex = idx;
                updateFocus();
            });
        });

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!container.contains(e.target) && isOpen) {
                toggleList(false);
            }
        });
    });

    // ==========================================
    // CUSTOM CHECKBOX COMPONENTS
    // ==========================================
    const customCheckboxes = document.querySelectorAll('.custom-checkbox-container');
    
    customCheckboxes.forEach(container => {
        const hiddenInput = container.querySelector('input[type="hidden"]');
        const box = container.querySelector('.checkbox-box');
        const icon = box.querySelector('svg');
        
        // Initialize state
        let isChecked = container.getAttribute('aria-checked') === 'true';
        if (isChecked) {
            hiddenInput.removeAttribute('disabled');
            box.classList.add('bg-blue-600', 'border-blue-600');
            box.classList.remove('bg-gray-100', 'dark:bg-gray-700', 'border-gray-300', 'dark:border-gray-600');
            icon.classList.remove('hidden');
        }

        const toggleCheckbox = () => {
            isChecked = !isChecked;
            container.setAttribute('aria-checked', isChecked);
            
            if (isChecked) {
                hiddenInput.removeAttribute('disabled');
                box.classList.add('bg-blue-600', 'border-blue-600');
                box.classList.remove('bg-gray-100', 'dark:bg-gray-700', 'border-gray-300', 'dark:border-gray-600');
                icon.classList.remove('hidden');
            } else {
                hiddenInput.setAttribute('disabled', 'true');
                box.classList.remove('bg-blue-600', 'border-blue-600');
                box.classList.add('bg-gray-100', 'dark:bg-gray-700', 'border-gray-300', 'dark:border-gray-600');
                icon.classList.add('hidden');
            }
            
            // Dispatch change event to trigger form/filter submissions naturally
            container.dispatchEvent(new Event('change', { bubbles: true }));
        };

        container.addEventListener('click', (e) => {
            e.preventDefault();
            toggleCheckbox();
        });

        container.addEventListener('keydown', (e) => {
            if (e.key === ' ' || e.key === 'Enter') {
                e.preventDefault();
                toggleCheckbox();
            }
        });
    });
});
