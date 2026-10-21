<?php
require __DIR__ . '/../includes/config.php';

$q = $_GET['q'] ?? '';

if ($q !== '') {
    // VULNERABLE: SQL Injection, user input is concatenated directly into the query
    $sql = "SELECT * FROM products WHERE name LIKE '%$q%'";
} else {
    $sql = "SELECT * FROM products ORDER BY id";
}
$products = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$fullWidth = true; // presentation only: the hero uses the full width
include __DIR__ . '/../includes/header.php';
?>

<?php if ($q === ''): ?>
<section class="hero">
    <div class="container hero-inner">
        <span class="eyebrow">New season</span>
        <h1>Timeless pieces for everyday wear</h1>
        <p>Soft fabrics, clean cuts and colours that work together. Find your next favourite outfit.</p>
        <a class="btn btn-lg" href="#new-arrivals">Shop Now</a>
    </div>
</section>
<?php endif; ?>

<section class="section" id="new-arrivals">
    <div class="container">
        <div class="section-head">
            <h2><?= $q !== '' ? 'Search results' : 'New Arrivals' ?></h2>
            <span><?= count($products) ?> items</span>
        </div>

        <?php if ($q !== ''): ?>
            <!-- VULNERABLE: reflected XSS, the q parameter is printed without htmlspecialchars() -->
            <p class="msg">Results for: <?= $q ?></p>
        <?php endif; ?>

        <?php if (!$products): ?>
            <div class="empty">
                <p>No products found.</p>
                <a class="btn btn-outline" href="/index.php">Back to all products</a>
            </div>
        <?php else: ?>
            <div class="grid-products">
                <?php foreach ($products as $p): shop_card($p); endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($q === ''): ?>
<section class="section section-alt" id="categories">
    <div class="container">
        <div class="section-head">
            <h2>Categories</h2>
        </div>
        <div class="grid-categories">
            <a class="category category-women" href="#new-arrivals">
                <div><h3>Women</h3><span>Explore the collection</span></div>
            </a>
            <a class="category category-men" href="#new-arrivals">
                <div><h3>Men</h3><span>Explore the collection</span></div>
            </a>
            <a class="category category-accessories" href="#new-arrivals">
                <div><h3>Accessories</h3><span>Explore the collection</span></div>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
