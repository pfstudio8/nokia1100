<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin_auth();

if ($_SESSION['admin_role'] !== 'admin') {
    header("Location: panel_empleado.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Nokia 1100</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navbar (Admin) -->
    <nav class="navbar">
        <div class="container nav-inner">
            <a href="#" class="logo">NOKIA<span>ADMIN</span></a>
            <div class="nav-links">
                <span class="nav-link">Hola, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                <a href="auth/logout.php" class="btn btn-outline btn-sm">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container" style="padding-top: 3rem;">
        
        <div style="text-align: center; margin-bottom: 4rem;">
            <p style="text-transform: uppercase; letter-spacing: 0.2em; font-size: 0.75rem; color: var(--primary); font-weight: 800; margin-bottom: 0.5rem;">Administración Central</p>
            <h1>Panel de Control</h1>
            <div style="height: 2px; width: 40px; background: var(--primary); margin: 1rem auto;"></div>
        </div>

        <div class="grid-products admin-grid-cards">
            <!-- Cards -->
            <div class="glass-card card-admin hover-effect">
                <h3>Usuarios</h3>
                <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Administrar permisos y cuentas.</p>
                <div style="display: grid; gap: 0.5rem;">
                    <a href="usuarios/admin_usuarios.php" class="btn btn-primary">Gestionar Usuarios</a>
                    <a href="register.php" class="btn btn-primary">Registrar Nuevo</a>
                </div>
            </div>

            <div class="glass-card card-admin hover-effect">
                <h3>Ventas</h3>
                <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Ver transacciones y detalles.</p>
                <a href="ventas/ventas.php" class="btn btn-primary" style="width: 100%">Ver Ventas</a>
            </div>

            <div class="glass-card card-admin hover-effect">
                <h3>Inventario</h3>
                <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Control de stock en tiempo real.</p>
                <div style="display: grid; gap: 0.5rem;">
                    <a href="inventario/inventario.php" class="btn btn-primary">Ver Inventario</a>
                    <a href="inventario/agregar_stock.php" class="btn btn-primary">Agregar Stock</a>
                </div>
            </div>

            <div class="glass-card card-admin hover-effect">
                <h3>Estadísticas</h3>
                <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Análisis de rendimiento.</p>
                <a href="ventas/graficas_ventas.php" class="btn btn-primary" style="width: 100%">Ver Gráficos</a>
            </div>

            <div class="glass-card card-admin hover-effect">
                <h3>Proveedores</h3>
                <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Gestión de suministros.</p>
                <div style="display: grid; gap: 0.5rem;">
                    <a href="compras/proveedores.php" class="btn btn-primary">Proveedores</a>
                    <a href="compras/nueva_compra.php" class="btn btn-primary">Registrar Compra</a>
                </div>
            </div>

            <div class="glass-card card-admin hover-effect">
                <h3>Tienda</h3>
                <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Vista previa del cliente.</p>
                <a href="../tienda/index.php" class="btn btn-primary" style="width: 100%">Ir a la Tienda</a>
            </div>
        </div>
    </div>
</body>
</html>
