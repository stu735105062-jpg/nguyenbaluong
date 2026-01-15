-- 1. Xóa database cũ để làm mới hoàn toàn
DROP DATABASE IF EXISTS `student_management`;

-- 2. Tạo mới database với mã hóa chuẩn Tiếng Việt
CREATE DATABASE `student_management` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `student_management`;

-- 3. Bảng users: Quản lý tài khoản đăng nhập
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Bảng classes: Danh sách lớp học (E1, E2, E3)
CREATE TABLE `classes` (
  `class_id` int NOT NULL AUTO_INCREMENT,
  `class_name` varchar(50) NOT NULL UNIQUE,
  `teacher_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Bảng subjects: Danh mục môn học
CREATE TABLE `subjects` (
  `subject_id` int NOT NULL AUTO_INCREMENT,
  `subject_code` varchar(50) NOT NULL UNIQUE,
  `subject_name` varchar(255) NOT NULL,
  PRIMARY KEY (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Bảng students: Thông tin chi tiết sinh viên
CREATE TABLE `students` (
  `student_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL UNIQUE,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `class_id` int DEFAULT NULL,
  PRIMARY KEY (`student_id`),
  CONSTRAINT `fk_std_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_std_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Bảng grades: Lưu điểm số (Chống lặp: 1 SV - 1 Môn chỉ có 1 dòng điểm)
CREATE TABLE `grades` (
  `grade_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `score` decimal(4,2) DEFAULT NULL,
  PRIMARY KEY (`grade_id`),
  UNIQUE KEY `unique_student_subject` (`student_id`, `subject_id`),
  CONSTRAINT `fk_grd_std` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_grd_sub` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Bảng attendance: Điểm danh (Chống lặp: 1 SV - 1 Môn - 1 Ngày chỉ có 1 bản ghi)
CREATE TABLE `attendance` (
  `attendance_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('present','absent') NOT NULL,
  PRIMARY KEY (`attendance_id`),
  UNIQUE KEY `unique_att_day` (`student_id`, `subject_id`, `attendance_date`),
  CONSTRAINT `fk_att_std` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_att_sub` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- DỮ LIỆU CƠ BẢN (SEED DATA)
-- --------------------------------------------------------

-- Thêm tài khoản Admin mặc định (Pass: 123456)
INSERT INTO `users` (`username`, `password`, `role`) VALUES ('admin', '123456', 'admin');

-- Thêm 3 lớp học theo yêu cầu
INSERT INTO `classes` (`class_name`, `teacher_name`) VALUES 
('E1', 'Giảng viên Nguyễn Văn A'),
('E2', 'Giảng viên Trần Thị B'),
('E3', 'Giảng viên Lê Văn C');

-- Thêm một số môn học mẫu
INSERT INTO `subjects` (`subject_code`, `subject_name`) VALUES 
('COMP251', 'Phân tích thiết kế hệ thống'),
('JAVA201', 'Lập trình Java Cơ bản'),
('WEB101', 'Thiết kế Web (HTML/CSS)');