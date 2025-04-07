<?php
$file_path = "votes.txt";

// Ensure file exists before reading
if (!file_exists($file_path)) {
    file_put_contents($file_path, ""); // Create an empty file if it doesn't exist
}

// Read votes from the file
$votes = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Initialize vote counts
$vote_count = [
    "Candidate A" => 0,
    "Candidate B" => 0,
    "Candidate C" => 0
];

// Count votes
foreach ($votes as $vote) {
    $parts = explode(":", $vote); // Format: username:candidate
    if (count($parts) == 2) {
        $candidate = trim($parts[1]);
        if (isset($vote_count[$candidate])) {
            $vote_count[$candidate]++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results</title>
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
        .results {
            font-size: 20px;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Election Results</h2>
    <div class="results">
        <p><strong>Candidate A:</strong> <?php echo $vote_count["Candidate A"]; ?> votes</p>
        <p><strong>Candidate B:</strong> <?php echo $vote_count["Candidate B"]; ?> votes</p>
        <p><strong>Candidate C:</strong> <?php echo $vote_count["Candidate C"]; ?> votes</p>
    </div>
    <br>
    <a href="dashboard.php">Go Back to Dashboard</a>
</div>

</body>
</html>
