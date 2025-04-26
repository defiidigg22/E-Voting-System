
<?php
session_start();

// Redirect if user not logged in or no PIN associated with session
if (!isset($_SESSION['user']) || !isset($_SESSION['pin'])) {
    // Maybe redirect to login or dashboard with an error?
    header("Location: index.php"); // Redirect to login if session is invalid
    exit();
}

$error = "";
// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_pin = trim($_POST['pin']);

    // Verify entered PIN against the PIN stored in the session
    if ($entered_pin === $_SESSION['pin']) {
        // PIN is correct!
        $_SESSION['pin_verified'] = true; // Set flag indicating PIN verification success
        // Redirect to the NEW page that lists elections
        header("Location: select_election.php");
        exit();
    } else {
        // Incorrect PIN
        $error = "Incorrect PIN. Please try again.";
        unset($_SESSION['pin_verified']); // Ensure flag is not set if PIN is wrong
    }
} else {
     // If accessing page via GET, ensure pin_verified is not set
     unset($_SESSION['pin_verified']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify PIN</title>
    <style>
        /* You can reuse styles from index.php or style.css */
        body { font-family: Arial, sans-serif; background: #f4f7fc; text-align: center; padding-top: 100px; }
        .container { background: white; padding: 30px 40px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); display: inline-block; width: 350px; }
        h2 { color: #0056b3; margin-bottom: 20px; }
        input[type="text"], input[type="password"] { /* Treat PIN like password */
            padding: 12px;
            margin-bottom: 15px;
            width: 100%;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        button {
            background: #0056b3;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
             transition: background-color 0.2s;
        }
        button:hover { background: #003d80; }
        .error { color: red; font-size: 14px; margin-top: 15px; min-height: 20px; /* Prevent layout shift */ }
        .back-link { margin-top: 20px; display: block; font-size: 14px; }
        .back-link a { color: #0056b3; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <h2>Enter Your Account PIN</h2>
    <form method="post" action="verify_pin.php">
        <input type="password" name="pin" placeholder="Enter PIN" required pattern="\d{6}" title="PIN must be 6 digits" maxlength="6" inputmode="numeric"> <?php /* Use password type */ ?>
        <br>
        <button type="submit">Verify PIN</button>
        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php else: ?>
             <p class="error"></p> <?php /* Placeholder for consistent spacing */ ?>
        <?php endif; ?>
    </form>
     <div class="back-link">
        <a href="dashboard.php">Cancel and go back to Dashboard</a>
    </div>
</div>

</body>
</html>