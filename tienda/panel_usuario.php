<?php
// tienda/panel_usuario.php
session_start();
require_once '../config/bd.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Cuenta - Nokia Store</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    
    <nav class="navbar">
        <div class="container nav-inner">
            <a href="index.php" class="logo">NOKIA<span>STORE</span></a>
            <div class="nav-links">
                <a href="index.php" class="nav-link">Inicio</a>
                <a href="catalogo.php" class="nav-link">Catálogo</a>
                <a href="mis_pedidos.php" class="nav-link">Mis Pedidos</a>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 3rem;">
        <h1 style="margin-bottom: 2rem;">Mi Cuenta</h1>
        
        <div class="grid-products" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
            
            <div class="card" style="padding: 2rem;">
                <h3 style="margin-bottom: 1rem;">Mis Pedidos</h3>
                <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Revisa el estado de tus compras y su historial.</p>
                <a href="mis_pedidos.php" class="btn btn-primary">Ver Historial</a>
            </div>

            <div class="card" style="padding: 2rem;">
                <h3 style="margin-bottom: 1rem;">Mis Datos</h3>
                <p style="color: var(--text-muted); mb-4">Usuario: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
                <!-- Future: Edit Profile -->
                <button class="btn btn-outline" disabled>Editar Perfil (Próximamente)</button>
            </div>

        </div>
    </div>

</body>
</html>
