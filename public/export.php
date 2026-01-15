<?php
session_start();

// 1. KẾT NỐI CƠ SỞ DỮ LIỆU
$conn = mysqli_connect('localhost', 'root', '', 'student_management');
if (!$conn) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");

// 2. NHẬN LOẠI DỮ LIỆU CẦN XUẤT (Nhận từ Dashboard)
$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$title = "BÁO CÁO TỔNG HỢP";

// Tùy biến truy vấn dựa trên tham số type
switch ($type) {
    case 'students':
        $title = "DANH SÁCH CHI TIẾT SINH VIÊN";
        break;
    case 'classes':
        $title = "THỐNG KÊ ĐIỂM THEO LỚP HỌC";
        break;
    case 'subjects':
        $title = "BẢNG ĐIỂM THEO MÔN HỌC";
        break;
}

// 3. CẤU HÌNH FILE EXCEL
$filename = "SMS_Export_" . $type . "_" . date('Ymd_His') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Expires: 0");

// 4. TRUY VẤN DỮ LIỆU (Đã đồng bộ tên bảng với Dashboard)
$query = "SELECT 
            s.student_id, 
            s.full_name AS student_name, 
            c.class_name, 
            sub.subject_name AS course_name,
            COALESCE(g.score, 0) AS score,
            (SELECT COUNT(*) FROM attendance WHERE student_id = s.student_id AND status = 'present') AS total_present
          FROM students s 
          LEFT JOIN classes c ON s.class_id = c.class_id
          LEFT JOIN grades g ON s.student_id = g.student_id
          LEFT JOIN subjects sub ON g.subject_id = sub.subject_id
          ORDER BY s.student_id ASC";

$res = mysqli_query($conn, $query);
?>

<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        .table-style { border: 0.5pt solid #000000; }
        .table-style th { background-color: #4361ee; color: #ffffff; font-weight: bold; border: 0.5pt solid #000000; }
        .table-style td { border: 0.5pt solid #000000; }
        .header-title { font-size: 16pt; font-weight: bold; color: #4361ee; text-align: center; }
        .bad-score { color: #ff0000; } 
        .good-score { color: #008000; }
    </style>
</head>
<body>

<table>
    <tr>
        <td colspan="6" class="header-title"><?php echo $title; ?></td>
    </tr>
    <tr>
        <td colspan="6" style="text-align: center;">Ngày xuất: <?= date('d/m/Y H:i') ?></td>
    </tr>
    <tr><td></td></tr>
</table>

<table class="table-style" border="1">
    <thead>
        <tr>
            <th style="width: 50px;">ID</th>
            <th style="width: 200px;">Họ và Tên</th>
            <th style="width: 100px;">Lớp Học</th>
            <th style="width: 150px;">Tên Môn Học</th>
            <th style="width: 80px;">Điểm Số</th>
            <th style="width: 120px;">Chuyên Cần</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($res && mysqli_num_rows($res) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($res)): ?>
                <tr>
                    <td style="text-align: center;"><?php echo $row['student_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                    <td style="text-align: center;"><?php echo htmlspecialchars($row['class_name'] ?? 'Chưa xếp lớp'); ?></td>
                    <td><?php echo htmlspecialchars($row['course_name'] ?? 'N/A'); ?></td>
                    
                    <td style="text-align: center; font-weight: bold;" class="<?php echo ($row['score'] < 5) ? 'bad-score' : 'good-score'; ?>">
                        <?php echo number_format((float)$row['score'], 2); ?>
                    </td>
                    
                    <td style="text-align: center;">
                        <?php echo $row['total_present']; ?> buổi
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px;">Không có dữ liệu phù hợp.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>