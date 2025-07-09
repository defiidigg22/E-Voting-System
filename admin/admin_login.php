<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'db_config.php'; // PDO

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$error = "";
$input_username = ''; // Retain username on error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $input_username = $username; // Store for retaining value

    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {                // Login successful
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['username'] = $admin['username'];
                $_SESSION['is_superadmin'] = $admin['is_superadmin'];

                // Update last login
                $updateStmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE admin_id = ?");
                $updateStmt->execute([$admin['admin_id']]);

                // Log login (optional, ensure table exists)
                /*
                try {
                    $logStmt = $pdo->prepare("INSERT INTO audit_log (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
                    $logStmt->execute([$admin['admin_id'], 'login', 'Admin logged in', $_SERVER['REMOTE_ADDR']]);
                } catch (PDOException $logE) {
                    // Log error if audit fails, but don't stop login
                    error_log("Audit log failed for admin login: " . $logE->getMessage());
                }
                */

                header("Location: admin_dashboard.php");
                exit();
            } else {
                // Invalid credentials
                $error = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $error = "Database error. Please try again later.";
             // error_log("Admin Login DB Error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - E-Voting System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Animate.css for animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 50%, #f0abfc 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: rgba(255,255,255,0.88);
            box-shadow: 0 8px 32px 0 rgba(80, 80, 180, 0.18);
            border-radius: 1.5rem;
            border: none;
            padding: 2.5rem 2rem;
            margin: 2rem auto;
            max-width: 420px;
            width: 100%;
            text-align: center;
            animation: fadeInDown 1s cubic-bezier(.23,1,.32,1);
        }
        @keyframes fadeInDown {
            0% { opacity: 0; transform: translateY(-40px) scale(0.98); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        h2 {
            color: #4b2994;
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 2em;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .message-area {
            margin-bottom: 20px;
            min-height: 40px;
            text-align: center;
        }
        .message {
            font-size: 1em;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid transparent;
            display: block;
            box-sizing: border-box;
            word-wrap: break-word;
        }
        .message.error {
            color: #fff;
            background: linear-gradient(90deg, #f472b6, #a78bfa);
            border: none;
            box-shadow: 0 2px 8px #f472b655;
        }
        .form-group {
            margin-bottom: 18px;
            position: relative;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #6366f1;
            font-size: 0.97em;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 14px 15px;
            border: 1px solid #c7d2fe;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
            background-color: #f8fafc;
            color: #22223b;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #a78bfa;
            box-shadow: 0 0 0 0.2rem #a78bfa80, 0 2px 8px #a78bfa33;
        }
        .password-input-wrapper input {
            padding-right: 40px;
        }
        .password-toggle-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6366f1;
            font-size: 1.1em;
            z-index: 2;
            transition: color 0.2s;
        }
        .password-toggle-icon:hover {
            color: #a78bfa;
        }
        button[type="submit"] {
            width: 100%;
            padding: 14px;
            background: linear-gradient(90deg, #a78bfa, #6366f1);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
            box-shadow: 0 4px 16px #a78bfa33;
            transition: background 0.3s, box-shadow 0.3s;
            position: relative;
            overflow: hidden;
        }
        button[type="submit"]:hover {
            background: linear-gradient(90deg, #6366f1, #a78bfa);
            box-shadow: 0 8px 32px #6366f144;
        }

    </style>
</head>
<body>
    <div class="login-container animate__animated animate__fadeInDown">
        <h2>Admin Login</h2>
        <div class="message-area">
             <?php if ($error): ?>
                <div class="message error animate__animated animate__shakeX"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
        </div>
        <form method="POST" action="admin_login.php">
            <div class="form-group animate__animated animate__fadeInLeft animate__delay-1s">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($input_username); ?>">
            </div>
            <div class="form-group password-input-wrapper animate__animated animate__fadeInLeft animate__delay-2s">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="password-input" required>
                 <i class="fas fa-eye password-toggle-icon" onclick="togglePasswordVisibility('password')"></i>
            </div>
            <button type="submit" class="animate__animated animate__pulse animate__delay-3s">Login</button>
        </form