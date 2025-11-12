<?php
session_start();
include '../db.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Delete therapist
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM therapists WHERE id = $id");
    header("Location: view_therapist.php");
    exit();
}

// Update therapist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $update_id = intval($_POST['update_id']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    $stmt = $conn->prepare("UPDATE therapists SET full_name = ?, email = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("sssi", $full_name, $email, $phone, $update_id);
    $stmt->execute();
    header("Location: view_therapist.php");
    exit();
}

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    $stmt = $conn->prepare("SELECT * FROM therapists WHERE full_name LIKE ? OR email LIKE ? OR login_id LIKE ? ORDER BY id DESC");
    $likeSearch = "%" . $search . "%";
    $stmt->bind_param("sss", $likeSearch, $likeSearch, $likeSearch);
    $stmt->execute();
    $therapists = $stmt->get_result();
} else {
    $therapists = $conn->query("SELECT * FROM therapists ORDER BY id DESC");
}

$editing_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Therapists</title>
    <link rel="stylesheet" href="view_therapist.css">
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="add_therapist.php">Register Therapist</a>
    <a href="view_therapist.php" class="active">View Therapists</a>
    <a href="view_clients.php">View Users</a>
    <a href="view_appointments.php">View Appointments</a>
    <a href="add_new_admin.php">Add new Admin</a>
    <a href="view_admins.php">View Admins</a>
     <a href="https://dashboard.tawk.to/#/chat">Live Chat</a>
    <a href="../index.php">Logout</a>
</div>

<div class="main-content">
    <h1>All Registered Therapists</h1>

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
            <th>Profile</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $therapists->fetch_assoc()) { ?>
            <tr>
                <?php if ($editing_id === intval($row['id'])): ?>
                    <form method="POST">
                        <td><?= $row['id'] ?><input type="hidden" name="update_id" value="<?= $row['id'] ?>"></td>
                        <td><?= htmlspecialchars($row['login_id']) ?></td>
                        <td><input type="text" name="full_name" value="<?= htmlspecialchars($row['full_name']) ?>" required></td>
                        <td><input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" required></td>
                        <td><input type="text" name="phone" value="<?= htmlspecialchars($row['phone']) ?>" required></td>
                        <td>
                            <?php
                            $profilePath = "../" . $row['profile_picture'];
                            if (!empty($row['profile_picture']) && file_exists($profilePath)) {
                                echo "<img src='$profilePath' alt='Profile' style='width:40px;height:40px;border-radius:50%;object-fit:cover;'>";
                            } else {
                                echo "<img src='../uploads/default.png' alt='Default' style='width:40px;height:40px;border-radius:50%;object-fit:cover;'>";
                            }
                            ?>
                        </td>
                        <td>
                            <button type="submit">💾 Save</button>
                            <a href="view_therapist.php">✖ Cancel</a>
                        </td>
                    </form>
                <?php else: ?>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['login_id']) ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['phone']) ?></td>
                    <td>
                        <?php
                        $profilePath = "../" . $row['profile_picture'];
                        if (!empty($row['profile_picture']) && file_exists($profilePath)) {
                            echo "<img src='$profilePath' alt='Profile' style='width:40px;height:40px;border-radius:50%;object-fit:cover;'>";
                        } else {
                            echo "<img src='../uploads/default.png' alt='Default' style='width:40px;height:40px;border-radius:50%;object-fit:cover;'>";
                        }
                        ?>
                    </td>
                    <td>
                        <a href="?edit=<?= $row['id'] ?>">✏ Edit</a> |
                        <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')">🗑 Delete</a>
                    </td>
                <?php endif; ?>
            </tr>
        <?php } ?>
    </table>
</div>

</body>
</html>
