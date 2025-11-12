<?php
// 🌿 DATABASE CONNECTION for GreenLife Wellness

$servername = "localhost";     // XAMPP default
$username   = "root";          // Default username
$password   = "";              // Leave empty for XAMPP
$database   = "greenlifewellness";  // Your database name

// 🌿 Create connection
$conn = new mysqli($servername, $username, $password, $database);

// 🌿 Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// echo "✅ Connected successfully"; // (Optional: enable for testing only)
?>
