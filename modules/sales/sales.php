<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

require_once __DIR__ . '/../../classes/Layout.php';

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

Layout::renderHead('Historial de Ventas - Nokia 1100');
Layout::renderAdminSidebar('ventas');
?>
<style>
    .btn-export {
        background: var(--text-main);
        padding: 0.5rem 1rem;
        text-decoration: none;
        color: var(--text-inverse);
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        border: 1px solid var(--border);
        transition: all 0.2s;
    }
    .btn-export:hover { 
        background: var(--text-muted); 
    }
</style>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8">
        <div class="dashboard-header flex justify-between items-center mb-8" style="flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2>Registro de Ventas</h2>
                <p>Historial completo de productos vendidos</p>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center; justify-content: flex-end; flex: 1;">
                <input type="text" id="search-input" placeholder="Buscar venta..." style="width: 250px; padding: 0.5rem 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 0.9rem;">
                <a href="<?php echo BASE_URL; ?>/modules/admin/dashboard.php" class="btn-back">Volver</a>
                <a href="export_sales.php" class="btn-export flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">download</span> Exportar
                </a>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID Venta</th>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Método</th>
                        <th style="text-align: right;">Total</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="text-primary font-medium text-sm">#TX-<?php echo $row['id_venta']; ?></td>
                                <td class="text-text-muted text-sm"><?php echo date('d/m/Y H:i', strtotime($row['fecha'])); ?></td>
                                <td class="font-medium font-display"><?php echo htmlspecialchars($row['producto']); ?></td>
                                <td><?php echo $row['cantidad']; ?> u.</td>
                                <td>
                                    <span class="px-2 py-1 rounded border border-border text-[10px] font-medium text-text-muted uppercase tracking-wider">
                                        <?php echo htmlspecialchars($row['metodo_de_pago']); ?>
                                    </span>
                                </td>
                                <td style="text-align: right; font-weight: 600;">$<?php echo number_format($row['total'], 2); ?></td>
                                <td style="text-align: right;">
                                    <a href="invoice.php?id=<?php echo $row['id_venta']; ?>" target="_blank" class="px-3 py-1 bg-surface border border-border rounded text-sm text-text-main hover:bg-border transition inline-flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[1rem]">print</span> Factura
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                No hay ventas registradas en el sistema.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php Layout::renderFooter(); ?>
