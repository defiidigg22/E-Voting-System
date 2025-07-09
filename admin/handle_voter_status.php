<?php
session_start();
require_once 'db_config.php'; // Uses PDO connection from admin section

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Check if admin details can be fetched (and thus if they are a valid admin)
$stmt_admin_check = $pdo->prepare("SELECT admin_id FROM admins WHERE admin_id = ?");
$stmt_admin_check->execute([$_SESSION['admin_id']]);
if (!$stmt_admin_check->fetch()) {
    session_destroy();
    $_SESSION['error_message'] = "Admin session invalid.";
    header("Location: admin_login.php");
    exit();
}

if (isset($_GET['user_id']) && is_numeric($_GET['user_id']) && isset($_GET['action'])) {
    $user_id = intval($_GET['user_id']);
    $action = $_GET['action'];
    $new_status = null;

    if ($action === 'activate') {
        $new_status = 1;
    } elseif ($action === 'deactivate') {
        $new_status = 0;
    } else {
        $_SESSION['error_message'] = "Invalid action specified.";
        header("Location: manage_voters.php");
        exit();
    }

    // Ensure we are not trying to deactivate/activate a non-existent user or self (if admins were in users table)
    // For now, we assume 'users' table is only for voters.

    try {
        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $user_id]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['success_message'] = "Voter account status updated successfully.";
        } else {
            $_SESSION['error_message'] = "No changes made. Voter not found or status is already as requested.";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        // error_log("Error in handle_voter_status.php: " . $e->getMessage()); // For server-side logging
    }
} else {
    $_SESSION['error_message'] = "Invalid request parameters.";
}

header("Location: manage_voters.php");
exit();
?>