document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            return 'Invalid email format';
        }
        return '';
    }

    function showError(input, message) {
        const errorDiv = input.nextElementSibling;
        if (errorDiv && errorDiv.classList.contains('text-red-500')) {
            errorDiv.textContent = message;
        } else {
            const div = document.createElement('p');
            div.className = 'text-red-500 text-xs mt-1';
            div.textContent = message;
            input.parentNode.insertBefore(div, input.nextSibling);
        }
    }

    function clearError(input) {
        const errorDiv = input.nextElementSibling;
        if (errorDiv && errorDiv.classList.contains('text-red-500')) {
            errorDiv.remove();
        }
    }

    function validateForm() {
        let isValid = true;
        const email = emailInput.value.trim();
        const password = passwordInput.value;

        clearError(emailInput);
        clearError(passwordInput);

        if (!email) {
            showError(emailInput, 'Email is required');
            isValid = false;
        } else {
            const emailError = validateEmail(email);
            if (emailError) {
                showError(emailInput, emailError);
                isValid = false;
            }
        }

        if (!password) {
            showError(passwordInput, 'Password is required');
            isValid = false;
        }

        return isValid;
    }

    form.addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
        }
    });

    emailInput.addEventListener('input', function() {
        clearError(emailInput);
        const error = validateEmail(this.value.trim());
        if (error) {
            showError(emailInput, error);
        }
    });

    passwordInput.addEventListener('input', function() {
        clearError(passwordInput);
    });
}); 