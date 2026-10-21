<?php
require __DIR__ . '/../includes/config.php';

$id = $_GET['id'] ?? '1';

// VULNERABLE: SQL Injection, the id parameter is not validated as a number and no prepared statement is used
$product = $pdo->query("SELECT * FROM products WHERE id = $id")->fetch(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';

if (!$product): ?>
    <div class="empty">
        <p>Product not found.</p>
        <a class="btn btn-outline" href="/index.php">Back to shop</a>
    </div>
<?php else:
    // Presentation only: other products for the "You may also like" section (query has no user input)
    $related = $pdo->query("SELECT * FROM products WHERE id != " . (int)$product['id'] . " ORDER BY id LIMIT 4")
                   ->fetchAll(PDO::FETCH_ASSOC);
    $initial = preg_match('/\p{L}/u', strip_tags((string)$product['name']), $m) ? $m[0] : '*';
?>
    <div class="product">
        <div class="product-media">
            <?php if ($product['image']): ?>
                <img src="/uploads/<?= $product['image'] ?>" alt="">
            <?php else: ?>
                <span class="ph"><?= htmlspecialchars($initial) ?></span>
            <?php endif; ?>
        </div>

        <div class="product-info">
            <h1><?= $product['name'] ?></h1>
            <div class="product-price"><?= number_format($product['price'], 2) ?> EUR</div>
            <p class="product-desc"><?= $product['description'] ?></p>

            <!-- VULNERABLE: the price is sent from the client as a hidden field and can be changed before submitting -->
            <form method="post" action="/cart.php">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <input type="hidden" name="name" value="<?= $product['name'] ?>">
                <input type="hidden" name="price" value="<?= $product['price'] ?>">

                <span class="option-label">Size</span>
                <div class="sizes">
                    <?php foreach (['XS', 'S', 'M', 'L', 'XL'] as $size): ?>
                        <label class="size">
                            <input type="radio" name="size" value="<?= $size ?>"<?= $size === 'M' ? ' checked' : '' ?>>
                            <span><?= $size ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <span class="option-label">Quantity</span>
                <div class="buy-row">
                    <div class="qty" data-min="1">
                        <button type="button" class="qty-btn" data-step="-1" aria-label="Decrease">&minus;</button>
                        <input type="number" name="qty" value="1" min="1" aria-label="Quantity">
                        <button type="button" class="qty-btn" data-step="1" aria-label="Increase">+</button>
                    </div>
                    <button class="btn btn-lg">Add to Cart</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($related): ?>
    <section style="margin-top:80px">
        <div class="section-head">
            <h2>You may also like</h2>
        </div>
        <div class="grid-products">
            <?php foreach ($related as $r): shop_card($r); endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
<?php endif;
include __DIR__ . '/../includes/footer.php';
