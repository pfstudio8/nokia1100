<?php
// modules/auth/process_reset.php

require_once __DIR__ . '/Controllers/AuthController.php';

$controller = new AuthController();
$controller->reset_password();
?>
