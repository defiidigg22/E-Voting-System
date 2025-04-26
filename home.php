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
        .role-buttons {
            margin-top: 30px;
        }
        .role-button {
            background-color: #ff00ff;
            padding: 15px 30px;
            margin: 10px;
            border: none;
            border-radius: 5px;
            font-size: 20px;
            cursor: pointer;
            transition: transform 0.3s;
            color: white;
            text-decoration: none;
        }
        .role-button:hover {
            transform: scale(1.1);
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
        E-Voting System <a href="login.php" style="color: #fff; text-decoration: none;"></a>
    </header>
    
    <section class="hero">
        <h1>Secure, Transparent, and Fast E-Voting System</h1>
        <div class="role-buttons">
            <a href="index.php" class="role-button">Login as Voter</a>
            <a href="admin/admin_login.php" class="role-button">Login as Admin</a>
        </div>
    </section>

    <footer class="footer">
        &copy; 2025 E-Voting System. All rights reserved.
    </footer>
</body>
</html>

