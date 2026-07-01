<?php
// api/check_stock.php

require_once __DIR__ . '/../modules/inventory/Controllers/InventoryController.php';

$controller = new InventoryController();
$controller->check_stock();
?>
