<?php
// modules/inventory/add_stock.php

require_once __DIR__ . '/Controllers/InventoryController.php';

$controller = new InventoryController();
$controller->add_stock();
?>
