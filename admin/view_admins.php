<?php
session_start();
include '../db.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Delete admin
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM admins WHERE id = $id");
    header("Location: view_admins.php");
    exit();
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $update_id = intval($_POST['update_id']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $nic = trim($_POST['nic']);
    $address = trim($_POST['address']);

    $stmt = $conn->prepare("UPDATE admins SET full_name = ?, email = ?, phone = ?, nic = ?, address = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $full_name, $email, $phone, $nic, $address, $update_id);
    $stmt->execute();
    header("Location: view_admins.php");
    exit();
}

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    $stmt = $conn->prepare("SELECT * FROM admins WHERE full_name LIKE ? OR email LIKE ? OR login_id LIKE ? ORDER BY id DESC");
    $likeSearch = "%" . $search . "%";
    $stmt->bind_param("sss", $likeSearch, $likeSearch, $likeSearch);
    $stmt->execute();
    $admins = $stmt->get_result();
} else {
    $admins = $conn->query("SELECT * FROM admins ORDER BY id DESC");
}

$editing_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Admins - GreenLife Wellness</title>
    <link rel="stylesheet" href="view_admins.css">
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
    <a class="active" href="view_admins.php">View Admins</a>
     <a href="https://dashboard.tawk.to/#/chat">Live Chat</a>
    <a href="../index.php">Logout</a>
</div>

<div class="main-content">
    <h1>All Admins</h1>

    <form method="GET" style="margin-bottom: 20px; max-width: 400px;">
        <input type="text" name="search" placeholder="Search by name, email or login ID"
               value="<?= htmlspecialchars($search) ?>"
               style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Login ID</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>NIC</th>
            <th>Address</th>
            <th>Profile</th>
            <th>Actions</th>
        </tr>

        <?php while ($row = $admins->fetch_assoc()) { ?>
        <tr>
            <?php if ($editing_id === intval($row['id'])): ?>
                <form method="POST">
                    <td><?= $row['id'] ?><input type="hidden" name="update_id" value="<?= $row['id'] ?>"></td>
                    <td><?= htmlspecialchars($row['login_id']) ?></td>
                    <td><input type="text" name="full_name" value="<?= htmlspecialchars($row['full_name']) ?>" required></td>
                    <td><input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" required></td>
                    <td><input type="text" name="phone" value="<?= htmlspecialchars($row['phone']) ?>" required></td>
                    <td><input type="text" name="nic" value="<?= htmlspecialchars($row['nic']) ?>" required></td>
                    <td><input type="text" name="address" value="<?= htmlspecialchars($row['address']) ?>" required></td>
                    <td>
                        <?php if (!empty($row['profile_picture']) && file_exists("../" . $row['profile_picture'])): ?>
                            <img src="../<?= htmlspecialchars($row['profile_picture']) ?>" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <img src="../assets/default-user.png" alt="Default" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                        <?php endif; ?>
                    </td>
                    <td>
                        <button type="submit">💾 Save</button>
                        <a href="view_admins.php">✖ Cancel</a>
                    </td>
                </form>
            <?php else: ?>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['login_id']) ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars($row['nic']) ?></td>
                <td><?= htmlspecialchars($row['address']) ?></td>
                <td>
                    <?php if (!empty($row['profile_picture']) && file_exists("../" . $row['profile_picture'])): ?>
                        <img src="../<?= htmlspecialchars($row['profile_picture']) ?>" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <img src="../assets/default-user.png" alt="Default" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    <?php endif; ?>
                </td>
                <td>
                    <a href="?edit=<?= $row['id'] ?>">✏ Edit</a> |
                    <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this admin?')">🗑 Delete</a>
                </td>
            <?php endif; ?>
        </tr>
        <?php } ?>
    </table>
</div>

</body>
</html>
