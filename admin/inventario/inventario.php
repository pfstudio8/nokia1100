<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin_auth();
?>
<?php
require_once '../../config/bd.php';

// Obtener inventario con nombres de productos
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where_clause = "";
if ($search) {
    $where_clause = " WHERE p.nombre LIKE '%$search%' OR d.marca LIKE '%$search%' OR d.modelo LIKE '%$search%'";
}

$sql = "SELECT p.id_producto, p.nombre, d.marca, d.modelo, i.cantidad, p.precio, p.is_active
        FROM inventario i
        JOIN producto p ON i.id_producto = p.id_producto
        JOIN producto_detalle d ON p.id_producto = d.id_producto
        $where_clause
        ORDER BY p.nombre ASC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - Nokia 1100</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <div class="dashboard-container" style="padding-top: 3rem;">
        
        <div style="margin-bottom: 3rem; width: 100%; max-width: 1000px; margin-left: auto; margin-right: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <p style="text-transform: uppercase; letter-spacing: 0.2em; font-size: 0.7rem; color: var(--primary); font-weight: 800; margin-bottom: 0.25rem; opacity: 0.8;">Logística & Existencias</p>
                    <h1 style="margin-bottom: 0; font-size: 2.2rem; letter-spacing: -0.02em;">Control de Inventario</h1>
                </div>
                <div style="display: flex; gap: 0.75rem;">
                    <a href="<?php echo $_SESSION['admin_role'] === 'admin' ? '../panel_admin.php' : '../panel_empleado.php'; ?>" class="btn btn-outline" style="padding: 0.6rem 1.2rem; font-size: 0.85rem;">Cerrar</a>
                    <a href="agregar_stock.php" class="btn btn-primary" style="padding: 0.6rem 1.5rem; font-size: 0.85rem;">Nuevo Producto</a>
                </div>
            </div>
            <div style="height: 3px; width: 30px; background: var(--primary); margin-top: 1.5rem; border-radius: 2px;"></div>
        </div>

        <div class="glass-card" style="padding: 1.5rem;">
            <div class="toolbar">
                <form method="GET" action="" style="flex: 1; display: flex; gap: 0.75rem; align-items: center; max-width: 500px;">
                    <input type="text" name="search" placeholder="Buscar producto, marca..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; background: transparent; border: none; padding: 0.5rem 0; font-size: 0.9rem; border-bottom: 1px solid rgba(255,255,255,0.1);">
                    <button type="submit" class="btn btn-sm btn-primary" style="padding: 0.4rem 1rem;">Filtrar</button>
                    <?php if ($search): ?>
                        <a href="inventario.php" class="btn btn-sm btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.75rem; text-decoration: none;">Limpiar</a>
                    <?php endif; ?>
                </form>
                <div style="font-size: 0.8rem; color: var(--text-muted);">
                    Mostrando: <strong><?php echo $result->num_rows; ?></strong> productos
                </div>
            </div>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'has_sales'): ?>
                <div class="alert alert-error" style="margin-bottom: 1.5rem;">
                    Acción bloqueada: El producto tiene ventas registradas. Use 'Desactivar'.
                </div>
            <?php endif; ?>

            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 250px;">Producto</th>
                            <th style="width: 200px;">Detalles</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Estado</th>
                            <th>Visible</th>
                            <?php if ($_SESSION['admin_role'] === 'admin'): ?>
                                <th style="text-align: right;">Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div style="color: var(--text-main); font-weight: 700; font-size: 0.95rem;"><?php echo htmlspecialchars($row['nombre']); ?></div>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.85rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['marca'] . ' ' . $row['modelo']); ?></div>
                                    </td>
                                    <td style="font-family: 'Inter', sans-serif; font-weight: 600;">
                                        $<?php echo number_format($row['precio'], 0, ',', '.'); ?>
                                    </td>
                                    <td style="font-weight: 700;">
                                        <?php echo $row['cantidad']; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['cantidad'] > 5): ?>
                                            <span class="stock-badge stock-ok">DISPONIBLE</span>
                                        <?php else: ?>
                                            <span class="stock-badge stock-low">BAJO STOCK</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['is_active']): ?>
                                            <span style="color: #10b981; font-size: 0.75rem; font-weight: 800;">● ACTIVO</span>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 0.75rem;">○ OCULTO</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($_SESSION['admin_role'] === 'admin'): ?>
                                    <td style="text-align: right;">
                                        <div class="btn-group-sm" style="justify-content: flex-end;">
                                            <a href="../../api/cambiar_estado.php?id=<?php echo $row['id_producto']; ?>" class="btn btn-sm btn-outline" style="font-size: 0.7rem; padding: 0.3rem 0.6rem;">
                                                <?php echo $row['is_active'] ? 'OCULTAR' : 'MOSTRAR'; ?>
                                            </a>
                                            <a href="../productos_imagenes.php?id=<?php echo $row['id_producto']; ?>" class="btn btn-sm btn-outline" style="font-size: 0.7rem; padding: 0.3rem 0.6rem;">
                                                IMAGE
                                            </a>
                                            <a href="editar_stock.php?id=<?php echo $row['id_producto']; ?>" class="btn btn-sm btn-primary" style="font-size: 0.7rem; padding: 0.3rem 0.6rem;">
                                                EDIT
                                            </a>
                                            <a href="eliminar_producto.php?id=<?php echo $row['id_producto']; ?>" class="btn btn-sm btn-danger" style="font-size: 0.7rem; padding: 0.3rem 0.6rem;" onclick="return confirm('¿Eliminar producto?');">
                                                DEL
                                            </a>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo $_SESSION['admin_role'] === 'admin' ? '7' : '6'; ?>" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                    <div style="margin-bottom: 1rem; color: var(--text-muted);">No se encontraron productos</div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
