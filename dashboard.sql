USE classync_db;

ALTER TABLE attendance
    ADD COLUMN user_id INT DEFAULT NULL,
    ADD INDEX idx_attendance_user_id (user_id),
    ADD CONSTRAINT fk_attendance_user
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE;

UPDATE attendance
SET user_id = (SELECT user_id FROM users LIMIT 1)
WHERE user_id IS NULL
  AND (SELECT COUNT(*) FROM users) = 1;
