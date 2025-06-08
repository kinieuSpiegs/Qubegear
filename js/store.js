document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    const sortByHidden = document.getElementById('sort_by_hidden');
    const sortOrderHidden = document.getElementById('sort_order_hidden');

    const sortNewButton = document.getElementById('sortNew');
    const sortPriceAscButton = document.getElementById('sortPriceAsc');
    const sortPriceDescButton = document.getElementById('sortPriceDesc');

    if (sortNewButton) {
        sortNewButton.addEventListener('click', function() {
            sortByHidden.value = 'created_at';
            sortOrderHidden.value = 'DESC';
            filterForm.submit();
        });
    }

    if (sortPriceAscButton) {
        sortPriceAscButton.addEventListener('click', function() {
            sortByHidden.value = 'price';
            sortOrderHidden.value = 'ASC';
            filterForm.submit();
        });
    }

    if (sortPriceDescButton) {
        sortPriceDescButton.addEventListener('click', function() {
            sortByHidden.value = 'price';
            sortOrderHidden.value = 'DESC';
            filterForm.submit();
        });
    }

    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const quantity = 1;

            fetch('/qubegear/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}`,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product added to cart!');
                } else {
                    alert('Failed to add product to cart: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding to cart.');
            });
        });
    });
}); 