<?php
session_start();



// Redirect if user hasn't verified PIN
if (!isset($_SESSION['user']) || !isset($_SESSION['pin_verified']) || $_SESSION['pin_verified'] !== true) {
    header("Location: verify_pin.php");
    exit();
}

$username = $_SESSION['user']; // Correct session variable
$file_path = "voted_users.txt";

// Ensure the file exists
if (!file_exists($file_path)) {
    file_put_contents($file_path, ""); // Create an empty file
}

$voted_users = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Ensure $voted_users is always an array
if ($voted_users === false) {
    $voted_users = [];
}

// Check if the user has already voted
if (in_array($username, $voted_users)) {
    echo "<h2>You have already voted.</h2>";
    echo "<a href='dashboard.php'>Go back to Dashboard</a>";
    exit;
}

// Process the vote
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['candidate'])) {
    $candidate = $_POST['candidate'];

    // Store the vote in a text file
    file_put_contents("votes.txt", "$username:$candidate\n", FILE_APPEND);

    // Mark the user as voted
    file_put_contents($file_path, "$username\n", FILE_APPEND);

    // Reset PIN verification session after voting
    unset($_SESSION['pin_verified']);

    echo "<h2>Thank you for voting!</h2>";
    echo "<a href='results.php'>View Results</a>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote Now</title>
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
    </style>
</head>
<body>

<div class="container">
    <h2>Vote for Your Favorite Candidate</h2>
    <form method="post">
        <label>
            <input type="radio" name="candidate" value="Candidate (Meadow)" required> Candidate A
        </label><br>
        <label>
            <input type="radio" name="candidate" value="Candidate (Meadow ki Maa)" required> Candidate B
        </label><br>
        <label>
            <input type="radio" name="candidate" value="(Meadow k baap)" required> Candidate C
        </label><br><br>
        <label>
            <input type="radio" name="candidate" value="Candidate (uncle ki)" required> Candidate B
        </label><br>
        <button type="submit">Submit Vote</button>
    </form>
</div>

</body>
</html>
