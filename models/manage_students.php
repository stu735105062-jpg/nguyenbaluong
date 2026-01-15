<?php
session_start();

/* =====================
    1. KẾT NỐI DATABASE
===================== */
$conn = mysqli_connect("localhost", "root", "", "student_management");
if (!$conn) { die("Kết nối thất bại: " . mysqli_connect_error()); }
mysqli_set_charset($conn, "utf8mb4");

/* =====================
    2. XỬ LÝ LỌC & TÌM KIẾM
===================== */
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$class_filter = isset($_GET['class_id']) ? mysqli_real_escape_string($conn, $_GET['class_id']) : '';

// Truy vấn lấy danh sách sinh viên và tên lớp
$sql = "SELECT s.*, c.class_name 
        FROM students s 
        LEFT JOIN classes c ON s.class_id = c.class_id 
        WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (s.full_name LIKE '%$search%' OR s.student_id LIKE '%$search%')";
}

if (!empty($class_filter)) {
    $sql .= " AND s.class_id = '$class_filter'";
}

$sql .= " ORDER BY s.student_id DESC";
$result = mysqli_query($conn, $sql);

// Lấy danh sách lớp để làm bộ lọc dropdown
$classes = mysqli_query($conn, "SELECT * FROM classes");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Sinh viên - SMS PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary: #4361ee; --bg: #f4f7fe; }
        body { background: var(--bg); font-family: 'Inter', sans-serif; }
        
        /* Sidebar */
        .sidebar { width: 260px; height: 100vh; position: fixed; background: #fff; border-right: 1px solid #e9ecef; z-index: 1000; }
        .main-content { margin-left: 260px; padding: 40px; }
        .nav-link { color: #6c757d; border-radius: 12px; padding: 12px 15px; margin-bottom: 8px; }
        .nav-link.active { background: var(--primary); color: #fff !important; font-weight: bold; }

        /* Components */
        .card-custom { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); background: #fff; }
        .table thead th { background: #f8faff; text-transform: uppercase; font-size: 0.75rem; color: #8d99ae; padding: 15px; border: none; }
        
        /* Nút Thao tác giống ảnh mẫu của bạn */
        .btn-action { width: 36px; height: 36px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; background: #fff; border: 1px solid #eee; transition: 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .btn-action:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .btn-edit { color: #6c757d; }
        .btn-delete { color: #dc3545; }
        
        .badge-class { background: #eef2ff; color: var(--primary); font-weight: 600; padding: 6px 12px; border-radius: 8px; }
    </style>
</head>
<body>

<div class="sidebar p-4 d-none d-lg-block">
    <h4 class="fw-bold text-primary mb-5 text-center"><i class="bi bi-mortarboard-fill me-2"></i>SMS ADMIN</h4>
    <nav class="nav flex-column gap-1">
        <a href="../public/home.php" class="nav-link"><i class="bi bi-grid-1x2 me-2"></i> Dashboard</a>
        <a href="manage_grades.php" class="nav-link"><i class="bi bi-book me-2"></i> Môn học</a>
        <a href="#" class="nav-link active"><i class="bi bi-people me-2"></i> Sinh viên</a>
        <a href="manage_grades.php" class="nav-link"><i class="bi bi-star me-2"></i> Điểm số</a>
    </nav>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold m-0">Danh sách Sinh viên</h2>
            <p class="text-muted small">Quản lý hồ sơ và ghi nhận điểm số sinh viên.</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow" onclick="location.href='add_student.php'">
            <i class="bi bi-plus-lg me-2"></i>Thêm sinh viên
        </button>
    </div>

    <div class="card-custom p-4 mb-4">
        <form class="row g-3" method="GET">
            <div class="col-lg-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-0 shadow-none" placeholder="Tên hoặc mã sinh viên..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-lg-3">
                <select name="class_id" class="form-select border-0 bg-light shadow-none">
                    <option value="">Tất cả các lớp</option>
                    <?php mysqli_data_seek($classes, 0); ?>
                    <?php while($c = mysqli_fetch_assoc($classes)): ?>
                        <option value="<?= $c['class_id'] ?>" <?= $class_filter == $c['class_id'] ? 'selected' : '' ?>>
                            Lớp <?= htmlspecialchars($c['class_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <button type="submit" class="btn btn-dark w-100 rounded-3">Lọc dữ liệu</button>
            </div>
            <div class="col-lg-2">
                <a href="export.php" class="btn btn-success w-100 rounded-3"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a>
            </div>
        </form>
    </div>

    <div class="card-custom overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Thông tin Sinh viên</th>
                        <th>Mã SV</th>
                        <th>Lớp học</th>
                        <th>Ngày sinh</th>
                        <th class="text-center">Ghi nhận</th>
                        <th class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <?php 
                            // Xử lý an toàn để tránh lỗi "Undefined array key"
                            $std_id = $row['student_id'] ?? $row['id'] ?? 'N/A';
                            $full_name = $row['full_name'] ?? $row['name'] ?? 'Không rõ';
                            $email = $row['email'] ?? 'Chưa cập nhật';
                            $birthday = (!empty($row['birthday'])) ? date('d/m/Y', strtotime($row['birthday'])) : '-';
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar" style="width: 40px; height: 40px; background: #eef2ff; color: var(--primary); display: flex; align-items: center; justify-content: center; border-radius: 10px; font-weight: bold;">
                                        <?= strtoupper(substr($full_name, 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($full_name) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($email) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="fw-bold">#<?= $std_id ?></span></td>
                            <td><span class="badge-class"><?= htmlspecialchars($row['class_name'] ?? 'Chưa xếp lớp') ?></span></td>
                            <td class="text-muted"><?= $birthday ?></td>
                            
                            <td class="text-center">
                                <a href="class_details.php?subject_id=4" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    <i class="bi bi-pencil-fill me-1"></i> Nhập điểm
                                </a>
                            </td>

                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="edit_student.php?id=<?= $std_id ?>" class="btn-action btn-edit" title="Sửa">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="delete_student.php?id=<?= $std_id ?>" class="btn-action btn-delete" 
                                       onclick="return confirm('Bạn có chắc chắn muốn xóa sinh viên [<?= htmlspecialchars($full_name) ?>]?')" title="Xóa">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Không tìm thấy sinh viên nào.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>