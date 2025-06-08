<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QubeGear - Reliable Sports Equipment</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/qubegear/assets/css/style.css">
</head>
<body class="antialiased">
    
    <nav class="fixed top-0 w-full z-50 bg-white shadow-md border-b border-gray-200 py-3 transition-all">
        <div class="container mx-auto flex justify-between items-center px-4">
            
            <a href="/qubegear/" class="text-2xl font-bold text-gray-800 flex items-center">
                <img src="/qubegear/assets/images/QG_LOGO.png" alt="Logo" class="w-10 h-10">
            </a>

            
            <div class="hidden md:flex space-x-6 ml-auto">
                <a href="/qubegear/" class="text-gray-700 hover:text-gray-900">Home</a>
                <a href="/qubegear/cart.php" class="text-gray-700 hover:text-gray-900">Cart</a>
                <a href="/qubegear/store.php" class="text-gray-700 hover:text-gray-900">Store</a>
                <a href="/qubegear/contact.php" class="text-gray-700 hover:text-gray-900">Contact Us</a>
                <a href="/qubegear/orders.php" class="text-gray-700 hover:text-gray-900">Orders</a>
            </div>

           
            <div class="hidden md:flex items-center space-x-4 ml-4">
                <?php if ($user): ?>
                    <a href="/qubegear/account.php" class="flex items-center space-x-2 hover:opacity-80 transition-opacity">
                        <div class="w-8 h-8 rounded-full overflow-hidden">
                            <img src="/qubegear/<?php echo htmlspecialchars($user['profile_image'] ?? 'assets/images/placeholder-avatar.jpg'); ?>" 
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

            <button class="md:hidden" id="mobile-menu-button">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>

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