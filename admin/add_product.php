<?php
require_once '../includes/db.php';
require_once '../includes/config.php';

// Check if user is logged in and is an admin
$user = getCurrentUser();
if (!$user || $user['role'] !== 'admin') {
    redirect('/qubegear/login.php');
}

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $stock = trim($_POST['stock'] ?? '');
    $color = trim($_POST['color'] ?? '');

    // Server-side validation
    if (empty($name)) {
        $errors['name'] = 'Product name is required.';
    }
    if (empty($price) || !is_numeric($price) || $price < 0) {
        $errors['price'] = 'Valid price is required.';
    }
    if (empty($category)) {
        $errors['category'] = 'Category is required.';
    }
    if (empty($stock) || !is_numeric($stock) || $stock < 0 || filter_var($stock, FILTER_VALIDATE_INT) === false) {
        $errors['stock'] = 'Valid stock quantity is required.';
    }
    if (empty($color)) {
        $errors['color'] = 'Color is required.';
    } elseif (!in_array($color, ['red', 'white', 'black'])) {
        $errors['color'] = 'Invalid color selected.';
    }

    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../assets/images/products/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image_name = basename($_FILES['image']['name']);
        $image_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($image_extension, $allowed_extensions)) {
            $errors['image'] = 'Only JPG, JPEG, PNG, GIF files are allowed.';
        } elseif ($_FILES['image']['size'] > 5000000) { // 5MB
            $errors['image'] = 'Image size must be less than 5MB.';
        } else {
            $unique_name = uniqid() . '.' . $image_extension;
            $target_file = $target_dir . $unique_name;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $errors['image'] = 'Failed to upload image.';
            } else {
                $image_path = 'assets/images/products/' . $unique_name;
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, image, category, stock, color) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdssis", $name, $description, $price, $image_path, $category, $stock, $color);

        if ($stmt->execute()) {
            $success_message = 'Product added successfully!';
            // Clear form fields after successful submission
            $name = $description = $price = $category = $stock = $color = '';
            $image_path = ''; // Reset image path
        } else {
            $errors['general'] = 'Failed to add product: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="antialiased bg-gray-100">
    <nav class="bg-gray-800 p-4 text-white">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Admin Panel</h1>
            <div class="space-x-4">
                <a href="/qubegear/" class="hover:text-gray-300">Home</a>
                <a href="/qubegear/admin/add_product.php" class="hover:text-gray-300">Add Product</a>
                <a href="/qubegear/logout.php" class="hover:text-gray-300">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        <h2 class="text-3xl font-bold text-gray-800 mb-6">Add New Product</h2>

        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors['general'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($errors['general']); ?></span>
            </div>
        <?php endif; ?>

        <form action="/qubegear/admin/add_product.php" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow-md" id="addProductForm">
            <div class="mb-4">
                <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Product Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <?php if (!empty($errors['name'])): ?><p class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($errors['name']); ?></p><?php endif; ?>
            </div>
            <div class="mb-4">
                <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Description:</label>
                <textarea id="description" name="description" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
            </div>
            <div class="mb-4">
                <label for="price" class="block text-gray-700 text-sm font-bold mb-2">Price:</label>
                <input type="number" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($price ?? ''); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <?php if (!empty($errors['price'])): ?><p class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($errors['price']); ?></p><?php endif; ?>
            </div>
            <div class="mb-4">
                <label for="image" class="block text-gray-700 text-sm font-bold mb-2">Product Image:</label>
                <input type="file" id="image" name="image" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <?php if (!empty($errors['image'])): ?><p class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($errors['image']); ?></p><?php endif; ?>
            </div>
            <div class="mb-4">
                <label for="category" class="block text-gray-700 text-sm font-bold mb-2">Category:</label>
                <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($category ?? ''); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <?php if (!empty($errors['category'])): ?><p class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($errors['category']); ?></p><?php endif; ?>
            </div>
            <div class="mb-4">
                <label for="color" class="block text-gray-700 text-sm font-bold mb-2">Color:</label>
                <select id="color" name="color" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">Select a color</option>
                    <option value="red" <?php echo (isset($color) && $color === 'red') ? 'selected' : ''; ?>>Red</option>
                    <option value="white" <?php echo (isset($color) && $color === 'white') ? 'selected' : ''; ?>>White</option>
                    <option value="black" <?php echo (isset($color) && $color === 'black') ? 'selected' : ''; ?>>Black</option>
                </select>
                <?php if (!empty($errors['color'])): ?><p class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($errors['color']); ?></p><?php endif; ?>
            </div>
            <div class="mb-6">
                <label for="stock" class="block text-gray-700 text-sm font-bold mb-2">Stock Quantity:</label>
                <input type="number" id="stock" name="stock" value="<?php echo htmlspecialchars($stock ?? ''); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <?php if (!empty($errors['stock'])): ?><p class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($errors['stock']); ?></p><?php endif; ?>
            </div>
            <div class="flex items-center justify-between">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Add Product
                </button>
            </div>
        </form>
    </div>

    <script src="/qubegear/js/script.js"></script>
    <script src="../js/admin_add_product.js"></script>
</body>
</html> 