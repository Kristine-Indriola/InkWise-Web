document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const emailInput = document.querySelector('input[name="email"]');
    const passwordInput = document.querySelector('input[name="password"]');
    const submitButton = document.querySelector('button[type="submit"]');
    const strengthIndicator = document.getElementById('password-strength');
    const strengthBar = strengthIndicator ? strengthIndicator.querySelector('.password-strength__bar') : null;
    const strengthLabel = strengthIndicator ? strengthIndicator.querySelector('.password-strength__label') : null;
    const togglePassword = document.getElementById('togglePassword');
    const loginContainer = document.getElementById('loginContainer');
    const errorContainer = document.createElement('div');
    errorContainer.id = 'inline-errors';
    errorContainer.setAttribute('aria-live', 'assertive');
    errorContainer.style.display = 'none';
    errorContainer.setAttribute('role', 'alert');
    errorContainer.setAttribute('aria-hidden', 'true');
    if (!form) {
        return;
    }

    form.insertBefore(errorContainer, form.firstChild);

    let inputThrottleTimer;
    let inlineErrorTimeout;

    if (!emailInput || !passwordInput || !submitButton) {
        return;
    }

    // Email validation function
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    // Password strength function
    function checkPasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        return strength;
    }

    // Update strength indicator
    function updateStrengthIndicator(password) {
        if (!strengthIndicator || !strengthBar || !strengthLabel) {
            return;
        }
        const strength = checkPasswordStrength(password);
        let className;
        let text;
        if (strength <= 1) {
            className = 'weak';
            text = 'Weak';
        } else if (strength <= 3) {
            className = 'medium';
            text = 'Medium';
        } else {
            className = 'strong';
            text = 'Strong';
        }

        strengthIndicator.classList.remove('weak', 'medium', 'strong');
        strengthIndicator.classList.add(className, 'is-active');
        strengthIndicator.setAttribute('aria-hidden', 'false');
        strengthLabel.textContent = text;

        const progress = Math.min(Math.max(strength / 5, 0.2), 1);
        strengthBar.style.setProperty('--strength-progress', progress.toFixed(2));
    }

    function resetStrengthIndicator() {
        if (!strengthIndicator) {
            return;
        }
        strengthIndicator.classList.remove('is-active', 'weak', 'medium', 'strong');
        strengthIndicator.setAttribute('aria-hidden', 'true');
        if (strengthLabel) {
            strengthLabel.textContent = 'Strength';
        }
        if (strengthBar) {
            strengthBar.style.setProperty('--strength-progress', '0');
        }
    }

    // Show inline errors
    function showInlineErrors(messages) {
        clearTimeout(inlineErrorTimeout);
        errorContainer.innerHTML = messages.map(msg => `<p>${msg}</p>`).join('');
        errorContainer.classList.add('is-visible');
        errorContainer.style.display = '';
        errorContainer.setAttribute('aria-hidden', 'false');
        inlineErrorTimeout = setTimeout(() => {
            errorContainer.classList.remove('is-visible');
            errorContainer.style.display = 'none';
            errorContainer.innerHTML = '';
            errorContainer.setAttribute('aria-hidden', 'true');
            inlineErrorTimeout = null;
        }, 5000);
    }

    // Form validation on submit
    form.addEventListener('submit', function(e) {
        let isValid = true;
        let errorMessages = [];

        // Check email
        if (!emailInput.value.trim()) {
            errorMessages.push('Email is required.');
            isValid = false;
        } else if (!validateEmail(emailInput.value.trim())) {
            errorMessages.push('Please enter a valid email address.');
            isValid = false;
        }

        // Check password
        if (!passwordInput.value.trim()) {
            errorMessages.push('Password is required.');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            showInlineErrors(errorMessages);
            return false;
        }

        // Add loading state
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner"></span> Logging in...';
        submitButton.style.cursor = 'not-allowed';
        submitButton.classList.add('is-loading');
    });

    // Password strength on input with throttling
    passwordInput.addEventListener('input', function() {
        clearTimeout(inputThrottleTimer);
        inputThrottleTimer = setTimeout(() => {
            if (!strengthIndicator) {
                return;
            }
            if (this.value) {
                updateStrengthIndicator(this.value);
            } else {
                resetStrengthIndicator();
            }
        }, 300);
    });

    passwordInput.addEventListener('blur', function() {
        if (!this.value.trim()) {
            this.style.borderColor = '#ff6a88';
            resetStrengthIndicator();
        } else {
            this.style.borderColor = 'rgba(157, 194, 236, 0.65)';
        }
    });

    // Optional: Real-time validation feedback for email with throttling
    emailInput.addEventListener('input', function() {
        clearTimeout(inputThrottleTimer);
        inputThrottleTimer = setTimeout(() => {
            if (!this.value.trim()) {
                this.style.borderColor = '#ff6a88';
            } else if (!validateEmail(this.value.trim())) {
                this.style.borderColor = '#ff6a88';
            } else {
                this.style.borderColor = 'rgba(157, 194, 236, 0.65)';
            }
        }, 300);
    });

    // Form reset to clear indicator
    form.addEventListener('reset', function() {
        clearTimeout(inputThrottleTimer);
        clearTimeout(inlineErrorTimeout);
        inputThrottleTimer = null;
        inlineErrorTimeout = null;
        resetStrengthIndicator();
        errorContainer.classList.remove('is-visible');
        errorContainer.style.display = 'none';
        errorContainer.innerHTML = '';
        errorContainer.setAttribute('aria-hidden', 'true');
    });

    // Keyboard navigation: Enter to submit
    form.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
            e.preventDefault();
            submitButton.click();
        }
    });

    // Hide logo-final until animation ends
    const logoFinal = document.getElementById('logoFinal');
    const logoAnimContainer = document.getElementById('logoAnimContainer');
    const logoImage = document.querySelector('.logo-final img');
    if (logoFinal) {
        logoFinal.style.opacity = 0;
    }

    // Animate octagons to morph and move to center
    setTimeout(function() {
        document.querySelectorAll('.logo-anim-container .octagon').forEach(function(oct) {
            oct.classList.add('bee-move');
        });
    }, 1700); // Start morph after initial tumble

    // Fade out octagons and fade in logo image
    setTimeout(function() {
        if (logoAnimContainer) {
            logoAnimContainer.style.display = 'none';
        }
        if (logoFinal) {
            logoFinal.style.opacity = 1;
        }
        if (logoImage) {
            logoImage.classList.add('show-logo');
        }
    }, 2900); // after all animation

    // Toggle password visibility
    if (togglePassword) {
        togglePassword.addEventListener('change', function() {
            const passwordField = document.getElementById('passwordField');
            if (passwordField) {
                passwordField.type = this.checked ? 'text' : 'password';
            }
        });
    }

    // Toggle (scale) the form only when mouse is over the form, no movement
    if (loginContainer) {
        loginContainer.addEventListener('mouseenter', function() {
            loginContainer.style.transform = 'scale(1.03)';
        });
        loginContainer.addEventListener('mouseleave', function() {
            loginContainer.style.transform = '';
        });
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        clearTimeout(inputThrottleTimer);
    });
});