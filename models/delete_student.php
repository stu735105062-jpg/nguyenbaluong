<?php
session_start();

// 1. KẾT NỐI DATABASE
$conn = mysqli_connect("localhost", "root", "", "student_management");
if (!$conn) {
    $_SESSION['error_msg'] = "Không thể kết nối cơ sở dữ liệu!";
    header("Location: manage_students.php");
    exit();
}
mysqli_set_charset($conn, "utf8mb4");

// 2. XỬ LÝ LOGIC XÓA
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $student_id = mysqli_real_escape_string($conn, $_GET['id']);
    mysqli_begin_transaction($conn);

    try {
        // Xóa bảng con trước (Điểm số) để tránh lỗi khóa ngoại
        mysqli_query($conn, "DELETE FROM grades WHERE student_id = '$student_id'");

        // Xóa bảng chính (Sinh viên)
        if (mysqli_query($conn, "DELETE FROM students WHERE student_id = '$student_id'")) {
            if (mysqli_affected_rows($conn) > 0) {
                mysqli_commit($conn);
                $_SESSION['success_msg'] = "Đã xóa thành công sinh viên mã: #$student_id";
            } else {
                throw new Exception("Sinh viên không tồn tại hoặc đã bị xóa.");
            }
        } else {
            throw new Exception(mysqli_error($conn));
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error_msg'] = "Lỗi: " . $e->getMessage();
    }
} else {
    $_SESSION['error_msg'] = "Yêu cầu không hợp lệ!";
}

mysqli_close($conn);
header("Location: manage_students.php");
exit();