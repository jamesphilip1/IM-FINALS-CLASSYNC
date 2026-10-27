<?php
require_once "config/auth.php";
auth_require_login();

require_once "config/database.php";
require_once "classes/Attendance.php";

$database = new Database();
$db = $database->connect();

$attendance = new Attendance($db, $_SESSION['user_id']);

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

if ($keyword !== '') {
    $stmt = $attendance->searchAttendances($keyword);
} else {
    $stmt = $attendance->getAttendances();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Classync — Attendance Records</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="navbar">
    <div class="brand">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Student', ENT_QUOTES, 'UTF-8'); ?></h1>
        <span>Your personal attendance dashboard</span>
    </div>
    <form class="search-form navbar-search" method="GET" action="index.php">
        <input type="text" name="keyword" placeholder="Search attendance..."
               value="<?php echo htmlspecialchars($keyword); ?>" aria-label="Search attendance records">
        <button class="btn secondary" type="submit">Search</button>
    </form>
    <details class="nav-menu">
        <summary aria-label="Open navigation menu"><span></span><span></span><span></span></summary>
        <nav aria-label="Main navigation">
            <a href="index.php">Records</a>
            <a href="add.php">Add Attendance</a>
            <a href="#proof">Proof</a>
        </nav>
    </details>
    <form class="logout-form" method="POST" action="logout.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(auth_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <button class="btn secondary small" type="submit">Sign out</button>
    </form>
</div>

<div class="container">

    <?php if (isset($_GET['status'])): ?>
        <?php if ($_GET['status'] === 'added'): ?>
            <div class="alert success">Attendance record added successfully.</div>
        <?php elseif ($_GET['status'] === 'updated'): ?>
            <div class="alert success">Attendance record updated successfully.</div>
        <?php elseif ($_GET['status'] === 'deleted'): ?>
            <div class="alert success">Attendance record deleted.</div>
        <?php elseif ($_GET['status'] === 'error'): ?>
            <div class="alert error">Something went wrong. Please try again.</div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card">
        <div class="toolbar">
            <div>
                <h2>Attendance Records</h2>
                <?php if ($keyword !== ''): ?>
                    <a class="btn secondary small" href="index.php">Clear search</a>
                <?php endif; ?>
            </div>
            <a class="btn" href="add.php">+ Add Attendance</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Reason</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $count = 0;
                $proofs = [];
                if ($stmt) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $count++;
                        if (!empty($row['photo_path'])) {
                            $proofs[] = $row;
                        }
                        $statusClass = $row['status'] === 'Present' ? 'present' : 'absent';
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['attendance_date']) . "</td>";
                        echo "<td>" . htmlspecialchars(($row['subject_code'] ?? '') . " — " . ($row['subject_name'] ?? '')) . "</td>";
                        echo "<td><span class='badge {$statusClass}'>" . htmlspecialchars($row['status']) . "</span></td>";
                        echo "<td>" . htmlspecialchars($row['reason'] ?: '—') . "</td>";
                        echo "<td>";
                        echo "<details class='record-menu'><summary>More</summary><div class='record-menu-panel'>";
                        if (!empty($row['photo_path'])) {
                            echo "<a href='" . htmlspecialchars($row['photo_path']) . "' target='_blank' rel='noopener'>View proof</a>";
                        } else {
                            echo "<span class='no-proof'>No proof uploaded</span>";
                        }
                        echo "<a class='btn secondary small' href='edit.php?id=" . $row['attendance_id'] . "'>Edit</a> ";
                        echo "<form class='delete-form' method='POST' action='delete.php' onsubmit=\"return confirm('Delete this attendance record?');\">";
                        echo "<input type='hidden' name='csrf_token' value='" . htmlspecialchars(auth_csrf_token(), ENT_QUOTES, 'UTF-8') . "'>";
                        echo "<input type='hidden' name='id' value='" . (int) $row['attendance_id'] . "'>";
                        echo "<button class='btn danger small' type='submit'>Delete</button>";
                        echo "</form>";
                        echo "</div></details>";
                        echo "</td>";
                        echo "</tr>";
                    }
                }
                ?>
            </tbody>
        </table>

        <?php if ($count === 0): ?>
            <div class="empty">
                <?php echo $keyword !== '' ? "No records match \"" . htmlspecialchars($keyword) . "\"." : "No attendance records yet. Click \"Add Attendance\" to create one."; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card proof-card" id="proof">
        <div class="proof-heading">
            <div>
                <h2>Photo Proof</h2>
                <p>Uploaded proof for your attendance records.</p>
            </div>
            <span class="proof-count"><?php echo count($proofs); ?> uploaded</span>
        </div>
        <?php if (count($proofs) > 0): ?>
            <div class="proof-gallery">
                <?php foreach ($proofs as $proof): ?>
                    <a class="proof-item" href="<?php echo htmlspecialchars($proof['photo_path']); ?>" target="_blank" rel="noopener">
                        <img src="<?php echo htmlspecialchars($proof['photo_path']); ?>" alt="Attendance proof for <?php echo htmlspecialchars($proof['attendance_date']); ?>">
                        <span>
                            <strong><?php echo htmlspecialchars($proof['attendance_date']); ?></strong>
                            <?php echo htmlspecialchars(($proof['subject_code'] ?? '') . ' — ' . ($proof['subject_name'] ?? '')); ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty">No photo proof has been uploaded yet.</div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
