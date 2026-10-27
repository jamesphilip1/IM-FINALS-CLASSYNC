<?php
require_once "config/database.php";
require_once "config/auth.php";

auth_start_session();

if (!empty($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = strtolower(trim($_POST['identifier'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!auth_verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif ($identifier === '' || $password === '') {
        $error = 'Enter your email or username and password.';
    } else {
        $database = new Database();
        $db = $database->connect();
        $stmt = $db->prepare('SELECT user_id, full_name, email, username, password_hash FROM users WHERE email = :email OR username = :username LIMIT 1');
        $stmt->execute([
            ':email' => $identifier,
            ':username' => $identifier,
        ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['user_id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            header("Location: index.php");
            exit;
        }

        $error = 'The email/username or password you entered is incorrect.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — Classync</title>
    <link rel="stylesheet" href="assets/style.css?v=6">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <a class="auth-home" href="login.php" aria-label="Classync home">Classync</a>
        <section class="auth-card" aria-labelledby="auth-title">
            <div class="auth-mark" aria-hidden="true">C</div>
            <h1 id="auth-title">Welcome back!</h1>
            <p class="auth-subtitle">Sign in to manage your attendance.</p>

            <?php if ($error !== ''): ?>
                <div class="alert error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(auth_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="field">
                    <label for="identifier">Email or username</label>
                    <input type="text" name="identifier" id="identifier" value="<?php echo htmlspecialchars($identifier, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter your email or username" autocomplete="username" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-input">
                        <input type="password" name="password" id="password" placeholder="Enter your password" autocomplete="current-password" required>
                        <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false">Show</button>
                    </div>
                </div>
                <button class="btn auth-submit" type="submit">Sign in</button>
            </form>

            <p class="auth-switch">Don't have an account? <a href="register.php">Create one</a></p>
        </section>
        <p class="auth-caption">Your classes, all in sync.</p>
    </main>
    <script>
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.querySelector('.password-toggle');
        passwordToggle.addEventListener('click', () => {
            const showPassword = passwordInput.type === 'password';
            passwordInput.type = showPassword ? 'text' : 'password';
            passwordToggle.textContent = showPassword ? 'Hide' : 'Show';
            passwordToggle.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
            passwordToggle.setAttribute('aria-pressed', String(showPassword));
        });
    </script>
</body>
</html>
