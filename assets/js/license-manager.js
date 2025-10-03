/**
 * Zwicky Technology License Management System
 * Enhanced License Manager JavaScript
 * 
 * @author Zwicky Technology
 * @version 2.0.0
 * @since 2025
 */

// Global variables
let currentEditingLicense = null;
let searchTimeout = null;
let currentStep = 1;
const totalSteps = 3;

// Document ready
document.addEventListener('DOMContentLoaded', function() {
    initializeLicenseManager();
});

/**
 * Initialize the license manager
 */
function initializeLicenseManager() {
    // Set default expiration date (1 year from now)
    const expirationInput = document.getElementById('expiresAt');
    if (expirationInput) {
        const oneYearFromNow = new Date();
        oneYearFromNow.setFullYear(oneYearFromNow.getFullYear() + 1);
        expirationInput.value = formatDateTimeLocal(oneYearFromNow);
    }
    
    // Handle extend days selection
    const extendDaysSelect = document.getElementById('extendDays');
    if (extendDaysSelect) {
        extendDaysSelect.addEventListener('change', function() {
            const customDaysGroup = document.getElementById('customDaysGroup');
            if (this.value === 'custom') {
                customDaysGroup.style.display = 'block';
                document.getElementById('customDays').required = true;
            } else {
                customDaysGroup.style.display = 'none';
                document.getElementById('customDays').required = false;
            }
        });
    }
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            closeAllModals();
        }
    });
    
    // Handle escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeAllModals();
        }
    });
}

/**
 * Format date time for local input
 */
function formatDateTimeLocal(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

/**
 * Step Navigation Functions
 */
function nextStep() {
    if (validateCurrentStep()) {
        if (currentStep < totalSteps) {
            currentStep++;
            showStep(currentStep);
            updatePreview();
        }
    }
}

function previousStep() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
    }
}

function showStep(step) {
    // Hide all steps
    document.querySelectorAll('.form-step').forEach(stepElement => {
        stepElement.style.display = 'none';
    });
    
    // Show current step
    const currentStepElement = document.querySelector(`[data-step="${step}"]`);
    if (currentStepElement) {
        currentStepElement.style.display = 'block';
    }
    
    // Update progress indicators
    document.querySelectorAll('.step').forEach((stepElement, index) => {
        if (index + 1 <= step) {
            stepElement.classList.add('active');
        } else {
            stepElement.classList.remove('active');
        }
    });
    
    // Update navigation buttons
    updateNavigationButtons();
}

function updateNavigationButtons() {
    const prevBtn = document.getElementById('prevStepBtn');
    const nextBtn = document.getElementById('nextStepBtn');
    const submitBtn = document.getElementById('submitBtn');
    
    if (!prevBtn || !nextBtn || !submitBtn) return;
    
    // Previous button
    if (currentStep === 1) {
        prevBtn.style.display = 'none';
    } else {
        prevBtn.style.display = 'block';
    }
    
    // Next/Submit button
    if (currentStep === totalSteps) {
        nextBtn.style.display = 'none';
        submitBtn.style.display = 'block';
        const btnText = submitBtn.querySelector('.btn-text');
        if (btnText) {
            btnText.textContent = currentEditingLicense ? 'Update License' : 'Create License';
        }
    } else {
        nextBtn.style.display = 'block';
        submitBtn.style.display = 'none';
    }
}

function resetSteps() {
    currentStep = 1;
    
    // Clear all validation states
    document.querySelectorAll('.enhanced-input, .enhanced-select, .enhanced-textarea').forEach(input => {
        input.classList.remove('valid', 'invalid');
    });
    
    // Hide all error messages
    document.querySelectorAll('.field-error').forEach(error => {
        error.style.display = 'none';
        error.classList.remove('show');
    });
    
    // Reset form messages
    const formMessages = document.getElementById('formMessages');
    if (formMessages) {
        formMessages.style.display = 'none';
        formMessages.className = 'form-messages';
    }
}

/**
 * Form Validation Functions
 */
function validateCurrentStep() {
    let isValid = true;
    let firstInvalidField = null;
    
    const currentStepElement = document.querySelector(`[data-step="${currentStep}"]`);
    if (!currentStepElement) return true;
    
    const requiredFields = currentStepElement.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!validateField({ target: field })) {
            isValid = false;
            if (!firstInvalidField) {
                firstInvalidField = field;
            }
        }
    });
    
    // Additional step-specific validation
    if (currentStep === 1) {
        const emailField = document.getElementById('customerEmail');
        if (emailField && !validateEmailField({ target: emailField })) {
            isValid = false;
            if (!firstInvalidField) firstInvalidField = emailField;
        }
    }
    
    if (currentStep === 2) {
        const domainsField = document.getElementById('allowedDomains');
        if (domainsField && domainsField.value && !validateDomainField({ target: domainsField })) {
            isValid = false;
            if (!firstInvalidField) firstInvalidField = domainsField;
        }
    }
    
    if (!isValid && firstInvalidField) {
        firstInvalidField.focus();
        showFormMessage('Please fix the errors before continuing.', 'error');
    } else {
        hideFormMessage();
    }
    
    return isValid;
}

function validateField(event) {
    const field = event.target;
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';
    
    // Required field validation
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'This field is required.';
    }
    
    // Field-specific validation
    if (isValid && value) {
        switch (field.type) {
            case 'email':
                isValid = validateEmail(value);
                if (!isValid) errorMessage = 'Please enter a valid email address.';
                break;
            case 'number':
                const min = parseInt(field.min) || 1;
                const max = parseInt(field.max) || 999;
                const numValue = parseInt(value);
                if (numValue < min || numValue > max) {
                    isValid = false;
                    errorMessage = `Value must be between ${min} and ${max}.`;
                }
                break;
        }
    }
    
    // Update field appearance
    updateFieldState(field, isValid, errorMessage);
    
    return isValid;
}

function validateEmailField(event) {
    const field = event.target;
    const email = field.value.trim();
    let isValid = true;
    let errorMessage = '';
    
    if (email) {
        if (!validateEmail(email)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address.';
        }
    }
    
    updateFieldState(field, isValid, errorMessage);
    return isValid;
}

function validateDomainField(event) {
    const field = event.target;
    const domains = field.value.trim();
    let isValid = true;
    let errorMessage = '';
    
    if (domains) {
        const domainList = domains.split(',').map(d => d.trim()).filter(d => d);
        for (const domain of domainList) {
            if (!validateDomain(domain)) {
                isValid = false;
                errorMessage = `Invalid domain: ${domain}. Use formats like example.com or *.example.com`;
                break;
            }
        }
    }
    
    updateFieldState(field, isValid, errorMessage);
    return isValid;
}

function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function validateDomain(domain) {
    // Allow wildcards and standard domains
    const domainRegex = /^(\*\.)?[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$|^localhost$/;
    return domainRegex.test(domain);
}

function updateFieldState(field, isValid, errorMessage) {
    const errorElement = document.getElementById(field.id + '-error');
    
    // Update field classes
    field.classList.remove('valid', 'invalid');
    if (field.value.trim()) {
        field.classList.add(isValid ? 'valid' : 'invalid');
    }
    
    // Update error message
    if (errorElement) {
        if (isValid || !field.value.trim()) {
            errorElement.style.display = 'none';
            errorElement.classList.remove('show');
        } else {
            errorElement.textContent = errorMessage;
            errorElement.style.display = 'block';
            errorElement.classList.add('show');
        }
    }
}

function clearFieldError(event) {
    const field = event.target;
    if (field.classList.contains('invalid') && field.value.trim()) {
        // Re-validate on input
        setTimeout(() => validateField(event), 100);
    }
}

/**
 * Utility Functions for Enhanced UX
 */
function adjustNumber(fieldId, change) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    const currentValue = parseInt(field.value) || 1;
    const min = parseInt(field.min) || 1;
    const max = parseInt(field.max) || 999;
    
    const newValue = Math.max(min, Math.min(max, currentValue + change));
    field.value = newValue;
    
    // Trigger validation
    validateField({ target: field });
}

function setQuickDate(days) {
    const expiresAt = document.getElementById('expiresAt');
    if (!expiresAt) return;
    
    if (days === 0) {
        // Lifetime license - set far future date
        const farFuture = new Date();
        farFuture.setFullYear(farFuture.getFullYear() + 100);
        expiresAt.value = formatDateTimeLocal(farFuture);
    } else {
        const futureDate = new Date();
        futureDate.setDate(futureDate.getDate() + days);
        expiresAt.value = formatDateTimeLocal(futureDate);
    }
    
    // Trigger validation
    validateField({ target: expiresAt });
}

function addDomainExample(domain) {
    const domainsField = document.getElementById('allowedDomains');
    if (!domainsField) return;
    
    const currentValue = domainsField.value.trim();
    
    if (currentValue) {
        domainsField.value = currentValue + ', ' + domain;
    } else {
        domainsField.value = domain;
    }
    
    // Trigger validation
    validateDomainField({ target: domainsField });
}

function updateCharacterCounter() {
    const notesField = document.getElementById('notes');
    const counter = document.getElementById('notesCounter');
    
    if (notesField && counter) {
        const currentLength = notesField.value.length;
        counter.textContent = currentLength;
        
        // Update color based on limit
        if (currentLength > 450) {
            counter.style.color = 'var(--error-color)';
        } else if (currentLength > 400) {
            counter.style.color = 'var(--warning-color)';
        } else {
            counter.style.color = 'var(--text-muted)';
        }
    }
}

function updatePreview() {
    if (currentStep === 3) {
        const productName = document.getElementById('productName')?.value || 'Product Name';
        const customerName = document.getElementById('customerName')?.value || 'Customer Name';
        const customerEmail = document.getElementById('customerEmail')?.value || 'customer@example.com';
        const maxActivations = document.getElementById('maxActivations')?.value || '1';
        const expiresAt = document.getElementById('expiresAt')?.value;
        const status = document.getElementById('status')?.value;
        
        // Update preview elements if they exist
        const previewProduct = document.querySelector('.preview-product');
        const previewCustomer = document.querySelector('.preview-customer');
        const previewEmail = document.querySelector('.preview-email');
        const previewActivations = document.querySelector('.preview-activations');
        const previewExpires = document.querySelector('.preview-expires');
        const previewStatus = document.querySelector('.preview-status');
        
        if (previewProduct) previewProduct.textContent = productName;
        if (previewCustomer) previewCustomer.textContent = customerName;
        if (previewEmail) previewEmail.textContent = customerEmail;
        if (previewActivations) previewActivations.textContent = maxActivations;
        
        // Format expiration date
        if (expiresAt && previewExpires) {
            const expDate = new Date(expiresAt);
            const isLifetime = expDate.getFullYear() > 2050;
            previewExpires.textContent = isLifetime ? 'Lifetime' : expDate.toLocaleDateString();
        }
        
        // Update status
        if (status && previewStatus) {
            const statusEmoji = status === 'active' ? '🟢' : status === 'suspended' ? '🟡' : '🔴';
            previewStatus.textContent = statusEmoji + ' ' + status.charAt(0).toUpperCase() + status.slice(1);
        }
    }
}

function showFormMessage(message, type) {
    const formMessages = document.getElementById('formMessages');
    if (formMessages) {
        formMessages.textContent = message;
        formMessages.className = `form-messages ${type}`;
        formMessages.style.display = 'block';
    }
}

function hideFormMessage() {
    const formMessages = document.getElementById('formMessages');
    if (formMessages) {
        formMessages.style.display = 'none';
    }
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Open create license modal with enhanced multi-step interface
 */
function openCreateLicenseModal() {
    currentEditingLicense = null;
    currentStep = 1;
    
    // Reset modal state
    document.getElementById('modalTitle').textContent = 'Create New License';
    const modalSubtitle = document.querySelector('.modal-subtitle');
    if (modalSubtitle) {
        modalSubtitle.textContent = 'Generate a new software license for your customer';
    }
    
    document.getElementById('licenseForm').reset();
    document.getElementById('licenseId').value = '';
    
    // Show/hide appropriate elements for create mode
    const progressElement = document.getElementById('licenseProgress');
    if (progressElement) {
        progressElement.style.display = 'block';
    }
    
    const statusElement = document.getElementById('status');
    if (statusElement && statusElement.parentElement) {
        statusElement.parentElement.style.display = 'none';
    }
    
    // Reset all steps
    resetSteps();
    showStep(1);
    
    // Set default values
    setDefaultValues();
    
    // Initialize form validation
    initializeFormValidation();
    
    // Show modal
    const modal = document.getElementById('licenseModal');
    modal.style.display = 'flex';
    modal.classList.add('show');
    
    // Focus first input
    setTimeout(() => {
        const firstInput = document.getElementById('productName');
        if (firstInput) {
            firstInput.focus();
        }
    }, 100);
}

/**
 * Set default values for new license
 */
function setDefaultValues() {
    // Set default expiration date (1 year from now)
    const expirationInput = document.getElementById('expiresAt');
    if (expirationInput) {
        const oneYearFromNow = new Date();
        oneYearFromNow.setFullYear(oneYearFromNow.getFullYear() + 1);
        expirationInput.value = formatDateTimeLocal(oneYearFromNow);
    }
    
    // Set default max activations
    const maxActivationsInput = document.getElementById('maxActivations');
    if (maxActivationsInput) {
        maxActivationsInput.value = '1';
    }
    
    // Set default status
    const statusInput = document.getElementById('status');
    if (statusInput) {
        statusInput.value = 'active';
    }
}

/**
 * Initialize enhanced form validation
 */
function initializeFormValidation() {
    const form = document.getElementById('licenseForm');
    if (!form) return;
    
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        // Remove existing event listeners
        input.removeEventListener('blur', validateField);
        input.removeEventListener('input', clearFieldError);
        
        // Add new event listeners
        input.addEventListener('blur', validateField);
        input.addEventListener('input', clearFieldError);
        
        // Special handling for specific fields
        if (input.id === 'customerEmail') {
            input.addEventListener('input', debounce(validateEmailField, 500));
        }
        
        if (input.id === 'allowedDomains') {
            input.addEventListener('input', debounce(validateDomainField, 500));
        }
        
        if (input.id === 'notes') {
            input.addEventListener('input', updateCharacterCounter);
        }
    });
    
    // Initialize character counter
    updateCharacterCounter();
}

/**
 * Edit license
 */
async function editLicense(licenseId) {
    try {
        const response = await fetch('license-manager.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_license_details&license_id=${licenseId}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            const license = result.license;
            currentEditingLicense = licenseId;
            currentStep = 1;
            
            // Set modal to edit mode
            document.getElementById('modalTitle').textContent = 'Edit License';
            const modalSubtitle = document.querySelector('.modal-subtitle');
            if (modalSubtitle) {
                modalSubtitle.textContent = 'Update license information and settings';
            }
            
            // Hide progress steps for edit mode
            const progressElement = document.getElementById('licenseProgress');
            if (progressElement) {
                progressElement.style.display = 'none';
            }
            
            // Show all form sections for edit mode
            document.querySelectorAll('.form-step').forEach(step => {
                step.style.display = 'block';
            });
            
            // Populate form fields
            document.getElementById('licenseId').value = license.id;
            document.getElementById('productName').value = license.product_name;
            document.getElementById('customerName').value = license.customer_name;
            document.getElementById('customerEmail').value = license.customer_email;
            document.getElementById('maxActivations').value = license.max_activations;
            document.getElementById('expiresAt').value = formatDateTimeLocal(new Date(license.expires_at));
            document.getElementById('allowedDomains').value = license.allowed_domains || '';
            document.getElementById('features').value = license.features || '';
            document.getElementById('notes').value = license.notes || '';
            document.getElementById('status').value = license.status;
            
            // Show status field for editing
            const statusElement = document.getElementById('status');
            if (statusElement && statusElement.parentElement) {
                statusElement.parentElement.style.display = 'block';
            }
            
            // Update navigation for edit mode
            const prevBtn = document.getElementById('prevStepBtn');
            const nextBtn = document.getElementById('nextStepBtn');
            const submitBtn = document.getElementById('submitBtn');
            
            if (prevBtn) prevBtn.style.display = 'none';
            if (nextBtn) nextBtn.style.display = 'none';
            if (submitBtn) {
                submitBtn.style.display = 'block';
                const btnText = submitBtn.querySelector('.btn-text');
                if (btnText) {
                    btnText.textContent = 'Update License';
                }
            }
            
            // Initialize validation for edit mode
            initializeFormValidation();
            
            // Show modal
            document.getElementById('licenseModal').style.display = 'flex';
            document.getElementById('productName').focus();
        } else {
            showNotification('Error loading license details: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error loading license details', 'error');
    }
}

/**
 * Save license (create or update)
 */
async function saveLicense(event) {
    event.preventDefault();
    
    // Final validation before submission
    if (!currentEditingLicense) {
        // For create mode, validate all steps
        const isValid = validateAllSteps();
        if (!isValid) {
            showFormMessage('Please fix all errors before saving.', 'error');
            return;
        }
    } else {
        // For edit mode, validate visible fields
        const form = document.getElementById('licenseForm');
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!validateField({ target: input })) {
                isValid = false;
            }
        });
        
        if (!isValid) {
            showFormMessage('Please fix all errors before saving.', 'error');
            return;
        }
    }
    
    const form = event.target;
    const formData = new FormData(form);
    
    const action = currentEditingLicense ? 'update_license' : 'create_license';
    formData.append('action', action);
    
    try {
        const submitButton = document.getElementById('submitBtn');
        const btnText = submitButton?.querySelector('.btn-text');
        const btnLoader = submitButton?.querySelector('.btn-loader');
        
        // Show loading state
        if (submitButton) {
            submitButton.disabled = true;
            if (btnText) btnText.style.display = 'none';
            if (btnLoader) btnLoader.style.display = 'inline-block';
        }
        
        hideFormMessage();
        
        const response = await fetch('license-manager.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showFormMessage(result.message, 'success');
            
            // Delay modal close for better UX
            setTimeout(() => {
                closeLicenseModal();
                showNotification(result.message, 'success');
                location.reload();
            }, 1500);
        } else {
            showFormMessage('Error: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showFormMessage('Error saving license. Please try again.', 'error');
    } finally {
        // Reset button state
        const submitButton = document.getElementById('submitBtn');
        const btnText = submitButton?.querySelector('.btn-text');
        const btnLoader = submitButton?.querySelector('.btn-loader');
        
        setTimeout(() => {
            if (submitButton) {
                submitButton.disabled = false;
                if (btnText) btnText.style.display = 'inline';
                if (btnLoader) btnLoader.style.display = 'none';
            }
        }, 1000);
    }
}

/**
 * Validate all steps for create mode
 */
function validateAllSteps() {
    let allValid = true;
    let firstInvalidStep = null;
    
    for (let step = 1; step <= totalSteps; step++) {
        const stepElement = document.querySelector(`[data-step="${step}"]`);
        if (!stepElement) continue;
        
        const requiredFields = stepElement.querySelectorAll('[required]');
        let stepValid = true;
        
        requiredFields.forEach(field => {
            if (!validateField({ target: field })) {
                stepValid = false;
                allValid = false;
            }
        });
        
        // Additional step-specific validation
        if (step === 1) {
            const emailField = document.getElementById('customerEmail');
            if (emailField && !validateEmailField({ target: emailField })) {
                stepValid = false;
                allValid = false;
            }
        }
        
        if (step === 2) {
            const domainsField = document.getElementById('allowedDomains');
            if (domainsField && domainsField.value && !validateDomainField({ target: domainsField })) {
                stepValid = false;
                allValid = false;
            }
        }
        
        if (!stepValid && firstInvalidStep === null) {
            firstInvalidStep = step;
        }
    }
    
    // Navigate to first invalid step
    if (!allValid && firstInvalidStep) {
        currentStep = firstInvalidStep;
        showStep(currentStep);
    }
    
    return allValid;
}

/**
 * Close license modal
 */
function closeLicenseModal() {
    const modal = document.getElementById('licenseModal');
    modal.style.display = 'none';
    modal.classList.remove('show');
    currentEditingLicense = null;
    currentStep = 1;
    
    // Reset form state
    resetSteps();
    
    // Clear form
    const form = document.getElementById('licenseForm');
    if (form) {
        form.reset();
    }
    
    // Hide progress for edit mode
    const progressElement = document.getElementById('licenseProgress');
    if (progressElement) {
        progressElement.style.display = 'none';
    }
}

/**
 * Extend license
 */
function extendLicense(licenseId) {
    document.getElementById('extendLicenseId').value = licenseId;
    document.getElementById('extendForm').reset();
    document.getElementById('extendDays').value = '365';
    const customDaysGroup = document.getElementById('customDaysGroup');
    if (customDaysGroup) {
        customDaysGroup.style.display = 'none';
    }
    document.getElementById('extendModal').style.display = 'flex';
}

/**
 * Save extend license
 */
async function saveExtendLicense(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    let extendDays = formData.get('extend_days');
    if (extendDays === 'custom') {
        extendDays = formData.get('custom_days');
    }
    
    formData.set('extend_days', extendDays);
    formData.append('action', 'extend_license');
    
    try {
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Extending...';
        
        const response = await fetch('license-manager.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            closeExtendModal();
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showNotification('Error: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error extending license', 'error');
    } finally {
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-calendar-plus"></i> Extend License';
    }
}

/**
 * Close extend modal
 */
function closeExtendModal() {
    document.getElementById('extendModal').style.display = 'none';
}

/**
 * View license details
 */
async function viewLicenseDetails(licenseId) {
    try {
        const response = await fetch('license-manager.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_license_details&license_id=${licenseId}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            const license = result.license;
            const content = generateLicenseDetailsHTML(license);
            document.getElementById('licenseDetailsContent').innerHTML = content;
            document.getElementById('detailsModal').style.display = 'flex';
        } else {
            showNotification('Error loading license details: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error loading license details', 'error');
    }
}

/**
 * Generate license details HTML
 */
function generateLicenseDetailsHTML(license) {
    const expiresAt = new Date(license.expires_at);
    const createdAt = new Date(license.created_at);
    const now = new Date();
    const daysLeft = Math.ceil((expiresAt - now) / (1000 * 60 * 60 * 24));
    const isExpired = expiresAt < now;
    
    return `
        <div class="license-details">
            <div class="details-header">
                <div class="license-key-display">
                    <label>License Key:</label>
                    <div class="key-container">
                        <code>${license.license_key}</code>
                        <button class="btn btn-sm btn-secondary" onclick="copyLicenseKey('${license.license_key}')">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
                <div class="license-status">
                    <span class="status-badge status-${license.status}">${license.status.charAt(0).toUpperCase() + license.status.slice(1)}</span>
                </div>
            </div>
            
            <div class="details-grid">
                <div class="detail-card">
                    <h4><i class="fas fa-box"></i> Product Information</h4>
                    <div class="detail-item">
                        <label>Product Name:</label>
                        <span>${license.product_name}</span>
                    </div>
                    <div class="detail-item">
                        <label>Features:</label>
                        <span>${license.features || 'No features specified'}</span>
                    </div>
                </div>
                
                <div class="detail-card">
                    <h4><i class="fas fa-user"></i> Customer Information</h4>
                    <div class="detail-item">
                        <label>Name:</label>
                        <span>${license.customer_name}</span>
                    </div>
                    <div class="detail-item">
                        <label>Email:</label>
                        <span><a href="mailto:${license.customer_email}">${license.customer_email}</a></span>
                    </div>
                </div>
                
                <div class="detail-card">
                    <h4><i class="fas fa-calendar"></i> License Dates</h4>
                    <div class="detail-item">
                        <label>Created:</label>
                        <span>${createdAt.toLocaleString()}</span>
                    </div>
                    <div class="detail-item">
                        <label>Expires:</label>
                        <span class="${isExpired ? 'text-danger' : (daysLeft <= 30 ? 'text-warning' : '')}">${expiresAt.toLocaleString()}</span>
                    </div>
                    <div class="detail-item">
                        <label>Days Left:</label>
                        <span class="${isExpired ? 'text-danger' : (daysLeft <= 30 ? 'text-warning' : '')}">
                            ${isExpired ? `Expired ${Math.abs(daysLeft)} days ago` : `${daysLeft} days`}
                        </span>
                    </div>
                </div>
                
                <div class="detail-card">
                    <h4><i class="fas fa-server"></i> Activation Information</h4>
                    <div class="detail-item">
                        <label>Max Activations:</label>
                        <span>${license.max_activations}</span>
                    </div>
                    <div class="detail-item">
                        <label>Current Activations:</label>
                        <span>${license.activation_count || 0}</span>
                    </div>
                    <div class="detail-item">
                        <label>Allowed Domains:</label>
                        <span>${license.allowed_domains || 'Any domain'}</span>
                    </div>
                </div>
            </div>
            
            ${license.notes ? `
                <div class="detail-card full-width">
                    <h4><i class="fas fa-sticky-note"></i> Notes</h4>
                    <div class="notes-content">${license.notes}</div>
                </div>
            ` : ''}
            
            <div class="details-actions">
                <button class="btn btn-primary" onclick="editLicense(${license.id}); closeDetailsModal();">
                    <i class="fas fa-edit"></i> Edit License
                </button>
                <button class="btn btn-info" onclick="extendLicense(${license.id}); closeDetailsModal();">
                    <i class="fas fa-calendar-plus"></i> Extend License
                </button>
                <button class="btn btn-warning" onclick="regenerateKey(${license.id})">
                    <i class="fas fa-sync"></i> Regenerate Key
                </button>
            </div>
        </div>
    `;
}

/**
 * Close details modal
 */
function closeDetailsModal() {
    document.getElementById('detailsModal').style.display = 'none';
}

/**
 * Close all modals
 */
function closeAllModals() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.style.display = 'none';
        modal.classList.remove('show');
    });
}

/**
 * Regenerate license key
 */
async function regenerateKey(licenseId) {
    if (!confirm('Are you sure you want to regenerate this license key? The old key will no longer work.')) {
        return;
    }
    
    try {
        const response = await fetch('license-manager.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=regenerate_key&license_id=${licenseId}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('License key regenerated successfully', 'success');
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showNotification('Error: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error regenerating license key', 'error');
    }
}

/**
 * Suspend license
 */
async function suspendLicense(licenseId) {
    if (!confirm('Are you sure you want to suspend this license?')) {
        return;
    }
    
    try {
        const response = await fetch('license-manager.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_license&license_id=${licenseId}&status=suspended`
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('License suspended successfully', 'success');
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showNotification('Error: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error suspending license', 'error');
    }
}

/**
 * Revoke license
 */
async function revokeLicense(licenseId) {
    if (!confirm('Are you sure you want to revoke this license? This action cannot be undone.')) {
        return;
    }
    
    try {
        const response = await fetch('license-manager.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=revoke_license&license_id=${licenseId}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('License revoked successfully', 'success');
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showNotification('Error: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error revoking license', 'error');
    }
}

/**
 * Copy license key to clipboard
 */
async function copyLicenseKey(licenseKey) {
    try {
        await navigator.clipboard.writeText(licenseKey);
        showNotification('License key copied to clipboard', 'success');
    } catch (error) {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = licenseKey;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showNotification('License key copied to clipboard', 'success');
    }
}

/**
 * Apply filters
 */
function applyFilters() {
    const status = document.getElementById('status-filter')?.value;
    const search = document.getElementById('search-input')?.value;
    
    const url = new URL(window.location);
    if (status) url.searchParams.set('status', status);
    if (search) url.searchParams.set('search', search);
    url.searchParams.set('page', '1');
    
    window.location.href = url.toString();
}

/**
 * Handle search input keyup
 */
function handleSearchKeyup(event) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        if (event.key === 'Enter') {
            applyFilters();
        }
    }, 300);
}

/**
 * Toggle dropdown menu
 */
function toggleDropdown(button) {
    const dropdown = button.parentElement;
    const menu = dropdown.querySelector('.dropdown-menu');
    
    // Close all other dropdowns
    document.querySelectorAll('.dropdown-menu.show').forEach(d => {
        if (d !== menu) {
            d.classList.remove('show');
        }
    });
    
    menu.classList.toggle('show');
}

/**
 * Show expiring licenses
 */
function showExpiringLicenses() {
    const url = new URL(window.location);
    url.searchParams.set('status', 'active');
    url.searchParams.set('expiring', '30');
    window.location.href = url.toString();
}

/**
 * Export licenses
 */
function exportLicenses() {
    const url = new URL(window.location);
    url.searchParams.set('export', 'csv');
    window.open(url.toString(), '_blank');
}

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    // Remove existing notifications
    document.querySelectorAll('.notification').forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
            <span>${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});