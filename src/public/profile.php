<?php
require __DIR__ . '/../includes/config.php';

$me = current_user();
if (!$me) {
    header('Location: /login.php');
    exit;
}

// VULNERABLE: no CSRF token on the profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = $_POST['bio'] ?? '';
    // VULNERABLE: SQL Injection in the bio update
    $pdo->exec("UPDATE users SET bio = '$bio' WHERE id = " . $me['id']);
    header('Location: /profile.php');
    exit;
}

// VULNERABLE: IDOR, any signed-in user can view another profile by changing the id in the URL
$viewId = $_GET['id'] ?? $me['id'];
$profile = $pdo->query("SELECT * FROM users WHERE id = $viewId")->fetch(PDO::FETCH_ASSOC);

$orders = [];
if ($profile) {
    $orders = $pdo->query("SELECT * FROM orders WHERE user_id = " . $profile['id'] . " ORDER BY id DESC")
                  ->fetchAll(PDO::FETCH_ASSOC);
}

include __DIR__ . '/../includes/header.php';
?>
<h1>Profile</h1>
<?php if (!$profile): ?>
    <p>User not found.</p>
<?php else: ?>
    <p>Username: <?= $profile['username'] ?> (<?= $profile['role'] ?>)</p>
    <p>Email: <?= $profile['email'] ?></p>
    <!-- VULNERABLE: stored XSS, the bio is printed without htmlspecialchars() -->
    <p>Bio: <?= $profile['bio'] ?></p>

    <?php if ((int)$profile['id'] === (int)$me['id']): ?>
    <form method="post">
        <textarea name="bio" rows="3" cols="40"><?= $profile['bio'] ?></textarea><br>
        <button>Save bio</button>
    </form>
    <?php endif; ?>

    <h3>Orders</h3>
    <?php if (!$orders): ?>
        <p>No orders yet.</p>
    <?php else: ?>
        <ul>
        <?php foreach ($orders as $o): ?>
            <li><a href="/order.php?id=<?= $o['id'] ?>">Order no. <?= $o['id'] ?></a>, <?= number_format($o['total'], 2) ?> EUR</li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
<?php endif;
include __DIR__ . '/../includes/footer.php';
