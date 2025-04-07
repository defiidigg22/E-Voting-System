<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user'])) {
    header("Location: index.php"); 
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Voting Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background: #f4f7fc;
            color: #333;
        }
        header {
            background: #0056b3;
            color: white;
            padding: 15px 0;
            text-align: center;
            font-size: 22px;
            font-weight: bold;
        }
        nav {
            display: flex;
            justify-content: center;
            background: #003d80;
            padding: 10px 0;
        }
        nav a {
            color: white;
            text-decoration: none;
            margin: 0 20px;
            font-size: 18px;
            transition: 0.3s;
        }
        nav a:hover {
            color: #ffcc00;
        }
        .container {
            width: 80%;
            margin: auto;
            padding: 40px 0;
        }
        .section {
            margin-bottom: 50px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        .section:hover {
            transform: scale(1.02);
        }
        h2 {
            color: #0056b3;
            text-align: center;
        }
        p {
            text-align: justify;
            font-size: 16px;
        }
        .cta {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 30px;
            padding: 15px;
            background: rgba(0, 86, 179, 0.1);
            border-radius: 10px;
        }
        .cta a {
            background: #0056b3;
            color: white;
            padding: 12px 20px;
            border-radius: 5px;
            font-size: 18px;
            text-decoration: none;
            transition: 0.3s;
            display: inline-block;
        }
        .cta a:hover {
            background: #003d80;
        }
        footer {
            background: #003d80;
            color: white;
            text-align: center;
            padding: 15px 0;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <header>E-Voting System</header>
    <nav>
        <a href="home.php">Home</a>
        <a href="about.php">About</a>
        <a href="how-it-works.php">How It Works</a>
        <a href="results.php">Results</a>
        <a href="contact.php">Contact</a>
    </nav>
    
    <div class="container">
        <div class="section">
            <h2>Introduction to E-Voting</h2>
            <p>E-Voting is a secure and convenient way to participate in elections from anywhere. It ensures transparency, accessibility, and efficiency in the voting process.</p>
        </div>
        <div class="section">
            <h2>Advantages of E-Voting</h2>
            <p>E-Voting enhances security, accessibility, and transparency, reducing the risk of fraud and making voting more efficient for everyone.</p>
        </div>
        <div class="section">
            <h2>How to Vote Online</h2>
            <p>1. Register on the platform<br>2. Receive your secure voting credentials<br>3. Log in and cast your vote securely</p>
        </div>
        <div class="section">
            <h2>Security Measures</h2>
            <p>We implement end-to-end encryption, multi-factor authentication, and audit trails to ensure secure and fraud-proof voting.</p>
        </div>
        <div class="section">
            <h2>Frequently Asked Questions</h2>
            <p>Find answers to common queries about E-Voting security, eligibility, and functionality.</p>
        </div>
        <div class="section">
            <h2>Testimonials</h2>
            <p>See what users and organizations are saying about our trusted E-Voting platform.</p>
        </div>

        
        <div class="cta">
            <a href="verify_pin.php">Go to Voting Page</a>
        </div>
    </div>
    
    <footer>
        &copy; 2025 E-Voting System | Contact: support@evoting.com
    </footer>
</body>
</html>
