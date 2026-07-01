<?php
// includes/auth_tienda.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Middleware para proteger rutas de la tienda. Redirige al login de tienda si el cliente no está autenticado.
function require_cliente_auth() {
    if (!isset($_SESSION['cliente_id'])) {
        header("Location: /nokia1100/tienda/login.php?error=Inicia sesión para continuar");
        exit();
    }
}

// Middleware para proteger rutas de administración e inventario. Redirige al login de administración si no está autenticado.
function require_admin_auth() {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: /nokia1100/admin/login.php?error=Acceso restringido");
        exit();
    }
}
?>
