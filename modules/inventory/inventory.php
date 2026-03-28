<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}
require_once __DIR__ . '/../../classes/Layout.php';

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

Layout::renderHead('Inventario - Nokia 1100');

if ($_SESSION['role'] === 'admin') {
    Layout::renderAdminSidebar('inventario');
} else {
    Layout::renderEmployeeSidebar('inventario');
}
?>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8">
        <div class="dashboard-header">
            <div>
                <h2>Inventario de Equipos</h2>
                <p>Control de stock en tiempo real</p>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="add_product.php" class="btn-primary" style="background: var(--primary-color); color: var(--text-inverse); text-decoration: none; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600; font-size: 0.85rem;">+ Nuevo Producto</a>
                <?php endif; ?>
                <a href="<?php echo BASE_URL . ($_SESSION['role'] === 'admin' ? '/modules/admin/dashboard.php' : '/modules/employee/dashboard.php'); ?>" class="btn-back">← Volver al Panel</a>
            </div>
        </div>

        <form method="GET" action="" style="margin-bottom: 1.5rem; display: flex; flex-direction: row; gap: 1rem; align-items: center;">
            <input type="text" id="search-input" name="search" placeholder="Buscar producto..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn-add" style="width: auto;">Buscar</button>
            <?php if ($search): ?>
                <a href="<?php echo BASE_URL; ?>/modules/inventory/inventory.php" class="btn-back">Limpiar</a>
            <?php endif; ?>
        </form>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'has_sales'): ?>
            <div class="alert alert-error">
                No se puede eliminar este producto porque tiene ventas asociadas. Por favor, desactívelo en su lugar para mantener el historial.
            </div>
        <?php endif; ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Marca / Modelo</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Estado Stock</th>
                        <th>Activo</th>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight: 500;"><?php echo htmlspecialchars($row['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($row['marca'] . ' ' . $row['modelo']); ?></td>
                                <td>$<?php echo number_format($row['precio'], 2); ?></td>
                                <td><?php echo $row['cantidad']; ?></td>
                                <td>
                                    <?php if ($row['cantidad'] > 5): ?>
                                        <span class="stock-badge stock-ok">Disponible</span>
                                    <?php else: ?>
                                        <span class="stock-badge stock-low">Bajo Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['is_active']): ?>
                                        <span class="stock-badge stock-ok">Sí</span>
                                    <?php else: ?>
                                        <span class="stock-badge stock-low">No</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                <td>
                                    <div style="display: flex; gap: 0.5rem; justify-content: flex-start;">
                                        <a href="change_status.php?id=<?php echo $row['id_producto']; ?>" class="btn-edit text-text-muted hover:text-text-main">
                                            <?php echo $row['is_active'] ? 'Desactivar' : 'Activar'; ?>
                                        </a>
                                        <a href="edit_stock.php?id=<?php echo $row['id_producto']; ?>" class="btn-edit text-primary">Editar</a>
                                        <a href="delete_product.php?id=<?php echo $row['id_producto']; ?>" class="btn-delete text-red-500" data-confirm="¿Estás seguro de que deseas eliminar este producto?" data-confirm-title="Eliminar Producto">Eliminar</a>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo $_SESSION['role'] === 'admin' ? '7' : '6'; ?>" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                No hay productos en inventario
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php Layout::renderFooter(); ?>
