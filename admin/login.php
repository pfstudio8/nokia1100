<?php
// admin/login.php
session_start();
if (isset($_SESSION['admin_id'])) {
    header("Location: panel_admin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Stock - Nokia System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="dashboard-container">
    <div class="glass-card card-admin" style="max-width: 400px;">
        <div class="text-center mb-4">
            <a href="#" class="logo">NOKIA<span>SYSTEM</span></a>
            <h2 class="mt-4">Gestión de Stock</h2>
            <p>Acceso para Administradores y Empleados</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form action="auth/login_process.php" method="POST">
            <div class="form-group">
                <label for="username">Nombre de Usuario</label>
                <input type="text" id="username" name="username" required placeholder="ej: admin_uriel">
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Entrar al Sistema</button>
        </form>

        <div class="text-center mt-4">
            <a href="../tienda/index.php" style="font-size: 0.8rem; color: var(--text-dim);">Ir a la Tienda Online</a>
        </div>
    </div>
</body>
</html>
