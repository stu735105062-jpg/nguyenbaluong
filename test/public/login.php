<!-- For Login.php Backend code -->
<?php
session_start();
require '../config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare and execute the SQL statement
    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($storedPassword);

    // Check if the user exists and verify the password
    if ($stmt->fetch() && $password === $storedPassword) {
        $_SESSION['authenticated'] = true;
        header("Location: home.php");
    } else {
        $_SESSION['error'] = "Invalid username or password.";
        header("Location: index.php");
    }

    $stmt->close();
    $conn->close();
}
?>
