<?php
// modules/admin/index.php
require_once __DIR__ . '/Controllers/AdminController.php';
$controller = new AdminController();

$action = $_GET['action'] ?? ($_POST['action'] ?? 'dashboard');

switch ($action) {
    case 'dashboard':
        $controller->dashboard();
        break;
    case 'users':
        $controller->users();
        break;
    case 'add_user':
        $controller->add_user();
        break;
    case 'edit_user':
        $controller->edit_user();
        break;
    case 'delete_user':
        $controller->delete_user();
        break;
    case 'save_user_modules':
        $controller->save_user_modules();
        break;
    case 'update_user_role':
        $controller->update_user_role();
        break;
    case 'profile':
        $controller->profile();
        break;
    case 'audit':
        $controller->audit();
        break;
    case 'get_dashboard_data':
        $controller->get_dashboard_data();
        break;
    default:
        http_response_code(404);
        exit('Acción no encontrada');
}
