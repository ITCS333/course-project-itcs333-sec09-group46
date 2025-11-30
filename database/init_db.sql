CREATE DATABASE IF NOT EXISTS itcs333_course CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE itcs333_course;


-- جدول المستخدمين
CREATE TABLE IF NOT EXISTS users (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(100) NOT NULL,
student_id VARCHAR(50),
email VARCHAR(150) NOT NULL UNIQUE,
password VARCHAR(255) NOT NULL,
role ENUM('admin','student') NOT NULL DEFAULT 'student',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- جدول تعليقات (للاحق)
CREATE TABLE IF NOT EXISTS comments (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
resource_type VARCHAR(50) DEFAULT 'resource',
resource_id INT DEFAULT NULL,
comment TEXT NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- مثال حساب مشرف
INSERT INTO users (name, student_id, email, password, role)
VALUES ('Course Admin', 'T001', 'admin@course.local', '" + md5("CS333-group46") + "', 'admin');