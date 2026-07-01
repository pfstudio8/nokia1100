<?php
// modules/auth/auth.php

require_once __DIR__ . '/Controllers/AuthController.php';

$controller = new AuthController();
$controller->login();
?>
