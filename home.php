<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Voting System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #0a0a0a;
            color: #fff;
            text-align: center;
            overflow-x: hidden;
        }
        header {
            background: linear-gradient(90deg, #ff00ff, #6600ff);
            padding: 20px;
            font-size: 24px;
            font-weight: bold;
        }
        .hero {
            position: relative;
            height: 80vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: url('banner.jpg') no-repeat center center/cover;
            animation: fadeIn 2s;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .vote-button {
            background-color: #ff00ff;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 20px;
            cursor: pointer;
            transition: transform 0.3s;
            color: white;
            text-decoration: none;
        }
        .vote-button:hover {
            transform: scale(1.1);
        }
        .countdown {
            font-size: 24px;
            margin-top: 20px;
        }
        .footer {
            background: #222;
            padding: 20px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <header>
        E-Voting System | <a href="login.php" style="color: #fff; text-decoration: none;">Login/Register</a>
    </header>
    
    <section class="hero">
        <h1>Secure, Transparent, and Fast E-Voting System</h1>
        <a href="verify_pin.php" class="vote-button">Vote Now</a>
    </section>
    
    <section class="countdown">
        Election starts in: <span id="timer">Loading...</span>
    </section>
    
    <script>
        function countdown() {
            let countDownDate = new Date("Dec 31, 2025 00:00:00").getTime();
            let x = setInterval(function() {
                let now = new Date().getTime();
                let distance = countDownDate - now;
                let days = Math.floor(distance / (1000 * 60 * 60 * 24));
                let hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                let seconds = Math.floor((distance % (1000 * 60)) / 1000);
                document.getElementById("timer").innerHTML = days + "d " + hours + "h " + minutes + "m " + seconds + "s ";
                if (distance < 0) {
                    clearInterval(x);
                    document.getElementById("timer").innerHTML = "Voting Started!";
                }
            }, 1000);
        }
        countdown();
    </script>
    
    <footer class="footer">
        &copy; 2025 E-Voting System. All rights reserved.
    </footer>
</body>
</html>
