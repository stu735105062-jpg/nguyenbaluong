<?php
session_start();
require_once __DIR__ . '/../config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Truy vấn lấy password, role và id để định danh user
    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // Kiểm tra mật khẩu (đang để dạng plain text theo database của bạn)
        if ($password === $user['password']) {
            
            $_SESSION['authenticated'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $user['role']; 
            
            session_regenerate_id(true);

            // PHÂN LUỒNG NGƯỜI DÙNG
            if ($user['role'] === 'admin') {
                header("Location: home.php");
            } else if ($user['role'] === 'student') {
                header("Location: ../models/student.php");
            } else {
                // Các vai trò khác như 'teacher' hoặc 'staff' nếu có
                 header("Location: student.php");
              
            }
            exit;
        } else {
            $_SESSION['error'] = "Mật khẩu không chính xác.";
        }
    } else {
        $_SESSION['error'] = "Tài khoản không tồn tại.";
    }

    $stmt->close();
    $conn->close();
    header("Location: index.php");
    exit;
}