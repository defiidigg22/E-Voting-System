<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check login, PIN verification status, and voter ID
if (!isset($_SESSION['user']) || !isset($_SESSION['voter_id']) || !isset($_SESSION['pin_verified']) || $_SESSION['pin_verified'] !== true) {
    unset($_SESSION['pin_verified']);
    header("Location: verify_pin.php"); // Redirect if not properly authenticated
    exit();
}

require_once 'config.php'; // Use mysqli connection $conn
$voter_id = $_SESSION['voter_id'];

// Get election ID from URL parameter
$election_id = isset($_GET['election_id']) ? intval($_GET['election_id']) : 0;

if ($election_id <= 0) {
    // Redirect with error if no valid election ID
    $_SESSION['error_message'] = "Invalid election specified."; // Use session for message
    header("Location: select_election.php");
    exit();
}

$election_title = '';
$candidates = [];
$error = '';
$message = '';
$has_voted = false; // Flag to check if user already voted in this election

// Check database connection
if ($conn && !$conn->connect_error) {

    // 1. Check if the user has ALREADY voted in this election using the 'votes' table
    try {
        $sql_check_voted = "SELECT COUNT(*) as vote_count FROM votes WHERE voter_id = ? AND election_id = ?";
        $stmt_check_voted = $conn->prepare($sql_check_voted);
        if($stmt_check_voted === false) throw new Exception("Check Voted Prepare Error: ".$conn->error);
        $stmt_check_voted->bind_param("ii", $voter_id, $election_id);
        if(!$stmt_check_voted->execute()) throw new Exception("Check Voted Execute Error: ".$stmt_check_voted->error);
        $result_voted = $stmt_check_voted->get_result();
        $voted_data = $result_voted->fetch_assoc();
        $stmt_check_voted->close();

        if ($voted_data['vote_count'] > 0) {
            $has_voted = true; // User has already voted
        }
    } catch (Exception $e) {
        $error = "Error checking vote status: " . $e->getMessage();
        // error_log($error);
    }

    // 2. If not already voted, fetch election details and candidates
    if (!$error && !$has_voted) {
        try {
            $now = date('Y-m-d H:i:s');
            // Fetch election title and candidates if election is active
            $sql_election_info = "SELECT e.title, c.candidate_id, c.name, c.party
                                 FROM elections e
                                 LEFT JOIN candidates c ON e.election_id = c.election_id
                                 WHERE e.election_id = ?
                                   AND e.status = 'active'
                                   AND e.start_datetime <= ?
                                   AND e.end_datetime >= ?
                                 ORDER BY c.name"; // Order candidates alphabetically
            $stmt_info = $conn->prepare($sql_election_info);
            if($stmt_info === false) throw new Exception("Election Info Prepare Error: ".$conn->error);
            $stmt_info->bind_param("iss", $election_id, $now, $now);
            if(!$stmt_info->execute()) throw new Exception("Election Info Execute Error: ".$stmt_info->error);
            $result_info = $stmt_info->get_result();
            $first_row = true;
            while ($row = $result_info->fetch_assoc()) {
                if ($first_row) {
                    $election_title = $row['title']; // Get title from first row
                    $first_row = false;
                }
                // Only add candidate if candidate_id is not null
                if ($row['candidate_id'] !== null) {
                     $candidates[] = ['candidate_id' => $row['candidate_id'], 'name' => $row['name'], 'party' => $row['party']];
                }
            }
            $stmt_info->close();

            // Check if election was found and is active
            if (empty($election_title)) {
                 $error = "This election is not active or does not exist.";
                 // Redirect if election is invalid
                 $_SESSION['error_message'] = $error;
                 header("Location: select_election.php");
                 exit();
            } elseif (empty($candidates)) {
                // Election is active, but no candidates
                $error = "No candidates are currently available for this election.";
            }

        } catch (Exception $e) {
             $error = "Error fetching election details: " . $e->getMessage();
             // error_log($error);
        }
    } elseif ($has_voted && !$error) {
        // If already voted, still fetch election title for display
        try {
            $sql_title = "SELECT title FROM elections WHERE election_id = ?";
            $stmt_title = $conn->prepare($sql_title);
            $stmt_title->bind_param("i", $election_id);
            $stmt_title->execute();
            $result_title = $stmt_title->get_result();
            if ($row_title = $result_title->fetch_assoc()) {
                $election_title = $row_title['title'];
            }
            $stmt_title->close();
        } catch (Exception $e) { /* Ignore error fetching title if already voted */ }
    }


    // 3. Process the vote submission if NOT already voted and NO errors so far
    if (!$error && !$has_voted && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['candidate_id'])) {
        $selected_candidate_id = intval($_POST['candidate_id']);

        // Basic validation: ensure selected candidate is valid for this election
        $valid_candidate = false;
        foreach ($candidates as $candidate) { // Ensure $candidates was populated
            if ($candidate['candidate_id'] == $selected_candidate_id) {
                $valid_candidate = true;
                break;
            }
        }

        if ($valid_candidate) {
            // Start transaction
            $conn->begin_transaction();
            try {
                // Insert into votes table
                $sql_insert_vote = "INSERT INTO votes (election_id, voter_id, candidate_id, voted_at, ip_address) VALUES (?, ?, ?, NOW(), ?)";
                $stmt_insert = $conn->prepare($sql_insert_vote);
                if($stmt_insert === false) throw new Exception("Vote Insert Prepare Error: ".$conn->error);
                $ip_address = $_SERVER['REMOTE_ADDR']; // Get user's IP
                $stmt_insert->bind_param("iiis", $election_id, $voter_id, $selected_candidate_id, $ip_address);

                if(!$stmt_insert->execute()) {
                     if ($conn->errno == 1062) { throw new Exception("Duplicate vote detected.", 1062); }
                     else { throw new Exception("Vote Insert Execute Error: ".$stmt_insert->error); }
                }
                $stmt_insert->close();

                // Commit transaction
                $conn->commit();

                // Mark as voted for this page load and set success message
                $has_voted = true;
                $message = "Thank you! Your vote has been successfully recorded.";

                // Unset PIN verification flag ONLY after successful vote
                unset($_SESSION['pin_verified']);

            } catch (Exception $e) {
                $conn->rollback(); // Rollback on error
                if ($e->getCode() == 1062) {
                     $error = "It seems you have already voted in this election.";
                     $has_voted = true; // Treat as voted if duplicate error
                } else {
                    $error = "Database error processing vote: " . $e->getMessage();
                    // error_log("Vote Processing Error: " . $e->getMessage());
                }
            }
        } else {
            $error = "Invalid candidate selection.";
        }
    }

} else {
     $error = "Database connection error.";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote Now - <?php echo htmlspecialchars($election_title ?: 'Election'); ?></title>
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Base styles similar to voter dashboard */
        :root {
            --primary-color: #0056b3;
            --secondary-color: #003d80;
            --light-color: #f4f7fc;
            --card-bg: #ffffff;
            --text-dark: #333;
            --text-light: #555;
            --accent-color: #ffcc00;
            --border-color: #e0e0e0;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --success-color: #28a745;
            --success-dark: #1f8a38;
            --success-bg: #d4edda;
            --error-color: #721c24;
            --error-bg: #f8d7da;
            --info-color: #0c5460;
            --info-bg: #d1ecf1;
        }
        body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background-color: var(--light-color); color: var(--text-dark); line-height: 1.6; }
        header { background: var(--primary-color); color: white; padding: 1rem 0; text-align: center; font-size: 1.6em; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        nav { display: flex; justify-content: center; background: var(--secondary-color); padding: 0.8rem 0; flex-wrap: wrap; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        nav a { color: white; text-decoration: none; margin: 5px 20px; font-size: 1.1em; transition: color 0.3s; padding: 5px 0; display: inline-flex; align-items: center; }
        nav a i { margin-right: 8px; }
        nav a:hover, nav a.active { color: var(--accent-color); font-weight: bold; }

        /* Main Container */
        .container {
            width: 90%;
            max-width: 750px; /* Adjusted max-width for voting form */
            margin: 30px auto;
            background: var(--card-bg);
            padding: 2rem 2.5rem; /* More padding */
            border-radius: 8px;
            box-shadow: 0 4px 12px var(--shadow-color);
        }
        .election-title {
            text-align: center;
            color: var(--primary-color);
            margin-top: 0;
            margin-bottom: 1rem;
            font-size: 1.8em;
            font-weight: 600;
        }
        .election-instructions {
            text-align: center;
            color: var(--text-light);
            margin-bottom: 2.5rem;
            font-size: 1.05em;
        }

        /* Candidate Selection Styling */
        .candidate-list { list-style: none; padding: 0; margin: 0; }
        .candidate-item { margin-bottom: 1rem; }
        .candidate-label {
            display: block; /* Make label take full width */
            background-color: #fdfdff; /* Slightly off-white */
            padding: 1.1rem 1.4rem; /* Increased padding */
            border-radius: 6px;
            border: 2px solid var(--border-color);
            cursor: pointer;
            transition: background-color 0.2s, border-color 0.2s, box-shadow 0.2s;
            display: flex;
            align-items: center;
        }
        .candidate-label:hover {
            background-color: #f5f8ff; /* Light blue tint on hover */
            border-color: #b0c4de; /* Light steel blue border */
        }
        /* Style for the selected candidate */
        input[type="radio"]:checked + .candidate-label {
            background-color: #e7f1ff; /* Light blue background */
            border-color: var(--primary-color);
            box-shadow: 0 0 10px rgba(0, 86, 179, 0.25); /* More prominent shadow */
            font-weight: bold;
        }
        input[type="radio"] {
            /* Hide the default radio button */
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            position: absolute; /* Take it out of flow */
            opacity: 0; width: 0; height: 0;
        }
        .candidate-info { flex-grow: 1; margin-left: 15px; } /* Add margin */
        .candidate-name { font-size: 1.2em; color: var(--text-dark); margin-bottom: 0.1rem; }
        .candidate-party { font-size: 0.95em; color: var(--text-light); }
        /* Custom radio button appearance */
        .custom-radio {
            min-width: 22px; /* Fixed size */
            width: 22px;
            height: 22px;
            border: 2px solid #ccc;
            border-radius: 50%;
            display: inline-block;
            position: relative;
            transition: border-color 0.2s, background-color 0.2s;
            flex-shrink: 0; /* Prevent shrinking */
            background-color: #fff;
        }
        .candidate-label:hover .custom-radio { border-color: #999; }
        input[type="radio"]:checked + .candidate-label .custom-radio {
            border-color: var(--primary-color);
            background-color: var(--primary-color); /* Fill when checked */
        }
        input[type="radio"]:checked + .candidate-label .custom-radio::after {
            content: '\f00c'; /* Font Awesome check mark */
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: white;
            font-size: 12px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            line-height: 1;
        }

        /* Submit Button */
        .submit-section { text-align: center; margin-top: 2.5rem; }
        .submit-button {
            background: var(--success-color);
            color: white;
            padding: 0.9rem 2.5rem; /* Larger padding */
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.2em; /* Larger font */
            font-weight: bold;
            transition: background-color 0.2s, transform 0.2s, box-shadow 0.2s;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 3px 6px rgba(0,0,0,0.15);
        }
        .submit-button i { margin-right: 10px; }
        .submit-button:hover {
            background: var(--success-dark); /* Darker green */
            transform: scale(1.03);
            box-shadow: 0 5px 10px rgba(0,0,0,0.2);
        }
        .submit-button:disabled {
            background: #aaa;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Message Styling */
        .message { text-align: center; font-size: 1.1em; margin-top: 1.5rem; padding: 1rem 1.5rem; border-radius: 6px; border: 1px solid transparent; display: flex; align-items: center; justify-content: center; }
        .message i { margin-right: 10px; font-size: 1.2em; }
        .message.success { background-color: var(--success-bg); color: var(--success-color); border-color: var(--success-color); }
        .message.error { background-color: var(--error-bg); color: var(--error-color); border-color: var(--error-color); }
        .message.info { background-color: var(--info-bg); color: var(--info-color); border-color: var(--info-color); }

        /* Navigation Links */
        .nav-links { margin-top: 2.5rem; text-align: center; border-top: 1px solid var(--border-color); padding-top: 1.5rem;}
        .nav-links a { color: var(--primary-color); text-decoration: none; margin: 0 12px; font-size: 0.95em; display: inline-flex; align-items: center;}
        .nav-links a i { margin-right: 5px; }
        .nav-links a:hover { text-decoration: underline; }

        /* Footer */
        footer { background: var(--secondary-color); color: white; text-align: center; padding: 1.2rem 0; margin-top: 40px; }

         /* Responsive */
        @media (max-width: 600px) {
             .container { padding: 1.5rem; width: 95%; }
             .election-title { font-size: 1.5em; }
             .candidate-label { padding: 0.8rem 1rem; flex-direction: column; align-items: flex-start; } /* Stack radio/text */
             .custom-radio { margin-bottom: 8px; } /* Space below radio */
             .candidate-info { margin-left: 0; }
             .submit-button { width: 100%; padding: 0.8rem; font-size: 1.1em; justify-content: center; }
        }
    </style>
</head>
<body>

    <header>E-Voting System</header>
     <nav>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
        <a href="select_election.php" class="active"><i class="fas fa-person-booth"></i>Vote Now</a>
        <a href="results.php"><i class="fas fa-chart-bar"></i>View Results</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
    </nav>

<div class="container">
    <h2 class="election-title"><?php echo htmlspecialchars($election_title ?: 'Election Voting'); ?></h2>

    <?php if ($message): // Vote Success ?>
        <div class="message success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?></div>
    <?php elseif ($has_voted && !$error): // Already Voted ?>
        <div class="message info"><i class="fas fa-info-circle"></i> You have already voted in this election.</div>
    <?php elseif ($error): // Any other error ?>
         <div class="message error"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>


    <?php // Show voting form ONLY if no errors AND not already voted AND candidates exist ?>
    <?php if (!$error && !$has_voted && !empty($candidates)): ?>
        <p class="election-instructions">Please select your preferred candidate below and click "Submit Vote".</p>
        <form method="post" action="vote.php?election_id=<?php echo $election_id; ?>">
            <ul class="candidate-list">
                <?php foreach ($candidates as $candidate): ?>
                    <li class="candidate-item">
                        <input type="radio" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>" id="candidate_<?php echo $candidate['candidate_id']; ?>" required>
                        <label for="candidate_<?php echo $candidate['candidate_id']; ?>" class="candidate-label">
                             <span class="custom-radio"></span> <div class="candidate-info">
                                <div class="candidate-name"><?php echo htmlspecialchars($candidate['name']); ?></div>
                                <?php if (!empty($candidate['party'])): ?>
                                    <div class="candidate-party"><?php echo htmlspecialchars($candidate['party']); ?></div>
                                <?php endif; ?>
                                </div>
                        </label>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="submit-section">
                 <button type="submit" class="submit-button"><i class="fas fa-check-to-slot"></i> Submit Vote</button>
            </div>
        </form>
    <?php endif; ?>


     <div class="nav-links">
         <a href="select_election.php"><i class="fas fa-arrow-left"></i> Select Another Election</a> |
         <a href="dashboard.php"><i class="fas fa-home"></i> Go to Dashboard</a>
    </div>

</div>

 <footer>
    &copy; <?php echo date("Y"); ?> E-Voting System. All Rights Reserved.
</footer>

</body>
</html>
