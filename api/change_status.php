<?php
// api/change_status.php

// Carga el controlador de inventario desde su ubicación correspondiente para poder delegar la lógica
require_once __DIR__ . '/../modules/inventory/Controllers/InventoryController.php';

// Instancia un nuevo objeto del controlador de inventario
$controller = new InventoryController();

// Llama al método del controlador encargado de alternar el estado del producto (activar/desactivar)
$controller->change_status();
?>
