<?php
// modules/sales/invoice.php

require_once __DIR__ . '/Controllers/SalesController.php';

$controller = new SalesController();
$controller->invoice();
?>
