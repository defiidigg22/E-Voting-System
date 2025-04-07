<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $message = trim($_POST["message"]);

    if (!empty($name) && !empty($email) && !empty($message)) {
        $to = "support@evoting.com"; 
        $subject = "New Contact Message from $name";
        $body = "Name: $name\nEmail: $email\n\nMessage:\n$message";

        $file = "messages.txt";
        $entry = "Name: $name\nEmail: $email\nMessage: $message\n---\n";
        file_put_contents($file, $entry, FILE_APPEND);

        echo "<script>alert('Message sent successfully!'); window.location.href='contact.php';</script>";
    } else {
        echo "<script>alert('Please fill in all fields.'); window.history.back();</script>";
    }
} else {
    header("Location: contact.php");
    exit();
}
?>
