<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = trim($_POST['otp']);

    if ($entered_otp == $_SESSION['otp']) {
        // OTP is correct, save user to file
        $user_data = $_SESSION['temp_user'];
        file_put_contents("users.txt", "{$user_data['username']}|{$user_data['email']}|{$user_data['password']}|PIN\n", FILE_APPEND);
        
        unset($_SESSION['otp'], $_SESSION['temp_user']); // Remove temporary data
        header("Location: login.php"); // Redirect to login
        exit();
    } else {
        $error = "Invalid OTP. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>Verify OTP</title></head>
<body>
    <h2>Enter OTP</h2>
    <form method="post">
        <input type="text" name="otp" placeholder="Enter OTP" required>
        <button type="submit">Verify</button>
    </form>
    <p><?php echo isset($error) ? $error : ''; ?></p>
</body>
</html>
