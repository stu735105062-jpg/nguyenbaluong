<?php
// 1. Khởi tạo session và kết nối Database
session_start();
// Thay đổi thông tin kết nối nếu bạn không dùng file config
$conn = mysqli_connect('localhost', 'root', '', 'student_management');
mysqli_set_charset($conn, "utf8mb4");

// 2. Kiểm tra quyền truy cập
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$u_id = $_SESSION['user_id'];
$msg = ""; $error = "";

// 3. Lấy chính xác student_id và class_id từ user_id đang đăng nhập
$stu_query = mysqli_query($conn, "SELECT id, class_id FROM student_info WHERE user_id = '$u_id'");
$student = mysqli_fetch_assoc($stu_query);

if (!$student) {
    die("<div class='alert alert-danger'>Lỗi: Tài khoản chưa được liên kết với hồ sơ sinh viên.</div>");
}

$student_id = $student['id'];
$current_class_id = $student['class_id'];

// 4. Xử lý Đăng ký môn học
if (isset($_POST['confirm_registration'])) {
    $c_id = intval($_POST['course_id']);
    
    if ($c_id > 0) {
        // Kiểm tra xem đã đăng ký chưa
        $check = mysqli_query($conn, "SELECT id FROM course_registrations WHERE student_id = '$student_id' AND course_id = '$c_id'");
        
        if (mysqli_num_rows($check) > 0) {
            $error = "Bạn đã đăng ký môn học này rồi!";
        } else {
            // Thêm vào bảng đăng ký
            $sql_reg = "INSERT INTO course_registrations (student_id, course_id) VALUES ('$student_id', '$c_id')";
            if (mysqli_query($conn, $sql_reg)) {
                // Tự động tạo dòng điểm trống trong bảng grades
                mysqli_query($conn, "INSERT IGNORE INTO grades (student_id, course_id) VALUES ('$student_id', '$c_id')");
                $msg = "Đăng ký môn học thành công!";
            } else {
                $error = "Lỗi hệ thống SQL.";
            }
        }
    }
}

// 5. Lấy danh sách môn học (Quan trọng: Phải có JOIN để lấy tên lớp)
// Chỉ lấy môn của các lớp KHÁC lớp mình đang học
$sql_avail = "SELECT c.course_id, c.course_name, cl.class_name 
              FROM courses c 
              JOIN classes cl ON c.class_id = cl.class_id
              WHERE c.class_id != '$current_class_id' 
              AND c.course_id NOT IN (SELECT course_id FROM course_registrations WHERE student_id = '$student_id')";
$available_courses = mysqli_query($conn, $sql_avail);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký môn học tự chọn</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .reg-card { max-width: 500px; margin: 80px auto; border-radius: 20px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .header-box { background: #0d6efd; color: white; border-radius: 20px 20px 0 0; padding: 30px; }
    </style>
</head>
<body>

<div class="container">
    <div class="card reg-card">
        <div class="header-box text-center">
            <i class="bi bi-book-half fs-1"></i>
            <h4 class="mt-2">Đăng ký học phần</h4>
            <p class="small mb-0 opacity-75">Chọn môn học tự chọn từ các lớp khác</p>
        </div>
        <div class="card-body p-4">
            <?php if($msg): ?>
                <div class="alert alert-success border-0 shadow-sm"><i class="bi bi-check-circle me-2"></i><?= $msg ?></div>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="alert alert-danger border-0 shadow-sm"><i class="bi bi-exclamation-triangle me-2"></i><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-4">
                    <label class="form-label fw-bold">Danh sách môn học khả dụng:</label>
                    <select name="course_id" class="form-select form-select-lg" required>
                        <option value="" selected disabled>-- Chọn môn học --</option>
                        <?php if (mysqli_num_rows($available_courses) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($available_courses)): ?>
                                <option value="<?= $row['course_id'] ?>">
                                    <?= htmlspecialchars($row['course_name']) ?> (Lớp: <?= htmlspecialchars($row['class_name']) ?>)
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="" disabled>Không có môn học nào mới để đăng ký</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <button type="submit" name="confirm_registration" class="btn btn-primary w-100 py-3 fw-bold rounded-3 mb-3">
                    XÁC NHẬN ĐĂNG KÝ
                </button>
                <a href="student.php" class="btn btn-outline-secondary w-100">Quay lại trang cá nhân</a>
            </form>
        </div>
    </div>
</div>

</body>
</html>