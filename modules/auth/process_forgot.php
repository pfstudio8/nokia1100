<?php
// modules/auth/process_forgot.php

require_once __DIR__ . '/Controllers/AuthController.php';

$controller = new AuthController();
$controller->forgot_password();
?>
