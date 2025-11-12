<?php
session_start();
include '../db.php';

$success = "";
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

// Add or Edit Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = trim($_POST['client_id']);
    $therapist_id = trim($_POST['therapist_id']);
    $service_name = trim($_POST['service_name']);
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $status = $_POST['status'];

    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $stmt = $conn->prepare("UPDATE appointments SET client_id=?, therapist_id=?, service_name=?, appointment_date=?, appointment_time=?, status=? WHERE id=?");
        $stmt->bind_param("ssssssi", $client_id, $therapist_id, $service_name, $appointment_date, $appointment_time, $status, $_GET['id']);
        $stmt->execute();
        $success = "Appointment updated successfully!";
    } else {
        $stmt = $conn->prepare("INSERT INTO appointments (client_id, therapist_id, service_name, appointment_date, appointment_time, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssss", $client_id, $therapist_id, $service_name, $appointment_date, $appointment_time, $status);
        $stmt->execute();
        $success = "Appointment added successfully!";
    }
}

// Delete Handler
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $stmt = $conn->prepare("DELETE FROM appointments WHERE id=?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $success = "Appointment deleted successfully!";
}

// Fetch appointment data
$query = "SELECT a.*, c.full_name AS client_name, t.full_name AS therapist_name, s.service_name
          FROM appointments a
          LEFT JOIN clients c ON a.client_id = c.login_id
          LEFT JOIN therapists t ON a.therapist_id = t.login_id
          JOIN services s ON a.service_name = s.service_name";

if (!empty($search)) {
    $escapedSearch = $conn->real_escape_string($search);
    $query .= " WHERE 
        c.full_name LIKE '%$escapedSearch%' OR 
        t.full_name LIKE '%$escapedSearch%' OR 
        a.client_id LIKE '%$escapedSearch%' OR 
        a.therapist_id LIKE '%$escapedSearch%' OR 
        a.status LIKE '%$escapedSearch%' OR 
        s.service_name LIKE '%$escapedSearch%'";
}

$query .= " ORDER BY a.appointment_date DESC";
$result = $conn->query($query);

// Fetch dropdown data
$clients = $conn->query("SELECT login_id, full_name FROM clients");
$therapists = $conn->query("SELECT login_id, full_name FROM therapists");
$services = $conn->query("SELECT service_name FROM services");

$editData = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM appointments WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - View Appointments</title>
    <link rel="stylesheet" href="admin_dashboard.css">
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="add_therapist.php">Register Therapist</a>
    <a href="view_therapist.php">View Therapists</a>
    <a href="view_clients.php">View Users</a>
    <a href="view_appointments.php" class="active">View Appointments</a>
    <a href="add_new_admin.php">Add new Admin</a>
    <a href="view_admins.php">View Admins</a>
     <a href="https://dashboard.tawk.to/#/chat">Live Chat</a>
    <a href="../index.php">Logout</a>
</div>

<div class="main-content">
    <div class="header">Appointments Overview</div>

    <?php if ($success): ?>
        <div class="message success"><?= $success ?></div>
    <?php endif; ?>

    <form method="GET" style="margin-bottom: 20px; max-width: 400px;">
        <input type="text" name="search" placeholder="Search by name, email or login ID"
               value="<?= htmlspecialchars($search) ?>"
               style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ccc;">
    </form>

    <div class="table-container">
        <table id="appointmentsTable">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Therapist</th>
                    <th>Service</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['client_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['therapist_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['service_name']) ?></td>
                    <td><?= htmlspecialchars($row['appointment_date']) ?></td>
                    <td><?= htmlspecialchars($row['appointment_time']) ?></td>
                    <td><span class="status <?= strtolower($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                    <td>
                        <a href="?action=edit&id=<?= $row['id'] ?>" class="action-btn">Edit</a>
                        <a href="?action=delete&id=<?= $row['id'] ?>" onclick="return confirm('Delete this appointment?')" class="action-btn danger">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php
    // Reset pointers before dropdowns
    $clients->data_seek(0);
    $therapists->data_seek(0);
    $services->data_seek(0);
    ?>

<div class="form-wrapper" style="margin-top: 40px;">
        <div class="form-section">
            <form method="post">
                <label>Client:</label>
                <select name="client_id" required>
                    <option value="">-- Select Client --</option>
                    <?php while ($row = $clients->fetch_assoc()): ?>
                        <option value="<?= $row['login_id'] ?>" <?= (isset($editData) && $editData['client_id'] == $row['login_id']) ? 'selected' : '' ?>>
                            <?= $row['full_name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label>Therapist:</label>
                <select name="therapist_id" required>
                    <option value="">-- Select Therapist --</option>
                    <?php while ($row = $therapists->fetch_assoc()): ?>
                        <option value="<?= $row['login_id'] ?>" <?= (isset($editData) && $editData['therapist_id'] == $row['login_id']) ? 'selected' : '' ?>>
                            <?= $row['full_name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label>Service:</label>
                <select name="service_name" required>
                    <option value="">-- Select Service --</option>
                    <?php while ($row = $services->fetch_assoc()): ?>
                        <option value="<?= $row['service_name'] ?>" <?= (isset($editData) && $editData['service_name'] == $row['service_name']) ? 'selected' : '' ?>>
                            <?= $row['service_name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label>Date:</label>
                <input type="date" name="appointment_date" value="<?= $editData['appointment_date'] ?? '' ?>" required>

                <label>Time:</label>
                <input type="time" name="appointment_time" value="<?= $editData['appointment_time'] ?? '' ?>" required>

                <label>Status:</label>
                <select name="status" required>
                    <option value="Pending" <?= (isset($editData) && $editData['status'] == 'Pending') ? 'selected' : '' ?>>Pending</option>
                    <option value="Confirmed" <?= (isset($editData) && $editData['status'] == 'Confirmed') ? 'selected' : '' ?>>Confirmed</option>
                    <option value="Cancelled" <?= (isset($editData) && $editData['status'] == 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                </select>

                <button type="submit"><?= isset($editData) ? 'Update Appointment' : 'Add Appointment' ?></button>
            </form>
        </div>
    </div>
</div>

<script>
function searchTable() {
    const input = document.getElementById("searchInput")?.value.toLowerCase() || "";
    const rows = document.querySelectorAll("#appointmentsTable tbody tr");
    rows.forEach(row => {
        const cells = row.querySelectorAll("td");
        let match = false;
        cells.forEach(cell => {
            if (cell.textContent.toLowerCase().includes(input)) {
                match = true;
            }
        });
        row.style.display = match ? "" : "none";
    });
}
</script>

</body>
</html>
