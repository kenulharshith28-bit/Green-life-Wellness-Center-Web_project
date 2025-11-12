<?php
session_start();
include '../db.php';

$create_msg = "";

// Auto-generate admin login ID (e.g., AD001, AD002...)
function generateAdminLoginID($conn) {
    $prefix = "AD";
    $result = $conn->query("SELECT login_id FROM admins WHERE login_id LIKE '$prefix%' ORDER BY id DESC LIMIT 1");
    if ($row = $result->fetch_assoc()) {
        $last = intval(substr($row['login_id'], 2));
        $new = $last + 1;
    } else {
        $new = 1;
    }
    return $prefix . str_pad($new, 3, "0", STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    $login_id = generateAdminLoginID($conn); // auto-generated
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $nic = trim($_POST['nic']);
    $address = trim($_POST['address']);
    $password = $_POST['password'];

    // Profile picture upload
    $profile_picture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $allowed)) {
            $profile_picture = 'uploads/' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['profile_picture']['tmp_name'], '../' . $profile_picture);
        }
    }

    // Validation
    if (empty($full_name) || empty($email) || empty($password)) {
        $create_msg = "<p class='message error'>Full Name, Email, and Password are required.</p>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $create_msg = "<p class='message error'>Invalid email format.</p>";
    } elseif (strlen($password) < 8) {
        $create_msg = "<p class='message error'>Password must be at least 8 characters long.</p>";
    } else {
        // Check existing
        $check = $conn->prepare("SELECT id FROM admins WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $create_msg = "<p class='message error'>An admin with this email already exists.</p>";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO admins (login_id, full_name, email, phone, nic, address, password, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $login_id, $full_name, $email, $phone, $nic, $address, $hashed, $profile_picture);
            $stmt->execute();
            $create_msg = "<p class='message success'>Admin account created successfully! Login ID: <strong>$login_id</strong></p>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add New Admin - GreenLife Wellness</title>
    <link rel="stylesheet" href="add_new_admin.css">
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="add_therapist.php">Register Therapist</a>
    <a href="view_therapist.php">View Therapists</a>
    <a href="view_clients.php">View Users</a>
    <a href="view_appointments.php">View Appointments</a>
    <a class="active" href="add_new_admin.php">Add new Admin</a>
    <a href="view_admins.php">View Admins</a>
     <a href="https://dashboard.tawk.to/#/chat">Live Chat</a>
    <a href="../index.php">Logout</a>
</div>

<div class="main-content">
    <h2 style="text-align: center; color: #05668d; margin-bottom: 25px;">Add New Admin</h2>

    <div class="form-wrapper">
        <?= $create_msg ?>
        <form method="POST" enctype="multipart/form-data">
            <!-- Login ID is now auto-generated and NOT shown as input -->

            <label>Full Name:</label>
            <input type="text" name="full_name" required>

            <label>Email:</label>
            <input type="email" name="email" required>

            <label>Phone:</label>
            <input type="text" name="phone">

            <label>NIC:</label>
            <input type="text" name="nic">

            <label>Address:</label>
            <input type="text" name="address">

            <label>Password:</label>
            <input type="password" name="password" required>

            <label>Profile Picture:</label>
            <input type="file" name="profile_picture" accept="image/*">

            <input type="submit" name="create_admin" value="Create Admin">
        </form>
    </div>
</div>

</body>
</html>
