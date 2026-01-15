<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// 1. KIỂM TRA QUYỀN TRUY CẬP
if (!isset($_SESSION['authenticated']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username']; 
$msg = ""; $error = "";

// 2. LẤY THÔNG TIN CHI TIẾT SINH VIÊN
$stu_query = $conn->prepare("
    SELECT s.*, c.class_name, c.teacher_name 
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.class_id 
    WHERE s.email = ? OR (SELECT username FROM users WHERE id = s.user_id) = ?
");
$stu_query->bind_param("ss", $username, $username);
$stu_query->execute();
$student = $stu_query->get_result()->fetch_assoc();
$student_id = $student['student_id'];

// 3. XỬ LÝ ĐĂNG KÝ MÔN HỌC (Khi nhấn nút Đăng ký)
if (isset($_POST['btn_register'])) {
    $subject_id = (int)$_POST['subject_id'];
    
    // Kiểm tra xem đã đăng ký chưa
    $check_reg = $conn->prepare("SELECT grade_id FROM grades WHERE student_id = ? AND subject_id = ?");
    $check_reg->bind_param("ii", $student_id, $subject_id);
    $check_reg->execute();
    
    if ($check_reg->get_result()->num_rows > 0) {
        $error = "Bạn đã đăng ký môn học này rồi!";
    } else {
        $reg_query = $conn->prepare("INSERT INTO grades (student_id, subject_id, score) VALUES (?, ?, NULL)");
        $reg_query->bind_param("ii", $student_id, $subject_id);
        if ($reg_query->execute()) {
            $msg = "Đăng ký môn học thành công!";
        } else {
            $error = "Có lỗi xảy ra khi đăng ký.";
        }
    }
}

// 4. LẤY DANH SÁCH MÔN HỌC CHƯA ĐĂNG KÝ (Để hiển thị trong form đăng ký)
$sql_available = "SELECT * FROM subjects WHERE subject_id NOT IN (SELECT subject_id FROM grades WHERE student_id = ?)";
$stmt_avai = $conn->prepare($sql_available);
$stmt_avai->bind_param("i", $student_id);
$stmt_avai->execute();
$available_subjects = $stmt_avai->get_result()->fetch_all(MYSQLI_ASSOC);

// 5. LẤY BẢNG ĐIỂM CHI TIẾT (Các môn đã đăng ký)
$sql_results = "
    SELECT sj.subject_code, sj.subject_name, g.score 
    FROM subjects sj
    INNER JOIN grades g ON sj.subject_id = g.subject_id
    WHERE g.student_id = ?
    ORDER BY sj.subject_name ASC";
$stmt_res = $conn->prepare($sql_results);
$stmt_res->bind_param("i", $student_id);
$stmt_res->execute();
$results_data = $stmt_res->get_result()->fetch_all(MYSQLI_ASSOC);

// 6. TÍNH TOÁN GPA
$total_score = 0; $count = 0;
foreach($results_data as $r) {
    if($r['score'] !== null) {
        $total_score += $r['score'];
        $count++;
    }
}
$gpa = $count > 0 ? round($total_score / $count, 2) : 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cổng sinh viên - Đăng ký học phần</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Inter', sans-serif; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .info-label { font-size: 0.75rem; color: #6c757d; text-transform: uppercase; font-weight: bold; }
        .info-value { font-weight: 600; color: #212529; display: block; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="container py-4">
    <?php if($msg): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= $msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card card-custom p-4 mb-4">
                <h5 class="fw-bold mb-3">Hồ sơ sinh viên</h5>
                <label class="info-label">Họ tên</label>
                <span class="info-value"><?= htmlspecialchars($student['full_name']) ?></span>
                
                <label class="info-label">Lớp & GVCN</label>
                <span class="info-value"><?= $student['class_name'] ?> - <?= $student['teacher_name'] ?></span>
                
                <label class="info-label">GPA Tích lũy</label>
                <span class="info-value text-primary fs-4"><?= number_format($gpa, 2) ?></span>
                
                <hr>
                <label class="info-label">Liên hệ</label>
                <span class="info-value small"><?= $student['email'] ?> | <?= $student['phone'] ?></span>
            </div>

            <div class="card card-custom p-4 border-top border-primary border-4">
                <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-plus-circle me-2"></i>Đăng ký môn học</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Chọn học phần</label>
                        <select name="subject_id" class="form-select" required>
                            <option value="">-- Danh sách môn mở --</option>
                            <?php foreach($available_subjects as $sub): ?>
                                <option value="<?= $sub['subject_id'] ?>">
                                    <?= $sub['subject_code'] ?> - <?= $sub['subject_name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="btn_register" class="btn btn-primary w-100 fw-bold">
                        XÁC NHẬN ĐĂNG KÝ
                    </button>
                </form>
                <?php if(empty($available_subjects)): ?>
                    <p class="text-muted small mt-2 text-center">Bạn đã đăng ký hết các môn hiện có.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card card-custom overflow-hidden">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">Môn học đã đăng ký & Kết quả</h5>
                    <a href="../public/logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">Đăng xuất</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr class="small text-muted text-uppercase">
                                <th class="ps-4">Mã môn</th>
                                <th>Tên môn học</th>
                                <th class="text-center">Điểm</th>
                                <th class="text-center pe-4">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($results_data as $row): ?>
                            <tr>
                                <td class="ps-4 small fw-bold text-muted"><?= $row['subject_code'] ?></td>
                                <td class="fw-bold"><?= $row['subject_name'] ?></td>
                                <td class="text-center fw-bold">
                                    <?= ($row['score'] !== null) ? number_format($row['score'], 1) : '<span class="badge bg-secondary-subtle text-secondary fw-normal">Đợi điểm</span>' ?>
                                </td>
                                <td class="text-center pe-4">
                                    <?php if($row['score'] === null): ?>
                                        <span class="text-primary small fw-bold">Đang học</span>
                                    <?php elseif($row['score'] >= 4.0): ?>
                                        <span class="badge bg-success-subtle text-success border border-success px-3">Đạt</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger px-3">Học lại</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($results_data)): ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted">Chưa có môn học nào được đăng ký.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>