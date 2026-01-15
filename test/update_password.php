<?php
session_start();
require '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Validate
    if (empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "Please fill in all fields.";
        header("Location: reset_password.php?token=" . urlencode($token));
        exit;
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: reset_password.php?token=" . urlencode($token));
        exit;
    }

    // Check token validity
    $stmt = $conn->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        $_SESSION['error'] = "Invalid or expired password reset link.";
        header("Location: reset_request.php");
        exit;
    }

    // Update password (plain text)
    $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
    $stmt->bind_param("si", $password, $user['id']);
    $stmt->execute();

    $_SESSION['success'] = "✅ Your password has been updated successfully. You can now login.";
    header("Location: login.php");
    exit;
}
