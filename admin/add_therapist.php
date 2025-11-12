<?php
session_start();
include '../db.php';

// Logout handler
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Variables
$create_msg = "";
$error_msg = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_therapist'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $nic = trim($_POST['NIC']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if (empty($full_name) || empty($email) || empty($password) || empty($nic) || empty($phone) || empty($address)) {
        $create_msg = "<p class='message error'>All fields are required.</p>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $create_msg = "<p class='message error'>Invalid email format.</p>";
    } elseif (strlen($password) < 8) {
        $create_msg = "<p class='message error'>Password must be at least 8 characters long.</p>";
    } else {
        $stmt = $conn->prepare("SELECT id FROM therapists WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $create_msg = "<p class='message error'>Email already exists.</p>";
        } else {
            $result = $conn->query("SELECT login_id FROM therapists ORDER BY id DESC LIMIT 1");
            $last_login_id = '';
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $last_login_id = $row['login_id'];
            }

            if ($last_login_id) {
                preg_match('/TH(\d+)/', $last_login_id, $matches);
                $new_login_id = 'TH' . str_pad((int)$matches[1] + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $new_login_id = 'TH001';
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO therapists (login_id, full_name, email, phone, nic, address, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $new_login_id, $full_name, $email, $phone, $nic, $address, $hashed_password);

            if ($stmt->execute()) {
                $create_msg = "<p class='message success'>✅ Therapist created successfully! Login ID: <strong>$new_login_id</strong></p>";
            } else {
                $create_msg = "<p class='message error'>Error: " . $stmt->error . "</p>";
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register Therapist - GreenLife Wellness</title>
    <link rel="stylesheet" href="add_therapist.css">
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="add_therapist.php">Register Therapist</a>
    <a href="view_therapist.php">View Therapists</a>
    <a href="view_clients.php">View Users</a>
    <a href="view_appointments.php">View Appointments</a>
    <a href="add_new_admin.php">Add new Admin</a>
    <a href="view_admins.php">View Admins</a>
    <a href="https://dashboard.tawk.to/#/chat">Live Chat</a>
    <a href="index.php">Logout</a>
</div>

<div class="main-content">
    <h2>Register Therapist</h2>

    <div class="form-wrapper">
        <!-- ✅ Place message here inside the box -->
        <?= $create_msg ?>

        <form method="post">
            <label>Full Name:</label>
            <input type="text" name="full_name" required>

            <label>Email:</label>
            <input type="email" name="email" required>

            <label>Password:</label>
            <input type="password" name="password" required>

            <label>NIC:</label>
            <input type="text" name="NIC" required>

            <label>Phone:</label>
            <input type="text" name="phone" required>

            <label>Address:</label>
            <input type="text" name="address" required>

            <input type="submit" name="create_therapist" value="Create Therapist">
        </form>
    </div>
</div>

</body>
</html>
