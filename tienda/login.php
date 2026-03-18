<?php
// tienda/login.php
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
    <title>Login - Nokia Store</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="dashboard-container">
    <div class="glass-card" style="max-width: 400px;">
        <div class="text-center mb-4">
            <a href="index.php" class="logo">NOKIA<span>STORE</span></a>
            <h2 class="mt-4">Iniciar Sesión</h2>
            <p>Accede a tu cuenta de cliente</p>
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

        <form action="auth/login_process.php" method="POST">
            <?php 
                $redirect = isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : (isset($_POST['redirect']) ? htmlspecialchars($_POST['redirect']) : '');
            ?>
            <input type="hidden" name="redirect" value="<?php echo $redirect; ?>">
            
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" required placeholder="tu@email.com">
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Ingresar</button>
        </form>

        <div class="text-center mt-4">
            <p>¿No tienes cuenta? <a href="register.php" style="color: var(--primary); font-weight: 600;">Regístrate aquí</a></p>
            <hr style="border: 0; border-top: 1px solid var(--border); margin: 1.5rem 0;">
            <a href="../admin/login.php" style="font-size: 0.8rem; color: var(--text-dim);">Acceso Personal (Stock)</a>
        </div>
    </div>
</body>
</html>
