<?php
session_start();

// --- 1. KẾT NỐI CƠ SỞ DỮ LIỆU ---
$conn = mysqli_connect('localhost', 'root', '', 'student_management');
if (!$conn) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");

// Lấy danh sách lớp học để đổ vào dropdown
$classes_res = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name ASC");

$msg = "";
$error = "";
$default_pass = "123456"; 

// --- 2. XỬ LÝ KHI NGƯỜI DÙNG NHẤN NÚT LƯU ---
if (isset($_POST['btn_save'])) {
    // Làm sạch dữ liệu đầu vào
    $name     = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone    = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $address  = mysqli_real_escape_string($conn, trim($_POST['address']));
    $class_id = !empty($_POST['class_id']) ? (int)$_POST['class_id'] : null;

    // Bước 2.1: Kiểm tra xem Email đã tồn tại trong bảng users chưa
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        $error = "Lỗi: Email/Tên đăng nhập <b>'$email'</b> đã tồn tại trên hệ thống!";
    } else {
        // Bắt đầu Transaction để đảm bảo an toàn dữ liệu 2 bảng
        mysqli_begin_transaction($conn);

        try {
            // Bước 2.2: Thêm vào bảng users trước
            $role = 'student';
            $stmt_user = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt_user->bind_param("sss", $email, $default_pass, $role);
            
            if (!$stmt_user->execute()) {
                throw new Exception("Không thể tạo tài khoản đăng nhập.");
            }
            
            $user_id = $conn->insert_id; // Lấy ID vừa tạo để liên kết bảng students

            // Bước 2.3: Thêm vào bảng students
            $sql_student = "INSERT INTO students (user_id, full_name, email, phone, address, class_id) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_student);
            $stmt_insert->bind_param("issssi", $user_id, $name, $email, $phone, $address, $class_id);
            
            if (!$stmt_insert->execute()) {
                throw new Exception("Không thể tạo hồ sơ chi tiết sinh viên.");
            }

            // Nếu mọi thứ OK, xác nhận lưu vào DB
            mysqli_commit($conn);

            $msg = "<b>Thành công!</b> Đã tạo hồ sơ cho <b>$name</b>.<br>
                    <small>Tên đăng nhập: $email | Mật khẩu: $default_pass</small>";
            
            // Xóa dữ liệu cũ trong form sau khi lưu thành công
            $name = $email = $phone = $address = "";

        } catch (Exception $e) {
            // Nếu có lỗi ở bất kỳ bước nào, hoàn tác toàn bộ (không tạo user rác)
            mysqli_rollback($conn);
            $error = "Lỗi hệ thống: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm sinh viên | SMS PRO</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --primary-color: #4e73df; }
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; }
        .card { border: none; border-radius: 1rem; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1); }
        .form-label { font-weight: 600; color: #444; }
        .btn-primary { background-color: var(--primary-color); border: none; padding: 0.7rem; }
        .btn-primary:hover { background-color: #2e59d9; }
    </small></style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="mb-4">
                <a href="home.php" class="btn btn-sm btn-light border shadow-sm text-muted">
                    <i class="bi bi-arrow-left"></i> Quay lại
                </a>
            </div>

            <div class="card p-4 bg-white">
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-dark">Thêm Sinh Viên Mới</h3>
                    <p class="text-muted small">Hệ thống sẽ tự động tạo tài khoản đăng nhập</p>
                </div>

                <?php if($msg): ?>
                    <div class="alert alert-success border-0 shadow-sm small mb-4">
                        <i class="bi bi-check-circle-fill me-2"></i> <?= $msg ?>
                    </div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div class="alert alert-danger border-0 shadow-sm small mb-4">
                        <i class="bi bi-exclamation-octagon-fill me-2"></i> <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Họ và Tên</label>
                        <input type="text" name="full_name" class="form-control" value="<?= isset($name) ? htmlspecialchars($name) : '' ?>" placeholder="Nhập tên đầy đủ" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email đăng nhập</label>
                        <input type="email" name="email" class="form-control" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" placeholder="ten@gmail.com" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Điện thoại</label>
                            <input type="text" name="phone" class="form-control" value="<?= isset($phone) ? htmlspecialchars($phone) : '' ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Lớp</label>
                            <select name="class_id" class="form-select" required>
                                <option value="">Chọn lớp...</option>
                                <?php 
                                mysqli_data_seek($classes_res, 0);
                                while($row = mysqli_fetch_assoc($classes_res)): 
                                ?>
                                    <option value="<?= $row['class_id'] ?>" <?= (isset($_POST['class_id']) && $_POST['class_id'] == $row['class_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($row['class_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Địa chỉ</label>
                        <textarea name="address" class="form-control" rows="2"><?= isset($address) ? htmlspecialchars($address) : '' ?></textarea>
                    </div>

                    <button type="submit" name="btn_save" class="btn btn-primary w-100 fw-bold shadow-sm">
                        <i class="bi bi-plus-circle me-1"></i> LƯU HỒ SƠ
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>