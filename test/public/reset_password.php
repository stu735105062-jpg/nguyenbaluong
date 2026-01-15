<?php
session_start();
require '../config/config.php';

// Get email from URL
if (!isset($_GET['email'])) {
    $_SESSION['error'] = "Invalid password reset link.";
    header("Location: reset_request.php");
    exit;
}

$email = $_GET['email'];

// Check if email exists
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    $_SESSION['error'] = "This email does not exist in our records.";
    header("Location: reset_request.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: reset_password.php?email=" . urlencode($email));
        exit;
    }

    // Update password in database (plain text)
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $password, $email);
    if ($stmt->execute()) {
        $_SESSION['success'] = "✅ Your password has been successfully updated.";
        header("Location: reset_request.php"); // Redirect to reset_request.php or login page
        exit;
    } else {
        $_SESSION['error'] = "❌ Failed to update password. Please try again.";
        header("Location: reset_password.php?email=" . urlencode($email));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="../public/asset/css/bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../public/asset/css/style.css">
    <link rel="shortcut icon" href="../public/asset/images/password.png" type="image/x-icon">
</head>
<body>
    <div class="login-container">
        <h2 class="text-center">Set Your New Password</h2>
        <p class="text-center">Enter your new password below</p>

        <!-- Flash messages -->
        <?php
        if (isset($_SESSION['success'])) {
            echo '<div class="flash-messages">';
            echo '<div class="alert alert-success text-center">' . $_SESSION['success'] . '</div>';
            echo '</div>';
            unset($_SESSION['success']);
        }

        if (isset($_SESSION['error'])) {
            echo '<div class="flash-messages">';
            echo '<div class="alert alert-danger text-center">' . $_SESSION['error'] . '</div>';
            echo '</div>';
            unset($_SESSION['error']);
        }
        ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <input type="password" class="form-control" name="password" id="password" placeholder="Enter new password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="Confirm new password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Reset</button>
        </form>

        <p class="text-muted mt-3 text-center">
            Remembered your password? <a href="../public/index.php">Login here</a>
        </p>
    </div>
</body>
</html>