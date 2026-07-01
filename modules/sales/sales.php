<?php
// modules/sales/sales.php

require_once __DIR__ . '/Controllers/SalesController.php';

$controller = new SalesController();
$controller->sales();
?>
