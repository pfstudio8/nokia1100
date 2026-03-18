<?php
require_once __DIR__ . '/../includes/auth.php';
require_cliente_auth();
require_once __DIR__ . '/../config/bd.php';

$user_id = $_SESSION['cliente_id'];

// Fetch Orders
$sql = "SELECT * FROM venta WHERE id_usuario = ? ORDER BY fecha DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Pedidos - Nokia Store</title>
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
                <a href="mis_pedidos.php" class="nav-link active">Mis Pedidos</a>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 3rem;">
        <h1 style="margin-bottom: 2rem;">Historial de Compras</h1>

        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success">¡Compra realizada con éxito! Tu pedido está en proceso.</div>
        <?php endif; ?>

        <?php if ($result->num_rows > 0): ?>
            <div class="grid-products" style="grid-template-columns: 1fr; gap: 1rem;">
                <?php while($venta = $result->fetch_assoc()): ?>
                    <div class="card" style="flex-direction: row; align-items: center; padding: 1.5rem; gap: 2rem; flex-wrap: wrap;">
                        <div>
                            <div style="font-size: 0.9rem; color: var(--text-muted);">Pedido #<?php echo $venta['id_venta']; ?></div>
                            <div style="font-weight: 600; margin-top: 0.2rem;"><?php echo date('d/m/Y H:i', strtotime($venta['fecha'])); ?></div>
                        </div>
                        
                        <div style="flex: 1;">
                            <div style="font-size: 0.9rem; color: var(--text-muted);">Dirección de Envío</div>
                            <div><?php echo htmlspecialchars($venta['direccion_envio'] ?? 'Retiro en local'); ?></div>
                        </div>

                        <div>
                            <div style="font-size: 0.9rem; color: var(--text-muted);">Total</div>
                            <div style="color: var(--primary); font-weight: 700; font-size: 1.2rem;">$<?php echo number_format($venta['total'], 0, ',', '.'); ?></div>
                        </div>

                        <div>
                            <span class="card-badge" style="background: var(--primary); color: black;">
                                <?php echo ucfirst($venta['estado'] ?? 'Pendiente'); ?>
                            </span>
                        </div>
                        
                        <!-- Details (could be expanded) -->
                        <div style="width: 100%; border-top: 1px solid var(--border); margin-top: 1rem; padding-top: 1rem;">
                           <small style="color: var(--text-muted);">Productos:</small>
                           <?php
                               $id_venta = $venta['id_venta'];
                               $detalles = $conn->query("SELECT * FROM detalle_venta WHERE id_venta = $id_venta");
                               while($d = $detalles->fetch_assoc()) {
                                   echo "<div style='display:flex; justify-content:space-between; font-size:0.9rem; margin-top:0.2rem;'>";
                                   echo "<span>{$d['cantidad']}x {$d['nombre_producto']}</span>";
                                   echo "<span>$" . number_format($d['precio_unitario'],0,',','.') . "</span>";
                                   echo "</div>";
                               }
                           ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="card" style="padding: 3rem; text-align: center;">
                <p>No tienes pedidos realizados aún.</p>
                <a href="catalogo.php" class="btn btn-primary" style="margin-top: 1rem;">Ir a Comprar</a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
