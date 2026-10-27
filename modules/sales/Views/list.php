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
                <button type="button" onclick="exportTableToExcel('sales-table', 'ventas', this)" class="px-3 py-2 rounded-xl border border-green-500/30 bg-green-500/10 hover:bg-green-500/20 text-xs font-medium text-green-400 hover:text-green-300 transition-colors flex items-center gap-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-[16px]">download</span> Excel
                </button>
                <button type="button" onclick="exportTableToPDF('sales-table', 'Historial de Ventas', 'ventas', this)" class="px-3 py-2 rounded-xl border border-red-500/30 bg-red-500/10 hover:bg-red-500/20 text-xs font-medium text-red-400 hover:text-red-300 transition-colors flex items-center gap-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-[16px]">download</span> PDF
                </button>
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
                                        <a href="index.php?action=invoice&id=<?php echo $row['id_venta']; ?>" target="_blank" class="whitespace-nowrap px-3 py-1 bg-surface border border-border rounded text-sm text-text-main hover:bg-border transition inline-flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[1rem]">print</span> Factura
                                        </a>
                                        <?php if ($row['estado'] !== 'anulada'): ?>
                                        <button type="button" onclick="confirmRollback(<?php echo $row['id_venta']; ?>)" class="whitespace-nowrap px-3 py-1 bg-red-500/10 border border-red-500/20 rounded text-sm text-red-400 hover:bg-red-500 hover:text-white transition inline-flex items-center gap-1 shadow-[0_0_10px_rgba(239,68,68,0.1)] hover:shadow-[0_0_15px_rgba(239,68,68,0.3)]" style="width: auto; padding: 0.35rem 0.75rem; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.4); color: #f87171; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.75rem;">
                                            <span class="material-symbols-outlined text-[1rem]">undo</span> Anular
                                        </button>
                                        <?php else: ?>
                                        <a href="credit_note.php?id=<?php echo $row['id_venta']; ?>" target="_blank" class="whitespace-nowrap px-3 py-1 bg-red-500/10 border border-red-500/20 rounded text-sm text-red-400 hover:bg-red-500 hover:text-white transition inline-flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[1rem]">receipt_long</span> Nota Crédito
                                        </a>
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

        <?php 
        $total_items = $total_items ?? count($sales ?? []);
        $limit = $limit ?? 10;
        $page = $page ?? 1;
        $total_pages = $total_pages ?? max(1, ceil($total_items / $limit));
        $count_current = count($sales ?? []);
        $from = ($total_items > 0) ? (($page - 1) * $limit + 1) : 0;
        $to = ($total_items > 0) ? min($from + $count_current - 1, $total_items) : 0;

        $query_args = [];
        ?>

        <div class="mt-6 pt-4 border-t border-border/50 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-text-muted">
            <div>
                <?php if ($total_items > 0): ?>
                    Mostrando <strong class="text-text-main font-semibold"><?php echo $from; ?>–<?php echo $to; ?></strong> de <strong class="text-text-main font-semibold"><?php echo $total_items; ?></strong> ventas
                    <span class="mx-1.5 opacity-40">•</span>
                    Página <strong class="text-primary font-semibold"><?php echo $page; ?></strong> de <strong class="text-text-main font-semibold"><?php echo $total_pages; ?></strong>
                <?php else: ?>
                    <span>No hay ventas registradas</span>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="flex items-center gap-1.5 flex-wrap justify-center">
                    <?php if ($page > 1): 
                        $prev_args = $query_args;
                        $prev_args['page'] = $page - 1;
                    ?>
                        <a href="?<?php echo http_build_query($prev_args); ?>" class="px-2.5 py-1.5 border border-border rounded-lg bg-surface hover:bg-surface-hover text-text-main transition-colors flex items-center gap-1" title="Página anterior">
                            <span class="material-symbols-outlined text-[16px]">chevron_left</span>
                            <span class="hidden md:inline">Anterior</span>
                        </a>
                    <?php else: ?>
                        <span class="px-2.5 py-1.5 border border-border/40 rounded-lg bg-surface/30 text-text-muted/40 cursor-not-allowed flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">chevron_left</span>
                            <span class="hidden md:inline">Anterior</span>
                        </span>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): 
                        $num_args = $query_args;
                        $num_args['page'] = $i;
                        $isCurrent = ($i === $page);
                    ?>
                        <a href="?<?php echo http_build_query($num_args); ?>" class="min-w-[32px] h-8 flex items-center justify-center px-2.5 rounded-lg border text-xs font-semibold transition-all <?php echo $isCurrent ? 'bg-primary text-background border-primary shadow-sm shadow-primary/20' : 'bg-surface border-border text-text-muted hover:text-text-main hover:bg-surface-hover'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): 
                        $next_args = $query_args;
                        $next_args['page'] = $page + 1;
                    ?>
                        <a href="?<?php echo http_build_query($next_args); ?>" class="px-2.5 py-1.5 border border-border rounded-lg bg-surface hover:bg-surface-hover text-text-main transition-colors flex items-center gap-1" title="Página siguiente">
                            <span class="hidden md:inline">Siguiente</span>
                            <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                        </a>
                    <?php else: ?>
                        <span class="px-2.5 py-1.5 border border-border/40 rounded-lg bg-surface/30 text-text-muted/40 cursor-not-allowed flex items-center gap-1">
                            <span class="hidden md:inline">Siguiente</span>
                            <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<script>
    window.CSRF_TOKEN = '<?php echo htmlspecialchars($_SESSION['session_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>';
</script>
<script src="<?php echo BASE_URL; ?>/modules/sales/sales_list.js?v=<?php echo time(); ?>"></script>
<?php Layout::renderFooter(); ?>
