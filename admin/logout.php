<?php
session_start();

// Log the logout action if admin was logged in
if (isset($_SESSION['admin_id'])) {
    require_once 'db_config.php';
    
    $logStmt = $pdo->prepare("INSERT INTO audit_log (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
    $logStmt->execute([
        $_SESSION['admin_id'],
        'logout',
        'Admin logged out',
        $_SERVER['REMOTE_ADDR']
    ]);
}

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: admin_login.php");
exit();
?>