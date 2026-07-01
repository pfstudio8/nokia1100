<?php
// api/get_purchase_history.php

require_once __DIR__ . '/../modules/inventory/Controllers/InventoryController.php';

$controller = new InventoryController();
$controller->get_purchase_history();
?>
