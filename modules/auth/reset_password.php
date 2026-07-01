<?php
// modules/auth/reset_password.php

require_once __DIR__ . '/Controllers/AuthController.php';

$controller = new AuthController();
$controller->reset_password_view();
?>
