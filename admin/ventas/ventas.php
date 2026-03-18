<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin_auth();
require_once '../../config/bd.php';

// Obtener ventas + productos vendidos
$sql = "SELECT 
            v.id_venta, 
            v.fecha, 
            v.total, 
            v.metodo_de_pago,
            COALESCE(dv.nombre_producto, p.nombre) AS producto,
            dv.cantidad
        FROM venta v
        INNER JOIN detalle_venta dv ON v.id_venta = dv.id_venta
        LEFT JOIN producto p ON dv.id_producto = p.id_producto
        ORDER BY v.fecha DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas - Nokia 1100</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

</head>
<body>
    <div class="dashboard-container" style="padding-top: 3rem;">
        
        <div style="margin-bottom: 3rem; width: 100%; max-width: 1000px; margin-left: auto; margin-right: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <p style="text-transform: uppercase; letter-spacing: 0.2em; font-size: 0.7rem; color: var(--primary); font-weight: 800; margin-bottom: 0.25rem; opacity: 0.8;">Reportes & Transacciones</p>
                    <h1 style="margin-bottom: 0; font-size: 2.2rem; letter-spacing: -0.02em;">Historial de Ventas</h1>
                </div>
                <div style="display: flex; gap: 0.75rem;">
                    <a href="../panel_admin.php" class="btn btn-outline" style="padding: 0.6rem 1.2rem; font-size: 0.85rem;">Cerrar</a>
                    <a href="exportar_ventas.php" class="btn btn-primary" style="padding: 0.6rem 1.5rem; font-size: 0.85rem;">Exportar a Excel</a>
                </div>
            </div>
            <div style="height: 3px; width: 30px; background: var(--primary); margin-top: 1.5rem; border-radius: 2px;"></div>
        </div>

        <div class="glass-card" style="padding: 1.5rem;">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Fecha & Hora</th>
                            <th style="width: 300px;">Producto</th>
                            <th style="text-align: center;">Cant.</th>
                            <th>Método</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><span style="color: var(--text-dim);">#</span><?php echo $row['id_venta']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['fecha'])); ?></td>
                                    <td style="font-weight: 500; color: var(--text-main);"><?php echo htmlspecialchars($row['producto']); ?></td>
                                    <td style="text-align: center;"><?php echo $row['cantidad']; ?></td>
                                    <td><span class="badge badge-info" style="font-size: 0.7rem;"><?php echo htmlspecialchars($row['metodo_de_pago']); ?></span></td>
                                    <td style="text-align: right; font-weight: 700; color: var(--success);">$<?php echo number_format($row['total'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 4rem; color: var(--text-dim);">
                                    No se encontraron registros de ventas en la base de datos.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>
</body>
</html>
