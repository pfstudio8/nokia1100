<?php
// modules/auth/logout.php

require_once __DIR__ . '/Controllers/AuthController.php';

$controller = new AuthController();
$controller->logout();
?>
