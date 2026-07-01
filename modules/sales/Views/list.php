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
                <button type="button" onclick="exportTableToExcel('sales-table', 'ventas')" style="width: auto; padding: 0.5rem 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; color: var(--text-muted); font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">Excel</button>
                <button type="button" onclick="exportTableToPDF('sales-table', 'Historial de Ventas', 'ventas')" style="width: auto; padding: 0.5rem 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; color: var(--text-muted); font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">PDF</button>
                <a href="<?php echo BASE_URL; ?>/modules/admin/dashboard.php" class="btn-back">Volver</a>
                <button type="button" id="exportBtn" class="btn-export-premium button relative">
                    <span class="button-text">
                        <span class="material-symbols-outlined text-sm">download</span> Exportar
                    </span>
                    <span class="progress-percent">0%</span>
                    <div class="icon-container">
                        <div class="icon">
                            <svg viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" />
                                <path class="arrow-path" d="M12 4v12 M8 12l4 4 4-4" />
                                <path class="line-path" d="M2 16 Q12 16 22 16" />
                            </svg>
                        </div>
                    </div>
                </button>
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
                        <th style="text-align: right;">Total</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($sales) > 0): ?>
                        <?php foreach($sales as $row): ?>
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
