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
            <tr>
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
if (isset($_POST['add_subject'])) {
    $code = strtoupper(trim($_POST['subject_code']));
    $name = trim($_POST['subject_name']);
    if (!empty($code) && !empty($name)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO subjects (subject_code, subject_name) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $code, $name);
        if (mysqli_stmt_execute($stmt)) $_SESSION['msg'] = "Thêm môn học thành công!";
        else $_SESSION['error'] = "Mã môn học đã tồn tại!";
    }
    header("Location: $current_page"); exit();
}

if (isset($_GET['delete'])) {
    $sid = (int)$_GET['delete'];
    $stmt = mysqli_prepare($conn, "DELETE FROM subjects WHERE subject_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $sid);
    mysqli_stmt_execute($stmt);
    $_SESSION['msg'] = "Đã xóa môn học thành công.";
    header("Location: $current_page"); exit();
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
    <title>Quản lý Môn học - SMS PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary-color: #4361ee; }
        body { background: #f8f9fa; font-family: 'Inter', sans-serif; }
        .sidebar { width: 260px; height: 100vh; position: fixed; background: #fff; border-right: 1px solid #e0e0e0; padding: 2rem 1.5rem; }
        .main { margin-left: 260px; padding: 2.5rem; }
        .nav-link { color: #6c757d; border-radius: 10px; margin-bottom: 5px; transition: 0.3s; }
        .nav-link.active { background: var(--primary-color); color: #fff !important; }
        
        /* CHỈNH SỬA ĐỂ CÁC CARD ĐỀU NHAU */
        .card-subject { 
            border: none; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
            transition: 0.3s;
            height: 100%; /* Ép card lấp đầy chiều cao của cột */
            display: flex;
            flex-direction: column;
        }
        .card-subject:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        
        /* Cố định chiều cao vùng nội dung tên môn học */
        .subject-title {
            min-height: 3rem; 
            display: -webkit-box;
            -webkit-line-clamp: 2; /* Giới hạn tối đa 2 dòng tên môn */
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="mb-5 text-center">
        <h4 class="fw-bold text-primary"><i class="bi bi-mortarboard-fill me-2"></i>SMS ADMIN</h4>
    </div>
    <nav class="nav flex-column gap-2">
        <a href="../public/home.php" class="nav-link"><i class="bi bi-grid-1x2 me-2"></i> Dashboard</a>
        <a href="<?= $current_page ?>" class="nav-link active"><i class="bi bi-book me-2"></i> Môn học</a>
        <a href="#" class="nav-link"><i class="bi bi-people me-2"></i> Sinh viên</a>
    </nav>
</div>

<div class="main">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold">Danh sách Môn học</h2>
            <p class="text-muted mb-0">Tổng cộng <b><?= count($subjects) ?></b> môn.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="?export=true" class="btn btn-outline-success rounded-3 px-3 fw-bold">Xuất Excel</a>
            <button class="btn btn-primary rounded-3 shadow" data-bs-toggle="modal" data-bs-target="#addModal">Thêm môn mới</button>
        </div>
    </div>

    <div class="row g-4">
        <?php foreach($subjects as $s): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card card-subject p-4 bg-white">
                <div class="flex-grow-1">
                    <h5 class="fw-bold text-dark mb-1 subject-title"><?= htmlspecialchars($s['subject_name']) ?></h5>
                    <p class="text-muted small">Mã môn: <span class="fw-bold text-primary"><?= htmlspecialchars($s['subject_code']) ?></span></p>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <div>
                        <small class="text-muted d-block">Sĩ số</small>
                        <span class="fw-bold"><?= $s['total_students'] ?> sinh viên</span>
                    </div>
                    <a href="class_details.php?subject_id=<?= $s['subject_id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">Chi tiết</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <form method="POST">
                <div class="modal-body p-4">
                    <h5 class="fw-bold mb-4">Thêm môn học mới</h5>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Mã môn học</label>
                        <input type="text" name="subject_code" class="form-control" required placeholder="VD: JAVA201">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Tên môn học</label>
                        <input type="text" name="subject_name" class="form-control" required placeholder="VD: Lập trình Java">
                    </div>
                    <button type="submit" name="add_subject" class="btn btn-primary w-100 py-2 fw-bold">XÁC NHẬN THÊM</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>