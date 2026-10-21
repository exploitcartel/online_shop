<?php
require __DIR__ . '/../includes/config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    // VULNERABLE: mass assignment, the role is taken from the hidden form field and the client can change it
    $role     = $_POST['role'] ?? 'customer';

    // VULNERABLE: no password policy (length, complexity) and unsalted MD5
    $hash = md5($password);

    try {
        // VULNERABLE: SQL Injection in registration as well
        $pdo->exec("INSERT INTO users (username, email, password, role)
                    VALUES ('$username', '$email', '$hash', '$role')");
        $message = 'Registration complete. You can sign in now.';
    } catch (PDOException $e) {
        // VULNERABLE: the full database error message is shown to the user
        $message = 'Error: ' . $e->getMessage();
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
    <div class="auth-card">
        <h1>Create account</h1>
        <p class="sub">Join Atelier to track your orders.</p>

        <?php if ($message): ?><p class="msg"><?= $message ?></p><?php endif; ?>

        <form method="post">
            <div class="field">
                <label for="username">Username</label>
                <input id="username" name="username" autocomplete="username">
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" autocomplete="email">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" autocomplete="new-password">
            </div>
            <input type="hidden" name="role" value="customer">
            <button class="btn btn-lg btn-block">Create account</button>
        </form>

        <p class="auth-links">
            Already have an account? <a href="/login.php">Sign in</a>
        </p>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
