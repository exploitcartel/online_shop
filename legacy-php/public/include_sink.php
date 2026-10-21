<?php
// VULNERABLE: file inclusion with a user-supplied parameter and no
// validation — the intentional sink for this exercise. Unlike preview.php
// in the main shop (which only reads a file's bytes with
// file_get_contents() and cannot run them), include() here parses and
// executes PHP code found in the target file. Written in PHP 5.4 syntax
// on purpose: this file runs only on the legacy-php service.
$file = isset($_GET['file']) ? $_GET['file'] : '';
if ($file && file_exists($file)) {
    include($file);
}
