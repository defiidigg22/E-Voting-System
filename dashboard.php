<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect to login if user session isn't set
if (!isset($_SESSION['user']) || !isset($_SESSION['voter_id'])) {
    header("Location: index.php");
    exit();
}

// Get user info from session
$user_full_name = $_SESSION['user'];
$voter_id = $_SESSION['voter_id'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Voting Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Basic styles */
        :root {
            --primary-color: #0056b3; /* Adjusted primary for voter section */
            --secondary-color: #003d80; /* Darker blue */
            --light-color: #f4f7fc;
            --card-bg: #ffffff;
            --text-dark: #333;
            --text-light: #555;
            --accent-color: #ffcc00; /* Accent color */
            --border-color: #e0e0e0;
            --shadow-color: rgba(0, 0, 0, 0.08);
            --success-color: #28a745;
            --success-bg: #e9f7ea;
        }
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--light-color);
            color: var(--text-dark);
            line-height: 1.6;
        }
        header {
            background: var(--primary-color);
            color: white;
            padding: 1rem 0;
            text-align: center;
            font-size: 1.6em; /* Larger header */
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        nav {
            display: flex;
            justify-content: center;
            background: var(--secondary-color);
            padding: 0.8rem 0; /* Slightly reduced padding */
            flex-wrap: wrap; /* Allow wrapping on small screens */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        nav a {
            color: white;
            text-decoration: none;
            margin: 5px 20px; /* Adjusted margin */
            font-size: 1.1em;
            transition: color 0.3s;
            padding: 5px 0; /* Add some padding for touch */
            display: inline-flex; /* Align icons */
            align-items: center;
        }
        nav a i {
            margin-right: 8px;
        }
        nav a:hover, nav a.active {
            color: var(--accent-color);
            font-weight: bold;
        }

        /* Main Container */
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 15px; /* Add horizontal padding */
            box-sizing: border-box;
        }

        /* Welcome Message */
        .welcome-banner {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 4px 10px var(--shadow-color);
        }
         .welcome-banner h2 {
             margin: 0 0 0.5rem 0;
             font-size: 1.8em;
         }
         .welcome-banner p {
             margin: 0;
             font-size: 1.1em;
             opacity: 0.9;
         }

        /* Card Layout for Sections */
        .info-grid {
            display: grid;
            /* Responsive grid */
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem; /* Increased gap */
            margin-bottom: 2rem;
        }
        .info-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 1.5rem 1.8rem; /* Adjusted padding */
            box-shadow: 0 4px 10px var(--shadow-color);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .info-card:hover {
             transform: translateY(-4px);
             box-shadow: 0 6px 15px rgba(0, 0, 0, 0.12);
        }
        .info-card h3 {
            margin-top: 0;
            margin-bottom: 1rem;
            color: var(--primary-color);
            font-size: 1.25em;
            display: flex;
            align-items: center;
        }
         .info-card h3 i { /* Icon in card title */
             margin-right: 10px;
             font-size: 1.1em;
             opacity: 0.9;
         }
        .info-card p {
            color: var(--text-light);
            font-size: 0.95em;
            margin-bottom: 0; /* Remove bottom margin if last element */
        }
        .info-card ul {
            padding-left: 20px;
            margin-top: 0.5rem;
            margin-bottom: 0;
            color: var(--text-light);
            font-size: 0.95em;
        }
         .info-card ul li {
             margin-bottom: 0.3rem;
         }

        /* Call to Action Card */
        .cta-card {
            background-color: var(--success-bg); /* Light green background */
            border: 1px solid var(--success-color);
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            margin-top: 2rem;
            box-shadow: 0 4px 10px var(--shadow-color);
        }
        .cta-card h3 {
            margin-top: 0;
            margin-bottom: 1rem;
            color: var(--success-color);
            font-size: 1.4em;
        }
        .cta-card p {
             color: var(--text-dark);
             margin-bottom: 1.5rem;
             font-size: 1.05em;
        }
        .cta-button {
            background: var(--success-color);
            color: white;
            padding: 0.8rem 1.8rem; /* Larger button */
            border-radius: 5px;
            font-size: 1.1em;
            text-decoration: none;
            transition: background-color 0.3s, transform 0.2s;
            display: inline-flex; /* Align icon */
            align-items: center;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.15);
        }
         .cta-button i {
             margin-right: 8px;
         }
        .cta-button:hover {
            background: #1f8a38; /* Darker green */
            transform: scale(1.03);
        }

        /* Footer */
        footer {
            background: var(--secondary-color);
            color: white;
            text-align: center;
            padding: 1.2rem 0; /* Increased padding */
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <header>E-Voting System</header>
    <nav>
        <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
        <a href="select_election.php"><i class="fas fa-person-booth"></i>Vote Now</a>
        <a href="results.php"><i class="fas fa-chart-bar"></i>View Results</a>
        <a href="contact.php"><i class="fas fa-envelope"></i>Contact</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
    </nav>

    <div class="container">
        <div class="welcome-banner">
            <h2>Welcome, <?php echo htmlspecialchars($user_full_name); ?>!</h2>
            <p>Your secure E-Voting Dashboard.</p>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <h3><i class="fas fa-shield-alt"></i>Secure Voting</h3>
                <p>Your vote is important and kept secure using encryption and strict verification processes.</p>
            </div>
            <div class="info-card">
                <h3><i class="fas fa-question-circle"></i>How to Vote</h3>
                <p>Follow these simple steps:</p>
                <ul>
                    <li>Verify your PIN (if prompted).</li>
                    <li>Select an active election.</li>
                    <li>Choose your preferred candidate.</li>
                    <li>Confirm and submit your vote.</li>
                </ul>
            </div>
             <div class="info-card">
                <h3><i class="fas fa-bullhorn"></i>Announcements</h3>
                <p>Stay tuned for updates on upcoming elections and results availability.</p>
                </div>
        </div>

        <div class="cta-card">
            <h3>Ready to Vote?</h3>
            <p>Proceed to the voting section to view active elections and cast your ballot.</p>
            <a href="select_election.php" class="cta-button"><i class="fas fa-person-booth"></i> Go to Voting Page</a>
        </div>
    </div>

    <footer>
        &copy; <?php echo date("Y"); ?> E-Voting System | Secure & Transparent Elections
    </footer>
</body>
</html>
```

