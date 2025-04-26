<?php
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

            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Login successful
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
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
         /* Base styles */
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Arial', sans-serif;
        }
        body {
            /* --- Centering Fix --- */
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f4f4f9; /* Light background for admin */
            padding: 20px;
            box-sizing: border-box;
        }
        .login-container {
            background-color: white;
            padding: 35px 40px;
            border-radius: 10px; /* Slightly more rounded */
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 420px; /* Max width for the box */
            text-align: center;
            box-sizing: border-box;
        }
        h2 {
            color: #333;
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 1.7em;
        }

        /* --- Message Styling (Moved Above Form) --- */
        .message-area {
            margin-bottom: 20px;
            min-height: 40px; /* Reserve space */
            text-align: center;
        }
        .message {
            font-size: 0.9em;
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid transparent;
            display: block;
            box-sizing: border-box;
            word-wrap: break-word;
        }
        .message.error {
            color: #721c24; /* Darker red text */
            background-color: #f8d7da; /* Light red background */
            border-color: #f5c6cb; /* Red border */
        }
        /* No success message needed on login page typically */

        /* Form Group Styling */
        .form-group {
            margin-bottom: 18px;
            position: relative; /* For icon positioning */
            text-align: left;
        }
        .form-group label { /* Optional labels */
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            color: #555;
            font-size: 0.9em;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px; /* Standard padding */
            border: 1px solid #ccc; /* Clearer border */
            border-radius: 5px;
            font-size: 1rem;
            box-sizing: border-box;
             /* --- Input Field Distinction --- */
            background-color: #fff; /* White background */
            color: #333; /* Dark text */
            transition: border-color 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #6600ff; /* Use primary color for focus */
            box-shadow: 0 0 0 2px rgba(102, 0, 255, 0.2); /* Subtle focus ring */
        }
         /* Specific padding for password input when icon is present */
        .password-input-wrapper input {
             padding-right: 40px; /* Make space for the icon */
        }

        /* Password Toggle Icon */
        .password-toggle-icon {
            position: absolute;
            right: 12px;
            /* Adjust top based on label presence */
            top: 50%; /* If no label */
            /* top: calc(50% + 13px); */ /* Approximate if using labels */
            transform: translateY(-50%);
            cursor: pointer;
            color: #888; /* Grey icon */
            font-size: 1.1em;
            z-index: 2;
        }
         .password-toggle-icon:hover {
             color: #333;
         }

        /* Button Styling */
        button {
            width: 100%;
            padding: 12px;
            background-color: #6600ff; /* Use primary color */
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s;
            font-weight: bold;
            margin-top: 10px;
        }
        button:hover {
            background-color: #4d00c2; /* Darker primary */
        }

    </style>
</head>
<body>
    <div class="login-container">
        <h2>Admin Login</h2>

        <div class="message-area">
             <?php if ($error): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
        </div>

        <form method="POST" action="admin_login.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($input_username); ?>">
            </div>
            <div class="form-group password-input-wrapper">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="password-input" required>
                 <i class="fas fa-eye password-toggle-icon" onclick="togglePasswordVisibility('password')"></i>
            </div>
            <button type="submit">Login</button>
        </form>
    </div>

     <script>
        function togglePasswordVisibility(inputId) {
            const passwordInput = document.getElementById(inputId);
            const icon = passwordInput.nextElementSibling; // Get the icon

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>

</body>
</html>