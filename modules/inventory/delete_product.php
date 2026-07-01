<?php
// modules/inventory/delete_product.php

require_once __DIR__ . '/Controllers/InventoryController.php';

$controller = new InventoryController();
$controller->delete_product();
?>
