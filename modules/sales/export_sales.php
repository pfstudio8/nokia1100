<?php
// modules/sales/export_sales.php

require_once __DIR__ . '/Controllers/SalesController.php';

$controller = new SalesController();
$controller->export_sales();
?>
