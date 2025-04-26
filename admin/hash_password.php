<?php
$passwordToHash = 'Jao@123'; // <-- Choose a strong password!
$hashedPassword = password_hash($passwordToHash, PASSWORD_DEFAULT);
echo 'Username: Bandar<br>'; // <-- Choose an admin username
echo 'Password Hash: ' . $hashedPassword;
?>