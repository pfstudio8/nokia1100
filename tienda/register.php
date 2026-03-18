<?php
// tienda/register.php
session_start();
if (isset($_SESSION['cliente_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Nokia Store</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="dashboard-container">
    <div class="glass-card" style="max-width: 450px;">
        <div class="text-center mb-4">
            <a href="index.php" class="logo">NOKIA<span>STORE</span></a>
            <h2 class="mt-4">Crear Cuenta</h2>
            <p>Registrate para realizar tus pedidos</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form action="auth/register_process.php" method="POST">
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" required placeholder="tu@email.com">
                <small style="color: var(--text-dim); font-size: 0.75rem;">Usarás este correo para ingresar.</small>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required placeholder="••••••••" minlength="6">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirmar Contraseña</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Registrarse</button>
        </form>

        <div class="text-center mt-4">
            <p>¿Ya tienes cuenta? <a href="login.php" style="color: var(--primary); font-weight: 600;">Inicia sesión</a></p>
        </div>
    </div>
</body>
</html>
