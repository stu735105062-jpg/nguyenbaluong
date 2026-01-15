<?php
$conn = mysqli_connect("localhost", "root", "", "student_management");
if (!$conn) { die("Kết nối thất bại"); }
mysqli_set_charset($conn, "utf8mb4");

// Tên file khi tải về
$filename = "Danh_sach_sinh_vien_" . date('Ymd_His') . ".xls";

// Header để trình duyệt hiểu đây là file Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

// SQL đã loại bỏ s.birthday để tránh lỗi Fatal Error
$sql = "SELECT s.student_id, s.full_name, s.email, s.phone, s.address, c.class_name 
        FROM students s 
        LEFT JOIN classes c ON s.class_id = c.class_id 
        ORDER BY s.student_id DESC";
        
$result = mysqli_query($conn, $sql);
?>

<meta charset="utf-8">
<table border="1">
    <thead>
        <tr style="background-color: #4361ee; color: #ffffff; font-weight: bold;">
            <th>Mã SV</th>
            <th>Họ và Tên</th>
            <th>Email</th>
            <th>Số điện thoại</th>
            <th>Lớp học</th>
            <th>Địa chỉ</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td style="text-align: center;"><?= $row['student_id'] ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td style="text-align: center;"><?= htmlspecialchars($row['class_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($row['address']) ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align: center;">Không có dữ liệu sinh viên.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>