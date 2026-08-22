<?php
// modules/auth/index.php
require_once __DIR__ . '/Controllers/AuthController.php';
$controller = new AuthController();

$action = $_GET['action'] ?? 'login';

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
    case 'get_token':
        $controller->get_token();
        break;
    case 'guestLogin':
        $controller->guestLogin();
        break;
    case 'login':
    default:
        $controller->login();
        break;
}
?>
