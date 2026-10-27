<?php
require_once "config/auth.php";
auth_require_login();

require_once "config/database.php";
require_once "classes/Attendance.php";
require_once "classes/Subject.php";

$database = new Database();
$db = $database->connect();

$attendance = new Attendance($db, $_SESSION['user_id']);
$subject = new Subject($db);
$subjects = $subject->getSubjects();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!auth_verify_csrf_token($_POST['csrf_token'] ?? null)) {
        header("Location: index.php?status=error");
        exit;
    }

    $photoPath = null;
    if (!empty($_FILES['photo']['name'])) {
        $uploadDir = __DIR__ . '/uploads/';
        $fileName = time() . '_' . basename($_FILES['photo']['name']);
        $targetPath = $uploadDir . $fileName;

        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed) && move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
            $photoPath = 'uploads/' . $fileName;
        }
    }

    
    $attendance->subject_id = $_POST['subject_id'];
    $attendance->attendance_date = $_POST['attendance_date'];
    $attendance->status = $_POST['status'];
    $attendance->reason = $_POST['reason'] ?? '';
    $attendance->photo_path = $photoPath;

    if ($attendance->addAttendance()) {
        header("Location: index.php?status=added");
        exit;
    } else {
        header("Location: index.php?status=error");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Attendance — Classync</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="navbar">
    <div class="brand">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Student', ENT_QUOTES, 'UTF-8'); ?></h1>
        <span>Your personal attendance dashboard</span>
    </div>
    <form class="logout-form" method="POST" action="logout.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(auth_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <button class="btn secondary small" type="submit">Sign out</button>
    </form>
</div>

<div class="container">
    <a class="back-link" href="index.php">&larr; Back to records</a>

    <div class="card">
        <h2>Add Attendance Record</h2>
        <form method="POST" action="add.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(auth_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

            <div class="field">
                <label for="subject_id">Subject</label>
                <select name="subject_id" id="subject_id" required>
                    <option value="">-- Select subject --</option>
                    <?php while ($s = $subjects->fetch(PDO::FETCH_ASSOC)): ?>
                        <option value="<?php echo $s['subject_id']; ?>">
                            <?php echo htmlspecialchars($s['subject_code'] . ' — ' . $s['subject_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="field">
                <label for="attendance_date">Date</label>
                <input type="date" name="attendance_date" id="attendance_date" required>
            </div>

            <div class="field">
                <label for="status">Status</label>
                <select name="status" id="status" required>
                    <option value="Present">Present</option>
                    <option value="Absent">Absent</option>
                </select>
            </div>

            <div class="field">
                <label for="reason">Reason (only needed if Absent)</label>
                <textarea name="reason" id="reason" rows="3" placeholder="e.g. Heavy flooding, medical appointment..."></textarea>
            </div>

            <div class="field">
                <label for="photo">Photo Proof (optional)</label>
                <input type="file" name="photo" id="photo" accept="image/*">
            </div>

            <button class="btn" type="submit">Save Record</button>
            <a class="btn secondary" href="index.php">Cancel</a>
        </form>
    </div>
</div>

</body>
</html>
