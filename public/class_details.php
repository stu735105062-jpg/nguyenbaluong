<?php
session_start();
$conn = mysqli_connect('localhost', 'root', '', 'student_management');
mysqli_set_charset($conn, "utf8mb4");

$class_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($class_id == 0) { header("Location: manage_grades.php"); exit(); }

// Lấy thông tin lớp & môn học
$class_res = mysqli_query($conn, "SELECT * FROM classes WHERE class_id = '$class_id'");
$class_info = mysqli_fetch_assoc($class_res);
$class_name = $class_info['class_name'];

$course_res = mysqli_query($conn, "SELECT course_id FROM courses WHERE class_id = '$class_id' LIMIT 1");
if (mysqli_num_rows($course_res) > 0) {
    $course_id = mysqli_fetch_assoc($course_res)['course_id'];
} else {
    mysqli_query($conn, "INSERT INTO courses (course_name, class_id) VALUES ('$class_name', '$class_id')");
    $course_id = mysqli_insert_id($conn);
}

$msg = ""; $error = "";

// 1. XỬ LÝ LƯU ĐIỂM & ĐIỂM DANH
if (isset($_POST['btn_save_all'])) {
    $scores = $_POST['scores'] ?? [];
    $attendance = $_POST['attendance'] ?? [];
    $today = date('Y-m-d');
    
    foreach ($scores as $s_id => $score) {
        if ($score !== "") {
            mysqli_query($conn, "INSERT INTO grades (student_id, course_id, score) VALUES ('$s_id', '$course_id', '$score') 
                                ON DUPLICATE KEY UPDATE score = '$score'");
        }
        $status = isset($attendance[$s_id]) ? 'present' : 'absent';
        mysqli_query($conn, "INSERT INTO attendance (student_id, date, status) VALUES ('$s_id', '$today', '$status') 
                            ON DUPLICATE KEY UPDATE status = '$status'");
    }
    $msg = "Cập nhật dữ liệu thành công!";
}

// 2. TRUY VẤN DANH SÁCH SINH VIÊN (CẢ TRONG LỚP VÀ ĐĂNG KÝ TỰ CHỌN)
$today = date('Y-m-d');
$sql_list = "
    SELECT DISTINCT s.id, s.name, s.email, g.score, a.status,
           (CASE WHEN s.class_id = '$class_id' THEN 'Chính quy' ELSE 'Tự chọn' END) as type
    FROM student_info s
    LEFT JOIN course_registrations cr ON s.id = cr.student_id
    LEFT JOIN grades g ON s.id = g.student_id AND g.course_id = '$course_id'
    LEFT JOIN attendance a ON s.id = a.student_id AND a.date = '$today'
    WHERE s.class_id = '$class_id' OR cr.course_id = '$course_id'
    ORDER BY type DESC, s.name ASC";
$list_students = mysqli_query($conn, $sql_list);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý lớp: <?= htmlspecialchars($class_name) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f0f2f5; }
        .card-custom { border-radius: 15px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .type-badge { font-size: 0.7rem; padding: 2px 8px; border-radius: 10px; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="card card-custom p-4 mb-4 bg-white">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-0 text-primary small-caps"><?= htmlspecialchars($class_name) ?></h2>
                <p class="text-muted mb-0">Giảng viên: <?= htmlspecialchars($class_info['teacher_name'] ?? 'Chưa cập nhật') ?></p>
            </div>
            <a href="../models/manage_grades.php" class="btn btn-outline-secondary rounded-pill"> Quay lại</a>
        </div>
    </div>

    <?php if($msg) echo "<div class='alert alert-success border-0 shadow-sm'>$msg</div>"; ?>

    <form method="POST">
        <div class="card card-custom bg-white">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold m-0"><i class="bi bi-list-check me-2"></i>Danh sách sinh viên</h5>
                <button type="submit" name="btn_save_all" class="btn btn-primary fw-bold px-4 shadow">LƯU BẢNG ĐIỂM</button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-center">
                        <tr>
                            <th class="text-start ps-4">Sinh viên</th>
                            <th>Loại hình</th>
                            <th width="150">Điểm môn</th>
                            <th width="120">Có mặt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($list_students) > 0): ?>
                            <?php while($s = mysqli_fetch_assoc($list_students)): ?>
                            <tr class="text-center">
                                <td class="text-start ps-4">
                                    <div class="fw-bold"><?= htmlspecialchars($s['name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($s['email']) ?></small>
                                </td>
                                <td>
                                    <span class="type-badge <?= $s['type'] == 'Chính quy' ? 'bg-info-subtle text-info' : 'bg-warning-subtle text-warning' ?>">
                                        <?= $s['type'] ?>
                                    </span>
                                </td>
                                <td>
                                    <input type="number" step="0.1" name="scores[<?= $s['id'] ?>]" 
                                           class="form-control text-center border-0 bg-light fw-bold" 
                                           value="<?= $s['score'] ?>" placeholder="-">
                                </td>
                                <td>
                                    <div class="form-check form-switch d-inline-block">
                                        <input type="checkbox" name="attendance[<?= $s['id'] ?>]" 
                                               class="form-check-input" <?= $s['status'] == 'present' ? 'checked' : '' ?>>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="py-5 text-center text-muted">Chưa có sinh viên nào đăng ký học phần này.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</div>
</body>
</html>