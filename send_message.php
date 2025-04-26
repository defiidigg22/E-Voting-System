<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $message = trim($_POST["message"]);

    if (!empty($name) && !empty($email) && !empty($message)) {
        $to = "support@evoting.com"; 
        $subject = "New Contact Message from $name";
        $body = "Name: $name\nEmail: $email\n\nMessage:\n$message";


        echo "<script>alert('Message sent successfully!'); window.location.href='contact.php';</script>";
    } else {
        echo "<script>alert('Please fill in all fields.'); window.history.back();</script>";
    }
} else {
    header("Location: contact.php");
    exit();
}
?>
<?php
include 'config.php'; // this should contain your DB connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    if (!empty($name) && !empty($email) && !empty($message)) {
        // Basic validation passed, now insert into DB
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $message);

        if ($stmt->execute()) {
            echo "<script>alert('Message sent successfully!'); window.location.href='contact.php';</script>";
        } else {
            echo "<script>alert('Something went wrong. Please try again later.'); window.location.href='contact.php';</script>";
        }

        $stmt->close();
    } else {
        echo "<script>alert('All fields are required.'); window.location.href='contact.php';</script>";
    }

    $conn->close();
} else {
    header("Location: contact.php");
    exit();
}
?>