# Classync — Attendance CRUD System (Midterm)

PHP + MySQL (PDO, OOP) CRUD web app built from the Classync persona/ERD project.
Main entity: **Attendance**.

## Requirements
- XAMPP (Apache + MySQL/MariaDB + PHP)
- A modern browser

## Setup
1. Copy this whole `classync` folder into `C:\xampp\htdocs\` (Windows) or `/Applications/XAMPP/htdocs/` (Mac).
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin`, click **Import**, and import `database/classync.sql`.
   (This creates the `classync_db` database with `users`, `subjects`, and `attendance` tables, plus sample data.)
   If you already created the database before adding login, import `database/auth.sql`, `database/dashboard.sql`, and `database/profile.sql` into the selected `classync_db` database, in that order.
4. Visit `http://localhost/classync/login.php`, choose **Create one**, and register with your name, a unique username, email, and gender. Sign in with either your email or username.
   Attendance pages are available only after signing in. Passwords are stored as secure hashes; there is no Google sign-in.
   Each account has its own attendance dashboard and records. Existing records are assigned to the only existing account during migration, when there is exactly one; otherwise, unassigned records stay hidden to prevent sharing records between accounts.
   Existing accounts receive a default username (`user` plus their account ID) and a “Prefer not to say” gender value during the profile migration; users can use that username or their email to sign in.
   Existing installations should also import `database/transactions.sql` to create the activity log table used by the attendance transactions.
   Attendance create, update, and delete each run as a database transaction. Each transaction also writes its activity log entry; if either write fails, both writes roll back.

## Files
- `config/database.php` — PDO connection (edit if your MySQL user/password differs from XAMPP defaults)
- `classes/Attendance.php` — **main entity class**: addAttendance(), getAttendances(), updateAttendance($id), deleteAttendance($id), searchAttendances($keyword)
- `classes/Subject.php` — small helper class so the subject dropdown has data
- `index.php` — list + search page
- `login.php` / `register.php` — database-backed sign-in and account creation
- `logout.php` — signs out and clears the session
- `config/auth.php` — session and authentication helpers
- `database/auth.sql` — accounts-table migration for existing installations
- `database/dashboard.sql` — per-account attendance ownership migration for existing installations
- `database/profile.sql` — username and gender migration for existing installations
- `database/transactions.sql` — activity log table required by attendance transactions
- `add.php` — add record form
- `edit.php` — edit record form
- `delete.php` — delete handler
- `uploads/` — where uploaded photo proof files are stored

No Firebase is used — this app is 100% PHP + MySQL via PDO, run locally through XAMPP.
