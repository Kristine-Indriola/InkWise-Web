document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const emailInput = document.querySelector('input[name="email"]');
    const passwordInput = document.querySelector('input[name="password"]');
    const submitButton = document.querySelector('button[type="submit"]');
    const strengthIndicator = document.getElementById('password-strength');
    const togglePassword = document.getElementById('togglePassword');
    const loginContainer = document.getElementById('loginContainer');
    const errorContainer = document.createElement('div');
    errorContainer.id = 'inline-errors';
    errorContainer.setAttribute('aria-live', 'assertive');
    errorContainer.style.display = 'none';
    form.insertBefore(errorContainer, form.firstChild);

    let inputThrottleTimer;

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
        const strength = checkPasswordStrength(password);
        let className = '';
        let text = '';
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
        strengthIndicator.className = `password-strength ${className}`;
        strengthIndicator.textContent = text;
        strengthIndicator.style.width = `${(strength / 5) * 100}%`;
    }

    // Show inline errors
    function showInlineErrors(messages) {
        errorContainer.innerHTML = messages.map(msg => `<p style="color: #ff6a88; margin: 0;">${msg}</p>`).join('');
        errorContainer.style.display = 'block';
        setTimeout(() => errorContainer.style.display = 'none', 5000); // Hide after 5 seconds
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
    });

    // Password strength on input with throttling
    passwordInput.addEventListener('input', function() {
        clearTimeout(inputThrottleTimer);
        inputThrottleTimer = setTimeout(() => {
            if (this.value) {
                updateStrengthIndicator(this.value);
                strengthIndicator.style.display = 'block';
            } else {
                strengthIndicator.style.display = 'none';
            }
        }, 300); // Throttle to 300ms
    });

    passwordInput.addEventListener('blur', function() {
        if (!this.value.trim()) {
            this.style.borderColor = '#ff6a88';
        } else {
            this.style.borderColor = '#9dc2ec';
        }
        // Hide indicator on blur if empty
        if (!this.value) {
            strengthIndicator.style.display = 'none';
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
                this.style.borderColor = '#9dc2ec';
            }
        }, 300);
    });

    // Form reset to clear indicator
    form.addEventListener('reset', function() {
        strengthIndicator.style.display = 'none';
        strengthIndicator.className = 'password-strength';
        strengthIndicator.style.width = '0%';
        errorContainer.style.display = 'none';
    });

    // Keyboard navigation: Enter to submit
    form.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
            e.preventDefault();
            submitButton.click();
        }
    });

    // Hide logo-final until animation ends
    document.getElementById('logoFinal').style.opacity = 0;

    // Animate octagons to morph and move to center
    setTimeout(function() {
        document.querySelectorAll('.logo-anim-container .octagon').forEach(function(oct) {
            oct.classList.add('bee-move');
        });
    }, 1700); // Start morph after initial tumble

    // Fade out octagons and fade in logo image
    setTimeout(function() {
        document.getElementById('logoAnimContainer').style.display = 'none';
        document.getElementById('logoFinal').style.opacity = 1;
        // Fade in logo image
        document.querySelector('.logo-final img').classList.add('show-logo');
    }, 2900); // after all animation

    // Toggle password visibility
    togglePassword.addEventListener('change', function() {
        const passwordField = document.getElementById('passwordField');
        if (this.checked) {
            passwordField.type = 'text';
        } else {
            passwordField.type = 'password';
        }
    });

    // Toggle (scale) the form only when mouse is over the form, no movement
    loginContainer.addEventListener('mouseenter', function() {
        loginContainer.style.transform = 'scale(1.03)';
    });
    loginContainer.addEventListener('mouseleave', function() {
        loginContainer.style.transform = '';
    });

    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        clearTimeout(inputThrottleTimer);
    });
});