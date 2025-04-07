<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Voting System - Login/Signup</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: url('https://media.istockphoto.com/id/1201072992/photo/voting.jpg?s=2048x2048&w=is&k=20&c=1fjvMK92St7l854bMAg1IziRGGncQ3LGiCteJ-MLNMM=') no-repeat center center/cover;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            backdrop-filter: blur(10px);
        }
        .container {
            background: rgba(255, 255, 255, 0.2);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.3);
            text-align: center;
            width: 400px;
            backdrop-filter: blur(15px);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        h2 {
            color: #ffffff;
            margin-bottom: 20px;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.7);
        }
        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.25);
            color: #fff;
            backdrop-filter: blur(8px);
            font-size: 16px;
        }
        input::placeholder {
            color: rgba(255, 255, 255, 0.8);
        }
        input:focus {
            outline: none;
            border: 2px solid #00bcd4;
            background: rgba(255, 255, 255, 0.3);
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #00bcd4;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: 0.3s;
            font-weight: bold;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
        }
        button:hover {
            background-color: #0097a7;
            box-shadow: 0px 6px 12px rgba(0, 0, 0, 0.3);
        }
        .error {
            color: #ff4444;
            font-size: 14px;
            margin-top: 10px;
        }
        .success {
            color: #4CAF50;
            font-size: 14px;
            margin-top: 10px;
        }
        p {
            margin-top: 15px;
            font-size: 14px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
        }
        a {
            color: #00bcd4;
            text-decoration: none;
            font-weight: bold;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<?php 
session_start();
include 'config.php'; 

$is_signup = isset($_GET['signup']);
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = isset($_POST['email']) ? trim($_POST['email']) : "";
    $password = trim($_POST['password']);

   
    $captcha = $_POST['g-recaptcha-response'];
    $secretKey = "6LfZqgErAAAAAF9Km5PflgKNkx6WUdV2DEGxykRJ"; 
    $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".RECAPTCHA_SECRET."&response=".$captcha);
    $responseKeys = json_decode($response, true);

    if (!$responseKeys["success"]) {
        $error = "CAPTCHA verification failed. Please try again.";
    } else {
        if ($is_signup) {
            if (empty($username) || empty($email) || empty($password)) {
                $error = "All fields are required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Invalid email format.";
            } elseif (strlen($password) < 6) {
                $error = "Password must be at least 6 characters long.";
            } else {
                $pin = rand(100000, 999999); 
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                file_put_contents("users.txt", "$username|$email|$hashed_password|$pin\n", FILE_APPEND);
                $success = "Account created! Your PIN is: <strong>$pin</strong>. Please save this PIN.";
            }
        } else {
            $users = file("users.txt", FILE_IGNORE_NEW_LINES);
            foreach ($users as $user) {
                list($stored_username, $stored_email, $stored_password, $stored_pin) = explode("|", $user);
                if ($username == $stored_username && password_verify($password, $stored_password)) {
                    $_SESSION['user'] = $username;
                    $_SESSION['pin'] = $stored_pin;
                    header("Location: dashboard.php");
                    exit();
                }
            }
            $error = "Invalid username or password.";
        }
    }
}
?>

    <div class="container">
        <h2><?php echo $is_signup ? 'Create an Account' : 'E-Voting System Login'; ?></h2>
        <form method="post" action="">
            <input type="text" name="username" placeholder="Enter your username" required>
            <?php if ($is_signup): ?>
                <input type="email" name="email" placeholder="Enter your email" required>
            <?php endif; ?>
            <input type="password" name="password" placeholder="Enter your password" required>
            <div class="g-recaptcha" data-sitekey="6LfZqgErAAAAAP2FFT_Y-fOar5wNQPDb2oFNg8lj"></div>
            <button type="submit"><?php echo $is_signup ? 'Sign Up' : 'Login'; ?></button>
            <p class="error"> <?php echo $error; ?> </p>
            <p class="success"> <?php echo $success; ?> </p>
        </form>
        <p>
            <?php if ($is_signup): ?>
                Already have an account? <a href="index.php">Login</a>
            <?php else: ?>
                Don't have an account? <a href="index.php?signup=1">Sign Up</a>
            <?php endif; ?>
        </p>
    </div>
</body>
</html>