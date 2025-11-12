<?php
$password = "1234"; // Replace with the password you want to hash
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

echo "Hashed Password: " . $hashedPassword;
?>
