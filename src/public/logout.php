<?php
require __DIR__ . '/../includes/config.php';

// VULNERABLE: logout does not fully destroy the session, only user_id is removed
unset($_SESSION['user_id']);
header('Location: /index.php');
exit;
