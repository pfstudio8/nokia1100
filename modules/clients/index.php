<?php
// modules/clients/index.php
require_once __DIR__ . '/Controllers/ClientsController.php';
$controller = new ClientsController();

$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'edit':
        $controller->edit();
        break;
    case 'index':
    default:
        $controller->index();
        break;
}
?>
