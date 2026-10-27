<?php
require_once "config/auth.php";
auth_require_login();

require_once "config/database.php";
require_once "classes/Attendance.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !auth_verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Invalid request.');
}

$database = new Database();
$db = $database->connect();

$attendance = new Attendance($db, $_SESSION['user_id']);

if (isset($_POST['id']) && filter_var($_POST['id'], FILTER_VALIDATE_INT)) {
    $id = (int) $_POST['id'];

    if ($attendance->deleteAttendance($id)) {
        header("Location: index.php?status=deleted");
        exit;
    } else {
        header("Location: index.php?status=error");
        exit;
    }
}

header("Location: index.php");
exit;
?>
