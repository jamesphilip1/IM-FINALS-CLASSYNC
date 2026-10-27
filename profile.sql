USE classync_db;

ALTER TABLE users
    ADD COLUMN username VARCHAR(30) NULL,
    ADD COLUMN gender ENUM('Male', 'Female', 'Non-binary', 'Other', 'Prefer not to say')
        NOT NULL DEFAULT 'Prefer not to say';

UPDATE users
SET username = CONCAT('user', user_id)
WHERE username IS NULL;

ALTER TABLE users
    MODIFY COLUMN username VARCHAR(30) NOT NULL,
    ADD UNIQUE INDEX uq_users_username (username);
