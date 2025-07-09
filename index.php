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
                $initial_is_active = 0; // MODIFIED: New accounts are inactive by default

                // Prepare INSERT statement
                // MODIFIED: Added is_active column
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, national_id, password, pin, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt === false) {
                    $error = "Database error (prepare failed). Please try again later.";
                     // error_log("Signup Prepare failed: (" . $conn->errno . ") " . $conn->error);
                } else {
                    // Bind parameters and execute
                    // MODIFIED: Added $initial_is_active and its type 'i'
                    $stmt->bind_param("sssssi", $full_name, $email, $national_id, $hashed_password, $pin, $initial_is_active);
                    if ($stmt->execute()) {
                        // MODIFIED: Updated success message
                        $success = "Account created successfully! Your general PIN is: <strong>$pin</strong>. Please save it. Your account is pending administrator approval.";
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
                // MODIFIED: Added is_active to SELECT
                $stmt = $conn->prepare("SELECT id, full_name, password, pin, is_active FROM users WHERE email = ?");
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

                        // MODIFIED: Check if account is active BEFORE checking password
                        if ($user['is_active'] != 1) {
                            $error = "Your account is not active. Please wait for admin approval or contact support.";
                        } elseif (password_verify($password_input, $user['password'])) {
                            // Login successful: Set session variables
                            $_SESSION['user'] = $user['full_name'];
                            $_SESSION['pin'] = $user['pin'];
                            $_SESSION['voter_id'] = $user['id'];
                            // $stmt->close(); // Close statement after use, moved below
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
                    $stmt->close(); // Close statement here
                }
            }
        } // End if captcha verified
    }
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
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Animate.css for animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 50%, #f0abfc 100%);
            /* Soft purple/blue gradient */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #tsparticles { display: none; } /* Hide particles for a cleaner look */
        .card {
            background: rgba(255,255,255,0.85);
            box-shadow: 0 8px 32px 0 rgba(80, 80, 180, 0.18);
            border-radius: 1.5rem;
            border: none;
            padding: 2.5rem 2rem;
            margin: 2rem auto;
            max-width: 420px;
        }
        .form-control, .btn {
            transition: box-shadow 0.3s, border-color 0.3s, background 0.3s, color 0.3s;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.2rem #a78bfa80, 0 2px 8px #a78bfa33;
        }
        .btn-primary {
            background: linear-gradient(90deg, #a78bfa, #6366f1);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, #6366f1, #a78bfa);
        }
        .password-toggle-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6366f1;
        }
        .switch-link a {
            color: #7c3aed;
            font-weight: 500;
        }
        .switch-link a:hover {
            text-decoration: underline;
        }
        .logo-animate {
            opacity: 0;
            transform: scale(0.8) rotate(-10deg);
            animation: logoFadeIn 1.2s cubic-bezier(0.23, 1, 0.32, 1) 0.5s forwards;
        }
        @keyframes logoFadeIn {
            to {
                opacity: 1;
                transform: scale(1) rotate(0deg);
            }
        }
    </style>
</head>
<body>
    <div id="tsparticles"></div>
    <div class="container d-flex align-items-center justify-content-center min-vh-100">
        <div class="col-12 col-md-8 col-lg-5 mx-auto">
            <div class="card animate__animated animate__fadeInDown" id="tilt-card">
                <div class="text-center mb-4">
                    <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="E-Voting Logo" width="64" class="mb-2 logo-animate">
                    <h2 class="fw-bold mb-0 text-dark"><?php echo $is_signup ? 'Create Your Account' : 'E-Voting System Login'; ?></h2>
                </div>
                <?php if ($error): ?>
                    <div class="alert alert-danger text-center animate__animated animate__shakeX"><?php echo htmlspecialchars($error); ?></div>
                <?php elseif ($success): ?>
                    <div class="alert alert-success text-center animate__animated animate__fadeInUp"><?php echo $success; // Contains HTML (<strong>), so don't escape ?></div>
                <?php endif; ?>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . ($is_signup ? '?signup=1' : ''); ?>">
                    <?php if ($is_signup): ?>
                        <div class="mb-3 animate__animated animate__fadeInLeft animate__delay-1s">
                            <input type="text" id="full_name" name="full_name" class="form-control form-control-lg" placeholder="Enter your full name" required value="<?php echo htmlspecialchars($input_full_name); ?>">
                        </div>
                        <div class="mb-3 animate__animated animate__fadeInLeft animate__delay-2s">
                            <input type="email" id="email" name="email" class="form-control form-control-lg" placeholder="Enter your email" required value="<?php echo htmlspecialchars($input_email); ?>">
                        </div>
                        <div class="mb-3 animate__animated animate__fadeInLeft animate__delay-3s">
                            <input type="text" id="national_id" name="national_id" class="form-control form-control-lg" placeholder="Enter your National ID / Unique ID" required value="<?php echo htmlspecialchars($input_national_id); ?>">
                        </div>
                    <?php else: // Login form ?>
                        <div class="mb-3 animate__animated animate__fadeInLeft animate__delay-1s">
                            <input type="email" id="email" name="email" class="form-control form-control-lg" placeholder="Enter your email" required value="<?php echo htmlspecialchars($input_email); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="mb-3 position-relative animate__animated animate__fadeInLeft animate__delay-2s">
                        <input type="password" id="password" name="password" class="form-control form-control-lg" placeholder="Enter your password" required>
                        <i class="fas fa-eye password-toggle-icon" onclick="togglePasswordVisibility('password')"></i>
                    </div>
                    <div class="g-recaptcha mb-3 animate__animated animate__fadeIn animate__delay-3s" data-sitekey="<?php echo defined('RECAPTCHA_SITE_KEY') ? RECAPTCHA_SITE_KEY : 'YOUR_SITE_KEY'; ?>"></div>
                    <button type="submit" class="btn btn-primary btn-lg w-100 mb-2 animate__animated animate__pulse animate__delay-4s btn-ripple"><span><?php echo $is_signup ? 'Sign Up' : 'Login'; ?></span></button>
                </form>
                <div class="switch-link text-center mt-3 animate__animated animate__fadeInUp animate__delay-5s">
                    <?php if ($is_signup): ?>
                        Already have an account? <a href="index.php">Login Here</a>
                    <?php else: ?>
                        Don't have an account? <a href="index.php?signup=1">Sign Up Here</a>
                        <br> <a href="forgot_password.php" class="d-inline-block mt-2">Forgot Password?</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function togglePasswordVisibility(inputId) {
            const passwordInput = document.getElementById(inputId);
            const icon = passwordInput.nextElementSibling;
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
        // Parallax tilt effect
        VanillaTilt.init(document.querySelectorAll("#tilt-card"), {
            max: 18,
            speed: 400,
            glare: true,
            "max-glare": 0.18
        });
        // tsParticles animated background
        tsParticles.load("tsparticles", {
            background: { color: { value: "#0000" } },
            fpsLimit: 60,
            particles: {
                number: { value: 60, density: { enable: true, area: 800 } },
                color: { value: ["#0d6efd", "#6f42c1", "#20c997", "#ffc107"] },
                shape: { type: "circle" },
                opacity: { value: 0.5 },
                size: { value: { min: 2, max: 5 } },
                move: { enable: true, speed: 1.2, direction: "none", outModes: { default: "out" } },
                links: { enable: true, distance: 120, color: "#0d6efd", opacity: 0.2, width: 1 }
            },
            detectRetina: true
        });
        // Ripple effect on button
        document.querySelectorAll('.btn-ripple').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const circle = document.createElement('span');
                circle.classList.add('ripple');
                const rect = btn.getBoundingClientRect();
                circle.style.width = circle.style.height = Math.max(rect.width, rect.height) + 'px';
                circle.style.left = (e.clientX - rect.left - rect.width/2) + 'px';
                circle.style.top = (e.clientY - rect.top - rect.height/2) + 'px';
                btn.appendChild(circle);
                setTimeout(() => circle.remove(), 600);
            });
        });
    </script>
</body>
</html>