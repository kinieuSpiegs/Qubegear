<?php
require_once 'includes/db.php';

$user = getCurrentUser();

if (!$user) {
    header('Location: login.php');
    exit();
}

$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);

if (!$order_id) {
    header('Location: orders.php');
    exit();
}

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, oi.product_id, oi.quantity, oi.price, p.name as product_name
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $user['id']);
$stmt->execute();
$result = $stmt->get_result();

$order_items = [];
$order_total = 0;
$order_date = '';

while ($row = $result->fetch_assoc()) {
    $order_items[] = $row;
    $order_total = $row['total_amount'];
    $order_date = $row['created_at'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - QubeGear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="antialiased bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <div class="container my-5">
        <div class="card">
            <div class="card-body">
                <div class="text-center mb-4">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    <h1 class="mt-3">Order Confirmed!</h1>
                    <p class="text-muted">Thank you for your purchase</p>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h5>Order Details</h5>
                        <p><strong>Order ID:</strong> #<?php echo $order_id; ?></p>
                        <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($order_date)); ?></p>
                        <p><strong>Total:</strong> $<?php echo number_format($order_total, 2); ?></p>
                    </div>
                </div>

                <hr>

                <h5>Order Items</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                    <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                <td><strong>$<?php echo number_format($order_total, 2); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="text-center mt-4">
                    <a href="store.php" class="btn btn-primary">Continue Shopping</a>
                    <a href="orders.php" class="btn btn-outline-primary ms-2">View All Orders</a>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 