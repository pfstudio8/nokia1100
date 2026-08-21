<?php
// api/rollback_sale.php

// Asegurar que no haya salida antes del JSON
ob_start();

require_once __DIR__ . '/../modules/sales/Controllers/SalesController.php';

$controller = new SalesController();
// El método rollback() del controlador se encargará de leer el POST y enviar el JSON.
$controller->rollback();
