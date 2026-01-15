<!-- For Index.php login form design -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../public/asset/css/bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../public/asset/css/style.css">
    <link rel="shortcut icon" href="../public/asset/images/password.png" type="image/x-icon">
</head>
<body>

    <div class="login-container">
        <h2 class="text-center">Welcome PHP WebApp</h2>
        <p class="text-center">Enter Your Credential to login</p>
         <!-- Start the session and display the success message if it exists -->
         <?php
        session_start();

        // Display success message after logout
        if (isset($_SESSION['success'])) {
            echo '<div class="flash-messages">';
            echo '<div class="alert alert-success text-center">' . $_SESSION['success'] . '</div>';
            echo '</div>';
            unset($_SESSION['success']); // Remove the message after displaying it
        }

        // Display error message if login fails
        if (isset($_SESSION['error'])) {
            echo '<div class="flash-messages">';
            echo '<div class="alert alert-danger text-center">' . $_SESSION['error'] . '</div>';
            echo '</div>';
            unset($_SESSION['error']);
        }
        ?>

        <form action="../public/login.php" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="text" class="form-control" name="username" id="username" placeholder="Enter your email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <p class="text-muted"> Forget Your Password? <a href="../public/reset_request.php">Reset Password</a></p>
    </div>
    
</body>
<script></script>
</html>