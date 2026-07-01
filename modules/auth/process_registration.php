<?php
// modules/auth/process_registration.php

require_once __DIR__ . '/Controllers/AuthController.php';

$controller = new AuthController();
$controller->register();
?>
