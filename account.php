<?php
require_once 'includes/db.php';
$user = getCurrentUser();

if (!$user) {
    redirect('/qubegear/login.php');
}

$errors = [];
$success_message = '';

// Handle profile picture upload
if (isset($_POST['upload_profile_image']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "assets/images/profiles/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image_name = basename($_FILES['profile_image']['name']);
        $image_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($image_extension, $allowed_extensions)) {
            $errors['profile_image'] = 'Only JPG, JPEG, PNG, GIF files are allowed.';
        } elseif ($_FILES['profile_image']['size'] > 5000000) { // 5MB
            $errors['profile_image'] = 'Image size must be less than 5MB.';
        } else {
            $unique_name = uniqid() . '.' . $image_extension;
            $target_file = $target_dir . $unique_name;
            if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                $errors['profile_image'] = 'Failed to upload image.';
            } else {
                $image_path = 'assets/images/profiles/' . $unique_name;
                
                // Update database
                $stmt = $conn->prepare("UPDATE users SET profile_image = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->bind_param("si", $image_path, $user['id']);
                if ($stmt->execute()) {
                    $success_message = 'Profile picture updated successfully!';
                    $user['profile_image'] = $image_path; // Update user session data immediately
                } else {
                    $errors['profile_image'] = 'Failed to update profile picture in database: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

// Handle password change
if (isset($_POST['change_password']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    if (empty($old_password) || empty($new_password) || empty($confirm_new_password)) {
        $errors['password_general'] = 'All password fields are required.';
    } elseif (!password_verify($old_password, $user['password'])) {
        $errors['old_password'] = 'Old password does not match.';
    } elseif (strlen($new_password) < 6) {
        $errors['new_password'] = 'New password must be at least 6 characters long.';
    } elseif ($new_password !== $confirm_new_password) {
        $errors['confirm_new_password'] = 'New password and confirmation do not match.';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user['id']);
        if ($stmt->execute()) {
            $success_message = 'Password changed successfully!';
        } else {
            $errors['password_general'] = 'Failed to change password: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle account deletion
if (isset($_POST['delete_account']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // For security, you might want to ask for password confirmation here
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user['id']);
    if ($stmt->execute()) {
        session_destroy(); // Destroy session after account deletion
        redirect('/qubegear/register.php'); // Redirect to register or home page
    } else {
        $errors['delete_account'] = 'Failed to delete account: ' . $stmt->error;
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - QubeGear</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="antialiased bg-gray-50 flex flex-col min-h-screen">
    <?php include 'includes/header.php'; ?>

    <main class="flex-1 container mx-auto px-4 py-8 pt-24">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">My Account</h1>

        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Profile Information</h2>
            <div class="flex items-center space-x-6 mb-6">
                <div class="w-24 h-24 rounded-full overflow-hidden border-2 border-gray-300">
                    <img src="/qubegear/<?php echo htmlspecialchars($user['profile_image'] ?? 'assets/images/placeholder-avatar.jpg'); ?>" 
                         alt="Profile Picture" class="w-full h-full object-cover">
                </div>
                <div>
                    <p class="text-lg text-gray-700">Name: <span class="font-medium"><?php echo htmlspecialchars($user['name']); ?></span></p>
                    <p class="text-lg text-gray-700">Email: <span class="font-medium"><?php echo htmlspecialchars($user['email']); ?></span></p>
                </div>
            </div>

            <!-- Profile Picture Upload Form -->
            <div class="mb-8 border-t pt-6 mt-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Change Profile Picture</h3>
                <form action="/qubegear/account.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label for="profile_image" class="block text-gray-700 text-sm font-bold mb-2">Upload new picture:</label>
                        <input type="file" id="profile_image" name="profile_image" class="block w-full text-sm text-gray-700
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-md file:border-0
                            file:text-sm file:font-semibold
                            file:bg-indigo-50 file:text-indigo-700
                            hover:file:bg-indigo-100" required>
                        <?php if (isset($errors['profile_image'])): ?><p class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($errors['profile_image']); ?></p><?php endif; ?>
                    </div>
                    <button type="submit" name="upload_profile_image" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Upload Image</button>
                </form>
            </div>

            <!-- Change Password Form -->
            <div class="mb-8 border-t pt-6 mt-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Change Password</h3>
                <form action="/qubegear/account.php" method="POST" class="space-y-4">
                    <div>
                        <label for="old_password" class="block text-gray-700 text-sm font-bold mb-2">Old Password:</label>
                        <input type="password" id="old_password" name="old_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        <?php if (isset($errors['old_password'])): ?><p class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($errors['old_password']); ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label for="new_password" class="block text-gray-700 text-sm font-bold mb-2">New Password:</label>
                        <input type="password" id="new_password" name="new_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        <?php if (isset($errors['new_password'])): ?><p class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($errors['new_password']); ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label for="confirm_new_password" class="block text-gray-700 text-sm font-bold mb-2">Confirm New Password:</label>
                        <input type="password" id="confirm_new_password" name="confirm_new_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        <?php if (isset($errors['confirm_new_password'])): ?><p class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($errors['confirm_new_password']); ?></p><?php endif; ?>
                    </div>
                    <?php if (isset($errors['password_general'])): ?><p class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($errors['password_general']); ?></p><?php endif; ?>
                    <button type="submit" name="change_password" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Change Password</button>
                </form>
            </div>

            <!-- Delete Account -->
            <div class="border-t pt-6 mt-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Danger Zone</h3>
                <p class="text-gray-700 mb-4">Permanently delete your account and all associated data.</p>
                <?php if (isset($errors['delete_account'])): ?><p class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($errors['delete_account']); ?></p><?php endif; ?>
                <form action="/qubegear/account.php" method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
                    <button type="submit" name="delete_account" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Delete Account</button>
                </form>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html> 