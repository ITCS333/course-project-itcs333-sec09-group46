CREATE DATABASE IF NOT EXISTS course_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE course_db;

-- جدول الأدمن (teachers/admin)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- أدمن افتراضي
-- username: admin
-- password: admin123
INSERT INTO users (username, password)
VALUES ('admin', MD5('admin123'));

-- جدول الطلاب
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    UNIQUE (student_id),
    UNIQUE (email)
);
