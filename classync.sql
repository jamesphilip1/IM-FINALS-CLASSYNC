

CREATE DATABASE IF NOT EXISTS classync_db;
USE classync_db;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(30) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    gender ENUM('Male', 'Female', 'Non-binary', 'Other', 'Prefer not to say') NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS subjects (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    schedule VARCHAR(100) DEFAULT NULL
);


CREATE TABLE IF NOT EXISTS attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    subject_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('Present', 'Absent') NOT NULL,
    reason VARCHAR(255) DEFAULT NULL,
    photo_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_attendance_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type VARCHAR(10) NOT NULL,
    details VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_activity_logs_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

INSERT INTO subjects (subject_code, subject_name, schedule) VALUES
('IM2', 'Information Management 2', 'MWF 9:00-10:30 AM'),
('CC104', 'Application Development and Emerging Technologies', 'TTH 1:00-2:30 PM'),
('PATHFIT3', 'Physical Fitness 3', 'SAT 8:00-10:00 AM');

INSERT INTO attendance (subject_id, attendance_date, status, reason, photo_path) VALUES
(1, '2026-08-20', 'Present', NULL, NULL),
(1, '2026-08-22', 'Absent', 'Heavy flooding on the way to school', NULL),
(2, '2026-08-21', 'Present', NULL, NULL);
