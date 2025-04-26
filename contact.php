

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - E-Voting</title>
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
        h2 {
            text-align: center;
            color: #0056b3;
        }
        .contact-info, .contact-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        .contact-info p {
            font-size: 16px;
            text-align: justify;
        }
        .contact-form form {
            display: flex;
            flex-direction: column;
        }
        .contact-form label {
            font-weight: bold;
            margin: 10px 0 5px;
        }
        .contact-form input, .contact-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        .contact-form textarea {
            height: 120px;
            resize: none;
        }
        .contact-form button {
            background: #0056b3;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 15px;
        }
        .contact-form button:hover {
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
        <h2>Contact Us</h2>

        <div class="contact-info">
            <h3>Our Office</h3>
            <p><strong>Address:</strong> 568 XX, LKO City, 2260xx</p>
            <p><strong>Email:</strong> support@evoting.com</p>
            <p><strong>Phone:</strong> +91-7388611853</p>
            <p>For any inquiries, support, or feedback, feel free to reach out to us. We value your input and are here to assist you.</p>
        </div>

        <div class="contact-form">
            <h3>Send Us a Message</h3>
            <form action="send_message.php" method="POST">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required>

                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>

                <label for="message">Message</label>
                <textarea id="message" name="message" required></textarea>

                <button type="submit">Submit</button>
            </form>
        </div>
    </div>

    <footer>
        &copy; 2025 E-Voting System. All Rights Reserved.
    </footer>
</body>
</html>
