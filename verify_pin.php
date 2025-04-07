<?php
session_start();
if (!isset($_SESSION['user']) || !isset($_SESSION['pin'])) {
    header("Location: index.php");
    exit();
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_pin = trim($_POST['pin']);
    if ($entered_pin === $_SESSION['pin']) {
        $_SESSION['pin_verified'] = true; // Mark PIN as verified
        header("Location: vote.php");
        exit();
    } else {
        $error = "Incorrect PIN. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify PIN</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7fc;
            text-align: center;
            padding: 50px;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: inline-block;
        }
        input {
            padding: 10px;
            margin: 10px;
            width: 80%;
            font-size: 16px;
        }
        button {
            background: #0056b3;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background: #003d80;
        }
        .error {
            color: red;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Enter Your PIN to Vote</h2>
    <form method="post">
        <input type="text" name="pin" placeholder="Enter PIN" required>
        <br>
        <button type="submit">Verify PIN</button>
        <p class="error"><?php echo $error; ?></p>
    </form>
</div>

</body>
</html>
