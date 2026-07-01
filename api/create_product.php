<?php
// api/create_product.php

require_once __DIR__ . '/../modules/inventory/Controllers/InventoryController.php';

$controller = new InventoryController();
$controller->ajax_create_product();
?>
