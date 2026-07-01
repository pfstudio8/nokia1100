<?php
// modules/suppliers/suppliers.php

require_once __DIR__ . '/Controllers/SuppliersController.php';

$controller = new SuppliersController();
$controller->suppliers();
?>
