<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'qubegear');

// Site configuration
define('SITE_NAME', 'QubeGear');
define('SITE_URL', 'http://localhost/qubegear');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?> 