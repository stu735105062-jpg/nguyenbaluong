<?php
/**
 * SMS ADMIN - Xử lý xóa sinh viên
 * File: delete_student.php
 */

session_start();

// 1. KẾT NỐI DATABASE
$conn = mysqli_connect("localhost", "root", "", "student_management");

// Kiểm tra kết nối
if (!$conn) {
    $_SESSION['error_msg'] = "Không thể kết nối cơ sở dữ liệu!";
    header("Location: manage_students.php");
    exit();
}

mysqli_set_charset($conn, "utf8mb4");

// 2. KIỂM TRA QUYỀN TRUY CẬP (Tùy chọn)
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     $_SESSION['error_msg'] = "Bạn không có quyền thực hiện thao tác này!";
//     header("Location: manage_students.php");
//     exit();
// }

// 3. XỬ LÝ LOGIC XÓA
if (isset($_GET['id']) && !empty($_GET['id'])) {
    
    // Làm sạch dữ liệu đầu vào (Anti-SQL Injection)
    $student_id = mysqli_real_escape_string($conn, $_GET['id']);

    // Sử dụng Transaction để đảm bảo an toàn dữ liệu
    mysqli_begin_transaction($conn);

    try {
        /**
         * Bước 1: Xóa các dữ liệu phụ thuộc (Điểm số, Điểm danh)
         * Điều này ngăn lỗi "Cannot delete or update a parent row: a foreign key constraint fails"
         */
        $delete_grades = "DELETE FROM grades WHERE student_id = '$student_id'";
        mysqli_query($conn, $delete_grades);

        /**
         * Bước 2: Xóa hồ sơ sinh viên chính
         */
        $delete_student = "DELETE FROM students WHERE student_id = '$student_id'";
        
        if (mysqli_query($conn, $delete_student)) {
            // Kiểm tra xem có dòng nào bị ảnh hưởng không (tránh xóa ID không tồn tại)
            if (mysqli_affected_rows($conn) > 0) {
                mysqli_commit($conn);
                $_SESSION['success_msg'] = "Đã xóa thành công sinh viên mã: #$student_id";
            } else {
                throw new Exception("Sinh viên không tồn tại hoặc đã bị xóa trước đó.");
            }
        } else {
            throw new Exception("Lỗi truy vấn SQL: " . mysqli_error($conn));
        }

    } catch (Exception $e) {
        // Nếu có bất kỳ lỗi nào, hoàn tác (Rollback) lại toàn bộ
        mysqli_rollback($conn);
        $_SESSION['error_msg'] = "Lỗi xóa sinh viên: " . $e->getMessage();
    }

} else {
    $_SESSION['error_msg'] = "Yêu cầu không hợp lệ. Không tìm thấy ID sinh viên!";
}

// 4. ĐÓNG KẾT NỐI VÀ QUAY LẠI TRANG DANH SÁCH
mysqli_close($conn);
header("Location: manage_students.php");
exit();