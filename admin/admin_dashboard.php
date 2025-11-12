<?php
session_start();
include '../db.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

// Admin ID (manually using ID 2 from your DB dump)
$admin_id = 2;

// Handle profile picture upload
if (isset($_POST['update_pic']) && isset($_FILES['new_profile_pic'])) {
    $file = $_FILES['new_profile_pic'];
    $fileName = $file['name'];
    $fileTmp = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($fileExt, $allowed)) {
        if ($fileError === 0 && $fileSize < 5 * 1024 * 1024) {
            $newFileName = 'admin_' . uniqid() . '.' . $fileExt;
            $uploadPath = 'uploads/' . $newFileName;
            move_uploaded_file($fileTmp, '../' . $uploadPath);

            $stmt = $conn->prepare("UPDATE admins SET profile_picture = ? WHERE id = ?");
            $stmt->bind_param("si", $uploadPath, $admin_id);
            $stmt->execute();
            $stmt->close();

            header("Location: admin_dashboard.php");
            exit();
        } else {
            echo "<script>alert('File too large or upload error.');</script>";
        }
    } else {
        echo "<script>alert('Invalid file type. Only JPG, PNG, GIF allowed.');</script>";
    }
}

// Fetch admin profile picture and name
$stmt = $conn->prepare("SELECT profile_picture, full_name FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($admin_image, $admin_name);
$stmt->fetch();
$stmt->close();

if (!$admin_image) {
    $admin_image = 'uploads/admin_profile.png';
}

// Fetch counts
$total_clients = $conn->query("SELECT COUNT(*) AS count FROM clients")->fetch_assoc()['count'];
$total_therapists = $conn->query("SELECT COUNT(*) AS count FROM therapists")->fetch_assoc()['count'];
$total_appointments = $conn->query("SELECT COUNT(*) AS count FROM appointments")->fetch_assoc()['count'];

// Fetch feedback messages
$feedbacks = $conn->query("SELECT * FROM contacts ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - GreenLife Wellness</title>
    <link rel="stylesheet" href="admin_dashboard.css">
</head>
<body>

<div class="sidebar">
    <img src="<?= '../' . htmlspecialchars($admin_image) ?>" alt="Admin Profile" class="admin-profile-pic">

    
        <form action="" method="post" enctype="multipart/form-data" class="upload-form profile-form">
        <br>
        <label for="file-upload" class="custom-file-label">Change Image</label>
        <input type="file" name="new_profile_pic" id="file-upload" accept="image/*" required onchange="updateFileName()">
        <small id="file-name" class="file-name"></small>
        <input type="submit" name="update_pic" value="Upload" class="upload-btn">
    </form>
    <br>
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="add_therapist.php">Register Therapist</a>
    <a href="view_therapist.php">View Therapists</a>
    <a href="view_clients.php">View Users</a>
    <a href="view_appointments.php">View Appointments</a>
    <a href="add_new_admin.php">Add new Admin</a>
    <a href="view_admins.php">View Admins</a>
     <a href="https://dashboard.tawk.to/#/chat">Live Chat</a>
    <a href="../index.php">Logout</a>
</div>

<div class="main-content" id="feedbacks">
    <div class="header">Welcome, <?= htmlspecialchars($admin_name) ?>!</div>

    <div class="summary-section">
        <h3 class="summary-title">Total Overview</h3>
        <div class="summary-boxes">
            <div class="summary-box">
                <h4>Total Clients</h4>
                <p><?= $total_clients ?></p>
            </div>
            <div class="summary-box">
                <h4>Total Therapists</h4>
                <p><?= $total_therapists ?></p>
            </div>
            <div class="summary-box">
                <h4>Total Appointments</h4>
                <p><?= $total_appointments ?></p>
            </div>
        </div>
    </div>

    <h2>Customer Feedback</h2>
    <table>
        <tr>
            <th>ID</th><th>Name</th><th>Email</th><th>Message</th><th>Date</th>
        </tr>
        <?php while ($row = $feedbacks->fetch_assoc()) { ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['message']) ?></td>
                <td><?= htmlspecialchars($row['created_at']) ?></td>
            </tr>
        <?php } ?>
    </table>
</div>

<script>
function updateFileName() {
    const input = document.getElementById('file-upload');
    const label = document.getElementById('file-name');
    if (input.files.length > 0) {
        label.textContent = input.files[0].name;
    }
}
</script>

</body>
</html>
