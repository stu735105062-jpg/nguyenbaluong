<?php
session_start();

/* =====================
    1. KẾT NỐI DATABASE
===================== */
$conn = mysqli_connect("localhost", "root", "", "student_management");
if (!$conn) {
    die("Lỗi kết nối DB: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");

/* =====================
    2. XỬ LÝ XUẤT FILE EXCEL
===================== */
if (isset($_GET['export'])) {
    $filename = "danh_sach_mon_hoc_" . date('d-m-Y') . ".xls";
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=$filename");
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
    echo '<table border="1">
            <tr style="height:30px;">
                <th style="background-color: #4361ee; color: white;">ID</th>
                <th style="background-color: #4361ee; color: white;">Mã Môn Học</th>
                <th style="background-color: #4361ee; color: white;">Tên Môn Học</th>
                <th style="background-color: #4361ee; color: white;">Số Lượng Sinh Viên</th>
            </tr>';

    $sql = "SELECT s.subject_id, s.subject_code, s.subject_name, COUNT(g.student_id) as total 
            FROM subjects s LEFT JOIN grades g ON s.subject_id = g.subject_id 
            GROUP BY s.subject_id";
    $res = mysqli_query($conn, $sql);

    while ($row = mysqli_fetch_assoc($res)) {
        echo "<tr>
                <td>{$row['subject_id']}</td>
                <td>{$row['subject_code']}</td>
                <td>{$row['subject_name']}</td>
                <td>{$row['total']}</td>
              </tr>";
    }
    echo '</table>';
    exit();
}

/* =====================
    3. XỬ LÝ THÊM/XÓA MÔN
===================== */
$current_page = $_SERVER['PHP_SELF'];

// XỬ LÝ THÊM MÔN (Có kiểm tra trùng mã)
if (isset($_POST['add_subject'])) {
    $code = strtoupper(trim($_POST['subject_code']));
    $name = trim($_POST['subject_name']);

    if (!empty($code) && !empty($name)) {
        // Bước 1: Kiểm tra xem mã này đã tồn tại chưa
        $check_stmt = mysqli_prepare($conn, "SELECT subject_id FROM subjects WHERE subject_code = ?");
        mysqli_stmt_bind_param($check_stmt, "s", $code);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);

        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $_SESSION['error'] = "Lỗi: Mã môn học [$code] đã tồn tại trong hệ thống!";
        } else {
            // Bước 2: Nếu chưa tồn tại thì mới Insert
            $stmt = mysqli_prepare($conn, "INSERT INTO subjects (subject_code, subject_name) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "ss", $code, $name);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['msg'] = "Thêm môn học [$name] thành công!";
            } else {
                $_SESSION['error'] = "Đã xảy ra lỗi khi lưu vào cơ sở dữ liệu.";
            }
        }
    }
    header("Location: $current_page"); 
    exit();
}

// XỬ LÝ XÓA MÔN
if (isset($_GET['delete'])) {
    $sid = (int)$_GET['delete'];
    // Kiểm tra xem có sinh viên nào đang có điểm môn này không trước khi xóa
    $check_grade = mysqli_query($conn, "SELECT 1 FROM grades WHERE subject_id = $sid LIMIT 1");
    if (mysqli_num_rows($check_grade) > 0) {
        $_SESSION['error'] = "Không thể xóa môn học đang có dữ liệu điểm sinh viên.";
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM subjects WHERE subject_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $sid);
        if(mysqli_stmt_execute($stmt)) $_SESSION['msg'] = "Đã xóa môn học thành công.";
        else $_SESSION['error'] = "Lỗi hệ thống khi xóa.";
    }
    header("Location: $current_page"); 
    exit();
}

/* =====================
    4. LẤY DANH SÁCH MÔN
===================== */
$query = "SELECT s.subject_id, s.subject_code, s.subject_name, COUNT(g.student_id) AS total_students 
          FROM subjects s LEFT JOIN grades g ON s.subject_id = g.subject_id 
          GROUP BY s.subject_id ORDER BY s.subject_id DESC";
$result = mysqli_query($conn, $query);
$subjects = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Môn học - SMS PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary: #4361ee; --bg: #f8f9fa; }
        body { background: var(--bg); font-family: 'Inter', system-ui, sans-serif; }
        .sidebar { width: 260px; height: 100vh; position: fixed; background: #fff; border-right: 1px solid #eee; padding: 2rem 1.5rem; z-index: 1000; }
        .main { margin-left: 260px; padding: 2.5rem; transition: 0.3s; }
        .nav-link { color: #6c757d; border-radius: 12px; padding: 0.8rem 1rem; margin-bottom: 5px; font-weight: 500; }
        .nav-link:hover { background: #f0f2ff; color: var(--primary); }
        .nav-link.active { background: var(--primary); color: #fff !important; box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3); }
        .card-subject { border: none; border-radius: 20px; background: #fff; box-shadow: 0 5px 20px rgba(0,0,0,0.03); transition: 0.3s; position: relative; overflow: hidden; height: 100%; display: flex; flex-direction: column; }
        .card-subject:hover { transform: translateY(-7px); box-shadow: 0 12px 30px rgba(0,0,0,0.08); }
        .delete-btn { position: absolute; top: 15px; right: 15px; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: rgba(220, 53, 69, 0.1); color: #dc3545; text-decoration: none; transition: 0.2s; border: none; }
        .delete-btn:hover { background: #dc3545; color: #fff; }
        .subject-title { min-height: 3rem; margin-right: 25px; }
        .badge-code { background: #eef2ff; color: var(--primary); padding: 5px 12px; border-radius: 8px; font-size: 0.75rem; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="mb-5 px-3">
        <h4 class="fw-bold text-primary m-0"><i class="bi bi-layers-half me-2"></i>SMS ADMIN</h4>
    </div>
    <nav class="nav flex-column">
        <a href="../public/home.php" class="nav-link"><i class="bi bi-house-door me-2"></i> Dashboard</a>
        <a href="<?= $current_page ?>" class="nav-link active"><i class="bi bi-journal-text me-2"></i> Môn học</a>
        <a href="manage_students.php" class="nav-link"><i class="bi bi-people me-2"></i> Sinh viên</a>
    </nav>
</div>

<div class="main">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item small"><a href="#">Hệ thống</a></li>
                    <li class="breadcrumb-item small active">Môn học</li>
                </ol>
            </nav>
            <h2 class="fw-bold m-0">Danh mục Môn học</h2>
        </div>
        <div class="d-flex gap-2">
            <a href="?export=true" class="btn btn-light rounded-3 border px-3"><i class="bi bi-file-earmark-excel me-2 text-success"></i>Xuất Excel</a>
            <button class="btn btn-primary rounded-3 px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg me-2"></i>Thêm môn</button>
        </div>
    </div>

    <?php if(isset($_SESSION['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= $_SESSION['msg']; unset($_SESSION['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php foreach($subjects as $s): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card card-subject p-4">
                <a href="?delete=<?= $s['subject_id'] ?>" 
                   class="delete-btn" 
                   onclick="return confirm('Xác nhận xóa môn: <?= $s['subject_name'] ?>?')">
                    <i class="bi bi-trash"></i>
                </a>

                <div class="mb-3">
                    <span class="badge-code fw-bold"><?= htmlspecialchars($s['subject_code']) ?></span>
                </div>
                
                <h5 class="fw-bold text-dark mb-4 subject-title"><?= htmlspecialchars($s['subject_name']) ?></h5>
                
                <div class="d-flex justify-content-between align-items-center mt-auto pt-3 border-top">
                    <div>
                        <small class="text-muted d-block" style="font-size: 0.7rem;">SINH VIÊN ĐĂNG KÝ</small>
                        <span class="fw-bold text-dark"><?= $s['total_students'] ?> học viên</span>
                    </div>
                    <a href="class_details.php?subject_id=<?= $s['subject_id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold">Chi tiết</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-bold m-0">Tạo môn học mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Mã môn học (Duy nhất)</label>
                        <input type="text" name="subject_code" class="form-control form-control-lg rounded-3 fs-6" required placeholder="VD: PHP101">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Tên môn học</label>
                        <input type="text" name="subject_name" class="form-control form-control-lg rounded-3 fs-6" required placeholder="VD: Lập trình Web PHP">
                    </div>
                    <button type="submit" name="add_subject" class="btn btn-primary btn-lg w-100 py-3 rounded-3 fw-bold shadow">LƯU THÔNG TIN</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>