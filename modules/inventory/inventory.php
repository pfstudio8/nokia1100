<?php
// modules/inventory/inventory.php

require_once __DIR__ . '/Controllers/InventoryController.php';

$controller = new InventoryController();
$controller->inventory();
?>
