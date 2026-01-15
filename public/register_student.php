<?php
session_start();
// 1. Kết nối Database
$conn = mysqli_connect('localhost', 'root', '', 'student_management');
mysqli_set_charset($conn, "utf8mb4");

// 2. Kiểm tra quyền Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: home.php");
    exit();
}

$msg = ""; $error = "";

// --- LẤY DANH SÁCH LỚP (Lấy ra bao nhiêu dòng thì sẽ tạo bấy nhiêu mã E1, E2...) ---
$classes_res = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_id ASC");

// 3. XỬ LÝ ĐĂNG KÝ
if (isset($_POST['btn_register'])) {
    $name     = mysqli_real_escape_string($conn, $_POST['name']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $phone    = mysqli_real_escape_string($conn, $_POST['phone']);
    $address  = mysqli_real_escape_string($conn, $_POST['address']);
    $class_id = !empty($_POST['class_id']) ? (int)$_POST['class_id'] : "NULL";
    
    $password = "123456"; 

    $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$email'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Email này đã được đăng ký tài khoản!";
    } else {
        mysqli_begin_transaction($conn);
        try {
            mysqli_query($conn, "INSERT INTO users (username, password, role) VALUES ('$email', '$password', 'student')");
            $user_id = mysqli_insert_id($conn);

            $sql_info = "INSERT INTO student_info (user_id, class_id, name, email, phone, address) 
                         VALUES ($user_id, $class_id, '$name', '$email', '$phone', '$address')";
            
            if (mysqli_query($conn, $sql_info)) {
                mysqli_commit($conn);
                $msg = "Tạo tài khoản thành công! <br> Mật khẩu mặc định: <b>$password</b>";
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
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
    <title>Đăng ký sinh viên - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --primary-color: #4e73df; }
        body { background-color: #f8f9fc; }
        .card { border: none; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); border-radius: 12px; }
        .form-label { font-weight: 700; font-size: 0.75rem; color: #4e73df; letter-spacing: 0.5px; }
        .btn-primary { background-color: var(--primary-color); border: none; padding: 12px; transition: all 0.3s; }
        .btn-primary:hover { background-color: #2e59d9; transform: translateY(-1px); }
        .form-control, .form-select { border-radius: 8px; padding: 10px 15px; border: 1px solid #d1d3e2; }
        .form-control:focus { box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.1); border-color: #4e73df; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <a href="home.php" class="btn btn-link mb-3 text-decoration-none text-muted p-0">
                <i class="bi bi-arrow-left-circle"></i> Quay lại Dashboard
            </a>
            
            <div class="card">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h4 class="fw-bold text-dark">THÊM SINH VIÊN MỚI</h4>
                        <div class="dropdown-divider mb-3"></div>
                        <p class="text-muted small">Cấp tài khoản truy cập cho sinh viên Khoa CNTT</p>
                    </div>

                    <?php if($msg): ?> 
                        <div class="alert alert-success border-0 shadow-sm mb-4 small"><?= $msg ?></div> 
                    <?php endif; ?>

                    <?php if($error): ?> 
                        <div class="alert alert-danger border-0 shadow-sm mb-4 small"><?= $error ?></div> 
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label text-uppercase">Họ và Tên</label>
                            <input type="text" name="name" class="form-control" placeholder="Nguyễn Văn A" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-uppercase">Email (Tên đăng nhập)</label>
                            <input type="email" name="email" class="form-control" placeholder="sinhvien@cntt.com" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-uppercase">Lớp hành chính (Khoa CNTT)</label>
                            <select name="class_id" class="form-select">
                                <option value="">-- Chưa xếp lớp (Sinh viên tự do) --</option>
                                <optgroup label="HỆ THỐNG LỚP KHOA CNTT">
                                    <?php 
                                    $count = 1; // Biến đếm để tạo tên lớp E1, E2...
                                    while($c = mysqli_fetch_assoc($classes_res)): 
                                    ?>
                                        <option value="<?= $c['class_id'] ?>">
                                            Khoa CNTT - E<?= $count++ ?>
                                        </option>
                                    <?php endwhile; ?>
                                </optgroup>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-uppercase">Số điện thoại</label>
                            <input type="text" name="phone" class="form-control" placeholder="09xxxxxxxx">
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-uppercase">Địa chỉ liên hệ</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Xóm, xã, huyện, tỉnh..."></textarea>
                        </div>

                        <button type="submit" name="btn_register" class="btn btn-primary w-100 fw-bold">
                            <i class="bi bi-shield-check me-2"></i> KÍCH HOẠT TÀI KHOẢN
                        </button>
                    </form>
                </div>
            </div>
            
            <p class="text-center mt-4 text-muted style="font-size: 0.7rem;">
                Mật khẩu mặc định sau khi tạo là <span class="badge bg-secondary">123456</span>
            </p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>