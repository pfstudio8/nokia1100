<?php
// modules/inventory/add_product.php

require_once __DIR__ . '/Controllers/InventoryController.php';

$controller = new InventoryController();
$controller->add_product();
?>
