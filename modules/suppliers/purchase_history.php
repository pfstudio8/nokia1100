<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}


// Fetch all purchases with supplier info
$sql = "SELECT c.*, p.nombre as proveedor_nombre,
        (SELECT COUNT(*) FROM detalle_compra WHERE id_compra = c.id_compra) as items
        FROM compra c
        JOIN proveedor p ON c.id_proveedor = p.id_proveedor
        ORDER BY c.fecha DESC";
$result = $conn->query($sql);
?>
<?php
require_once __DIR__ . '/../../classes/Layout.php';
Layout::renderHead('Historial de Compras - NOKIA1100');
Layout::renderAdminSidebar('proveedores');
?>
<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8 border border-border/50">
        <div class="flex justify-between items-center mb-8 pb-4 border-b border-border/50">
            <div>
                <h2 class="text-2xl font-display font-medium text-text-main">Historial de Compras</h2>
                <p class="text-text-muted text-sm mt-1">Órdenes emitidas y procesadas por los proveedores</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/modules/admin/dashboard.php" class="px-4 py-2 rounded-xl border border-border bg-surface hover:bg-surface-hover text-sm font-medium transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span> Volver
            </a>
        </div>

        <div class="overflow-x-auto bg-surface/20 rounded-2xl border border-border/50 mb-8">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface/50 border-b border-border/50 text-xs uppercase tracking-wider text-text-muted">
                        <th class="p-4 font-semibold">Fecha</th>
                        <th class="p-4 font-semibold">Proveedor</th>
                        <th class="p-4 font-semibold">Descripción</th>
                        <th class="p-4 font-semibold text-center">Items</th>
                        <th class="p-4 font-semibold text-right">Total</th>
                        <th class="p-4 font-semibold text-right">IVA</th>
                        <th class="p-4 font-semibold text-center">Detalles</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/30">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-surface/30 transition-colors">
                                <td class="p-4 text-sm font-medium text-text-main"><?php echo date('d/m/Y H:i', strtotime($row['fecha'])); ?></td>
                                <td class="p-4 text-sm text-primary font-medium"><?php echo htmlspecialchars($row['proveedor_nombre']); ?></td>
                                <td class="p-4 text-sm text-text-muted"><?php echo htmlspecialchars($row['descripcion']); ?></td>
                                <td class="p-4 text-sm text-center"><span class="bg-surface border border-border px-3 py-1 rounded-full text-text-muted"><?php echo $row['items']; ?></span></td>
                                <td class="p-4 text-sm text-right font-medium text-text-main font-display">$<?php echo number_format($row['total'], 2); ?></td>
                                <td class="p-4 text-sm text-right text-text-muted"><?php echo $row['iva']; ?>%</td>
                                <td class="p-4 text-center">
                                    <button class="text-text-main hover:text-primary transition-colors inline-flex items-center gap-1 text-sm font-medium" onclick="toggleDetails(<?php echo $row['id_compra']; ?>)">
                                        <span class="material-symbols-outlined text-[18px]">visibility</span> Ver
                                    </button>
                                </td>
                            </tr>
                            <tr id="detail-<?php echo $row['id_compra']; ?>" class="hidden bg-surface/10 border-t border-border/30">
                                <td colspan="7" class="p-0">
                                    <div class="px-8 py-6 bg-primary/5 rounded-b-2xl">
                                        <div class="grid grid-cols-2 gap-4 mb-4 text-sm border-b border-primary/10 pb-4">
                                            <div>
                                                <span class="font-semibold text-text-muted uppercase tracking-wider text-[10px]">Tiempo de Entrega</span>
                                                <div class="text-text-main font-medium mt-1"><?php echo htmlspecialchars($row['tiempo_entrega']); ?></div>
                                            </div>
                                            <div>
                                                <span class="font-semibold text-text-muted uppercase tracking-wider text-[10px]">Autorizado Por</span>
                                                <div class="text-text-main font-medium mt-1"><?php echo htmlspecialchars($row['autorizado_por']); ?></div>
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
                                            <table class="w-full text-left text-sm mt-4">
                                                <thead>
                                                    <tr class="text-text-muted uppercase tracking-wider text-[11px]">
                                                        <th class="pb-2 font-medium">Lote de Producto</th>
                                                        <th class="pb-2 font-medium text-center">Cantidad</th>
                                                        <th class="pb-2 font-medium text-right">Precio Compra</th>
                                                        <th class="pb-2 font-medium text-right">Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-border/20">
                                                    <?php while($d = $detail_query->fetch_assoc()): ?>
                                                        <tr>
                                                            <td class="py-2 text-text-main"><?php echo htmlspecialchars($d['nombre_producto'] . ' ' . $d['marca'] . ' ' . $d['modelo']); ?></td>
                                                            <td class="py-2 text-center text-text-muted"><?php echo $d['cantidad']; ?></td>
                                                            <td class="py-2 text-right text-text-main">$<?php echo number_format($d['precio_compra'], 2); ?></td>
                                                            <td class="py-2 text-right font-medium text-primary">$<?php echo number_format($d['cantidad'] * $d['precio_compra'], 2); ?></td>
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
                        <tr><td colspan="7" class="p-8 text-center text-text-muted text-sm border-none">No hay compras registradas en el historial</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php Layout::renderFooter(); ?>
<script>
    function toggleDetails(id) {
        const row = document.getElementById('detail-' + id);
        row.classList.toggle('hidden');
    }
</script>
