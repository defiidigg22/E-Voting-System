<?php
session_start();
require_once 'config.php'; // Uses mysqli $conn

// >>> Include PHPMailer classes <<<
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
// Ensure this path is correct relative to your forgot_password.php file
// If forgot_password.php is in ca2/ and vendor/ is also in ca2/, then 'vendor/autoload.php' is correct.
// If vendor/ is in the parent directory of ca2/, it would be '../vendor/autoload.php'.
require 'vendor/autoload.php';

$message = ""; // For general success messages
$error = "";   // For specific error messages

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Fetch full_name as well for personalizing the email
        $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ? AND is_active = 1");
        if ($stmt === false) {
            $error = "Database error preparing statement. Please try again later.";
            // error_log("Prepare failed in forgot_password.php: " . $conn->error);
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                $user_id = $user['id'];
                $user_full_name = $user['full_name']; // Get user's name for email
                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token valid for 1 hour

                // Invalidate any previous active tokens for this user
                $invalidate_old_stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0");
                if ($invalidate_old_stmt) {
                    $invalidate_old_stmt->bind_param("i", $user_id);
                    $invalidate_old_stmt->execute();
                    $invalidate_old_stmt->close();
                } else {
                    // Optional: Log if this fails, but don't necessarily stop the process
                    // error_log("Failed to prepare statement for invalidating old tokens: " . $conn->error);
                }
                
                $insert_stmt = $conn->prepare("INSERT INTO password_resets (user_id, email, token, expires_at) VALUES (?, ?, ?, ?)");
                if ($insert_stmt === false) {
                    $error = "Database error creating reset token (prepare failed). Please try again later.";
                    // error_log("Insert token prepare failed in forgot_password.php: " . $conn->error);
                } else {
                    $insert_stmt->bind_param("isss", $user_id, $email, $token, $expires_at);
                    if ($insert_stmt->execute()) {
                        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                        $host = $_SERVER['HTTP_HOST'];
                        // Construct path robustly, assuming forgot_password.php is in the 'ca2' root
                        $script_dir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\'); // Remove trailing slash if any
                        $reset_link = $protocol . "://" . $host . $script_dir . "/reset_password.php?token=" . $token;


                        // >>> PHPMailer Integration <<<
                        $mail = new PHPMailer(true); // Passing `true` enables exceptions

                        try {
                            // Server settings - CONFIGURE THESE!
                            $mail->isSMTP();
                            $mail->Host       = 'smtp.gmail.com'; // e.g., 'smtp.gmail.com' or your hosting provider's SMTP
                            $mail->SMTPAuth   = true;
                            $mail->Username   = 'eldeksa6@gmail.com'; // Your SMTP username
                            $mail->Password   = 'zswt zcbc jiuf vqqa';  // Your SMTP password or App Password for Gmail
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Or PHPMailer::ENCRYPTION_SMTPS for SSL
                            $mail->Port       = 587; // Use 587 for TLS, 465 for SMTPS (SSL)

                            //Recipients
                            $mail->setFrom('noreply@yourdomain.com', 'E-Voting System'); // Change this to your "From" address and name
                            $mail->addAddress($email, $user_full_name);     // Add a recipient

                            // Content
                            $mail->isHTML(true);
                            $mail->Subject = 'Password Reset Request - E-Voting System';
                            $mail->Body    = "Hello {$user_full_name},<br><br>" .
                                             "You requested a password reset for your E-Voting System account.<br>" .
                                             "Please click the following link to reset your password:<br>" .
                                             "<a href='{$reset_link}'>{$reset_link}</a><br><br>" .
                                             "This link is valid for 1 hour.<br><br>" .
                                             "If you did not request this password reset, please ignore this email.<br><br>" .
                                             "Thank you,<br>The E-Voting System Team";
                            $mail->AltBody = "Hello {$user_full_name},\n\n" .
                                             "You requested a password reset for your E-Voting System account.\n" .
                                             "Please copy and paste the following link into your browser to reset your password:\n" .
                                             $reset_link . "\n\n" .
                                             "This link is valid for 1 hour.\n\n" .
                                             "If you did not request this password reset, please ignore this email.\n\n" .
                                             "Thank you,\nThe E-Voting System Team";

                            $mail->send();
                            $message = "If an active account with that email exists, a password reset link has been sent. Please check your inbox (and spam folder).";
                        } catch (Exception $e) {
                            $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}. Please contact support.";
                            // error_log("PHPMailer Error in forgot_password.php: " . $mail->ErrorInfo);
                        }
                    } else {
                        $error = "Database error saving reset token: " . $insert_stmt->error;
                        // error_log("Insert token execute failed in forgot_password.php: " . $insert_stmt->error);
                    }
                    $insert_stmt->close();
                }
            } else {
                // Email not found or account not active, show a generic message for security
                $message = "If an active account with that email exists, a password reset link has been sent. Please check your inbox (and spam folder).";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - E-Voting System</title>
    <link rel="stylesheet" href="style.css"> <style>
        /* Styles for forgot_password.php (can be moved to style.css or a shared CSS file) */
        body { display: flex; justify-content: center; align-items: center; height: 100vh; background: #f0f2f5; font-family: Arial, sans-serif; margin: 0; }
        .container {
            background: white;
            padding: 35px 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            width: 100%;
            max-width: 430px;
        }
        h2 { color: #333; margin-top: 0; margin-bottom: 15px; font-size: 1.6em; }
        p.instructions { color: #555; font-size: 0.95em; margin-bottom: 25px; line-height: 1.5; }
        input[type="email"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        button {
            background: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            width: 100%;
            transition: background-color 0.2s;
        }
        button:hover { background: #0056b3; }
        .message-display {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 0.9em;
            text-align: left;
        }
        .message-display.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message-display.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .login-link { margin-top: 25px; display: block; font-size: 0.9em; }
        .login-link a { color: #007bff; text-decoration: none; font-weight: bold; }
        .login-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Forgot Your Password?</h2>
        <p class="instructions">No problem! Enter your email address below. If it's associated with an active account, we'll send you a link to reset your password.</p>

        <?php if (!empty($message)): ?>
            <div class="message-display success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="message-display error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (empty($message) || !empty($error)): // Show form if no success message OR if there was an error and we want to allow retry ?>
        <form method="POST" action="forgot_password.php">
            <div style="margin-bottom: 20px;">
                <input type="email" id="email" name="email" placeholder="Enter your registered email address" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            <button type="submit">Send Password Reset Link</button>
        </form>
        <?php endif; ?>

        <p class="login-link"><a href="index.php">Back to Login</a></p>
    </div>
</body>
</html>