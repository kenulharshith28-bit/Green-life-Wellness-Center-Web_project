<?php
session_start();
include '../db.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Delete user
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM clients WHERE id = $id");
    header("Location: view_clients.php");
    exit();
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $update_id = intval($_POST['update_id']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    $stmt = $conn->prepare("UPDATE clients SET full_name = ?, email = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("sssi", $full_name, $email, $phone, $update_id);
    $stmt->execute();
    header("Location: view_clients.php");
    exit();
}

// Handle add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $raw_password = $_POST['password'];

    // Auto-generate login ID
    $res = $conn->query("SELECT login_id FROM clients ORDER BY id DESC LIMIT 1");
    $last_id = $res->num_rows > 0 ? intval(substr($res->fetch_assoc()['login_id'], 2)) : 0;
    $new_login_id = 'CL' . str_pad($last_id + 1, 3, '0', STR_PAD_LEFT);

    // Hash password
    $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO clients (login_id, full_name, email, phone, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $new_login_id, $full_name, $email, $phone, $hashed_password);
    $stmt->execute();
    header("Location: view_clients.php");
    exit();
}

// Search filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    $stmt = $conn->prepare("SELECT * FROM clients WHERE full_name LIKE ? OR email LIKE ? OR login_id LIKE ? ORDER BY id DESC");
    $likeSearch = "%" . $search . "%";
    $stmt->bind_param("sss", $likeSearch, $likeSearch, $likeSearch);
    $stmt->execute();
    $clients = $stmt->get_result();
} else {
    $clients = $conn->query("SELECT * FROM clients ORDER BY id DESC");
}

$editing_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Users - GreenLife Wellness</title>
    <link rel="stylesheet" href="view_therapist.css">
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="add_therapist.php">Register Therapist</a>
    <a href="view_therapist.php">View Therapists</a>
    <a href="view_clients.php" class="active">View Users</a>
    <a href="view_appointments.php">View Appointments</a>
    <a href="add_new_admin.php">Add new Admin</a>
    <a href="view_admins.php">View Admins</a>
    <a href="https://dashboard.tawk.to/#/chat">Live Chat</a>
    <a href="../index.php">Logout</a>
</div>

<div class="main-content">
    <h1>All Registered Users</h1>

    <form method="GET" style="margin-bottom: 20px; max-width: 400px;">
        <input type="text" name="search" placeholder="Search by name, email or login ID"
               value="<?= htmlspecialchars($search) ?>"
               style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
    </form>

    <form method="POST" style="margin-bottom: 30px; display: flex; gap: 10px; flex-wrap: wrap;">
        <input type="hidden" name="add_user" value="1">
        <input type="text" name="full_name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="phone" placeholder="Phone" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">➕ Add User</button>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Login ID</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Profile</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $clients->fetch_assoc()) { ?>
        <tr>
            <?php if ($editing_id === intval($row['id'])): ?>
                <form method="POST">
                    <td><?= $row['id'] ?><input type="hidden" name="update_id" value="<?= $row['id'] ?>"></td>
                    <td><?= htmlspecialchars($row['login_id']) ?></td>
                    <td><input type="text" name="full_name" value="<?= htmlspecialchars($row['full_name']) ?>" required></td>
                    <td><input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" required></td>
                    <td><input type="text" name="phone" value="<?= htmlspecialchars($row['phone']) ?>" required></td>
                    <td>
                        <?php if (!empty($row['profile_picture']) && file_exists("../uploads/" . $row['profile_picture'])): ?>
                            <img src="../uploads/<?= htmlspecialchars($row['profile_picture']) ?>" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <img src="../assets/default-user.png" alt="Default" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                        <?php endif; ?>
                    </td>
                    <td>
                        <button type="submit">💾 Save</button>
                        <a href="view_clients.php">✖ Cancel</a>
                    </td>
                </form>
            <?php else: ?>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['login_id']) ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td>
                    <?php if (!empty($row['profile_picture']) && file_exists("../uploads/" . $row['profile_picture'])): ?>
                        <img src="../uploads/<?= htmlspecialchars($row['profile_picture']) ?>" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <img src="../assets/default-user.png" alt="Default" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    <?php endif; ?>
                </td>
                <td>
                    <a href="?edit=<?= $row['id'] ?>">✏ Edit</a> |
                    <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this user?')">🗑 Delete</a>
                </td>
            <?php endif; ?>
        </tr>
        <?php } ?>
    </table>
</div>

</body>
</html>
