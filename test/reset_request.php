<?php
session_start();
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
        <h2 class="text-center">Forgot Password?</h2>
        <p class="text-center">Enter your registered email to receive a reset link</p>

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

        <form action="../includes/send_reset.php" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" name="email" id="email" placeholder="Enter your email" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
        </form>

        <p class="text-muted mt-3 text-center">
            Remembered your password? <a href="../public/index.php">Login here</a>
        </p>
    </div>
    
</body>
</html>
