<?php
// api/cambiar_estado.php

require_once __DIR__ . '/../modules/inventory/Controllers/InventoryController.php';

$controller = new InventoryController();
$controller->change_status();
?>
