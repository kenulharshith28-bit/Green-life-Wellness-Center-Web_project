<?php
include ('db.php');
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($conn) || $conn->connect_error) {
        $error_message = "Database connection error.";
    } else {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($full_name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
            $error_message = "Please fill in all required fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email format.";
        } elseif ($password !== $confirm_password) {
            $error_message = "Passwords do not match.";
        } elseif (strlen($password) < 8) {
            $error_message = "Password must be at least 8 characters long.";
        } else {
            $stmt = $conn->prepare("SELECT id FROM clients WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error_message = "Email already registered.";
            } else {
                $result = $conn->query("SELECT login_id FROM clients ORDER BY id DESC LIMIT 1");
                $last_login_id = '';
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $last_login_id = $row['login_id'];
                }

                if ($last_login_id) {
                    preg_match('/CL(\d+)/', $last_login_id, $matches);
                    $login_id = 'CL' . str_pad((int)$matches[1] + 1, 3, '0', STR_PAD_LEFT);
                } else {
                    $login_id = 'CL001';
                }

                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $profile_pic = 'default.png';

                $stmt = $conn->prepare("INSERT INTO clients (login_id, full_name, email, phone, password, profile_pic) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $login_id, $full_name, $email, $phone, $hashed_password, $profile_pic);

                if ($stmt->execute()) {
                    $success_message = "🎉 Registration successful! Your Login ID is: <strong>$login_id</strong>. <a href='login.php'>Log in here</a>";

                } else {
                    $error_message = "Something went wrong: " . $stmt->error;
                }
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register - GreenLife Wellness</title>
  <?php include('header.php'); ?>
  <link rel="stylesheet" href="includes/register.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body>

<!-- ✅ Registration Form Section -->
<div class="register-blur-bg">
  <div class="register-form-container">
    <div class="register-form">
      <h2>Create Your Account</h2>

      <?php if (!empty($success_message)) : ?>
        <div class="success-message"><?= $success_message ?></div>
      <?php elseif (!empty($error_message)) : ?>
        <div class="error-message"><?= $error_message ?></div>
      <?php endif; ?>

      <form action="register.php" method="POST">
        <div class="form-inner">
          <input type="text" name="full_name" placeholder="Full Name" required>
          <input type="email" name="email" placeholder="Email Address" required>
          <input type="tel" name="phone" placeholder="Phone Number" required>
          <input type="password" name="password" placeholder="Password (min 8 chars)" required>
          <input type="password" name="confirm_password" placeholder="Confirm Password" required>
          <button type="submit">Register Now</button>
        </div>
      </form>

      <p class="login-link">Already have an account? <a href="login.php">Log in</a></p>
    </div>
  </div>
</div>

</body>
</html>
