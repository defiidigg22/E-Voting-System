<?php
error_reporting(E_ALL); // You already have this, which is good
ini_set('display_errors', 1); // And this
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Check if user logged in AND if PIN was verified in this session
if (!isset($_SESSION['user']) || !isset($_SESSION['voter_id']) || !isset($_SESSION['pin_verified']) || $_SESSION['pin_verified'] !== true) {
    unset($_SESSION['pin_verified']); // Clear flag just in case
    header("Location: verify_pin.php");
    exit();
}

require_once 'config.php'; // Use mysqli connection $conn
$voter_id = $_SESSION['voter_id'];

// 2. Query for available elections (using mysqli) - Simplified Query
$available_elections = [];
$db_error = null;

if ($conn && !$conn->connect_error) {
    try {
        $now = date('Y-m-d H:i:s');
        // Simplified SQL: Select all active elections within the date range
        $sql = "SELECT e.election_id, e.title, e.description, e.end_datetime
                FROM elections e
                WHERE e.status = 'active'
                  AND e.start_datetime <= ?  -- bind $now
                  AND e.end_datetime >= ?    -- bind $now
                ORDER BY e.end_datetime ASC"; // Order by end date

        $stmt = $conn->prepare($sql);
        if ($stmt === false) { throw new Exception("Prepare failed: (" . $conn->errno . ") " . $conn->error); }

        $stmt->bind_param("ss", $now, $now);
        if (!$stmt->execute()) { throw new Exception("Execute failed: (" . $stmt->errno . ") " . $stmt->error); }

        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $available_elections[] = $row;
        }
        $stmt->close();

    } catch (Exception $e) {
        $db_error = "Error fetching elections: " . $e->getMessage();
        // error_log($db_error); // Log error for debugging
    }
} else {
    $db_error = "Database connection is not available.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Election</title>
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
        }
        body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background-color: var(--light-color); color: var(--text-dark); line-height: 1.6; }
        header { background: var(--primary-color); color: white; padding: 1rem 0; text-align: center; font-size: 1.6em; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        nav { display: flex; justify-content: center; background: var(--secondary-color); padding: 0.8rem 0; flex-wrap: wrap; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        nav a { color: white; text-decoration: none; margin: 5px 20px; font-size: 1.1em; transition: color 0.3s; padding: 5px 0; display: inline-flex; align-items: center; }
        nav a i { margin-right: 8px; }
        nav a:hover, nav a.active { color: var(--accent-color); font-weight: bold; }

        /* Main Container */
        .container { width: 90%; max-width: 1200px; margin: 30px auto; padding: 0 15px; box-sizing: border-box; }
        .page-title { text-align: center; color: var(--primary-color); margin-bottom: 2.5rem; font-size: 1.8em; font-weight: 600; }

        /* Election Card Grid */
        .election-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); /* Responsive grid */
            gap: 1.8rem; /* Spacing between cards */
        }
        .election-card {
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 4px 12px var(--shadow-color);
            overflow: hidden; /* Contain elements */
            display: flex;
            flex-direction: column; /* Stack content vertically */
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            border: 1px solid var(--border-color); /* Subtle border */
        }
        .election-card:hover {
             transform: translateY(-5px);
             box-shadow: 0 8px 18px rgba(0, 0, 0, 0.12);
        }
        .election-card-content {
            padding: 1.5rem 1.8rem; /* Padding inside card */
            flex-grow: 1; /* Allow content to fill space */
        }
        .election-card h3 {
            margin-top: 0;
            margin-bottom: 0.8rem;
            color: var(--primary-color);
            font-size: 1.3em;
            display: flex;
            align-items: center;
        }
        .election-card h3 i { /* Icon in title */
            margin-right: 10px;
            font-size: 1.1em;
            opacity: 0.9;
        }
        .election-card p.description { /* Style description */
            color: var(--text-light);
            font-size: 0.95em;
            margin-bottom: 1rem;
            min-height: 40px; /* Reserve some space */
            /* Limit description lines (optional) */
            /* display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden; */
        }
        .election-card .details { /* Style details section */
             font-size: 0.85em;
             color: var(--text-light);
             margin-top: auto; /* Push details to bottom if needed */
             padding-top: 0.8rem; /* Space above details */
             border-top: 1px dashed var(--border-color); /* Dashed separator */
        }
        .election-card .details span {
            margin-right: 15px;
            display: inline-block; /* Ensure spacing */
        }
         .election-card .details i {
             margin-right: 5px;
             color: var(--secondary-color); /* Icon color */
         }

        .election-card-action {
            padding: 1rem 1.8rem;
            background-color: #f9f9f9; /* Slightly different background for action area */
            border-top: 1px solid var(--border-color);
            text-align: right; /* Align button right */
        }
        .select-btn {
            display: inline-flex; /* Align icon */
            align-items: center;
            padding: 0.7rem 1.4rem; /* Slightly larger button */
            background-color: var(--success-color); /* Green button */
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 1em;
            transition: background-color 0.2s, transform 0.2s;
            border: none; /* Remove default border */
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .select-btn i { margin-right: 8px; }
        .select-btn:hover {
            background-color: var(--success-dark); /* Darker green */
            transform: scale(1.03);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        /* Message/Error Boxes */
        .message-box { text-align: center; padding: 30px; background: var(--card-bg); border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); color: var(--text-light); margin-top: 20px;}
        .message-box i { font-size: 1.5em; display: block; margin-bottom: 10px; color: var(--primary-color); }
        .error-box { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center;}

        /* Footer */
        footer { background: var(--secondary-color); color: white; text-align: center; padding: 1.2rem 0; margin-top: 40px; }

        /* Responsive */
        @media (max-width: 600px) {
             .election-list { grid-template-columns: 1fr; } /* Stack cards on small screens */
             header { font-size: 1.4em; }
             nav a { font-size: 1em; margin: 5px 10px; }
             .page-title { font-size: 1.5em; margin-bottom: 1.5rem; }
             .election-card-content { padding: 1.2rem 1.5rem; }
             .election-card-action { text-align: center; }
             .select-btn { width: 100%; justify-content: center; }
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
        <h2 class="page-title">Select an Election to Vote In</h2>

        <?php if ($db_error): ?>
            <div class="error-box">
                <p><?php echo htmlspecialchars($db_error); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!$db_error && !empty($available_elections)): ?>
            <div class="election-list">
                <?php foreach ($available_elections as $election): ?>
                    <div class="election-card">
                        <div class="election-card-content">
                            <h3><i class="fas fa-poll-h"></i><?php echo htmlspecialchars($election['title']); ?></h3>
                            <p class="description"><?php echo nl2br(htmlspecialchars($election['description'] ?: 'No description provided.')); ?></p>
                            <div class="details">
                                <span><i class="fas fa-calendar-times"></i> Ends: <?php echo date('M j, Y H:i A', strtotime($election['end_datetime'])); ?></span>
                            </div>
                        </div>
                        <div class="election-card-action">
                             <a href="vote.php?election_id=<?php echo $election['election_id']; ?>" class="select-btn"><i class="fas fa-arrow-right"></i> Select & Vote</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif (!$db_error): ?>
            <div class="message-box">
                 <p><i class="fas fa-info-circle"></i></p>
                 <p>There are currently no active elections available for you to vote in.</p>
                 <p><a href="dashboard.php" style="color: var(--primary-color); text-decoration: underline;">Return to Dashboard</a></p>
            </div>
        <?php endif; ?>

    </div>

     <footer>
        &copy; <?php echo date("Y"); ?> E-Voting System. All Rights Reserved.
    </footer>

</body>
</html>
