<?php
session_start();
include('db.php');

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_id = trim($_POST['login_id']);
    $password = $_POST['password'];

    if (empty($login_id) || empty($password)) {
        $error_message = "Please enter both Login ID and Password.";
    } else {
        $user_role = substr($login_id, 0, 2);
        $table = ($user_role === 'CL') ? 'clients' : (($user_role === 'TH') ? 'therapists' : (($user_role === 'AD') ? 'admins' : ''));

        if ($table) {
            $stmt = $conn->prepare("SELECT * FROM $table WHERE login_id = ?");
            $stmt->bind_param("s", $login_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                if (password_verify($password, $row['password'])) {
                    // Set session data
                    $_SESSION['full_name'] = $row['full_name'];
                    $_SESSION['login_id'] = $row['login_id'];
                    $_SESSION['success'] = "Login successful! Welcome back, " . $row['full_name'] . "!";

                    // Set role-specific ID and redirect
                    if ($user_role === 'CL') {
                        $_SESSION['client_id'] = $row['id'];
                        header("Location: client/client_dashboard.php");
                    } elseif ($user_role === 'TH') {
                        $_SESSION['therapist_id'] = $row['id'];
                        header("Location: therapist/therapist_dashboard.php");
                    } elseif ($user_role === 'AD') {
                        $_SESSION['admin_id'] = $row['id'];
                        header("Location: admin/admin_dashboard.php");
                    }
                    exit();
                } else {
                    $error_message = "Invalid password.";
                }
            } else {
                $error_message = "User not found.";
            }

            $stmt->close();
        } else {
            $error_message = "Invalid Login ID format.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Login - GreenLife Wellness</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="includes/login.css" />
  <link rel="stylesheet" href="includes/header.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body>

<!-- ✅ Top Bar + Header -->
<?php include('header.php'); ?>

<!-- ✅ Login Form with Blurred Background -->
<div class="login-blur-bg">
  <div class="login-form-container">
    <form action="login.php" method="POST" class="login-form">
      <h2>Welcome Back!</h2>

      <?php if (!empty($success_message)) : ?>
        <div class="success-message"><?= htmlspecialchars($success_message) ?></div>
      <?php elseif (!empty($error_message)) : ?>
        <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
      <?php endif; ?>

      <label for="login_id">Login ID</label>
      <input type="text" name="login_id" id="login_id" placeholder="Enter your login ID" required>

      <label for="password">Password</label>
      <div class="password-wrapper">
        <input type="password" name="password" id="password" placeholder="Enter your password" required>
        <i class="fa fa-eye toggle-password" onclick="togglePassword()"></i>
      </div>

      <button type="submit" name="login">Login</button>

      <div class="register-link">
        Don't have an account? <a href="register.php">Register here</a>
      </div>
    </form>
  </div>
</div>

<script>
function togglePassword() {
  const pwd = document.getElementById('password');
  const icon = document.querySelector('.toggle-password');
  if (pwd.type === 'password') {
    pwd.type = 'text';
    icon.classList.add('fa-eye-slash');
  } else {
    pwd.type = 'password';
    icon.classList.remove('fa-eye-slash');
  }
}
</script>

</body>
</html>
