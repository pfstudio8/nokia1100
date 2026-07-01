<?php
// modules/auth/guest_login.php

require_once __DIR__ . '/Controllers/AuthController.php';

$controller = new AuthController();
$controller->guestLogin();
?>
