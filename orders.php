<?php
require_once 'includes/db.php';

$user = getCurrentUser();

if (!$user) {
    header('Location: login.php');
    exit();
}

// Get all orders for the user
$stmt = $conn->prepare("
    SELECT o.*, 
           COUNT(oi.id) as total_items,
           GROUP_CONCAT(p.name SEPARATOR ', ') as product_names
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - QubeGear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="antialiased bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <div class="container my-5">
        <h1 class="mb-4">My Orders</h1>
        
        <?php if (empty($orders)): ?>
            <div class="alert alert-info">
                You haven't placed any orders yet. <a href="store.php" class="alert-link">Start shopping</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($orders as $order): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">Order #<?php echo $order['id']; ?></h5>
                                    <span class="badge bg-<?php echo $order['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                                
                                <p class="text-muted mb-2">
                                    <i class="bi bi-calendar"></i> 
                                    <?php echo date('F j, Y', strtotime($order['created_at'])); ?>
                                </p>
                                
                                <p class="mb-2">
                                    <strong>Items:</strong> <?php echo $order['total_items']; ?>
                                </p>
                                
                                <p class="mb-2">
                                    <strong>Products:</strong><br>
                                    <?php echo htmlspecialchars($order['product_names']); ?>
                                </p>
                                
                                <p class="mb-3">
                                    <strong>Total:</strong> $<?php echo number_format($order['total_amount'], 2); ?>
                                </p>
                                
                                <a href="order_success.php?order_id=<?php echo $order['id']; ?>" class="btn btn-outline-primary">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 