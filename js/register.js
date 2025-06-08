document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');

    function validateName(name) {
        if (name.length < 2 || name.length > 100) {
            return 'Name must be between 2 and 100 characters';
        }
        return '';
    }

    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            return 'Invalid email format';
        }
        return '';
    }

    function validatePassword(password) {
        if (password.length < 8) {
            return 'Password must be at least 8 characters';
        }
        if (!/[A-Z]/.test(password)) {
            return 'Password must contain at least one uppercase letter';
        }
        if (!/[a-z]/.test(password)) {
            return 'Password must contain at least one lowercase letter';
        }
        if (!/[0-9]/.test(password)) {
            return 'Password must contain at least one number';
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
        const name = nameInput.value.trim();
        const email = emailInput.value.trim();
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        clearError(nameInput);
        clearError(emailInput);
        clearError(passwordInput);
        clearError(confirmPasswordInput);

        const nameError = validateName(name);
        if (nameError) {
            showError(nameInput, nameError);
            isValid = false;
        }

        const emailError = validateEmail(email);
        if (emailError) {
            showError(emailInput, emailError);
            isValid = false;
        }

        const passwordError = validatePassword(password);
        if (passwordError) {
            showError(passwordInput, passwordError);
            isValid = false;
        }

        if (password !== confirmPassword) {
            showError(confirmPasswordInput, 'Passwords do not match');
            isValid = false;
        }

        return isValid;
    }

    form.addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
        }
    });

    nameInput.addEventListener('input', function() {
        clearError(nameInput);
        const error = validateName(this.value.trim());
        if (error) {
            showError(nameInput, error);
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
        const error = validatePassword(this.value);
        if (error) {
            showError(passwordInput, error);
        }
    });

    confirmPasswordInput.addEventListener('input', function() {
        clearError(confirmPasswordInput);
        if (this.value !== passwordInput.value) {
            showError(confirmPasswordInput, 'Passwords do not match');
        }
    });
}); 