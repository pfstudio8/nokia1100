<?php
// admin/register.php
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin_auth();

// Only Admins can register new users
if ($_SESSION['admin_role'] !== 'admin') {
    die("Acceso denegado. Solo administradores pueden registrar nuevos usuarios.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Personal - Nokia System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="dashboard-container">
    <div class="glass-card card-admin" style="max-width: 500px;">
        <div class="text-center mb-4">
            <h2 class="mt-4">Registrar Personal</h2>
            <p>Crear nueva cuenta de Administrador o Empleado</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <form action="auth/register_process.php" method="POST">
            <div class="form-group">
                <label for="nombre_usuario">Nombre de Usuario</label>
                <input type="text" id="nombre_usuario" name="nombre_usuario" required placeholder="ej: uriel_admin">
            </div>
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" required placeholder="staff@nokia.com">
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required placeholder="••••••••" minlength="6">
            </div>
            <div class="form-group">
                <label for="rol">Rol del Usuario</label>
                <select name="rol" id="rol" required>
                    <option value="empleado">Empleado (Solo stock y ventas)</option>
                    <option value="admin">Administrador (Acceso total)</option>
                </select>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <a href="panel_admin.php" class="btn btn-outline" style="flex: 1;">Volver</a>
                <button type="submit" class="btn btn-primary" style="flex: 2;">Registrar Usuario</button>
            </div>
        </form>
    </div>
</body>
</html>
