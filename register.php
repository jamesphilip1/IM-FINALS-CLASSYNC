<?php
require_once "config/database.php";
require_once "config/auth.php";

auth_start_session();

if (!empty($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$fullName = '';
$username = '';
$email = '';
$gender = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));
    $email = strtolower(trim($_POST['email'] ?? ''));
    $gender = $_POST['gender'] ?? '';
    $password = $_POST['password'] ?? '';
    $passwordConfirmation = $_POST['password_confirmation'] ?? '';

    if (!auth_verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif ($fullName === '' || strlen($fullName) > 100 || !preg_match('/^[a-z0-9_]{3,30}$/', $username) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $error = 'Enter your name, a valid email, and a username with 3–30 letters, numbers, or underscores.';
    } elseif (!in_array($gender, ['Male', 'Female', 'Non-binary', 'Other', 'Prefer not to say'], true)) {
        $error = 'Choose a gender option or select “Prefer not to say.”';
    } elseif (strlen($password) < 8) {
        $error = 'Your password must be at least 8 characters long.';
    } elseif ($password !== $passwordConfirmation) {
        $error = 'The passwords do not match.';
    } else {
        $database = new Database();
        $db = $database->connect();

        try {
            $db->beginTransaction();
            $stmt = $db->prepare('INSERT INTO users (full_name, username, email, gender, password_hash) VALUES (:full_name, :username, :email, :gender, :password_hash)');
            $stmt->execute([
                ':full_name' => $fullName,
                ':username' => $username,
                ':email' => $email,
                ':gender' => $gender,
                ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $userId = (int) $db->lastInsertId();

            $userCount = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
            if ($userCount === 1) {
                $claimLegacyRecords = $db->prepare('UPDATE attendance SET user_id = :user_id WHERE user_id IS NULL');
                $claimLegacyRecords->execute([':user_id' => $userId]);
            }

            $db->commit();

            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $fullName;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            if ($e->getCode() === '23000') {
                $error = 'That email or username is already in use.';
            } else {
                throw $e;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create account — Classync</title>
    <link rel="stylesheet" href="assets/style.css?v=6">
</head>
<body class="auth-page">
    <main class="auth-shell auth-shell-register">
        <a class="auth-home" href="login.php" aria-label="Classync home">Classync</a>
        <section class="auth-card auth-card-register" aria-labelledby="auth-title">
            <div class="auth-mark" aria-hidden="true">C</div>
            <h1 id="auth-title">Create your account</h1>
            <p class="auth-subtitle">Get started with your attendance tracker.</p>

            <?php if ($error !== ''): ?>
                <div class="alert error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(auth_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="field">
                    <label for="full_name">Name</label>
                    <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Your name" maxlength="100" autocomplete="name" required autofocus>
                </div>
                <div class="field">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Choose a username" minlength="3" maxlength="30" pattern="[A-Za-z0-9_]{3,30}" autocomplete="username" required>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" placeholder="you@example.com" maxlength="190" autocomplete="email" required>
                </div>
                <div class="field">
                    <label for="gender">Gender</label>
                    <select name="gender" id="gender" required>
                        <option value="">Choose an option</option>
                        <?php foreach (['Male', 'Female', 'Non-binary', 'Other', 'Prefer not to say'] as $genderOption): ?>
                            <option value="<?php echo htmlspecialchars($genderOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $gender === $genderOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($genderOption, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="password">Password <span class="auth-hint">(at least 8 characters)</span></label>
                    <input type="password" name="password" id="password" placeholder="Create a password" minlength="8" autocomplete="new-password" required>
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirm password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Enter your password again" minlength="8" autocomplete="new-password" required>
                </div>
                <button class="btn auth-submit" type="submit">Create account</button>
            </form>

            <p class="auth-switch">Already have an account? <a href="login.php">Sign in</a></p>
        </section>
        <p class="auth-caption">Your classes, all in sync.</p>
    </main>
</body>
</html>
