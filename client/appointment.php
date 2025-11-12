<?php
session_start();
include '../db.php';

if (!isset($_SESSION['client_id'])) {
    header("Location: ../login.php");
    exit();
}

$client_id = $_SESSION['client_id'];
$success_msg = $error_msg = "";

// Fetch client login_id and profile info
$stmt = $conn->prepare("SELECT login_id, full_name, profile_picture FROM clients WHERE id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$stmt->bind_result($client_login_id, $full_name, $profile_picture);
$stmt->fetch();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $therapist_id = $_POST['therapist_id'];
    $service_name = $_POST['service_name'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];

    if ($therapist_id && $service_name && $appointment_date && $appointment_time) {
        $stmt = $conn->prepare("INSERT INTO appointments (client_id, therapist_id, service_name, appointment_date, appointment_time, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("sssss", $client_login_id, $therapist_id, $service_name, $appointment_date, $appointment_time);
        if ($stmt->execute()) {
            $success_msg = "Appointment booked successfully!";
        } else {
            $error_msg = "Something went wrong. Please try again.";
        }
        $stmt->close();
    } else {
        $error_msg = "Please fill out all fields.";
    }
}

// Fetch therapists
$therapists = $conn->query("SELECT login_id, full_name FROM therapists");

// Fetch services
$services = $conn->query("SELECT service_name FROM services");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Appointment - GreenLife Wellness</title>
    <link rel="stylesheet" href="client_dashboard.css">
    <style>
        .appointment-wrapper {
            background: white;
            padding: 30px;
            max-width: 700px;
            margin: 20px auto;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .appointment-wrapper h2 {
            text-align: center;
            color: #05668d;
            margin-bottom: 20px;
        }

        .appointment-wrapper label {
            display: block;
            margin-top: 15px;
            font-weight: 500;
        }

        .appointment-wrapper input,
        .appointment-wrapper select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .appointment-wrapper button {
            margin-top: 25px;
            width: 100%;
            background-color: #05668d;
            color: white;
            border: none;
            padding: 12px;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
        }

        .appointment-wrapper button:hover {
            background-color: #028090;
        }

        .message {
            text-align: center;
            font-weight: bold;
            margin-top: 15px;
        }

        .success {
            color: green;
        }

        .error {
            color: red;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <img src="<?= $profile_picture ? $profile_picture : '../uploads/default.png' ?>" alt="Profile Picture">
    <h2><?= htmlspecialchars($full_name) ?></h2>

    <form action="" method="post" enctype="multipart/form-data" class="upload-form">
        <label for="file-upload" class="custom-file-label">Choose New Picture</label>
        <input type="file" name="new_profile_pic" id="file-upload" accept="image/*" disabled>
        <input type="submit" value="Change Picture" class="upload-btn" disabled>
    </form>

    <a href="client_dashboard.php">Dashboard</a>
    <a href="appointment.php">Book Appointment</a>
    <a href="https://dashboard.tawk.to/#/chat">Live Chat</a>
    <a href="../index.php">Logout</a>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="appointment-wrapper">
        <h2>Book an Appointment</h2>

        <?php if ($success_msg): ?>
            <div class="message success"><?= $success_msg ?></div>
        <?php elseif ($error_msg): ?>
            <div class="message error"><?= $error_msg ?></div>
        <?php endif; ?>

        <form action="appointment.php" method="POST">
            <label for="service_name">Select Service</label>
            <select name="service_name" required>
                <option value="">-- Select a Service --</option>
                <?php while ($service = $services->fetch_assoc()): ?>
                    <option value="<?= $service['service_name'] ?>"><?= $service['service_name'] ?></option>
                <?php endwhile; ?>
            </select>

            <label for="therapist_id">Select Therapist</label>
            <select name="therapist_id" required>
                <option value="">-- Select a Therapist --</option>
                <?php while ($therapist = $therapists->fetch_assoc()): ?>
                    <option value="<?= $therapist['login_id'] ?>"><?= $therapist['full_name'] ?></option>
                <?php endwhile; ?>
            </select>

            <label for="appointment_date">Select Date</label>
            <input type="date" name="appointment_date" min="<?= date('Y-m-d') ?>" required>

            <label for="appointment_time">Select Time</label>
            <input type="time" name="appointment_time" required>

            <button type="submit">Book Appointment</button>
        </form>
    </div>
</div>

</body>
</html>
