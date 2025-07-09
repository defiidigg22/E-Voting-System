<?php
date_default_timezone_set('Asia/Kolkata');
define("RECAPTCHA_SITE_KEY", "6LfZqgErAAAAAP2FFT_Y-fOar5wNQPDb2oFNg8lj");
define("RECAPTCHA_SECRET", "6LfZqgErAAAAAF9Km5PflgKNkx6WUdV2DEGxykRJ");
define("VOTING_END_DATE", "2025-04-30 23:59:59");
?>
<?php
$host = 'localhost:3306';
$db = 'evoting';
$user = 'root';
$pass = ''; // Replace with your MySQL password if set

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


?>
