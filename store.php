<?php
require_once 'includes/db.php';
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store - QubeGear</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="antialiased bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <div class="flex pt-20">
        <aside class="w-64 bg-white p-8 shadow-md border-r border-gray-200 min-h-screen">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Filters</h2>

            <form action="/qubegear/store.php" method="GET" id="filterForm">
                <input type="hidden" name="sort" id="sort_by_hidden" value="<?php echo htmlspecialchars($sort_by); ?>">
                <input type="hidden" name="order" id="sort_order_hidden" value="<?php echo htmlspecialchars($sort_order); ?>">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-3">Keywords</h3>
                    <div class="space-y-2">
                        <?php
                        $keywords = [
                            'Boxing Gloves', 'MMA Gloves', 'Sparring Gear', 'Shinguards',
                            'Headgear', 'Elbow, knee, ankle and wrist protective', 'Mouthguard',
                            'Protective shells', 'Protective bands'
                        ];
                        $selected_keywords = $_GET['keywords'] ?? [];
                        foreach ($keywords as $keyword) {
                            $checked = in_array($keyword, $selected_keywords) ? 'checked' : '';
                            echo '<label class="flex items-center text-gray-600">';
                            echo '<input type="checkbox" name="keywords[]" value="' . htmlspecialchars($keyword) . '" ' . $checked . ' class="form-checkbox h-4 w-4 text-indigo-600 rounded">';
                            echo '<span class="ml-2">' . htmlspecialchars($keyword) . '</span>';
                            echo '</label>';
                        }
                        ?>
                    </div>
                </div>

                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-3">Price Range</h3>
                    <div class="flex items-center space-x-2 mb-2">
                        <input type="number" name="min_price" placeholder="0" value="<?php echo htmlspecialchars($_GET['min_price'] ?? ''); ?>" class="w-20 px-2 py-1 border border-gray-300 rounded-md text-sm text-gray-800">
                        <span>-</span>
                        <input type="number" name="max_price" placeholder="250" value="<?php echo htmlspecialchars($_GET['max_price'] ?? ''); ?>" class="w-20 px-2 py-1 border border-gray-300 rounded-md text-sm text-gray-800">
                    </div>
                    <div class="w-full bg-gray-200 h-2 rounded-lg relative">
                        <div class="absolute bg-indigo-600 h-2 rounded-lg" style="width: 100%;"></div>
                        <div class="absolute w-4 h-4 bg-indigo-600 rounded-full shadow" style="left: 0%; top: -6px;"></div>
                        <div class="absolute w-4 h-4 bg-indigo-600 rounded-full shadow" style="left: 100%; top: -6px; transform: translateX(-100%);"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 mt-2">
                        <span>$0</span>
                        <span>$250</span>
                    </div>
                </div>

                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-3">Color</h3>
                    <div class="space-y-2">
                        <?php
                        $colors = ['Black', 'Red', 'White'];
                        $selected_colors = $_GET['colors'] ?? [];
                        foreach ($colors as $color) {
                            $checked = in_array($color, $selected_colors) ? 'checked' : '';
                            echo '<label class="flex items-center text-gray-600">';
                            echo '<input type="checkbox" name="colors[]" value="' . htmlspecialchars($color) . '" ' . $checked . ' class="form-checkbox h-4 w-4 text-indigo-600 rounded">';
                            echo '<span class="ml-2">' . htmlspecialchars($color) . '</span>';
                            echo '</label>';
                        }
                        ?>
                    </div>
                </div>
                <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Apply Filters</button>
            </form>
        </aside>

        <main class="flex-1 p-8">
            <?php
            $search_query = trim($_GET['search'] ?? '');
            $min_price = $_GET['min_price'] ?? '';
            $max_price = $_GET['max_price'] ?? '';
            $selected_keywords = isset($_GET['keywords']) && is_array($_GET['keywords']) ? $_GET['keywords'] : [];
            $selected_colors = isset($_GET['colors']) && is_array($_GET['colors']) ? $_GET['colors'] : [];

            $sort_by = $_GET['sort'] ?? 'created_at';
            $sort_order = $_GET['order'] ?? 'DESC';

            $sql_products = "SELECT * FROM products";
            $conditions = [];
            $params = [];
            $param_types = '';

            if (!empty($search_query)) {
                $conditions[] = "(name LIKE ? OR description LIKE ?)";
                $params[] = '%' . $search_query . '%';
                $params[] = '%' . $search_query . '%';
                $param_types .= 'ss';
            }

            if (!empty($selected_keywords)) {
                $keyword_placeholders = implode(', ', array_fill(0, count($selected_keywords), '?'));
                $conditions[] = "category IN (" . $keyword_placeholders . ")";
                foreach ($selected_keywords as $keyword) {
                    $params[] = $keyword;
                    $param_types .= 's';
                }
            }

            if (!empty($selected_colors)) {
                $color_placeholders = implode(', ', array_fill(0, count($selected_colors), '?'));
                $conditions[] = "color IN (" . $color_placeholders . ")";
                foreach ($selected_colors as $color) {
                    $params[] = $color;
                    $param_types .= 's';
                }
            }

            if (is_numeric($min_price) && $min_price >= 0) {
                $conditions[] = "price >= ?";
                $params[] = $min_price;
                $param_types .= 'd';
            }
            if (is_numeric($max_price) && $max_price >= 0) {
                $conditions[] = "price <= ?";
                $params[] = $max_price;
                $param_types .= 'd';
            }

            if (!empty($conditions)) {
                $sql_products .= " WHERE " . implode(' AND ', $conditions);
            }

            $allowed_sort_columns = ['created_at', 'price', 'name'];
            $allowed_sort_orders = ['ASC', 'DESC'];

            if (in_array($sort_by, $allowed_sort_columns) && in_array(strtoupper($sort_order), $allowed_sort_orders)) {
                $sql_products .= " ORDER BY " . $sort_by . " " . strtoupper($sort_order);
            } else {
                $sql_products .= " ORDER BY created_at DESC";
            }

            $stmt = $conn->prepare($sql_products);
            if ($param_types) {
                $bind_params = array($param_types);
                for ($i = 0; $i < count($params); $i++) {
                    $bind_params[] = &$params[$i];
                }
                call_user_func_array(array($stmt, 'bind_param'), $bind_params);
            }
            $stmt->execute();
            $result_products = $stmt->get_result();
            ?>
            <div class="flex justify-between items-center mb-8">
                <div class="flex-1 mr-6">
                    <input type="text" name="search" placeholder="Search" value="<?php echo htmlspecialchars($search_query); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" form="filterForm">
                </div>
                <div class="flex space-x-3">
                    <button type="button" id="sortNew" class="px-4 py-2 <?php echo ($sort_by === 'created_at') ? 'bg-gray-800 text-white' : 'border border-gray-300 bg-gray-100 text-gray-800'; ?> rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">New</button>
                    <button type="button" id="sortPriceAsc" class="px-4 py-2 <?php echo ($sort_by === 'price' && $sort_order === 'ASC') ? 'bg-gray-800 text-white' : 'border border-gray-300 bg-gray-100 text-gray-800'; ?> rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Price ↑</button>
                    <button type="button" id="sortPriceDesc" class="px-4 py-2 <?php echo ($sort_by === 'price' && $sort_order === 'DESC') ? 'bg-gray-800 text-white' : 'border border-gray-300 bg-gray-100 text-gray-800'; ?> rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Price ↓</button>
                </div>
                <a href="/qubegear/cart.php" class="ml-4 px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700">View Cart</a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php
                if ($result_products && $result_products->num_rows > 0) {
                    while ($product = $result_products->fetch_assoc()) {
                ?>
                        <a href="/qubegear/product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="block">
                            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                                <img src="/qubegear/<?php echo htmlspecialchars($product['image'] ?? 'assets/images/placeholder-product.jpg'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-48 object-cover">
                                <div class="p-4">
                                    <h3 class="text-lg font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p class="text-gray-600 text-sm mb-3 h-16 overflow-hidden"><?php echo htmlspecialchars($product['description']); ?></p>
                                    <div class="flex justify-between items-center">
                                        <span class="text-xl font-bold text-gray-900">$<?php echo number_format($product['price'], 2); ?></span>
                                        <button type="button" class="add-to-cart-btn px-3 py-1 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors duration-200" data-product-id="<?php echo htmlspecialchars($product['id']); ?>">Add to Cart</button>
                                    </div>
                                </div>
                            </div>
                        </a>
                <?php
                    }
                } else {
                    echo '<div class="col-span-full text-center py-8">';
                    echo '<p class="text-gray-500 text-lg">No products found matching your criteria.</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </main>
    </div>

    <script src="/qubegear/js/script.js"></script>
    <script src="js/store.js"></script>
</body>
</html> 