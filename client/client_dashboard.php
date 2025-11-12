<?php
session_start();
include '../db.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

if (!isset($_SESSION['client_id'])) {
    header("Location: ../login.php");
    exit();
}

$client_id = $_SESSION['client_id'];

// Handle profile picture update
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
            $newFileName = 'profile_' . uniqid() . '.' . $fileExt;
            $uploadPath = '../uploads/' . $newFileName;
            move_uploaded_file($fileTmp, $uploadPath);

            $stmt = $conn->prepare("UPDATE clients SET profile_picture = ? WHERE id = ?");
            $stmt->bind_param("si", $uploadPath, $client_id);
            $stmt->execute();
            $stmt->close();

            header("Location: client_dashboard.php");
            exit();
        } else {
            echo "<script>alert('File too large or upload error.');</script>";
        }
    } else {
        echo "<script>alert('Invalid file type. Only JPG, PNG, and GIF are allowed.');</script>";
    }
}

// Fetch client info
$stmt = $conn->prepare("SELECT full_name, profile_picture FROM clients WHERE id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$stmt->bind_result($full_name, $profile_picture);
$stmt->fetch();
$stmt->close();

// Fetch appointments
$appointments = $conn->prepare("
    SELECT a.appointment_date, a.service_name, a.status, t.full_name AS therapist_name
    FROM appointments a
    JOIN therapists t ON a.therapist_id = t.login_id
    WHERE a.client_id = (SELECT login_id FROM clients WHERE id = ?)
    ORDER BY a.appointment_date DESC
");
$appointments->bind_param("i", $client_id);
$appointments->execute();
$appointment_results = $appointments->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Client Dashboard - GreenLife Wellness</title>
    <link rel="stylesheet" href="client_dashboard.css">
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <img src="<?= $profile_picture ? $profile_picture : '../uploads/default.png' ?>" alt="Profile Picture">
    <br>
    <!-- Profile Upload Buttons -->
    <form action="" method="post" enctype="multipart/form-data" class="upload-form profile-form">
        <label for="file-upload" class="custom-file-label">Change Image</label>
        <input type="file" name="new_profile_pic" id="file-upload" accept="image/*" required onchange="updateFileName()">
        <span id="file-name" class="file-name"></span>
        <input type="submit" name="update_pic" value="Upload" class="upload-btn">
    </form>
    <br>
    <a href="client_dashboard.php"><strong>Dashboard</strong></a>
    <a href="appointment.php">Book Appointment</a>
    <a href="https://dashboard.tawk.to/#/chat">Live Chat</a>
    <a href="../index.php">Logout</a>
</div>

<!-- Main Content -->
<div class="main-content">
    <h2>Welcome, <?= htmlspecialchars($full_name) ?>!</h2>

    <div class="appointments">
        <h3>Your Appointment History</h3>
        <table>
            <tr>
                <th>Date</th>
                <th>Service</th>
                <th>Therapist</th>
                <th>Status</th>
            </tr>
            <?php while ($row = $appointment_results->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['appointment_date']) ?></td>
                    <td><?= htmlspecialchars($row['service_name']) ?></td>
                    <td><?= htmlspecialchars($row['therapist_name']) ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

<!-- Show selected file name script -->
<script>
function updateFileName() {
    const fileInput = document.getElementById('file-upload');
    const fileNameSpan = document.getElementById('file-name');
    fileNameSpan.textContent = fileInput.files.length > 0 ? fileInput.files[0].name : '';
}
</script>

<!--Start of Tawk.to Script-->
<script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/68625c2749c75b19094a7a3d/1iv03fur9';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
<!--End of Tawk.to Script-->

</body>
</html>
