<?php
session_start();

// 1. KẾT NỐI DATABASE
$conn = mysqli_connect("localhost", "root", "", "student_management");
if (!$conn) { die("Kết nối thất bại: " . mysqli_connect_error()); }
mysqli_set_charset($conn, "utf8mb4");

// 2. LẤY DỮ LIỆU SINH VIÊN CẦN SỬA
$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';
if (empty($id)) {
    header("Location: manage_students.php");
    exit();
}

// Truy vấn thông tin sinh viên
$res = mysqli_query($conn, "SELECT * FROM students WHERE student_id = '$id'");
$student = mysqli_fetch_assoc($res);

if (!$student) {
    $_SESSION['error_msg'] = "Không tìm thấy sinh viên!";
    header("Location: manage_students.php");
    exit();
}

// 3. XỬ LÝ CẬP NHẬT DỮ LIỆU (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email     = mysqli_real_escape_string($conn, $_POST['email']);
    $phone     = mysqli_real_escape_string($conn, $_POST['phone']);
    $address   = mysqli_real_escape_string($conn, $_POST['address']);
    $class_id  = mysqli_real_escape_string($conn, $_POST['class_id']);

    // Câu lệnh SQL không bao gồm cột birthday
    $sql_update = "UPDATE students SET 
                    full_name = '$full_name', 
                    email     = '$email', 
                    phone     = '$phone',
                    address   = '$address',
                    class_id  = '$class_id'
                   WHERE student_id = '$id'";
    
    if (mysqli_query($conn, $sql_update)) {
        $_SESSION['success_msg'] = "Cập nhật hồ sơ sinh viên #$id thành công!";
        header("Location: manage_students.php");
        exit();
    } else {
        $error = "Lỗi hệ thống: " . mysqli_error($conn);
    }
}

// Lấy danh sách lớp cho dropdown
$classes = mysqli_query($conn, "SELECT * FROM classes");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa hồ sơ - SMS PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary: #4361ee; --bg: #f4f7fe; }
        body { background: var(--bg); font-family: 'Inter', sans-serif; padding: 60px 0; }
        .form-card { 
            background: #fff; 
            border-radius: 24px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.06); 
            max-width: 650px; 
            margin: auto; 
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .header-box { background: var(--primary); color: #fff; padding: 30px; text-align: center; }
        .form-label { font-weight: 600; color: #4a5568; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control, .form-select { 
            border-radius: 12px; 
            padding: 12px 15px; 
            border: 1px solid #e2e8f0; 
            background: #f8fafc;
        }
        .form-control:focus { background: #fff; box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1); border-color: var(--primary); }
        .btn-submit { background: var(--primary); border: none; padding: 14px; border-radius: 14px; font-weight: 700; color: #fff; transition: 0.3s; }
        .btn-submit:hover { background: #3046bc; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(67, 97, 238, 0.2); }
    </style>
</head>
<body>

<div class="container">
    <div class="form-card">
        <div class="header-box">
            <h3 class="fw-bold m-0"><i class="bi bi-pencil-square me-2"></i>Chỉnh sửa hồ sơ</h3>
            <p class="small m-0 mt-1 opacity-75">Đang chỉnh sửa sinh viên mã: <strong>#<?= htmlspecialchars($id) ?></strong></p>
        </div>

        <div class="p-4 p-md-5">
            <?php if(isset($error)): ?>
                <div class="alert alert-danger rounded-4 mb-4 border-0 shadow-sm"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-4">
                    <label class="form-label">Họ và Tên sinh viên</label>
                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($student['full_name']) ?>" required placeholder="Ví dụ: Nguyễn Văn A">
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Địa chỉ Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($student['email']) ?>" placeholder="email@example.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Số điện thoại</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($student['phone']) ?>" placeholder="09xx xxx xxx">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Phân lớp học</label>
                    <select name="class_id" class="form-select" required>
                        <option value="">-- Chọn lớp học --</option>
                        <?php mysqli_data_seek($classes, 0); ?>
                        <?php while($c = mysqli_fetch_assoc($classes)): ?>
                            <option value="<?= $c['class_id'] ?>" <?= ($student['class_id'] == $c['class_id']) ? 'selected' : '' ?>>
                                Lớp <?= htmlspecialchars($c['class_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-5">
                    <label class="form-label">Địa chỉ liên hệ</label>
                    <textarea name="address" class="form-control" rows="3" placeholder="Số nhà, tên đường, quận/huyện..."><?= htmlspecialchars($student['address']) ?></textarea>
                </div>

                <div class="d-grid gap-3">
                    <button type="submit" class="btn btn-submit">Lưu các thay đổi</button>
                    <a href="manage_students.php" class="btn btn-light border-0 py-3 rounded-4 fw-bold text-muted">Hủy bỏ và quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>