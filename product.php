<?php
require_once 'includes/db.php';
$user = getCurrentUser();

$product = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
}

if (!$product) {
    // Product not found, redirect to store page or show a 404 message
    header("Location: /qubegear/store.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - QubeGear</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="antialiased bg-gray-50 flex flex-col min-h-screen pt-24">
    <!-- Navigation -->
    <nav class="fixed top-0 w-full z-50 bg-white shadow-md border-b border-gray-200 py-3 transition-all">
        <div class="container mx-auto flex justify-between items-center px-4">
            <!-- Logo -->
            <a href="/qubegear/" class="text-2xl font-bold text-gray-800 flex items-center">
                <img src="assets/images/QG_LOGO.png" alt="Logo" class="w-10 h-10">
            </a>

            <!-- Desktop Menu -->
            <div class="hidden md:flex space-x-6 ml-auto">
                <a href="/qubegear/" class="text-gray-700 hover:text-gray-900">Home</a>
                <a href="/qubegear/cart.php" class="text-gray-700 hover:text-gray-900">Cart</a>
                <a href="/qubegear/store.php" class="text-gray-700 hover:text-gray-900">Store</a>
                <a href="/qubegear/contact.php" class="text-gray-700 hover:text-gray-900">Contact Us</a>
                <a href="/qubegear/orders.php" class="text-gray-700 hover:text-gray-900">Orders</a>
            </div>

            <!-- Right Buttons -->
            <div class="hidden md:flex items-center space-x-4 ml-4">
                <?php if ($user): ?>
                    <a href="/qubegear/account.php" class="flex items-center space-x-2 hover:opacity-80 transition-opacity">
                        <div class="w-8 h-8 rounded-full overflow-hidden">
                            <img src="<?php echo $user['profile_image'] ?? 'assets/images/placeholder-avatar.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($user['name']); ?>"
                                 class="w-full h-full object-cover">
                        </div>
                        <span class="text-gray-700"><?php echo htmlspecialchars($user['name']); ?></span>
                    </a>
                    <?php if ($user['role'] === 'admin'): ?>
                        <a href="/qubegear/admin/add_product.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Admin Panel</a>
                    <?php endif; ?>
                    <a href="/qubegear/logout.php" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700">Logout</a>
                <?php else: ?>
                    <a href="/qubegear/login.php" class="px-4 py-2 border border-gray-400 rounded-lg bg-gray-100 text-gray-800 hover:bg-gray-200">Sign In</a>
                    <a href="/qubegear/register.php" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700">Register</a>
                <?php endif; ?>
            </div>

            <!-- Mobile Menu Button -->
            <button class="md:hidden" id="mobile-menu-button">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>

        <!-- Mobile Menu -->
        <div class="md:hidden hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="/qubegear/" class="block px-3 py-2 text-gray-700 hover:text-gray-900">Home</a>
                <a href="/qubegear/cart.php" class="block px-3 py-2 text-gray-700 hover:text-gray-900">Cart</a>
                <a href="/qubegear/store.php" class="block px-3 py-2 text-gray-700 hover:text-gray-900">Store</a>
                <a href="/qubegear/contact.php" class="block px-3 py-2 text-gray-700 hover:text-gray-900">Contact Us</a>
                <a href="/qubegear/orders.php" class="block px-3 py-2 text-gray-700 hover:text-gray-900">Orders</a>
            </div>
        </div>
    </nav>

    <!-- Product Details Section -->
    <main class="flex-1">
        <div class="container mx-auto px-4 py-8">
            <div class="flex flex-col md:flex-row gap-8 bg-white p-8 rounded-lg shadow-md">
                <div class="md:w-1/2">
                    <img src="/qubegear/<?php echo htmlspecialchars($product['image'] ?? 'assets/images/placeholder-product.jpg'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-96 object-contain rounded-lg shadow-sm">
                </div>
                <div class="md:w-1/2">
                    <h1 class="text-4xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($product['name']); ?></h1>
                    <p class="text-gray-700 text-lg mb-4"><?php echo htmlspecialchars($product['description']); ?></p>
                    <p class="text-3xl font-extrabold text-indigo-600 mb-4">$<?php echo number_format($product['price'], 2); ?></p>
                    <p class="text-gray-600 mb-4">Category: <span class="font-semibold"><?php echo htmlspecialchars($product['category']); ?></span></p>
                    <p class="text-gray-600 mb-6">Availability: 
                        <?php if ($product['stock'] > 0): ?>
                            <span class="text-green-600 font-semibold">In Stock (<?php echo htmlspecialchars($product['stock']); ?>)</span>
                        <?php else: ?>
                            <span class="text-red-600 font-semibold">Out of Stock</span>
                        <?php endif; ?>
                    </p>
                    <?php if ($product['stock'] > 0): ?>
                        <div class="flex items-center mb-4">
                            <label for="quantity" class="mr-2 text-gray-700">Quantity:</label>
                            <input type="number" id="quantity" value="1" min="1" max="<?php echo htmlspecialchars($product['stock']); ?>" class="w-20 px-3 py-2 border border-gray-300 rounded-md text-center text-gray-900">
                        </div>
                        <button type="button" id="addToCartBtn" data-product-id="<?php echo htmlspecialchars($product['id']); ?>" class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition-colors duration-200">
                            Add to Cart
                        </button>
                    <?php else: ?>
                        <button type="button" class="px-6 py-3 bg-gray-400 text-white font-semibold rounded-lg cursor-not-allowed" disabled>
                            Out of Stock
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-auto">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">QubeGear</h3>
                    <p>Your trusted source for quality sports equipment.</p>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="/qubegear/" class="hover:text-gray-300">Home</a></li>
                        <li><a href="/qubegear/store.php" class="hover:text-gray-300">Store</a></li>
                        <li><a href="/qubegear/contact.php" class="hover:text-gray-300">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Contact Us</h3>
                    <p>Email: info@qubegear.com</p>
                    <p>Phone: (123) 456-7890</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
    <script src="js/product.js"></script>
</body>
</html> 