<?php
// modules/inventory/process_product.php

require_once __DIR__ . '/Controllers/InventoryController.php';

$controller = new InventoryController();
$controller->process_product();
?>
