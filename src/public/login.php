<?php
require __DIR__ . '/../includes/config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // VULNERABLE: SQL Injection, the username is concatenated directly into the query (authentication bypass)
    $sql = "SELECT * FROM users WHERE username = '$username' AND password = '" . md5($password) . "'";

    try {
        $user = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // VULNERABLE: the full SQL error is shown, which helps an attacker rebuild the query
        $user = false;
        $message = 'Error: ' . $e->getMessage() . '<br>Query: ' . $sql;
    }

    if ($user) {
        // VULNERABLE: session_regenerate_id() is not called, the session identifier stays the same (session fixation)
        $_SESSION['user_id'] = $user['id'];
        header('Location: /profile.php');
        exit;
    }

    // VULNERABLE: no attempt limit and no account lockout (brute force without any barrier)
    if ($message === '') {
        $message = 'Wrong username or password.';
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
    <div class="auth-card">
        <h1>Welcome back</h1>
        <p class="sub">Sign in to your account.</p>

        <?php if ($message): ?><p class="msg"><?= $message ?></p><?php endif; ?>

        <form method="post">
            <div class="field">
                <label for="username">Username</label>
                <input id="username" name="username" autocomplete="username">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" autocomplete="current-password">
            </div>
            <button class="btn btn-lg btn-block">Sign in</button>
        </form>

        <p class="auth-links">
            <a class="forgot" href="#" title="Not available in this lab">Forgot password?</a>
            New here? <a href="/register.php">Create an account</a>
        </p>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
