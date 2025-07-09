<?php
session_start();
require_once 'config.php'; // Uses mysqli $conn
$error = "";
$success = "";
$token_is_valid = false;
$user_id_to_reset = null;

$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (empty($token)) {
    $error = "Password reset token is missing. Please use the link from your email.";
} else {
    // Validate the token: check if it exists, is not used, and not expired
    $stmt = $conn->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()");
    if ($stmt === false) {
        $error = "Database error preparing token validation. Please try again.";
        // error_log("Prepare token validation failed: " . $conn->error);
    } else {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $token_data = $result->fetch_assoc();
            $user_id_to_reset = $token_data['user_id'];
            $token_is_valid = true;
        } else {
            $error = "Invalid or expired password reset token. Please request a new reset link.";
            // Check if it existed but was used or expired to give more specific (but maybe less secure) feedback
            $check_expired_stmt = $conn->prepare("SELECT used, expires_at FROM password_resets WHERE token = ?");
            if($check_expired_stmt){
                $check_expired_stmt->bind_param("s", $token);
                $check_expired_stmt->execute();
                $expired_result = $check_expired_stmt->get_result();
                if($expired_result->num_rows === 1){
                    $data = $expired_result->fetch_assoc();
                    if($data['used'] == 1) $error = "This password reset link has already been used.";
                    else if (strtotime($data['expires_at']) <= time()) $error = "This password reset link has expired.";
                }
                $check_expired_stmt->close();
            }
        }
        $stmt->close();
    }
}

if ($token_is_valid && $_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['password']) && isset($_POST['confirm_password'])) {
        $new_password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($new_password) || empty($confirm_password)) {
            $error = "Both password fields are required.";
        } elseif (strlen($new_password) < 6) { // Match password policy from signup
            $error = "Password must be at least 6 characters long.";
        } elseif ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update the user's password in the 'users' table
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($update_stmt === false) {
                $error = "Database error preparing password update. Please try again.";
                // error_log("Update password prepare failed: " . $conn->error);
            } else {
                $update_stmt->bind_param("si", $hashed_password, $user_id_to_reset);
                if ($update_stmt->execute()) {
                    // Mark the token as used in the 'password_resets' table
                    $mark_used_stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
                    if($mark_used_stmt){
                        $mark_used_stmt->bind_param("s", $token);
                        $mark_used_stmt->execute();
                        $mark_used_stmt->close();
                    }

                    $success = "Your password has been successfully updated! You can now <a href='index.php'>login</a> with your new password.";
                    $token_is_valid = false; // Hide the form after successful reset
                } else {
                    $error = "Failed to update password. Please try again later. Error: " . $update_stmt->error;
                    // error_log("Update password execute failed: " . $update_stmt->error);
                }
                $update_stmt->close();
            }
        }
    } else {
        $error = "Please enter and confirm your new password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - E-Voting System</title>
    <link rel="stylesheet" href="style.css"> <style>
        /* You can reuse styles from index.php or add specific ones here */
        body { display: flex; justify-content: center; align-items: center; height: 100vh; background: #f0f2f5; }
        .container { background: white; padding: 30px 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); text-align: center; width: 100%; max-width: 420px;}
        h2 { color: #333; margin-bottom: 20px; }
        input[type="password"] { width: calc(100% - 22px); padding: 12px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 5px; font-size: 16px; }
        button { background: #28a745; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; width: 100%; transition: background-color 0.2s; }
        button:hover { background: #1e7e34; }
        .message-display { padding: 12px; margin-bottom: 20px; border-radius: 5px; font-size: 0.9em; }
        .message-display.success { background-color: #e6ffed; color: #28a745; border: 1px solid #c3e8cf; }
        .message-display.success a { color: #155724; font-weight: bold; text-decoration: underline; }
        .message-display.error { background-color: #ffebe6; color: #dc3545; border: 1px solid #f5c6cb; }
        .info-text { margin-top: 20px; font-size: 0.9em; }
        .info-text a { color: #007bff; text-decoration: none; }
        .info-text a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Set Your New Password</h2>

        <?php if (!empty($success)): ?>
            <div class="message-display success"><?php echo $success; // Allows HTML for the login link ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="message-display error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($token_is_valid && empty($success)): ?>
            <form method="POST" action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>">
                <div style="margin-bottom: 15px;">
                    <input type="password" id="password" name="password" placeholder="Enter new password" required>
                </div>
                <div style="margin-bottom: 20px;">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                </div>
                <button type="submit">Update Password</button>
            </form>
        <?php elseif (empty($success)): // Show this if token was invalid from the start or became invalid and no success yet ?>
            <p class="info-text">If you need to reset your password, please <a href="forgot_password.php">request a new reset link</a>.</p>
        <?php endif; ?>
        <p class="info-text" style="margin-top: 10px;"><a href="index.php">Back to Login</a></p>
    </div>
</body>
</html>