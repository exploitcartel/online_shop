<?php
require __DIR__ . '/../includes/config.php';

$message = '';
$uploaded = '';

$products = $pdo->query("SELECT id, name FROM products ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // VULNERABLE: the file name comes from the client without cleaning (path traversal in the name, file overwrite)
        $name = $_FILES['image']['name'];

        // VULNERABLE: the file extension is not checked, .php is allowed
        // VULNERABLE: the real content is not checked (finfo), the client Content-Type is fully trusted
        // VULNERABLE: no size limit beyond the PHP limit and the file is not renamed
        $target = __DIR__ . '/uploads/' . $name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $uploaded = $name;
            $message = 'File uploaded. Check it in the preview before publishing.';
        } else {
            $message = 'Upload failed.';
        }
    } else {
        $message = 'Please choose a file.';
    }
}

include __DIR__ . '/../includes/header.php';
?>
<h1>Upload a product image</h1>
<?php if ($message): ?><p class="msg"><?= $message ?></p><?php endif; ?>

<form method="post" enctype="multipart/form-data">
    File:<br><input type="file" name="image"><br>
    <button>Upload</button>
</form>

<?php if ($uploaded): ?>
    <p>
        <!-- VULNERABLE: the file name is placed in the URL and in the HTML without encoding, XSS and parameter manipulation -->
        <a href="/preview.php?file=<?= $uploaded ?>">Open preview of <?= $uploaded ?></a>
    </p>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
