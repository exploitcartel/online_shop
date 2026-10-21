<?php
// VULNERABLE: error display is enabled and reveals the internal structure (see also php/lab.ini)
ini_set('display_errors', '1');
error_reporting(E_ALL);

// VULNERABLE: session starts without security flags and without session_regenerate_id() after login (session fixation)
session_start();

$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$pdo = new PDO('sqlite:' . $dataDir . '/shop.sqlite');
// VULNERABLE: database exceptions are shown to the user with the full SQL text
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE,
    email TEXT,
    password TEXT,
    role TEXT DEFAULT 'customer',
    bio TEXT DEFAULT ''
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    description TEXT,
    price REAL,
    image TEXT DEFAULT ''
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    total REAL,
    note TEXT DEFAULT '',
    created_at TEXT
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER,
    product_id INTEGER,
    qty INTEGER,
    price REAL
)");

// Sample data, created only the first time
if ((int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() === 0) {
    $sample = [
        ['Linen Shirt', 'Lightweight linen shirt with a relaxed fit.', 49.00],
        ['Classic White Tee', 'Soft cotton t-shirt for everyday wear.', 19.90],
        ['Slim Fit Jeans', 'Stretch denim jeans with a modern cut.', 69.00],
        ['Wool Blend Coat', 'Warm long coat for the colder months.', 149.00],
        ['Knit Cardigan', 'Fine knit cardigan with button front.', 59.00],
        ['Summer Dress', 'Flowing midi dress in breathable fabric.', 79.00],
        ['Canvas Sneakers', 'Low-top sneakers with a rubber sole.', 54.00],
        ['Leather Belt', 'Full grain leather belt with a brushed buckle.', 29.00],
        ['Cotton Chinos', 'Straight chinos in washed cotton twill.', 59.00],
        ['Silk Scarf', 'Printed silk scarf, light and smooth.', 34.00],
        ['Hooded Sweatshirt', 'Heavy fleece hoodie with a kangaroo pocket.', 64.00],
        ['Leather Tote Bag', 'Roomy tote bag with an inner zip pocket.', 99.00],
    ];
    $stmt = $pdo->prepare("INSERT INTO products (name, description, price) VALUES (?, ?, ?)");
    foreach ($sample as $p) {
        $stmt->execute($p);
    }
    // VULNERABLE: passwords are stored with unsalted MD5
    $pdo->exec("INSERT INTO users (username, email, password, role) VALUES
        ('admin', 'admin@lab.local', '" . md5('admin123') . "', 'admin'),
        ('student', 'student@lab.local', '" . md5('student123') . "', 'customer')");
}

function current_user(): ?array
{
    global $pdo;
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    // VULNERABLE: the id comes from the session and is placed straight into SQL without parameters (bad practice)
    $row = $pdo->query("SELECT * FROM users WHERE id = " . $_SESSION['user_id'])->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function cart_count(): int
{
    $n = 0;
    foreach ($_SESSION['cart'] ?? [] as $item) {
        $n += (int)$item['qty'];
    }
    return $n;
}
