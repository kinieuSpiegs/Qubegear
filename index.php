<?php
require_once 'includes/db.php';
$user = getCurrentUser();
?>
<?php include 'includes/header.php'; ?>

    
    <div class="flex flex-col justify-center items-center h-screen bg-gray-100">
        <h1 class="text-6xl font-bold text-black">QubeGear</h1>
        <p class="text-xl text-gray-600 mt-4">Reliable Sports Equipment</p>
        <a href="/qubegear/cart.php" class="mt-6 px-20 py-2 border border-gray-900 text-gray-800 rounded-lg hover:bg-gray-600 hover:text-white transition text-center">
            My Cart
        </a>
    </div>

    <section class="w-full">
        <img src="assets/images/sportsequipment.jpg" 
             alt="Sports Equipment"
             class="w-full object-cover">
    </section>

<?php include 'includes/footer.php'; ?> 