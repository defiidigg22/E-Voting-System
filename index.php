<?php
session_start();
require_once 'config.php'; // contains $conn

$is_signup = isset($_GET['signup']);
$error = "";
$success = "";

// --- Define variables to retain form input on error ---
$input_full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$input_email = isset($_POST['email']) ? trim($_POST['email']) : '';
$input_national_id = isset($_POST['national_id']) ? trim($_POST['national_id']) : '';
// Don't retain password for security reasons

// Check if $conn was established (handle connection error)
if ($conn->connect_error) {
    $error = "Database connection error. Please try again later.";
}

// Only process form if DB connection is okay
if (!$conn->connect_error && $_SERVER["REQUEST_METHOD"] == "POST") {
    $password_input = trim($_POST['password']);

    // CAPTCHA Check
    $captcha = isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : '';
    $secretKey = defined('RECAPTCHA_SECRET') ? RECAPTCHA_SECRET : "YOUR_SECRET_KEY"; // Use constant or default

    $captcha_verified = false;
    // Verify CAPTCHA only if secret key is properly set
    if (!empty($secretKey) && $secretKey !== "YOUR_SECRET_KEY") {
        $response = @file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$captcha");
        if ($response !== false) {
            $responseKeys = json_decode($response, true);
            if ($responseKeys && isset($responseKeys["success"]) && $responseKeys["success"]) {
                $captcha_verified = true;
            } else {
                 $error = "CAPTCHA verification failed. Please try again.";
            }
        } else {
            // Network error or issue contacting Google
            $error = "Could not verify CAPTCHA. Please check server configuration or API keys.";
        }
    } else {
        // Bypass CAPTCHA check if secret key isn't configured properly (for local dev)
        // In production, you should ensure keys are set and remove this bypass or set an error.
         // error_log("Warning: reCAPTCHA secret key not configured in config.php. Bypassing check."); // Log warning
        $captcha_verified = true;
    }


    if ($captcha_verified && !$error) {
        if ($is_signup) {
            // --- SIGNUP LOGIC ---
            $full_name = $input_full_name;
            $email = $input_email;
            $national_id = $input_national_id;

            // Basic Validation
            if (empty($full_name) || empty($email) || empty($password_input) || empty($national_id)) {
                $error = "All fields (Full Name, Email, National ID, Password) are required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Invalid email format.";
            } elseif (strlen($password_input) < 6) { // Example minimum length
                $error = "Password must be at least 6 characters long.";
            } else {
                // Generate PIN and hash password
                $pin = rand(100000, 999999); // 6-digit PIN
                $hashed_password = password_hash($password_input, PASSWORD_DEFAULT);

                // Prepare INSERT statement
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, national_id, password, pin) VALUES (?, ?, ?, ?, ?)");
                if ($stmt === false) {
                    $error = "Database error (prepare failed). Please try again later.";
                     // error_log("Signup Prepare failed: (" . $conn->errno . ") " . $conn->error);
                } else {
                    // Bind parameters and execute
                    $stmt->bind_param("sssss", $full_name, $email, $national_id, $hashed_password, $pin);
                    if ($stmt->execute()) {
                        $success = "Account created! Your general PIN is: <strong>$pin</strong>. Please save this PIN securely.";
                        // Clear form fields on success
                        $input_full_name = $input_email = $input_national_id = '';
                    } else {
                        // Check for duplicate entry errors
                        if ($conn->errno == 1062) { // Error code for duplicate key
                            if (strpos($stmt->error, 'email') !== false) {
                                $error = "This email address is already registered.";
                            } elseif (strpos($stmt->error, 'national_id') !== false) {
                                $error = "This National ID is already registered.";
                            } else {
                                $error = "An account with these details already exists."; // Generic duplicate
                            }
                        } else {
                            $error = "Database error during signup. Please try again later.";
                             // error_log("Signup Execute Error: " . $stmt->error);
                        }
                    }
                    $stmt->close();
                }
            }
        } else {
            // --- LOGIN LOGIC ---
            $email_input = $input_email; // Use email from form
            if (empty($email_input) || empty($password_input)) {
                $error = "Email and password are required.";
            } else {
                // Prepare SELECT statement
                $stmt = $conn->prepare("SELECT id, full_name, password, pin FROM users WHERE email = ?");
                 if ($stmt === false) {
                     $error = "Database error (prepare failed). Please try again later.";
                      // error_log("Login Prepare failed: (" . $conn->errno . ") " . $conn->error);
                 } else {
                    // Bind email, execute, and get result
                    $stmt->bind_param("s", $email_input);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows === 1) {
                        $user = $result->fetch_assoc();
                        // Verify password
                        if (password_verify($password_input, $user['password'])) {
                            // Login successful: Set session variables
                            $_SESSION['user'] = $user['full_name'];
                            $_SESSION['pin'] = $user['pin'];
                            $_SESSION['voter_id'] = $user['id'];
                            $stmt->close();
                            // Redirect to dashboard
                            header("Location: dashboard.php");
                            exit();
                        } else {
                            // Invalid password
                            $error = "Invalid email or password.";
                        }
                    } else {
                        // Email not found
                        $error = "Invalid email or password.";
                    }
                    $stmt->close();
                }
            }
        }
    } // End if captcha verified

} elseif ($conn->connect_error) {
     // Handle connection error that occurred before POST check
     $error = "Database connection error. Please try again later.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Voting System - <?php echo $is_signup ? 'Signup' : 'Login'; ?></title>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Base styles */
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Arial', sans-serif;
        }
        body {
            background: url('https://media.istockphoto.com/id/1201072992/photo/voting.jpg?s=2048x2048&w=is&k=20&c=1fjvMK92St7l854bMAg1IziRGGncQ3LGiCteJ-MLNMM=') no-repeat center center fixed; /* Fixed background */
            background-size: cover;
            color: #fff;
            /* --- Centering Fix --- */
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px; /* Add padding for smaller screens */
            box-sizing: border-box;
        }
        .container {
            background: rgba(0, 0, 0, 0.65); /* Darker semi-transparent background */
            padding: 35px 40px; /* Increased padding */
            border-radius: 15px;
            box-shadow: 0px 10px 25px rgba(0, 0, 0, 0.5);
            text-align: center;
            width: 100%;
            max-width: 450px; /* Max width */
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-sizing: border-box;
        }
        h2 {
            color: #ffffff;
            margin-top: 0; /* Remove default top margin */
            margin-bottom: 25px; /* Increased bottom margin */
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.7);
            font-size: 1.8em; /* Slightly larger heading */
        }

        /* --- Message Styling (Moved Above Form) --- */
        .message-area {
            margin-bottom: 20px; /* Space between messages and form */
            min-height: 45px; /* Reserve space even when no message */
            text-align: center;
        }
        .message {
            font-size: 0.95em; /* Slightly larger message font */
            padding: 12px 15px;
            border-radius: 8px; /* Match input radius */
            border: 1px solid transparent;
            display: block; /* Ensure it takes full width */
            box-sizing: border-box;
            word-wrap: break-word;
        }
        .message.error {
            color: #ffdddd;
            background-color: rgba(255, 68, 68, 0.4); /* Slightly stronger red */
            border-color: rgba(255, 68, 68, 0.6);
        }
        .message.success {
            color: #ddffdd;
            background-color: rgba(76, 175, 80, 0.4); /* Slightly stronger green */
            border-color: rgba(76, 175, 80, 0.6);
        }
        .message.success strong { /* Style bold PIN */
            color: #fff;
            font-weight: bold;
        }

        /* Form Group Styling */
        .form-group {
            margin-bottom: 18px; /* Consistent spacing */
            position: relative; /* Needed for password toggle icon */
            text-align: left; /* Align labels left */
        }
        .form-group label { /* Optional: Add labels if desired */
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            font-size: 0.9em;
            color: rgba(255, 255, 255, 0.9);
        }
        input {
            width: 100%;
            padding: 14px; /* Slightly larger padding */
            /* --- Input Field Distinction --- */
            background: rgba(255, 255, 255, 0.15); /* Slightly less transparent */
            border: 1px solid rgba(255, 255, 255, 0.4); /* Slightly stronger border */
            border-radius: 8px; /* More rounded corners */
            color: #fff;
            font-size: 16px;
            box-sizing: border-box;
            transition: background-color 0.3s, border-color 0.3s;
        }
        input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        input:focus {
            outline: none;
            border-color: #00bcd4; /* Highlight focus */
            background: rgba(255, 255, 255, 0.25);
        }
        /* Specific padding for password input when icon is present */
        .password-input-wrapper input {
             padding-right: 45px; /* Make space for the icon */
        }

        /* Password Toggle Icon */
        .password-toggle-icon {
            position: absolute;
            right: 15px;
            /* Adjust top based on whether you have labels */
            top: 50%; /* If no labels */
            /* top: calc(50% + 12px); */ /* Approximate if using labels */
            transform: translateY(-50%);
            cursor: pointer;
            color: rgba(255, 255, 255, 0.7);
            font-size: 1.1em;
            z-index: 2; /* Ensure icon is clickable */
        }
         .password-toggle-icon:hover {
             color: #fff;
         }

        /* Style for reCAPTCHA */
        .g-recaptcha {
            display: flex; /* Use flex to center */
            justify-content: center; /* Center horizontally */
            margin-top: 15px;
            margin-bottom: 15px;
        }

        /* Button Styling */
        button {
            width: 100%;
            padding: 14px;
            background-color: #00bcd4;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 17px; /* Slightly larger font */
            transition: background-color 0.3s, box-shadow 0.3s;
            font-weight: bold;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
            margin-top: 15px; /* Space above button */
        }
        button:hover {
            background-color: #0097a7;
            box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.3);
        }

        /* Link Styling */
        .switch-link {
            margin-top: 25px; /* More space before links */
            font-size: 0.95em;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
        }
        .switch-link a {
            color: #00bcd4;
            text-decoration: none;
            font-weight: bold;
        }
        .switch-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><?php echo $is_signup ? 'Create Your Account' : 'E-Voting System Login'; ?></h2>

        <div class="message-area">
            <?php if ($error): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif ($success): ?>
                 <div class="message success"><?php echo $success; // Contains HTML (<strong>), so don't escape ?></div>
            <?php endif; ?>
        </div>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . ($is_signup ? '?signup=1' : ''); ?>">
            <?php if ($is_signup): ?>
                <div class="form-group">
                     <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required value="<?php echo htmlspecialchars($input_full_name); ?>">
                </div>
                <div class="form-group">
                     <input type="email" id="email" name="email" placeholder="Enter your email" required value="<?php echo htmlspecialchars($input_email); ?>">
                </div>
                <div class="form-group">
                     <input type="text" id="national_id" name="national_id" placeholder="Enter your National ID / Unique ID" required value="<?php echo htmlspecialchars($input_national_id); ?>">
                </div>
            <?php else: // Login form ?>
                 <div class="form-group">
                     <input type="email" id="email" name="email" placeholder="Enter your email" required value="<?php echo htmlspecialchars($input_email); ?>">
                 </div>
            <?php endif; ?>

            <div class="form-group password-input-wrapper">
                 <input type="password" id="password" name="password" class="password-input" placeholder="Enter your password" required>
                <i class="fas fa-eye password-toggle-icon" onclick="togglePasswordVisibility('password')"></i>
            </div>

            <div class="g-recaptcha" data-sitekey="<?php echo defined('RECAPTCHA_SITE_KEY') ? RECAPTCHA_SITE_KEY : 'YOUR_SITE_KEY'; // Use constant or default ?>"></div>

            <button type="submit"><?php echo $is_signup ? 'Sign Up' : 'Login'; ?></button>
        </form>

        <p class="switch-link">
            <?php if ($is_signup): ?>
                Already have an account? <a href="index.php">Login Here</a>
            <?php else: ?>
                Don't have an account? <a href="index.php?signup=1">Sign Up Here</a>
            <?php endif; ?>
        </p>
    </div>

    <script>
        function togglePasswordVisibility(inputId) {
            const passwordInput = document.getElementById(inputId);
            const icon = passwordInput.nextElementSibling; // Get the icon next to the input

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