<?php
// modules/sales/Views/list.php

// Variables inyectadas por SalesController::sales()
// @var array $sales
// @var int $page
// @var int $total_pages
$sales = $sales ?? [];
$page = $page ?? 1;
$total_pages = $total_pages ?? 1;

Layout::renderHead('Historial de Ventas - Nokia 1100');
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    Layout::renderAdminSidebar('ventas');
} else {
    Layout::renderEmployeeSidebar('venta');
}
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/sales/sales_list.css?v=<?php echo time(); ?>">

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8">
        <div class="dashboard-header flex justify-between items-center mb-8" style="flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2>Registro de Ventas</h2>
                <p>Historial completo de productos vendidos</p>
            </div>
            <div style="display: flex; gap: 0.75rem; align-items: center; justify-content: flex-end; flex: 1; flex-wrap: wrap;">
                <input type="text" id="search-input" placeholder="Buscar venta..." class="w-full sm:w-[220px]" style="padding: 0.5rem 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 0.9rem;">
                <?php echo Layout::renderExportButtons('sales-table', 'Historial de Ventas', 'ventas'); ?>
                <a href="<?php echo BASE_URL; ?>/modules/sales/index.php?action=new_sale" class="px-3.5 py-1.5 rounded-lg border border-primary bg-primary text-background hover:bg-primary-hover text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-sm shadow-primary/20">
                    <span class="material-symbols-outlined text-[16px]">add</span> Nueva Venta
                </a>
                <?php
                $backUrl = isset($_SESSION['role']) && $_SESSION['role'] === 'admin' 
                            ? BASE_URL . '/modules/admin/index.php' 
                            : BASE_URL . '/modules/employee/index.php';
                ?>
                <a href="<?php echo $backUrl; ?>" class="px-3.5 py-1.5 rounded-lg border border-border bg-surface hover:bg-surface-hover text-xs font-medium text-text-muted hover:text-text-main transition-colors flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span> Volver
                </a>
            </div>
        </div>

        <div class="table-container sales-table-wrap">
            <table id="sales-table">
                <thead>
                    <tr>
                        <th>ID Venta</th>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Método de Pago</th>
                        <th>Estado</th>
                        <th style="text-align: right;">Total</th>
                        <th class="sale-actions-head">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($sales) > 0): ?>
                        <?php foreach($sales as $row): ?>
                            <?php $isVoided = (($row['estado'] ?? '') === 'anulada'); ?>
                            <tr class="<?php echo $isVoided ? 'sale-row-voided' : ''; ?>">
                                <td class="font-mono font-bold text-xs <?php echo $isVoided ? 'text-[#7a7f84]' : 'text-[#15171a]'; ?>">#TX-<?php echo (int)$row['id_venta']; ?></td>
                                <td class="text-xs font-medium <?php echo $isVoided ? 'text-[#8a8f94]' : 'text-[#464b4f]'; ?>"><?php echo date('d/m/Y H:i', strtotime($row['fecha'])); ?></td>
                                <td class="font-medium font-display <?php echo $isVoided ? 'line-through text-[#7a7f84]' : 'text-[#15171a]'; ?>"><?php echo htmlspecialchars($row['producto']); ?></td>
                                <td class="<?php echo $isVoided ? 'text-[#7a7f84]' : 'text-[#15171a]'; ?>"><?php echo (int)$row['cantidad']; ?> u.</td>
                                <td class="sale-payment-cell">
                                    <?php echo Layout::renderPaymentBadge($row['metodo_de_pago'] ?? 'N/A'); ?>
                                </td>
                                <td>
                                    <?php if ($isVoided): ?>
                                        <span class="sale-status-badge sale-status-voided">Anulada</span>
                                    <?php else: ?>
                                        <span class="sale-status-badge sale-status-completed">Completada</span>
                                    <?php endif; ?>
                                </td>
                                <td class="font-mono text-xs <?php echo $isVoided ? 'line-through text-[#7a7f84]' : 'text-[#15171a]'; ?>" style="text-align: right; font-weight: 700;">$<?php echo number_format((float)$row['total'], 2); ?></td>
                                <td class="sale-actions-cell">
                                    <div class="sale-actions-grid">
                                        <?php if (!$isVoided): ?>
                                        <a href="index.php?action=invoice&id=<?php echo (int)$row['id_venta']; ?>" target="_blank" class="sale-action-btn sale-btn-invoice">
                                            <span class="material-symbols-outlined">print</span>
                                            <span>Factura</span>
                                        </a>
                                        <button type="button" onclick="confirmRollback(<?php echo (int)$row['id_venta']; ?>)" class="sale-action-btn sale-btn-rollback">
                                            <span class="material-symbols-outlined">undo</span>
                                            <span>Anular</span>
                                        </button>
                                        <?php else: ?>
                                        <a href="credit_note.php?id=<?php echo (int)$row['id_venta']; ?>" target="_blank" class="sale-action-btn sale-btn-creditnote sale-btn-solo">
                                            <span class="material-symbols-outlined">receipt_long</span>
                                            <span>Nota Crédito</span>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                No hay ventas registradas en el sistema.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php
        $total_items = (int)($total_items ?? count($sales ?? []));
        $limit = (int)($limit ?? 10);
        $page = (int)($page ?? 1);
        $total_pages = (int)($total_pages ?? max(1, ceil($total_items / max(1, $limit))));
        echo Layout::renderPagination($page, $total_pages, $total_items, 'venta', 'ventas');
        ?>
    </div>
</main>
<script>
    window.CSRF_TOKEN = '<?php echo htmlspecialchars($_SESSION['session_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>';
</script>
<script src="<?php echo BASE_URL; ?>/modules/sales/sales_list.js?v=<?php echo time(); ?>"></script>
<?php Layout::renderFooter(); ?>
