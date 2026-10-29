<?php
// modules/auth/index.php
require_once __DIR__ . '/Controllers/AuthController.php';
$controller = new AuthController();

$action = $_GET['action'] ?? ($_POST['action'] ?? 'login');

switch ($action) {
    case 'logout':
        $controller->logout();
        break;
    case 'register':
        $controller->register();
        break;
    case 'forgot_password':
        $controller->forgot_password();
        break;
    case 'reset_password_view':
        $controller->reset_password_view();
        break;
    case 'reset_password':
        $controller->reset_password();
        break;

    case 'guestLogin':
        $controller->guestLogin();
        break;
    case 'login':
        $controller->login();
        break;
    default:
        http_response_code(404);
        exit('Acción no encontrada');
}
