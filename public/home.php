<?php
session_start();

/* =====================
    1. KẾT NỐI DATABASE
===================== */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = mysqli_connect("localhost", "root", "", "student_management");
mysqli_set_charset($conn, "utf8mb4");

/* =====================
    2. KIỂM TRA ĐĂNG NHẬP & PHÂN QUYỀN
===================== */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$user_res = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_res);

if (!$user || $user['role'] === 'student') {
    if ($user['role'] === 'student') {
        header("Location: ../models/student.php");
    } else {
        session_destroy();
        header("Location: login.php");
    }
    exit();
}

$role = $user['role']; 
$username = $user['username'];

/* ===========================
    3. DATA: THỐNG KÊ
=========================== */
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM students"))['total'];
$total_classes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM classes"))['total'];
$total_subjects = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM subjects"))['total'];

$students_query = "SELECT s.full_name, ROUND(AVG(g.score), 2) AS avg_score 
                    FROM students s 
                    INNER JOIN grades g ON s.student_id = g.student_id 
                    GROUP BY s.student_id 
                    ORDER BY avg_score DESC 
                    LIMIT 10";
$students_res = mysqli_query($conn, $students_query);
$scoreLabels = []; $scoreValues = [];
while($row = mysqli_fetch_assoc($students_res)){
    $scoreLabels[] = $row['full_name'];
    $scoreValues[] = $row['avg_score'];
}

$att_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as p, 
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as a 
    FROM attendance"));
$present = (int)($att_data['p'] ?? 0); 
$absent = (int)($att_data['a'] ?? 0);
?>

<!DOCTYPE html>
<html lang="vi" id="htmlTag" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SMS PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --sidebar-bg: #4361ee; }
        body { background: #f1f3f9; font-family: 'Inter', sans-serif; }
        .sidebar { width: 260px; height: 100vh; position: fixed; background: var(--sidebar-bg); color: white; z-index: 1000; }
        .main { margin-left: 260px; padding: 2.5rem; transition: all 0.3s; }
        .nav-link { color: rgba(255,255,255,0.8); border-radius: 10px; margin-bottom: 5px; transition: 0.2s; padding: 0.8rem 1rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.2); color: white; }
        .admin-section { border-top: 1px solid rgba(255,255,255,0.1); margin-top: 15px; padding-top: 15px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); }
        .stat-icon { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body>

<div class="sidebar p-4 d-flex flex-column">
    <div class="text-center mb-5">
        <h3 class="fw-bold mb-0 text-white"><i class="bi bi-mortarboard-fill me-2"></i>SMS PRO</h3>
    </div>

    <ul class="nav flex-column flex-grow-1">
        <li class="nav-item"><a class="nav-link active" href="home.php"><i class="bi bi-grid-1x2 me-2"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="../models/manage_grades.php"><i class="bi bi-book me-2"></i>Quản Lý Môn Học</a></li>
        <li class="nav-item"><a class="nav-link" href="add_student.php"><i class="bi bi-people me-2"></i> Sinh viên</a></li>
        <li class="nav-item"><a class="nav-link" href="export.php"><i class="bi bi-award me-2"></i> Điểm số</a></li>

        <?php if ($role === 'admin'): ?>
        <div class="admin-section">
            <small class="text-uppercase opacity-50 px-3 mb-2 d-block" style="font-size: 0.7rem; letter-spacing: 1px;">Quản trị hệ thống</small>
            <li class="nav-item"><a class="nav-link" href="permissions.php"><i class="bi bi-shield-lock me-2"></i> Phân quyền người dùng</a></li>
        </div>
        <?php endif; ?>
    </ul>

    <div class="mt-auto">
        <div class="bg-white bg-opacity-10 p-3 rounded-4 mb-3">
            <div class="fw-bold text-truncate text-white"><?= htmlspecialchars($username) ?></div>
            <span class="badge bg-warning text-dark small mt-1"><?= strtoupper($role) ?></span>
        </div>
        <a href="logout.php" class="btn btn-danger w-100 rounded-pill shadow-sm">Đăng xuất</a>
    </div>
</div>

<div class="main">
    <header class="mb-5">
        <h2 class="fw-bold text-dark">Bảng điều khiển</h2>
        <p class="text-muted">Chào mừng quay trở lại, <b><?= htmlspecialchars($username) ?></b>.</p>
    </header>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <a href="export.php?type=students" class="text-decoration-none text-dark">
                <div class="card p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-4 text-primary me-3 stat-icon"><i class="bi bi-people fs-3"></i></div>
                        <div><h3 class="fw-bold mb-0"><?= number_format($total_students) ?></h3><small class="text-muted text-uppercase">Sinh viên</small></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="export.php?type=classes" class="text-decoration-none text-dark">
                <div class="card p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-success bg-opacity-10 p-3 rounded-4 text-success me-3 stat-icon"><i class="bi bi-door-open fs-3"></i></div>
                        <div><h3 class="fw-bold mb-0"><?= number_format($total_classes) ?></h3><small class="text-muted text-uppercase">Lớp học</small></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="export.php?type=subjects" class="text-decoration-none text-dark">
                <div class="card p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning bg-opacity-10 p-3 rounded-4 text-warning me-3 stat-icon"><i class="bi bi-journal-text fs-3"></i></div>
                        <div><h3 class="fw-bold mb-0"><?= number_format($total_subjects) ?></h3><small class="text-muted text-uppercase">Môn học</small></div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card p-4">
                <h6 class="fw-bold mb-4 text-primary"><i class="bi bi-bar-chart-line me-2"></i>Top 10 sinh viên xuất sắc</h6>
                <div style="height: 350px;"><canvas id="barChart"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card p-4 h-100 text-center">
                <h6 class="fw-bold mb-4 text-primary"><i class="bi bi-pie-chart me-2"></i>Tỉ lệ điểm danh</h6>
                <div style="height: 250px;"><canvas id="pieChart"></canvas></div>
                <div class="mt-4 d-flex justify-content-center gap-4">
                    <small><i class="bi bi-circle-fill text-success me-1"></i> Có mặt (<?= $present ?>)</small>
                    <small><i class="bi bi-circle-fill text-danger me-1"></i> Vắng (<?= $absent ?>)</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const ctxBar = document.getElementById('barChart');
new Chart(ctxBar, {
    type: 'bar',
    data: {
        labels: <?= json_encode($scoreLabels) ?>,
        datasets: [{
            label: 'Điểm trung bình',
            data: <?= json_encode($scoreValues) ?>,
            backgroundColor: 'rgba(67, 97, 238, 0.8)',
            borderColor: '#4361ee',
            borderWidth: 1,
            borderRadius: 8,
            barThickness: 30
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { 
            y: { beginAtZero: true, max: 10, grid: { drawBorder: false, color: '#f0f0f0' } },
            x: { grid: { display: false } }
        }
    }
});

const ctxPie = document.getElementById('pieChart');
new Chart(ctxPie, {
    type: 'doughnut',
    data: {
        labels: ['Có mặt', 'Vắng'],
        datasets: [{
            data: [<?= $present ?>, <?= $absent ?>],
            backgroundColor: ['#1cc88a', '#e74a3b'],
            hoverOffset: 10,
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '75%',
        plugins: { legend: { display: false } }
    }
});
</script>
</body>
</html>