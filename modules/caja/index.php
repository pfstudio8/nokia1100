<?php
// modules/caja/index.php

require_once __DIR__ . '/Controllers/CajaController.php';

$controller = new CajaController();

$action = $_GET['action'] ?? ($_POST['action'] ?? 'index');

switch ($action) {
    case 'open':
        $controller->open();
        break;
    case 'add_movement':
        $controller->add_movement();
        break;
    case 'remove_movement':
        $controller->remove_movement();
        break;
    case 'close':
        $controller->close();
        break;
    case 'detail':
        $controller->detail();
        break;
    case 'export_csv':
        $controller->export_csv();
        break;
    case 'index':
    default:
        $controller->index();
        break;
}
