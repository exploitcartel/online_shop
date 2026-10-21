<?php
$me = current_user();

// Presentation only: product card used on the home page and in "You may also like"
if (!function_exists('shop_card')) {
    function shop_card(array $p): void
    {
        $initial = preg_match('/\p{L}/u', strip_tags((string)$p['name']), $m) ? $m[0] : '*';
        ?>
        <article class="card">
            <div class="card-media-wrap">
                <a class="card-media" href="/product.php?id=<?= $p['id'] ?>">
                    <?php if ($p['image']): ?>
                        <img src="/uploads/<?= $p['image'] ?>" alt="">
                    <?php else: ?>
                        <span class="ph"><?= htmlspecialchars($initial) ?></span>
                    <?php endif; ?>
                </a>
                <!-- VULNERABLE: the price is sent from the client as a hidden field and can be changed before submitting -->
                <form class="card-add" method="post" action="/cart.php">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                    <input type="hidden" name="name" value="<?= $p['name'] ?>">
                    <input type="hidden" name="price" value="<?= $p['price'] ?>">
                    <input type="hidden" name="qty" value="1">
                    <button class="btn btn-sm">Add to Cart</button>
                </form>
            </div>
            <div class="card-body">
                <a class="card-title" href="/product.php?id=<?= $p['id'] ?>"><?= $p['name'] ?></a>
                <span class="price"><?= number_format($p['price'], 2) ?> EUR</span>
            </div>
        </article>
        <?php
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Atelier | ShopLab</title>
<link rel="stylesheet" href="/style.css">
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <button class="icon-btn menu-toggle" type="button" data-menu-toggle aria-label="Menu" aria-expanded="false">
            <svg viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>

        <a class="logo" href="/index.php">Atelier</a>

        <nav class="primary-nav" aria-label="Main">
            <a href="/index.php">Home</a>
            <a href="/index.php#new-arrivals">Shop</a>
            <a href="#site-footer">About</a>
            <a href="/cart.php">Cart</a>
        </nav>

        <div class="header-actions">
            <button class="icon-btn" type="button" data-search-toggle aria-label="Search" aria-expanded="false">
                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="M20 20l-4-4"/></svg>
            </button>

            <a class="icon-btn" href="/cart.php" aria-label="Cart">
                <svg viewBox="0 0 24 24"><path d="M5 8h14l-1 12H6L5 8z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
                <?php if (cart_count() > 0): ?><span class="badge"><?= cart_count() ?></span><?php endif; ?>
            </a>

            <details class="account">
                <summary class="icon-btn" aria-label="Account">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c1.5-4 5-5.5 8-5.5s6.5 1.5 8 5.5"/></svg>
                </summary>
                <div class="account-menu">
                    <?php if ($me): ?>
                        <!-- VULNERABLE: the username is printed without escaping, stored XSS if someone registers with HTML in the name -->
                        <a href="/profile.php">Profile (<?= $me['username'] ?>)</a>
                        <a href="/upload.php">Upload image</a>
                        <hr>
                        <a href="/logout.php">Sign out</a>
                    <?php else: ?>
                        <a href="/login.php">Sign in</a>
                        <a href="/register.php">Create account</a>
                        <hr>
                        <a href="/upload.php">Upload image</a>
                    <?php endif; ?>
                </div>
            </details>
        </div>
    </div>

    <div class="search-bar">
        <form action="/index.php" method="get">
            <input type="text" name="q" placeholder="Search products" aria-label="Search products" value="">
            <button class="btn">Search</button>
        </form>
    </div>
</header>

<main class="<?= !empty($fullWidth) ? 'main-full' : 'container main-pad' ?>">
