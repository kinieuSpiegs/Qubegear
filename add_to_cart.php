<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// session_start(); // Removed as session is started in includes/db.php
require_once 'includes/db.php';
$user = getCurrentUser(); // Get current user for database cart

// Removed: Temporary: Clear cart for debugging purposes to ensure a clean state
// unset($_SESSION['cart']);

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'Invalid request.'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    if ($product_id === false || $product_id === null || $quantity === false || $quantity === null || $quantity <= 0) {
        $response['message'] = 'Invalid product ID or quantity.';
    } else {
        // Fetch product details to ensure it exists and to get its price and name
        $stmt = $conn->prepare("SELECT id, name, price, stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();

        if ($product) {
            if ($product['stock'] < $quantity) {
                $response['message'] = 'Not enough stock available.';
            } else {
                if ($user) { // Logged-in user: Use database cart
                    // Check if item already in user's cart in DB
                    $stmt = $conn->prepare("SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
                    $stmt->bind_param("ii", $user['id'], $product_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $existing_item = $result->fetch_assoc();
                    $stmt->close();

                    if ($existing_item) {
                        // Update quantity in DB
                        $new_quantity = $existing_item['quantity'] + $quantity;
                        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND product_id = ?");
                        $stmt->bind_param("iii", $new_quantity, $user['id'], $product_id);
                        $stmt->execute();
                        $stmt->close();
                    } else {
                        // Insert new item into DB
                        $stmt = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
                        $stmt->bind_param("iii", $user['id'], $product_id, $quantity);
                        $stmt->execute();
                        $stmt->close();
                    }
                    // Ensure session cart is cleared for this product if it was there (for future merging clarity)
                    if (isset($_SESSION['cart'][$product_id])) {
                        unset($_SESSION['cart'][$product_id]);
                    }
                } else { // Guest user: Use session cart
                    // Initialize cart in session if it doesn't exist
                    if (!isset($_SESSION['cart'])) {
                        $_SESSION['cart'] = [];
                    }

                    // Add or update product in session cart
                    if (isset($_SESSION['cart'][$product_id])) {
                        $current_quantity = 0;
                        if (is_array($_SESSION['cart'][$product_id]) && isset($_SESSION['cart'][$product_id]['quantity'])) {
                            $current_quantity = (int)$_SESSION['cart'][$product_id]['quantity'];
                        } elseif (is_numeric($_SESSION['cart'][$product_id])) {
                            $current_quantity = (int)$_SESSION['cart'][$product_id];
                        }
                        $_SESSION['cart'][$product_id] = $current_quantity + $quantity;
                    } else {
                        $_SESSION['cart'][$product_id] = $quantity;
                    }
                }
                $response['success'] = true;
                $response['message'] = 'Product added to cart successfully.';
            }
        } else {
            $response['message'] = 'Product not found.';
        }
    }
}

echo json_encode($response);
?> 