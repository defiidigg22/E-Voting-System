<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>How It Works - E-Voting</title>
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
        .steps {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .step {
            display: flex;
            align-items: center;
            background: #ffffff;
            padding: 20px;
            margin: 10px 0;
            width: 80%;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .step img {
            width: 50px;
            height: 50px;
            margin-right: 15px;
        }

        .cta {
            display: flex;
            justify-content: center; /* Centers the button */
            margin-top: 30px;
        }
        .cta a {
            background: #0056b3;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            text-decoration: none;
            transition: 0.3s;
        }
        .cta a:hover {
            background: #003d80;
        }
        /* .vote-button {
            display: inline-block;
            justify-content: center;
            align-items: center;
            background: #0056b3;
            color: white;
            padding: 12px 20px;
            border-radius: 5px;
            font-size: 18px;
            text-decoration: none;
            transition: 0.3s;
        }
        .vote-button:hover {
            background: #003d80; */
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
        <h2>How E-Voting Works</h2>
        <div class="steps">
            <div class="step">
                <img src="https://cdn-icons-png.flaticon.com/512/6799/6799093.png" alt="Register">
                <p><strong>Step 1: Register</strong> - Sign up on the platform using a verified identity to access the voting system.</p>
            </div>
            <div class="step">
                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR_nFqg830P_VriGxOXvv4QbA13rkzVC8YwRA&s" alt="Login">
                <p><strong>Step 2: Secure Login</strong> - Use multi-factor authentication (MFA) to log in securely.</p>
            </div>
            <div class="step">
                <img src="https://media.istockphoto.com/id/2065215431/vector/ballot-box-voting-icon-hand-casting-vote-in-election-process-symbol-for-democratic.jpg?s=612x612&w=0&k=20&c=nxLUvmcuKnNuQ4xe9Js22wu6pCof0qFUh7-0SkW0Sec=" alt="Cast Vote">
                <p><strong>Step 3: Cast Your Vote</strong> - Select your preferred candidate and confirm your vote.</p>
            </div>
            <div class="step">
                <img src="https://cdn-icons-png.flaticon.com/512/3751/3751703.png" alt="Encryption">
                <p><strong>Step 4: Vote Encryption</strong> - Your vote is encrypted and stored securely to ensure anonymity.</p>
            </div>
            <div class="step">
                <img src="https://cdn2.iconfinder.com/data/icons/us-election-2020/60/003-vote-counting-512.png" alt="Counting">
                <p><strong>Step 5: Secure Counting</strong> - Votes are counted securely, ensuring transparency and accuracy.</p>
            </div>
            <div class="step">
                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQmdElHTpAg7OvA0FWBtEl-PbwdIgiAcn66Aw&s" alt="Results">
                <p><strong>Step 6: Results Declaration</strong> - Results are published in real-time with complete transparency.</p>
            </div>
        </div>

        <div class="cta">
            <a href="vote.php" class="vote-button">Vote Now</a>
        </div>
    </div>

    <footer>
        &copy; 2025 E-Voting System. All Rights Reserved.
    </footer>
</body>
</html>
