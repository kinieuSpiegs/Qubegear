<?php
// session_start(); // Removed as session is started in includes/db.php
require_once 'includes/db.php';
$user = getCurrentUser(); // Define $user before including header

// Check if user is logged in
if (!$user) {
    // If not logged in, redirect to login page
    header('Location: login.php');
    exit();
}

// Get cart items
$cart_items = [];
$total = 0;

$product_ids_in_cart = [];
$db_cart_quantities = [];

// Fetch cart items from the database for logged-in user
$stmt = $conn->prepare("SELECT product_id, quantity FROM cart_items WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result_db_cart = $stmt->get_result();

while ($row = $result_db_cart->fetch_assoc()) {
    $product_ids_in_cart[] = $row['product_id'];
    $db_cart_quantities[$row['product_id']] = $row['quantity'];
}
$stmt->close();

if (!empty($product_ids_in_cart)) {
    $placeholders = implode(', ', array_fill(0, count($product_ids_in_cart), '?'));
    $types = str_repeat('i', count($product_ids_in_cart));

    $stmt = $conn->prepare("SELECT id, name, price, image, stock FROM products WHERE id IN (" . $placeholders . ")");
    $bind_params = array($types);
    for ($i = 0; $i < count($product_ids_in_cart); $i++) {
        $bind_params[] = &$product_ids_in_cart[$i];
    }
    call_user_func_array(array($stmt, 'bind_param'), $bind_params);

    $stmt->execute();
    $result_products = $stmt->get_result();
    
    $products = [];
    while ($row = $result_products->fetch_assoc()) {
        $products[$row['id']] = $row;
    }
    $stmt->close();

    foreach ($db_cart_quantities as $product_id => $quantity_in_cart) {
        if (isset($products[$product_id])) {
            $product = $products[$product_id];
            $quantity = (int)$quantity_in_cart;
            $price = (float)$product['price'];

            $item_subtotal = $price * $quantity;
            $total += $item_subtotal;

            $cart_items[$product_id] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $item_subtotal,
                'image' => $product['image']
            ];
        } else {
            // If product not found in DB, remove from cart_items table
            $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user['id'], $product_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Handle quantity updates and removals (now interacting with database)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_quantity'])) {
        $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

        if ($product_id !== false && $product_id !== null && $quantity !== false && $quantity !== null) {
            if ($quantity > 0) {
                // Check stock before updating
                $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $product_stock = $result->fetch_assoc();
                $stmt->close();

                if ($product_stock && $product_stock['stock'] >= $quantity) {
                    $stmt = $conn->prepare("UPDATE cart_items SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND product_id = ?");
                    $stmt->bind_param("iii", $quantity, $user['id'], $product_id);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    // Optionally, set quantity to max available stock or show alert
                    if ($product_stock) {
                        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND product_id = ?");
                        $stmt->bind_param("iii", $product_stock['stock'], $user['id'], $product_id);
                        $stmt->execute();
                        $stmt->close();
                        echo '<script>alert("Not enough stock available. Quantity adjusted to maximum.");</script>';
                    } else {
                        // Product not found or no stock info, remove from cart
                        $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
                        $stmt->bind_param("ii", $user['id'], $product_id);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            } else {
                // Remove item if quantity is 0 or less
                $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
                $stmt->bind_param("ii", $user['id'], $product_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    } elseif (isset($_POST['remove_item'])) {
        $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        if ($product_id !== false && $product_id !== null) {
            $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user['id'], $product_id);
            $stmt->execute();
            $stmt->close();
        }
    } elseif (isset($_POST['clear_cart'])) {
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $stmt->close();
    }
    // Redirect to prevent form resubmission on refresh
    header("Location: /qubegear/cart.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - QubeGear</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="antialiased bg-gray-50 flex flex-col min-h-screen">
    <?php include 'includes/header.php'; ?>

    <main class="flex-1 mt-20">
        <div class="container mx-auto my-8 px-4">
            <h1 class="text-3xl font-bold mb-6">Shopping Cart</h1>
            
            <?php if (empty($cart_items)): ?>
                <div class="bg-blue-100 border-t-4 border-blue-500 rounded-b text-blue-900 px-4 py-3 shadow-md" role="alert">
                    <div class="flex">
                        <div class="py-1"><svg class="fill-current h-6 w-6 text-blue-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M2 10a8 8 0 1116 0 8 8 0 01-16 0zm10.707-3.707a1 1 0 00-1.414-1.414L9 8.586 7.707 7.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4a1 1 0 000-1.414z"/></svg></div>
                        <div>
                            <p class="font-bold">Your cart is empty.</p>
                            <a href="store.php" class="text-blue-700 hover:underline">Continue shopping</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="flex flex-col md:flex-row gap-8">
                    <div class="md:w-3/4">
                        <div class="bg-white shadow-md rounded-lg p-6">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="flex items-center mb-6 pb-6 border-b border-gray-200 last:border-b-0 last:pb-0 last:mb-0">
                                    <div class="w-1/5 pr-4">
                                        <img src="/qubegear/<?php echo htmlspecialchars($item['image'] ?? 'assets/images/placeholder-product.jpg'); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                             class="w-full h-auto rounded-md object-cover">
                                    </div>
                                    <div class="w-2/5">
                                        <h5 class="text-lg font-semibold mb-1 text-gray-800"><?php echo htmlspecialchars($item['name']); ?></h5>
                                        <p class="text-gray-600">$<?php echo number_format($item['price'], 2); ?></p>
                                    </div>
                                    <div class="w-1/5 flex items-center justify-center">
                                        <form action="/qubegear/cart.php" method="POST" class="flex items-center">
                                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                                   min="1" class="w-20 px-3 py-2 border border-gray-300 rounded-md text-center focus:outline-none focus:ring-2 focus:ring-indigo-500 text-gray-900">
                                            <button type="submit" name="update_quantity" class="ml-2 p-2 bg-indigo-500 text-white rounded-md hover:bg-indigo-600 transition-colors duration-200">
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <div class="w-1/10 text-right">
                                        <p class="text-lg font-bold text-gray-900">$<?php echo number_format($item['subtotal'], 2); ?></p>
                                    </div>
                                    <div class="w-1/10 text-right pl-4">
                                        <form action="/qubegear/cart.php" method="POST">
                                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" name="remove_item" class="p-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors duration-200">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <form action="/qubegear/cart.php" method="POST" class="text-right mt-6">
                                <button type="submit" name="clear_cart" class="px-4 py-2 bg-red-500 text-white font-semibold rounded-md hover:bg-red-600 transition-colors duration-200">
                                    Clear Cart
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="md:w-1/4">
                        <div class="bg-white shadow-md rounded-lg p-6">
                            <h5 class="text-xl font-bold mb-4 text-gray-900">Order Summary</h5>
                            <div class="flex justify-between mb-2 text-gray-700">
                                <span>Subtotal</span>
                                <span>$<?php echo number_format($total, 2); ?></span>
                            </div>
                            <div class="flex justify-between mb-4 text-gray-700">
                                <span>Shipping</span>
                                <span>Free</span>
                            </div>
                            <hr class="my-4 border-gray-300">
                            <div class="flex justify-between mb-4 text-xl font-bold text-gray-900">
                                <span>Total</span>
                                <span>$<?php echo number_format($total, 2); ?></span>
                            </div>
                            <a href="checkout.php" class="block text-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition-colors duration-200">Proceed to Checkout</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 