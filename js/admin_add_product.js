document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addProductForm');
    const nameInput = document.getElementById('name');
    const priceInput = document.getElementById('price');
    const categoryInput = document.getElementById('category');
    const imageInput = document.getElementById('image');
    const stockInput = document.getElementById('stock');

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

        clearError(nameInput);
        clearError(priceInput);
        clearError(categoryInput);
        clearError(imageInput);
        clearError(stockInput);

        if (nameInput.value.trim() === '') {
            showError(nameInput, 'Product name is required.');
            isValid = false;
        }

        const price = parseFloat(priceInput.value);
        if (isNaN(price) || price < 0) {
            showError(priceInput, 'Valid price is required.');
            isValid = false;
        }

        if (categoryInput.value.trim() === '') {
            showError(categoryInput, 'Category is required.');
            isValid = false;
        }

        const stock = parseInt(stockInput.value);
        if (isNaN(stock) || stock < 0) {
            showError(stockInput, 'Valid stock quantity is required (non-negative integer).');
            isValid = false;
        }

        if (imageInput.files.length > 0) {
            const file = imageInput.files[0];
            const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            const fileExtension = file.name.split('.').pop().toLowerCase();
            
            if (!allowedExtensions.includes(fileExtension)) {
                showError(imageInput, 'Only JPG, JPEG, PNG, GIF files are allowed.');
                isValid = false;
            }

            if (file.size > 5000000) { // 5MB
                showError(imageInput, 'Image size must be less than 5MB.');
                isValid = false;
            }
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
    });

    priceInput.addEventListener('input', function() {
        clearError(priceInput);
    });

    categoryInput.addEventListener('input', function() {
        clearError(categoryInput);
    });

    stockInput.addEventListener('input', function() {
        clearError(stockInput);
    });

    imageInput.addEventListener('change', function() {
        clearError(imageInput);
    });
}); 