<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'evoting');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create PDO instance
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Function to generate random PIN codes
function generatePinCode($length = 6) {
    $characters = '0123456789';
    $pin = '';
    for ($i = 0; $i < $length; $i++) {
        $pin .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $pin;
}
?>
