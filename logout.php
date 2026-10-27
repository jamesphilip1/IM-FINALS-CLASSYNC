<?php
require_once "config/auth.php";

auth_start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !auth_verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Invalid request.');
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
}

session_destroy();
header("Location: login.php");
exit;
