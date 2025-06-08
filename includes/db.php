<?php
session_start();

// Helper function for redirection
function redirect($url) {
    header("Location: " . $url);
    exit();
}

$host = 'localhost';
$dbname = 'qubegear';
$username = 'root';
$password = '';

$conn = mysqli_connect($host, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8mb4
mysqli_set_charset($conn, "utf8mb4");

// Create cart_items table if it doesn't exist
$sql_cart_items = "CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE (user_id, product_id)
)";

// Create orders table if it doesn't exist
$sql_orders = "CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) NOT NULL,
    shipping_address TEXT NOT NULL,
    shipping_city VARCHAR(100) NOT NULL,
    shipping_state VARCHAR(50) NOT NULL,
    shipping_zip VARCHAR(20) NOT NULL,
    shipping_phone VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

// Create order_items table if it doesn't exist
$sql_order_items = "CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)";

// Execute each query separately
mysqli_query($conn, $sql_cart_items);
mysqli_query($conn, $sql_orders);
mysqli_query($conn, $sql_order_items);

// Clear any remaining results
while (mysqli_more_results($conn)) {
    mysqli_next_result($conn);
}

function getCurrentUser() {
    global $conn;
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

function registerUser($name, $email, $password) {
    global $conn;
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hashedPassword);
    
    return mysqli_stmt_execute($stmt);
}

function loginUser($email, $password) {
    global $conn;
    
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        return true;
    }
    
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function logoutUser() {
    session_destroy();
}

function getProducts($category = null, $search = null, $sort = null) {
    global $conn;
    
    $query = "SELECT * FROM products WHERE 1=1";
    $params = [];
    $types = "";
    
    if ($category) {
        $query .= " AND category = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    if ($search) {
        $query .= " AND (name LIKE ? OR description LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }
    
    if ($sort) {
        switch ($sort) {
            case 'price_asc':
                $query .= " ORDER BY price ASC";
                break;
            case 'price_desc':
                $query .= " ORDER BY price DESC";
                break;
            case 'name_asc':
                $query .= " ORDER BY name ASC";
                break;
            case 'name_desc':
                $query .= " ORDER BY name DESC";
                break;
        }
    }
    
    $stmt = mysqli_prepare($conn, $query);
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $products = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
    
    return $products;
}

function getProductById($id) {
    global $conn;
    
    $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_fetch_assoc($result);
}

function getCategories() {
    global $conn;
    
    $result = mysqli_query($conn, "SELECT DISTINCT category FROM products ORDER BY category");
    $categories = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row['category'];
    }
    
    return $categories;
}

function addToCart($productId, $quantity = 1) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
}

function getCartItems() {
    if (!isset($_SESSION['cart'])) {
        return [];
    }
    
    $items = [];
    foreach ($_SESSION['cart'] as $productId => $quantity) {
        $product = getProductById($productId);
        if ($product) {
            $items[] = [
                'product' => $product,
                'quantity' => $quantity
            ];
        }
    }
    
    return $items;
}

function updateCartItem($productId, $quantity) {
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$productId]);
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
}

function removeFromCart($productId) {
    unset($_SESSION['cart'][$productId]);
}

function clearCart() {
    $_SESSION['cart'] = [];
}

function getCartTotal() {
    $items = getCartItems();
    $total = 0;
    
    foreach ($items as $item) {
        $total += $item['product']['price'] * $item['quantity'];
    }
    
    return $total;
}

function getCartItemCount() {
    if (!isset($_SESSION['cart'])) {
        return 0;
    }
    
    return array_sum($_SESSION['cart']);
}

function createOrder($userId, $items, $total) {
    global $conn;
    
    mysqli_begin_transaction($conn);
    
    try {
        $stmt = mysqli_prepare($conn, "INSERT INTO orders (user_id, total_amount) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "id", $userId, $total);
        mysqli_stmt_execute($stmt);
        
        $orderId = mysqli_insert_id($conn);
        
        $stmt = mysqli_prepare($conn, "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        
        foreach ($items as $item) {
            mysqli_stmt_bind_param($stmt, "iiid", 
                $orderId, 
                $item['product']['id'], 
                $item['quantity'], 
                $item['product']['price']
            );
            mysqli_stmt_execute($stmt);
        }
        
        mysqli_commit($conn);
        return $orderId;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        throw $e;
    }
}

function getUserOrders($userId) {
    global $conn;
    
    $stmt = mysqli_prepare($conn, "
        SELECT o.*, 
               GROUP_CONCAT(p.name SEPARATOR ', ') as product_names,
               GROUP_CONCAT(oi.quantity SEPARATOR ', ') as quantities
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $orders = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
    
    return $orders;
}

function updateUserProfile($userId, $name, $email, $profileImage = null) {
    global $conn;
    
    if ($profileImage) {
        $stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, email = ?, profile_image = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "sssi", $name, $email, $profileImage, $userId);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, email = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssi", $name, $email, $userId);
    }
    
    return mysqli_stmt_execute($stmt);
}

function updateUserPassword($userId, $currentPassword, $newPassword) {
    global $conn;
    
    $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    
    if (!$user || !password_verify($currentPassword, $user['password'])) {
        return false;
    }
    
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $hashedPassword, $userId);
    
    return mysqli_stmt_execute($stmt);
}

function deleteUserAccount($userId) {
    global $conn;
    
    mysqli_begin_transaction($conn);
    
    try {
        $stmt = mysqli_prepare($conn, "DELETE FROM order_items WHERE order_id IN (SELECT id FROM orders WHERE user_id = ?)");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        
        $stmt = mysqli_prepare($conn, "DELETE FROM orders WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        
        mysqli_commit($conn);
        return true;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return false;
    }
}