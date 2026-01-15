<?php
session_start();

/* =====================================================
   1. KẾT NỐI & CẤU HÌNH
===================================================== */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = mysqli_connect('localhost', 'root', '', 'student_management');
$conn->set_charset('utf8mb4');

/* =====================================================
   2. KHỞI TẠO BIẾN & KIỂM TRA ĐIỀU KIỆN ĐẦU VÀO
===================================================== */
$class_id = filter_input(INPUT_GET, 'class_id', FILTER_VALIDATE_INT) ?: 0;
$subject_id = filter_input(INPUT_GET, 'subject_id', FILTER_VALIDATE_INT) ?: 0;
$message = ['type' => '', 'content' => ''];

if ($class_id <= 0) {
    die("Lỗi: Yêu cầu mã lớp học hợp lệ.");
}

/* =====================================================
   3. XỬ LÝ CẬP NHẬT ĐIỂM (POST)
===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_save_scores'])) {
    $scores = $_POST['scores'] ?? [];
    
    // Bắt đầu Transaction để đảm bảo an toàn dữ liệu
    $conn->begin_transaction();
    try {
        $sql_upsert = "INSERT INTO grades (student_id, subject_id, score) 
                       VALUES (?, ?, ?) 
                       ON DUPLICATE KEY UPDATE score = VALUES(score)";
        $stmt_save = $conn->prepare($sql_upsert);

        foreach ($scores as $student_id => $score) {
            if ($score === '' || $score === null) continue;
            
            $score_val = floatval($score);
            $student_id_val = intval($student_id);
            
            $stmt_save->bind_param("iid", $student_id_val, $subject_id, $score_val);
            $stmt_save->execute();
        }
        
        $conn->commit();
        $message = ['type' => 'success', 'content' => '✅ Bảng điểm đã được cập nhật thành công!'];
    } catch (Exception $e) {
        $conn->rollback();
        $message = ['type' => 'danger', 'content' => '❌ Lỗi hệ thống: ' . $e->getMessage()];
    }
}

/* =====================================================
   4. LẤY DỮ LIỆU HIỂN THỊ
===================================================== */
// A. Lấy thông tin lớp
$class_stmt = $conn->prepare("SELECT class_name FROM classes WHERE class_id = ?");
$class_stmt->bind_param("i", $class_id);
$class_stmt->execute();
$class_info = $class_stmt->get_result()->fetch_assoc();
if (!$class_info) die("Lớp học không tồn tại.");

// B. Lấy danh sách môn học
$subjects = $conn->query("SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC")->fetch_all(MYSQLI_ASSOC);
if (!$subject_id && !empty($subjects)) {
    $subject_id = $subjects[0]['subject_id'];
}

// C. Lấy danh sách sinh viên và điểm môn hiện tại
$sql_list = "SELECT s.student_id, s.full_name, s.email, g.score 
             FROM students s 
             LEFT JOIN grades g ON s.student_id = g.student_id AND g.subject_id = ? 
             WHERE s.class_id = ? 
             ORDER BY s.full_name ASC";
$stmt_list = $conn->prepare($sql_list);
$stmt_list->bind_param("ii", $subject_id, $class_id);
$stmt_list->execute();
$students = $stmt_list->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhập điểm: <?= htmlspecialchars($class_info['class_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary-color: #4361ee; }
        body { background-color: #f1f3f9; font-family: 'Inter', sans-serif; }
        .grade-card { border: none; border-radius: 12px; overflow: hidden; }
        .table-header { background-color: var(--primary-color); color: white; }
        .score-input { width: 100px; margin: auto; transition: all 0.2s; }
        .score-input:focus { border-color: var(--primary-color); box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15); }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Quản lý điểm lớp: <?= htmlspecialchars($class_info['class_name']) ?></h1>
            <p class="text-muted small">Cập nhật điểm số định kỳ cho sinh viên</p>
        </div>
        <a href="manage_classes.php" class="btn btn-light border rounded-pill px-4">
            <i class="bi bi-chevron-left"></i> Quay lại
        </a>
    </div>

    <?php if ($message['content']): ?>
        <div class="alert alert-<?= $message['type'] ?> alert-dismissible fade show border-0 shadow-sm" role="alert">
            <?= $message['content'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card grade-card shadow-sm mb-4">
        <div class="card-body bg-white p-4">
            <form method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="class_id" value="<?= $class_id ?>">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Chọn học phần</label>
                    <select name="subject_id" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($subjects as $sub): ?>
                            <option value="<?= $sub['subject_id'] ?>" <?= $sub['subject_id'] == $subject_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sub['subject_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <form method="POST">
        <div class="card grade-card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-header">
                        <tr>
                            <th class="ps-4">Thông tin sinh viên</th>
                            <th>Email</th>
                            <th class="text-center" style="width: 200px;">Điểm số (0-10)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($students) > 0): ?>
                            <?php foreach ($students as $s): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold"><?= htmlspecialchars($s['full_name']) ?></div>
                                        <small class="text-muted">MS: SV-<?= $s['student_id'] ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($s['email']) ?></td>
                                    <td>
                                        <input type="number" 
                                               step="0.01" min="0" max="10" 
                                               name="scores[<?= $s['student_id'] ?>]" 
                                               value="<?= $s['score'] ?>" 
                                               class="form-control text-center score-input fw-bold"
                                               placeholder="-">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-5 text-muted">Không tìm thấy sinh viên nào trong lớp.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if (!empty($students)): ?>
            <div class="card-footer bg-white p-4 border-top-0 text-end">
                <button type="submit" name="btn_save_scores" class="btn btn-primary btn-lg px-5 rounded-pill shadow-sm">
                    <i class="bi bi-cloud-arrow-up me-2"></i> Xác nhận lưu điểm
                </button>
            </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>