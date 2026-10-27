<?php
// modules/workshop/Views/history.php

Layout::renderHead('Historial de Cobros - Nokia 1100');

if ($_SESSION['role'] === 'admin') {
    Layout::renderAdminSidebar('taller');
} else {
    Layout::renderEmployeeSidebar('taller');
}
?>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8">
        <div class="dashboard-header border-b border-border/50 pb-6 mb-6">
            <div class="flex items-center gap-4">
                <a href="index.php" class="w-10 h-10 rounded-full bg-surface border border-border flex items-center justify-center text-text-muted hover:text-text-main transition-colors shrink-0">
                    <span class="material-symbols-outlined">arrow_back</span>
                </a>
                <div>
                    <h2 class="text-3xl font-display font-medium text-text-main">Historial de Cobros</h2>
                    <p class="text-text-muted mt-1 text-sm">Registro de ingresos generados por reparaciones finalizadas</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <!-- Botones de Exportación -->
                <button type="button" onclick="exportTableToExcel('history-table', 'historial_reparaciones', this)" class="px-3 py-2 rounded-xl border border-green-500/30 bg-green-500/10 hover:bg-green-500/20 text-xs font-medium text-green-400 hover:text-green-300 transition-colors flex items-center gap-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-[16px]">download</span> Excel
                </button>
                <button type="button" onclick="exportTableToPDF('history-table', 'Historial de Cobros', 'historial_reparaciones', this)" class="px-3 py-2 rounded-xl border border-red-500/30 bg-red-500/10 hover:bg-red-500/20 text-xs font-medium text-red-400 hover:text-red-300 transition-colors flex items-center gap-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-[16px]">download</span> PDF
                </button>
            </div>
        </div>

        <div class="table-container rounded-2xl border border-border overflow-x-auto">
            <table class="w-full text-left" id="history-table">
                <thead class="bg-surface/50 border-b border-border text-xs uppercase text-text-muted tracking-wider">
                    <tr>
                        <th class="p-4 font-semibold">Fecha de Cobro</th>
                        <th class="p-4 font-semibold">Descripción (Orden / Equipo)</th>
                        <th class="p-4 font-semibold text-center">Método</th>
                        <th class="p-4 font-semibold text-right">Monto</th>
                        <th class="p-4 font-semibold text-right">Cobrado Por</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/50">
                    <?php if (count($paid_repairs) > 0): ?>
                        <?php foreach($paid_repairs as $r): ?>
                            <tr class="hover:bg-surface-hover/30 transition-colors">
                                <td class="p-4 text-text-muted text-sm"><?php echo date('d/m/Y H:i', strtotime($r['fecha'])); ?></td>
                                <td class="p-4 font-medium text-text-main"><?php echo htmlspecialchars($r['descripcion']); ?></td>
                                <td class="p-4 text-center">
                                    <span class="inline-block px-2.5 py-1 rounded text-[10px] uppercase font-bold tracking-widest bg-surface border border-border text-text-muted">
                                        <?php echo htmlspecialchars($r['metodo_de_pago']); ?>
                                    </span>
                                </td>
                                <td class="p-4 text-right font-medium text-primary">
                                    $<?php echo number_format($r['total'], 2); ?>
                                </td>
                                <td class="p-4 text-right text-text-muted text-sm">
                                    <?php echo htmlspecialchars($r['nombre_usuario']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="p-12 text-center text-text-muted">No hay cobros de reparaciones registrados aún.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php 
        $total_items = $total_items ?? count($paid_repairs ?? []);
        $limit = $limit ?? 10;
        $page = $page ?? 1;
        $total_pages = $total_pages ?? max(1, ceil($total_items / $limit));
        $count_current = count($paid_repairs ?? []);
        $from = ($total_items > 0) ? (($page - 1) * $limit + 1) : 0;
        $to = ($total_items > 0) ? min($from + $count_current - 1, $total_items) : 0;

        $query_args = ['action' => 'history'];
        ?>

        <div class="mt-6 pt-4 border-t border-border/50 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-text-muted">
            <div>
                <?php if ($total_items > 0): ?>
                    Mostrando <strong class="text-text-main font-semibold"><?php echo $from; ?>–<?php echo $to; ?></strong> de <strong class="text-text-main font-semibold"><?php echo $total_items; ?></strong> reparaciones
                    <span class="mx-1.5 opacity-40">•</span>
                    Página <strong class="text-primary font-semibold"><?php echo $page; ?></strong> de <strong class="text-text-main font-semibold"><?php echo $total_pages; ?></strong>
                <?php else: ?>
                    <span>No hay reparaciones en el historial</span>
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

<?php Layout::renderFooter(); ?>
