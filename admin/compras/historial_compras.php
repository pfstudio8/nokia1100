<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin_auth();
require_once '../../config/bd.php';

// Fetch all purchases with supplier info
$sql = "SELECT c.*, p.nombre as proveedor_nombre,
        (SELECT COUNT(*) FROM detalle_compra WHERE id_compra = c.id_compra) as items
        FROM compra c
        JOIN proveedor p ON c.id_proveedor = p.id_proveedor
        ORDER BY c.fecha DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Compras - Nokia 1100</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .table-container { overflow-x: auto; margin-top: 2rem; }
        table { width: 100%; border-collapse: collapse; color: var(--text-color); }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { background: rgba(255, 255, 255, 0.05); font-weight: 600; }
        tr:hover { background: rgba(255, 255, 255, 0.02); }
        .btn-view { color: var(--primary-color); text-decoration: none; font-weight: 600; cursor: pointer; }
        .btn-view:hover { text-decoration: underline; }
        .detail-row { background: rgba(16, 185, 129, 0.05); }
        .detail-table { margin: 1rem 0; font-size: 0.9rem; }
        .detail-table th, .detail-table td { padding: 0.5rem; }
    </style>
</head>
<body>
    <div class="container dashboard-container" style="max-width: 1200px;">
        <div class="glass-card">
            <div class="header-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2>Historial de Compras</h2>
                <a href="../panel_admin.php" class="btn-back" style="text-decoration: none; color: var(--text-muted); border: 1px solid var(--border-color); padding: 0.5rem 1rem; border-radius: 8px;">Volver</a>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Proveedor</th>
                            <th>Descripción</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>IVA</th>
                            <th>Detalles</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['fecha'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['proveedor_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                                    <td><?php echo $row['items']; ?></td>
                                    <td>$<?php echo number_format($row['total'], 2); ?></td>
                                    <td><?php echo $row['iva']; ?>%</td>
                                    <td>
                                        <span class="btn-view" onclick="toggleDetails(<?php echo $row['id_compra']; ?>)">
                                            Ver ▼
                                        </span>
                                    </td>
                                </tr>
                                <tr id="detail-<?php echo $row['id_compra']; ?>" style="display: none;" class="detail-row">
                                    <td colspan="7">
                                        <div style="padding: 1rem;">
                                            <h4 style="margin-bottom: 1rem;">Detalles de la Compra</h4>
                                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                                <div>
                                                    <strong>Tiempo de Entrega:</strong> <?php echo htmlspecialchars($row['tiempo_entrega']); ?>
                                                </div>
                                                <div>
                                                    <strong>Autorizado Por:</strong> <?php echo htmlspecialchars($row['autorizado_por']); ?>
                                                </div>
                                            </div>
                                            <?php
                                                $id_compra = $row['id_compra'];
                                                $detail_query = $conn->query("SELECT dc.*, pch.nombre_producto, pch.marca, pch.modelo
                                                    FROM detalle_compra dc
                                                    LEFT JOIN producto_compra_historial pch ON dc.id_detalle_compra = pch.id_detalle_compra
                                                    WHERE dc.id_compra = $id_compra");
                                                
                                                if ($detail_query && $detail_query->num_rows > 0):
                                            ?>
                                                <table class="detail-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Producto</th>
                                                            <th>Cantidad</th>
                                                            <th>Precio Compra</th>
                                                            <th>Subtotal</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php while($d = $detail_query->fetch_assoc()): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($d['nombre_producto'] . ' ' . $d['marca'] . ' ' . $d['modelo']); ?></td>
                                                                <td><?php echo $d['cantidad']; ?></td>
                                                                <td>$<?php echo number_format($d['precio_compra'], 2); ?></td>
                                                                <td>$<?php echo number_format($d['cantidad'] * $d['precio_compra'], 2); ?></td>
                                                            </tr>
                                                        <?php endwhile; ?>
                                                    </tbody>
                                                </table>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">No hay compras registradas</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function toggleDetails(id) {
            const row = document.getElementById('detail-' + id);
            if (row.style.display === 'none') {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
            }
        }
    </script>
</body>
</html>
