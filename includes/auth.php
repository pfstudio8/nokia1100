<?php
// includes/auth_tienda.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Middleware to protect Store routes.
 * Redirects to Store Login if the customer is not authenticated.
 */
function require_cliente_auth() {
    if (!isset($_SESSION['cliente_id'])) {
        header("Location: /nokia1100/tienda/login.php?error=Inicia sesión para continuar");
        exit();
    }
}

/**
 * Middleware to protect Admin/Stock routes.
 * Redirects to Admin Login if the admin/employee is not authenticated.
 */
function require_admin_auth() {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: /nokia1100/admin/login.php?error=Acceso restringido");
        exit();
    }
}
?>
