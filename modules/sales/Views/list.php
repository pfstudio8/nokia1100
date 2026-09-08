<?php
// modules/sales/Views/list.php

Layout::renderHead('Historial de Ventas - Nokia 1100');
Layout::renderAdminSidebar('ventas');
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/sales/sales_list.css?v=<?php echo time(); ?>">

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8">
        <div class="dashboard-header flex justify-between items-center mb-8" style="flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2>Registro de Ventas</h2>
                <p>Historial completo de productos vendidos</p>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center; justify-content: flex-end; flex: 1;">
                <input type="text" id="search-input" placeholder="Buscar venta..." style="width: 250px; padding: 0.5rem 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 0.9rem;">
                <!-- Botones de Exportación -->
                <button type="button" onclick="exportTableToExcel('sales-table', 'ventas', this)" class="px-3 py-1.5 rounded-lg border border-green-500/30 bg-green-500/10 hover:bg-green-500/20 text-xs font-medium text-green-400 hover:text-green-300 transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">download</span> Excel
                </button>
                <button type="button" onclick="exportTableToPDF('sales-table', 'Historial de Ventas', 'ventas', this)" class="px-3 py-1.5 rounded-lg border border-red-500/30 bg-red-500/10 hover:bg-red-500/20 text-xs font-medium text-red-400 hover:text-red-300 transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">download</span> PDF
                </button>
                <a href="<?php echo BASE_URL; ?>/modules/sales/new_sale.php" class="px-3 py-1.5 rounded-lg border border-primary bg-primary text-background hover:opacity-90 text-xs font-medium transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">add</span> Nueva Venta
                </a>
                <a href="<?php echo BASE_URL; ?>/modules/admin/dashboard.php" class="btn-back">Volver</a>
            </div>
        </div>

        <div class="table-container">
            <table id="sales-table">
                <thead>
                    <tr>
                        <th>ID Venta</th>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Método</th>
                        <th>Estado</th>
                        <th style="text-align: right;">Total</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($sales) > 0): ?>
                        <?php foreach($sales as $row): ?>
                            <tr class="<?php echo ($row['estado'] === 'anulada') ? 'opacity-60' : ''; ?>">
                                <td class="text-primary font-medium text-sm">#TX-<?php echo $row['id_venta']; ?></td>
                                <td class="text-text-muted text-sm"><?php echo date('d/m/Y H:i', strtotime($row['fecha'])); ?></td>
                                <td class="font-medium font-display <?php echo ($row['estado'] === 'anulada') ? 'line-through text-text-muted' : ''; ?>"><?php echo htmlspecialchars($row['producto']); ?></td>
                                <td><?php echo $row['cantidad']; ?> u.</td>
                                <td>
                                    <span class="px-2 py-1 rounded border border-border text-[10px] font-medium text-text-muted uppercase tracking-wider">
                                        <?php echo htmlspecialchars($row['metodo_de_pago']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['estado'] === 'anulada'): ?>
                                        <span class="px-2 py-1 rounded border border-red-500/30 text-[10px] font-medium text-red-400 uppercase tracking-wider bg-red-500/10">Anulada</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 rounded border border-green-500/30 text-[10px] font-medium text-green-400 uppercase tracking-wider bg-green-500/10">Completada</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; font-weight: 600;">$<?php echo number_format($row['total'], 2); ?></td>
                                <td style="text-align: right; min-width: 200px;">
                                    <div style="display: flex; gap: 0.75rem; justify-content: flex-end; align-items: center; flex-wrap: nowrap; white-space: nowrap;">
                                        <a href="invoice.php?id=<?php echo $row['id_venta']; ?>" target="_blank" class="whitespace-nowrap px-3 py-1 bg-surface border border-border rounded text-sm text-text-main hover:bg-border transition inline-flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[1rem]">print</span> Factura
                                        </a>
                                        <?php if ($row['estado'] !== 'anulada'): ?>
                                        <button type="button" onclick="confirmRollback(<?php echo $row['id_venta']; ?>)" class="whitespace-nowrap px-3 py-1 bg-red-500/10 border border-red-500/20 rounded text-sm text-red-400 hover:bg-red-500 hover:text-white transition inline-flex items-center gap-1 shadow-[0_0_10px_rgba(239,68,68,0.1)] hover:shadow-[0_0_15px_rgba(239,68,68,0.3)]" style="width: auto; padding: 0.35rem 0.75rem; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.4); color: #f87171; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.75rem;">
                                            <span class="material-symbols-outlined text-[1rem]">undo</span> Anular
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="<?php echo BASE_URL; ?>/modules/sales/sales_list.js?v=<?php echo time(); ?>"></script>
<?php Layout::renderFooter(); ?>
