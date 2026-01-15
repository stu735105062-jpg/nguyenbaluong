<?php
session_start();
// --- 1. KẾT NỐI DB & KIỂM TRA QUYỀN ---
$conn = mysqli_connect('localhost', 'root', '', 'student_management');
mysqli_set_charset($conn, "utf8mb4");

// Kiểm tra quyền (Chỉ Admin mới được vào trang này)
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("<div class='container mt-5 alert alert-danger rounded-4 shadow-sm'>
            <i class='bi bi-exclamation-octagon me-2'></i> Bạn không có quyền truy cập trang này!
         </div>");
}

$msg = "";
$type = "";

// --- 2. XỬ LÝ CẬP NHẬT QUYỀN (Sử dụng Prepared Statement) ---
if (isset($_POST['update_role'])) {
    $user_id = (int)$_POST['user_id'];
    $new_role = $_POST['role'];
    $current_admin_id = $_SESSION['user_id'];

    // Ngăn chặn tự đổi quyền của chính mình (để tránh mất quyền admin vô ý)
    if ($user_id == $current_admin_id) {
        $msg = "Bạn không thể tự hạ quyền của chính mình!";
        $type = "danger";
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE users SET role = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_role, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $msg = "Đã cập nhật quyền thành công!";
            $type = "success";
        }
    }
}

// --- 3. LẤY DANH SÁCH NGƯỜI DÙNG ---
$query = "SELECT id, username, role FROM users ORDER BY role ASC, username ASC";
$users = mysqli_fetch_all(mysqli_query($conn, $query), MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS PRO - Phân quyền tài khoản</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --primary-color: #4e73df; }
        body { background: #f8f9fc; font-family: 'Segoe UI', sans-serif; }
        .glass-card { background: white; border: none; border-radius: 15px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1); }
        .table thead { background: #f8f9fc; color: #4e73df; }
        .badge-role { font-size: 0.75rem; padding: 6px 12px; border-radius: 20px; text-transform: uppercase; font-weight: 700; }
        .btn-save { background: var(--primary-color); color: white; border: none; border-radius: 8px; transition: 0.3s; }
        .btn-save:hover { background: #2e59d9; transform: translateY(-1px); }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <a href="home.php" class="text-decoration-none small text-muted mb-2 d-block">
                <i class="bi bi-arrow-left me-1"></i> Trở về Dashboard
            </a>
            <h2 class="fw-bold text-dark mb-0"><i class="bi bi-shield-lock-fill text-primary me-2"></i>Quản trị phân quyền</h2>
        </div>
        <div class="text-end">
            <span class="badge bg-white text-dark border shadow-sm rounded-pill px-3 py-2">
                <i class="bi bi-person-circle me-2 text-primary"></i>Admin: <?= htmlspecialchars($_SESSION['username'] ?? 'Cán bộ') ?>
            </span>
        </div>
    </div>

    <?php if($msg): ?>
        <div class="alert alert-<?= $type ?> border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center">
            <i class="bi bi-<?= $type == 'success' ? 'check-circle' : 'exclamation-triangle' ?>-fill me-3 fs-4"></i> <?= $msg ?>
        </div>
    <?php endif; ?>

    <div class="card glass-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="small text-uppercase fw-bold">
                        <th class="ps-4">ID</th>
                        <th>Tên tài khoản</th>
                        <th>Quyền hiện tại</th>
                        <th class="text-center">Thay đổi quyền hành</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $u): ?>
                    <tr>
                        <td class="ps-4 text-muted">#<?= $u['id'] ?></td>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($u['username']) ?></div>
                        </td>
                        <td>
                            <?php 
                                $badge_class = 'bg-info'; // student
                                if($u['role'] == 'admin') $badge_class = 'bg-danger';
                                if($u['role'] == 'teacher') $badge_class = 'bg-warning text-dark';
                            ?>
                            <span class="badge badge-role <?= $badge_class ?>">
                                <?= $u['role'] ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" class="d-flex justify-content-center gap-2">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <select name="role" class="form-select form-select-sm shadow-sm border-0 bg-light" style="width: 150px; border-radius: 8px;">
                                    <option value="admin" <?= $u['role'] == 'admin' ? 'selected' : '' ?>>Quản trị viên</option>
                                    <option value="teacher" <?= $u['role'] == 'teacher' ? 'selected' : '' ?>>Giảng viên</option>
                                    <option value="student" <?= $u['role'] == 'student' ? 'selected' : '' ?>>Sinh viên</option>
                                </select>
                                <button type="submit" name="update_role" class="btn-save px-3 py-1 fw-bold small">
                                    LƯU
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>