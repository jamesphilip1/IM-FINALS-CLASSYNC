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

if (!isset($_GET['id']) && !isset($_POST['attendance_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!auth_verify_csrf_token($_POST['csrf_token'] ?? null)) {
        header("Location: index.php?status=error");
        exit;
    }

    $id = $_POST['attendance_id'];

    $photoPath = $_POST['existing_photo'] ?? null;
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

    if ($attendance->updateAttendance($id)) {
        header("Location: index.php?status=updated");
        exit;
    } else {
        header("Location: index.php?status=error");
        exit;
    }
}

$id = $_GET['id'];
$record = $attendance->getAttendanceById($id);
$subjects = $subject->getSubjects();

if (!$record) {
    header("Location: index.php?status=error");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Attendance — Classync</title>
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
        <h2>Edit Attendance Record</h2>
        <form method="POST" action="edit.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(auth_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="attendance_id" value="<?php echo $record['attendance_id']; ?>">
            <input type="hidden" name="existing_photo" value="<?php echo htmlspecialchars($record['photo_path'] ?? ''); ?>">

            <div class="field">
                <label for="subject_id">Subject</label>
                <select name="subject_id" id="subject_id" required>
                    <?php while ($s = $subjects->fetch(PDO::FETCH_ASSOC)): ?>
                        <option value="<?php echo $s['subject_id']; ?>" <?php echo ($s['subject_id'] == $record['subject_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['subject_code'] . ' — ' . $s['subject_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="field">
                <label for="attendance_date">Date</label>
                <input type="date" name="attendance_date" id="attendance_date" value="<?php echo htmlspecialchars($record['attendance_date']); ?>" required>
            </div>

            <div class="field">
                <label for="status">Status</label>
                <select name="status" id="status" required>
                    <option value="Present" <?php echo $record['status'] === 'Present' ? 'selected' : ''; ?>>Present</option>
                    <option value="Absent" <?php echo $record['status'] === 'Absent' ? 'selected' : ''; ?>>Absent</option>
                </select>
            </div>

            <div class="field">
                <label for="reason">Reason</label>
                <textarea name="reason" id="reason" rows="3"><?php echo htmlspecialchars($record['reason'] ?? ''); ?></textarea>
            </div>

            <div class="field">
                <label for="photo">Replace Photo Proof (optional)</label>
                <?php if (!empty($record['photo_path'])): ?>
                    <p><img class="photo-thumb" src="<?php echo htmlspecialchars($record['photo_path']); ?>" alt="current proof"></p>
                <?php endif; ?>
                <input type="file" name="photo" id="photo" accept="image/*">
            </div>

            <button class="btn" type="submit">Update Record</button>
            <a class="btn secondary" href="index.php">Cancel</a>
        </form>
    </div>
</div>

</body>
</html>
