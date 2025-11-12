<?php
session_start();
include '../db.php';

// Therapist login check
if (!isset($_SESSION['therapist_id'])) {
    header("Location: login.php");
    exit();
}

// Logout handler
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$therapist_id = $_SESSION['therapist_id'];

// Fetch therapist profile picture and name
$stmt = $conn->prepare("SELECT full_name, profile_picture FROM therapists WHERE id = ?");
$stmt->bind_param("i", $therapist_id);
$stmt->execute();
$stmt->bind_result($full_name, $profile_pic);
$stmt->fetch();
$stmt->close();

// Handle profile picture update
if (isset($_POST['update_pic']) && isset($_FILES['new_profile_picture'])) {
    $file = $_FILES['new_profile_picture'];
    $fileName = $file['name'];
    $fileTmp = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png'];

    if (in_array($fileExt, $allowed)) {
        if ($fileError === 0 && $fileSize < 5 * 1024 * 1024) {
            $newFileName = 'profile_' . uniqid() . '.' . $fileExt;
            $uploadPath = 'uploads/' . $newFileName;
            move_uploaded_file($fileTmp, '../' . $uploadPath);

            $stmt = $conn->prepare("UPDATE therapists SET profile_picture = ? WHERE id = ?");
            $stmt->bind_param("si", $uploadPath, $therapist_id);
            $stmt->execute();
            $stmt->close();

            header("Location: therapist_dashboard.php");
            exit();
        } else {
            echo "<script>alert('File too large or upload error.');</script>";
        }
    } else {
        echo "<script>alert('Only JPG and PNG files are allowed.');</script>";
    }
}

// Handle appointment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'], $_POST['action'])) {
    $appointment_id = $_POST['appointment_id'];
    $action = $_POST['action'];

    if (in_array($action, ['Confirmed', 'Cancelled'])) {
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ? AND therapist_id = (SELECT login_id FROM therapists WHERE id = ?)");
        $stmt->bind_param("sii", $action, $appointment_id, $therapist_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Get upcoming appointments
$appointments = $conn->prepare("
    SELECT a.id, a.appointment_date, a.appointment_time, a.service_name, a.status, c.full_name AS client_name
    FROM appointments a
    JOIN clients c ON a.client_id = c.login_id
    WHERE a.therapist_id = (SELECT login_id FROM therapists WHERE id = ?)
      AND a.appointment_date >= CURDATE()
    ORDER BY a.appointment_date, a.appointment_time
");
$appointments->bind_param("i", $therapist_id);
$appointments->execute();
$appointment_results = $appointments->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Therapist Dashboard</title>
    <link rel="stylesheet" href="therapist_dashboard.css">
</head>
<body>

<div class="sidebar">
    <img src="<?= !empty($profile_pic) ? '../' . $profile_pic : '../uploads/default.png' ?>" alt="Profile Picture">
    <h2>Dr.<?= htmlspecialchars($full_name) ?></h2>
    <br>

    <!-- Profile Picture Upload Form -->
    <form action="" method="post" enctype="multipart/form-data" class="upload-form profile-form">
        <label for="file-upload" class="custom-file-label">Change Image</label>
        <input type="file" name="new_profile_picture" id="file-upload" accept="image/*" required onchange="updateFileName()">
        <span id="file-name" class="file-name"></span>
        <input type="submit" name="update_pic" value="Upload" class="upload-btn">
    </form>

    <a href="therapist_dashboard.php">Dashboard</a>
    <a href="https://dashboard.tawk.to/#/chat">Live Chat</a>
    <a href="../index.php">Logout</a>
</div>

<div class="main-content">
    <div class="header">Upcoming Appointments</div>

    <div class="appointment-box">
        <table>
            <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Service</th>
                <th>Client</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php while ($row = $appointment_results->fetch_assoc()) { ?>
                <tr>
                    <td><?= htmlspecialchars($row['appointment_date']) ?></td>
                    <td><?= htmlspecialchars($row['appointment_time']) ?></td>
                    <td><?= htmlspecialchars($row['service_name']) ?></td>
                    <td><?= htmlspecialchars($row['client_name']) ?></td>
                    <td><span class="status <?= strtolower($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                    <td>
                        <?php if ($row['status'] === 'Pending') { ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="action" value="Confirmed" class="confirm-btn">Confirm</button>
                            </form>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="action" value="Cancelled" class="cancel-btn">Cancel</button>
                            </form>
                        <?php } else {
                            echo "-";
                        } ?>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

<script>
function updateFileName() {
    const fileInput = document.getElementById('file-upload');
    const fileNameSpan = document.getElementById('file-name');
    fileNameSpan.textContent = fileInput.files.length > 0 ? fileInput.files[0].name : '';
}
</script>

</body>
</html>
