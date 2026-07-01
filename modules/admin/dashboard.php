<?php
// modules/admin/dashboard.php

require_once __DIR__ . '/Controllers/AdminController.php';

$controller = new AdminController();
$controller->dashboard();
?>