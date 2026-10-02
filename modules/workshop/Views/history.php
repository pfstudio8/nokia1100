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
                <!-- Botón de Exportación -->
                <button type="button" onclick="exportTableToExcel('history-table', 'historial_reparaciones', this)" class="px-3 py-2 rounded-xl border border-green-500/30 bg-green-500/10 hover:bg-green-500/20 text-xs font-medium text-green-400 hover:text-green-300 transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">download</span> Excel
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

        <?php if (isset($total_pages) && $total_pages > 0): ?>
            <div class="mt-6 flex justify-center gap-2">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?action=history&page=<?php echo $i; ?>" class="px-3 py-1.5 border border-border rounded-xl text-sm font-medium transition-colors <?php echo $i === ($page ?? 1) ? 'bg-primary text-background' : 'bg-surface text-text-main hover:bg-surface-hover'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php Layout::renderFooter(); ?>
