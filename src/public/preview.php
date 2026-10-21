<?php
require __DIR__ . '/../includes/config.php';

$file = $_GET['file'] ?? '';
$publish = $_GET['publish'] ?? '';

$content = '';
$error = '';

if ($file !== '') {
    // VULNERABLE: path traversal / local file read (LFI), the file parameter is joined to the path without realpath() and without an allow list
    $path = __DIR__ . '/uploads/' . $file;

    if (file_exists($path)) {
        // The file content is read for visual verification before publishing
        $content = file_get_contents($path);
    } else {
        // VULNERABLE: the full server path is revealed in the error message
        $error = 'File not found: ' . $path;
    }
}

if ($file !== '' && $publish !== '' && $error === '') {
    // VULNERABLE: SQL Injection when publishing, and no authorization check that this user may do it
    $pdo->exec("UPDATE products SET image = '$file' WHERE id = " . $publish);
}

include __DIR__ . '/../includes/header.php';
?>
<h1>File preview</h1>
<?php if ($error): ?><p class="msg"><?= $error ?></p><?php endif; ?>

<?php if ($file !== '' && $error === ''): ?>
    <p>File: <?= $file ?></p>
    <h3>Content</h3>
    <!-- VULNERABLE: the file content is printed unfiltered, if it is HTML or JavaScript it runs in the browser (stored XSS) -->
    <div><?= $content ?></div>

    <h3>Publish as product image</h3>
    <form method="get">
        <input type="hidden" name="file" value="<?= $file ?>">
        <input name="publish" placeholder="Product ID">
        <button>Publish</button>
    </form>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
