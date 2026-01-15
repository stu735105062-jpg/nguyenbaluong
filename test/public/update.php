<?php
require "../config/config.php";
session_start(); // Start the session to store flash messages

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $sql = "UPDATE student_info SET name=?, email=?, phone=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $name, $email, $phone, $id);

    if ($stmt->execute()) {
        // Set a success message in the session
        $_SESSION['success'] = 'Data updated successfully';
        header("Location: home.php");
    } else {
        $_SESSION['error'] = "Error updating record: " . $conn->error;
        header("Location: home.php");
    }
    $stmt->close();
    $conn->close();
}
?>