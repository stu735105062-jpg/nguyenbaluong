<?php
session_start();
// 1. KẾT NỐI DATABASE
$conn = mysqli_connect('localhost', 'root', '', 'student_management');
if (!$conn) { die("Lỗi kết nối DB: " . mysqli_connect_error()); }
mysqli_set_charset($conn, "utf8mb4");

// 2. LẤY THÔNG TIN MÔN HỌC
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$stmt_sub = $conn->prepare("SELECT * FROM subjects WHERE subject_id = ?");
$stmt_sub->bind_param("i", $subject_id);
$stmt_sub->execute();
$subject_info = $stmt_sub->get_result()->fetch_assoc();

if (!$subject_info) { die("<div class='container mt-5 alert alert-danger'>Môn học không tồn tại!</div>"); }

$msg = "";
$today = date('Y-m-d');

// 3. XỬ LÝ LƯU DỮ LIỆU
if (isset($_POST['btn_update'])) {
    foreach ($_POST['students'] as $student_id => $values) {
        $score = ($values['score'] !== "") ? (float)$values['score'] : null;
        $status = isset($values['attendance']) ? $values['attendance'] : "";

        // Cập nhật điểm số (Chỉ cập nhật vì sinh viên đã phải có bản ghi trong grades mới hiện ở danh sách này)
        $stmt_grade = $conn->prepare("UPDATE grades SET score = ? WHERE student_id = ? AND subject_id = ?");
        $stmt_grade->bind_param("dii", $score, $student_id, $subject_id);
        $stmt_grade->execute();

        // Cập nhật điểm danh
        if ($status !== "") {
            $stmt_att = $conn->prepare("INSERT INTO attendance (student_id, subject_id, attendance_date, status) 
                                       VALUES (?, ?, ?, ?) 
                                       ON DUPLICATE KEY UPDATE status = VALUES(status)");
            $stmt_att->bind_param("iiss", $student_id, $subject_id, $today, $status);
            $stmt_att->execute();
        }
    }
    $msg = "<div class='alert alert-success shadow-sm animate__animated animate__fadeIn'>🎉 Đã lưu thay đổi thành công!</div>";
}

// 4. TRUY VẤN: CHỈ LẤY SINH VIÊN ĐÃ ĐĂNG KÝ MÔN NÀY
// Sử dụng INNER JOIN với bảng grades để lọc
$query = "SELECT 
            c.class_name, 
            si.student_id, 
            si.full_name, 
            g.score, 
            att.status as att_status
          FROM grades g
          INNER JOIN students si ON g.student_id = si.student_id
          LEFT JOIN classes c ON si.class_id = c.class_id
          LEFT JOIN (
              SELECT student_id, status 
              FROM attendance 
              WHERE subject_id = ? AND attendance_date = ?
          ) att ON si.student_id = att.student_id
          WHERE g.subject_id = ?
          ORDER BY c.class_name ASC, si.full_name ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("isi", $subject_id, $today, $subject_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $className = $row['class_name'] ?: 'Chưa phân lớp';
    $data[$className][] = $row;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý lớp học: <?= htmlspecialchars($subject_info['subject_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sticky-header { position: sticky; top: 0; z-index: 1020; background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); padding: 15px 0; border-bottom: 2px solid #4e73df; }
        .card-table { border: none; border-radius: 12px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); background: white; }
        .class-divider { background: #eaecf4; color: #4e73df; font-weight: 800; font-size: 0.85rem; letter-spacing: 0.05em; }
        .score-input { width: 80px; text-align: center; border-color: #d1d3e2; border-radius: 5px; }
        .score-input:focus { border-color: #4e73df; box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25); }
        .form-check-input:checked[value="present"] { background-color: #198754; border-color: #198754; }
        .form-check-input:checked[value="absent"] { background-color: #dc3545; border-color: #dc3545; }
    </style>
</head>
<body>

<div class="sticky-header mb-4">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-0 fw-bold text-primary text-uppercase"><?= htmlspecialchars($subject_info['subject_name']) ?></h4>
            <div class="text-muted small"><i class="bi bi-calendar3 me-1"></i>Hôm nay: <?= date('d/m/Y') ?></div>
        </div>
        <div class="d-flex gap-2">
            <a href="manage_grades.php" class="btn btn-outline-secondary px-4 rounded-pill">Quay lại</a>
            <button type="submit" form="mainForm" name="btn_update" class="btn btn-primary px-4 rounded-pill shadow-sm">
                <i class="bi bi-save me-2"></i>Lưu dữ liệu
            </button>
        </div>
    </div>
</div>

<div class="container mb-5">
    <?= $msg ?>
    
    <div class="card card-table overflow-hidden">
        <form id="mainForm" method="POST">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr class="text-center">
                            <th width="10%">MÃ SV</th>
                            <th width="30%" class="text-start">HỌ TÊN SINH VIÊN</th>
                            <th width="20%">ĐIỂM SỐ (0-10)</th>
                            <th width="40%">ĐIỂM DANH HÔM NAY</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <i class="bi bi-people fs-1 text-muted d-block mb-2"></i>
                                    <span class="text-muted">Chưa có sinh viên nào đăng ký môn học này.</span>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($data as $className => $students): ?>
                            <tr class="class-divider">
                                <td colspan="4" class="ps-4 py-2 uppercase">LỚP: <?= htmlspecialchars($className) ?></td>
                            </tr>
                            <?php foreach ($students as $s): ?>
                                <tr class="text-center">
                                    <td class="text-muted font-monospace">#<?= str_pad($s['student_id'], 4, '0', STR_PAD_LEFT) ?></td>
                                    <td class="text-start fw-bold"><?= htmlspecialchars($s['full_name']) ?></td>
                                    <td>
                                        <input type="number" step="0.1" min="0" max="10" 
                                               name="students[<?= $s['student_id'] ?>][score]" 
                                               value="<?= $s['score'] ?>" 
                                               class="form-control form-control-sm mx-auto score-input"
                                               placeholder="-">
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="students[<?= $s['student_id'] ?>][attendance]" value="present" <?= ($s['att_status'] == 'present') ? 'checked' : '' ?> id="p<?= $s['student_id'] ?>">
                                                <label class="form-check-label text-success fw-bold" for="p<?= $s['student_id'] ?>">Có mặt</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="students[<?= $s['student_id'] ?>][attendance]" value="absent" <?= ($s['att_status'] == 'absent') ? 'checked' : '' ?> id="a<?= $s['student_id'] ?>">
                                                <label class="form-check-label text-danger fw-bold" for="a<?= $s['student_id'] ?>">Vắng mặt</label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>