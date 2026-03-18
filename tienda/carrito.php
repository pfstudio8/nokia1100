<?php
// tienda/carrito.php
session_start();
require_once '../config/bd.php';

// Remove logic
if (isset($_GET['remove'])) {
    $idx = intval($_GET['remove']);
    if (isset($_SESSION['carrito'][$idx])) {
        unset($_SESSION['carrito'][$idx]);
        $_SESSION['carrito'] = array_values($_SESSION['carrito']);
    }
    header("Location: carrito.php");
    exit();
}

$cart = isset($_SESSION['carrito']) ? $_SESSION['carrito'] : [];
$total = 0;
foreach ($cart as $item) $total += $item['precio'] * $item['cantidad'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Carrito - Nokia Store</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    
    <nav class="navbar">
        <div class="container nav-inner">
            <a href="index.php" class="logo">NOKIA<span>STORE</span></a>
            <div class="nav-links">
                <a href="catalogo.php" class="nav-link">Seguir Comprando</a>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 3rem;">
        <h1 style="margin-bottom: 2rem;">Tu Carrito de Compras</h1>

        <?php if (empty($cart)): ?>
            <div class="card" style="padding: 4rem; text-align: center;">
                <h2 style="color: var(--text-muted);">El carrito está vacío</h2>
                <a href="catalogo.php" class="btn btn-primary" style="margin-top: 1rem;">Ir al Catálogo</a>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: 1fr 350px; gap: 2rem;">
                
                <!-- Cart Items -->
                <div class="card" style="padding: 0;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Precio</th>
                                <th>Cant</th>
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart as $i => $item): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 1rem;">
                                            <!-- Mini thumb -->
                                            <div style="width: 50px; height: 50px; background: #222; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                                <img src="<?php echo $item['imagen'] ?? 'https://via.placeholder.com/50'; ?>" style="max-height: 100%;">
                                            </div>
                                            <div>
                                                <div style="font-weight: 600;"><?php echo htmlspecialchars($item['nombre']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>$<?php echo number_format($item['precio'], 0, ',', '.'); ?></td>
                                    <td><?php echo $item['cantidad']; ?></td>
                                    <td style="font-weight: 700;">$<?php echo number_format($item['precio'] * $item['cantidad'], 0, ',', '.'); ?></td>
                                    <td><a href="?remove=<?php echo $i; ?>" style="color: var(--error);">✕</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div style="padding: 1rem;">
                        <a href="catalogo.php" style="color: var(--primary); font-size: 0.9rem;">← Agregar más productos</a>
                    </div>
                </div>

                <!-- Summary -->
                <div>
                    <div class="card" style="padding: 2rem; position: sticky; top: 100px;">
                        <h3 style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);">Resumen del Pedido</h3>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                            <span style="color: var(--text-muted);">Subtotal</span>
                            <span>$<?php echo number_format($total, 0, ',', '.'); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                            <span style="color: var(--text-muted);">Envío</span>
                            <span style="color: var(--success);">Gratis</span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border); font-size: 1.25rem; font-weight: 700;">
                            <span>Total</span>
                            <span>$<?php echo number_format($total, 0, ',', '.'); ?></span>
                        </div>

                        <?php if (isset($_SESSION['cliente_id'])): ?>
                            <a href="checkout.php" class="btn btn-primary" style="width: 100%; margin-top: 2rem;">Iniciar Pago</a>
                        <?php else: ?>
                            <div style="margin-top: 2rem; text-align: center;">
                                <p style="color: var(--text-muted); margin-bottom: 1rem;">Inicia sesión para finalizar</p>
                                <a href="login.php?redirect=carrito.php" class="btn btn-outline" style="width: 100%;">Ingresar</a>
                                <a href="register.php" style="display: block; margin-top: 0.5rem; font-size: 0.9rem; color: var(--primary);">O crear cuenta</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </div>

</body>
</html>
