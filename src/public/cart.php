<?php
require __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = '';

// VULNERABLE: no CSRF token, any external page can trigger these actions on behalf of the user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pid = $_POST['product_id'] ?? '';

    if ($action === 'add') {
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        // VULNERABLE: price and name are taken from the client request, not from the database
        $_SESSION['cart'][$pid] = [
            'name'  => $_POST['name'] ?? '',
            'price' => (float)($_POST['price'] ?? 0),
            'qty'   => ($_SESSION['cart'][$pid]['qty'] ?? 0) + $qty,
        ];
    } elseif ($action === 'update') {
        // VULNERABLE: a negative quantity is accepted, the total can become negative
        $_SESSION['cart'][$pid]['qty'] = (int)($_POST['qty'] ?? 1);
    } elseif ($action === 'remove') {
        unset($_SESSION['cart'][$pid]);
    } elseif ($action === 'checkout') {
        $me = current_user();
        if (!$me) {
            $message = 'You must sign in to place an order.';
        } elseif (empty($_SESSION['cart'])) {
            $message = 'Your cart is empty.';
        } else {
            $total = 0;
            foreach ($_SESSION['cart'] as $item) {
                $total += $item['price'] * $item['qty'];
            }
            $note = $_POST['note'] ?? '';
            // VULNERABLE: SQL Injection in the order note (the note field is concatenated into the query)
            $pdo->exec("INSERT INTO orders (user_id, total, note, created_at)
                        VALUES ({$me['id']}, $total, '$note', '" . date('Y-m-d H:i:s') . "')");
            $orderId = $pdo->lastInsertId();
            $ins = $pdo->prepare("INSERT INTO order_items (order_id, product_id, qty, price) VALUES (?, ?, ?, ?)");
            foreach ($_SESSION['cart'] as $pid => $item) {
                $ins->execute([$orderId, $pid, $item['qty'], $item['price']]);
            }
            $_SESSION['cart'] = [];
            header('Location: /order.php?id=' . $orderId);
            exit;
        }
    }
}

include __DIR__ . '/../includes/header.php';

// Presentation only: the subtotal shown in the summary
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['qty'];
}
?>
<h1 class="page-title">Your Cart</h1>
<?php if ($message): ?><p class="msg"><?= $message ?></p><?php endif; ?>

<?php if (empty($_SESSION['cart'])): ?>
    <div class="empty">
        <p>Your cart is empty.</p>
        <a class="btn" href="/index.php#new-arrivals">Continue shopping</a>
    </div>
<?php else: ?>
<div class="cart-layout">
    <div class="cart-list">
        <div class="cart-head">
            <span></span><span>Product</span><span>Quantity</span><span style="text-align:right">Subtotal</span><span></span>
        </div>

        <?php foreach ($_SESSION['cart'] as $pid => $item):
            $sub = $item['price'] * $item['qty'];
            $initial = preg_match('/\p{L}/u', strip_tags((string)$item['name']), $m) ? $m[0] : '*';
        ?>
        <div class="cart-row">
            <div class="cart-thumb"><?= htmlspecialchars($initial) ?></div>

            <div class="cart-info">
                <!-- VULNERABLE: the client-supplied product name is printed without escaping, stored XSS in the session -->
                <div class="cart-name"><?= $item['name'] ?></div>
                <div class="cart-unit"><?= number_format($item['price'], 2) ?> EUR</div>
            </div>

            <form class="cart-qty" method="post">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="product_id" value="<?= $pid ?>">
                <div class="qty" data-min="1" data-autosubmit>
                    <button type="button" class="qty-btn" data-step="-1" aria-label="Decrease">&minus;</button>
                    <input type="number" name="qty" value="<?= $item['qty'] ?>" aria-label="Quantity">
                    <button type="button" class="qty-btn" data-step="1" aria-label="Increase">+</button>
                </div>
                <noscript><button class="btn btn-sm">Update</button></noscript>
            </form>

            <div class="cart-sub"><?= number_format($sub, 2) ?> EUR</div>

            <form class="cart-remove" method="post">
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="product_id" value="<?= $pid ?>">
                <button aria-label="Remove item" title="Remove">&times;</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>

    <aside class="summary">
        <h3>Order summary</h3>
        <div class="summary-row"><span>Subtotal</span><span><?= number_format($subtotal, 2) ?> EUR</span></div>
        <div class="summary-row"><span>Shipping</span><span>Free</span></div>
        <div class="summary-row summary-total"><span>Total</span><span><?= number_format($subtotal, 2) ?> EUR</span></div>

        <form method="post">
            <input type="hidden" name="action" value="checkout">
            <label for="note">Order note (optional)</label>
            <textarea id="note" name="note" rows="3" placeholder="Note for your order"></textarea>
            <button class="btn btn-lg btn-block">Checkout</button>
        </form>
    </aside>
</div>
<?php endif;
include __DIR__ . '/../includes/footer.php';
