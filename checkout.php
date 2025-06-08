<?php
require_once 'includes/db.php';
require_once 'includes/config.php';

error_reporting(E_ALL); // Report all errors for debugging
ini_set('display_errors', 1); // Display errors for debugging

// Debugging: Check request method and initial success message
echo "<!-- Request Method: " . $_SERVER['REQUEST_METHOD'] . " -->\n";

$user = getCurrentUser();

// Check if user is logged in
if (!$user) {
    header('Location: login.php');
    exit();
}

// Get cart items
$cart_items = [];
$total = 0;

$stmt = $conn->prepare("SELECT ci.*, p.name, p.price, p.image, p.stock 
                       FROM cart_items ci 
                       JOIN products p ON ci.product_id = p.id 
                       WHERE ci.user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $total += $row['price'] * $row['quantity'];
}
$stmt->close();

if (empty($cart_items)) {
    header('Location: cart.php');
    exit();
}

$success_message = '';
$error_message = '';

// Debugging: Check success message before POST handling
echo "<!-- Success Message (before POST): '" . $success_message . "' -->\n";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zip = trim($_POST['zip'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name) || empty($email) || empty($address) || empty($city) || empty($state) || empty($zip) || empty($phone)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // Create order in database
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, shipping_address, shipping_city, shipping_state, shipping_zip, shipping_phone) VALUES (?, ?, 'pending', ?, ?, ?, ?, ?)");
        $stmt->bind_param("idsssss", $user['id'], $total, $address, $city, $state, $zip, $phone);
        
        if ($stmt->execute()) {
            $order_id = $conn->insert_id;
            
            // Add order items and update product stock
            $stmt_order_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt_update_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");

            foreach ($cart_items as $item) {
                // Insert into order_items
                $stmt_order_item->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
                $stmt_order_item->execute();
                
                // Update product stock
                $stmt_update_stock->bind_param("iii", $item['quantity'], $item['product_id'], $item['quantity']);
                $stmt_update_stock->execute();
            }
            $stmt_order_item->close();
            $stmt_update_stock->close();
            
            // Clear cart
            $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            $stmt->close();
            
            // Send confirmation email
            $to = $email;
            $subject = "Order Confirmation - QubeGear";
            $message = "Dear $name,\n\n";
            $message .= "Thank you for your order! Your order has been received and is being processed.\n\n";
            $message .= "Order Details:\n";
            foreach ($cart_items as $item) {
                $message .= "- " . $item['name'] . " x" . $item['quantity'] . " ($" . number_format($item['price'] * $item['quantity'], 2) . ")\n";
            }
            $message .= "\nTotal: $" . number_format($total, 2) . "\n\n";
            $message .= "Shipping Address:\n";
            $message .= "$address\n";
            $message .= "$city, $state $zip\n";
            $message .= "Phone: $phone\n\n";
            $message .= "We will contact you shortly with shipping updates.\n\n";
            $message .= "Best regards,\nQubeGear Team";
            
            $headers = "From: noreply@qubegear.com";
            
            if (mail($to, $subject, $message, $headers)) {
                $success_message = "Order placed successfully! A confirmation email has been sent to your email address.";
            } else {
                $success_message = "Order placed successfully! However, there was an issue sending the confirmation email.";
            }
        } else {
            $error_message = "There was an error processing your order. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - QubeGear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="antialiased bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <div class="container my-5">
        <h1 class="mb-4">Checkout</h1>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Order Summary</h5>
                            <?php foreach ($cart_items as $item): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <small class="text-muted">Quantity: <?php echo $item['quantity']; ?></small>
                                    </div>
                                    <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                </div>
                            <?php endforeach; ?>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <h5>Total</h5>
                                <h5>$<?php echo number_format($total, 2); ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Shipping Information</h5>
                            <?php if (!empty($error_message)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo htmlspecialchars($error_message); ?>
                                </div>
                            <?php endif; ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="address" name="address" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="city" class="form-label">City</label>
                                        <input type="text" class="form-control" id="city" name="city" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="state" class="form-label">State</label>
                                        <input type="text" class="form-control" id="state" name="state" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="zip" class="form-label">ZIP</label>
                                        <input type="text" class="form-control" id="zip" name="zip" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Place Order</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html> 