<?php
require __DIR__ . '/../includes/config.php';

$me = current_user();
if (!$me) {
    header('Location: /login.php');
    exit;
}

$id = $_GET['id'] ?? '0';

// VULNERABLE: IDOR, there is no check that the order belongs to the signed-in user
// VULNERABLE: SQL Injection, the id parameter is placed directly into the query
$order = $pdo->query("SELECT * FROM orders WHERE id = $id")->fetch(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';

if (!$order): ?>
    <p>Order not found.</p>
<?php else:
    $items = $pdo->query("SELECT oi.*, p.name FROM order_items oi
                          LEFT JOIN products p ON p.id = oi.product_id
                          WHERE oi.order_id = " . $order['id'])->fetchAll(PDO::FETCH_ASSOC); ?>
    <h1>Order no. <?= $order['id'] ?></h1>
    <p>Date: <?= $order['created_at'] ?></p>
    <!-- VULNERABLE: stored XSS, the order note is printed without escaping -->
    <p>Note: <?= $order['note'] ?></p>
    <table>
        <tr><th>Product</th><th>Quantity</th><th>Price</th></tr>
        <?php foreach ($items as $it): ?>
        <tr><td><?= $it['name'] ?></td><td><?= $it['qty'] ?></td><td><?= number_format($it['price'], 2) ?></td></tr>
        <?php endforeach; ?>
    </table>
    <p><strong>Total: <?= number_format($order['total'], 2) ?> EUR</strong></p>
<?php endif;
include __DIR__ . '/../includes/footer.php';
