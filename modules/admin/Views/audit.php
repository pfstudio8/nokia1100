<?php
// modules/admin/Views/audit.php

Layout::renderHead('Registro de Auditoría - NOKIA1100');
Layout::renderAdminSidebar('auditoria');
?>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8 pb-4 border-b border-border/50">
            <div>
                <h2 class="text-2xl font-display font-medium text-text-main flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">security</span> Registro de Auditoría
                </h2>
                <p class="text-text-muted text-sm mt-1">Historial de acciones críticas y eventos de seguridad</p>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-muted text-[20px]">search</span>
                    <input type="text" id="search-input" placeholder="Buscar en auditoría..." class="bg-surface border border-border pl-10 pt-2 pb-2 pr-4 rounded-xl text-sm text-text-main focus:outline-none focus:border-primary transition-colors">
                </div>
                <div class="flex gap-2">
                    <a href="<?php echo BASE_URL; ?>/modules/admin/audit.php?limit=100" class="px-4 py-2 rounded-xl border border-border <?php echo $limit == 100 ? 'bg-primary/20 text-primary' : 'bg-surface text-text-muted hover:text-text-main hover:bg-surface-hover'; ?> transition-colors text-sm font-medium">Últimos 100</a>
                    <a href="<?php echo BASE_URL; ?>/modules/admin/audit.php?limit=500" class="px-4 py-2 rounded-xl border border-border <?php echo $limit == 500 ? 'bg-primary/20 text-primary' : 'bg-surface text-text-muted hover:text-text-main hover:bg-surface-hover'; ?> transition-colors text-sm font-medium">Últimos 500</a>
                </div>
            </div>
        </div>

        <div class="table-container overflow-x-auto rounded-xl border border-border/50">
            <table class="w-full text-left border-collapse" id="auditTable">
                <thead>
                    <tr class="bg-surface/50 text-text-muted text-xs uppercase tracking-wider font-semibold border-b border-border/50">
                        <th class="p-4 rounded-tl-xl">ID</th>
                        <th class="p-4">Fecha</th>
                        <th class="p-4">Módulo</th>
                        <th class="p-4">Acción</th>
                        <th class="p-4">Usuario</th>
                        <th class="p-4">Detalle</th>
                        <th class="p-4 rounded-tr-xl">IP</th>
                    </tr>
                </thead>
                <tbody class="text-sm font-medium text-text-main divide-y divide-border/30">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="p-8 text-center text-text-muted bg-surface/20">
                                <span class="material-symbols-outlined text-4xl mb-2 opacity-50">history</span>
                                <p>No hay registros de auditoría disponibles.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-surface/30 transition-colors group">
                                <td class="p-4 text-text-muted">#<?php echo $log['id_log']; ?></td>
                                <td class="p-4 whitespace-nowrap"><?php echo date('d/m/Y H:i:s', strtotime($log['fecha'])); ?></td>
                                <td class="p-4 text-primary font-medium uppercase tracking-wider text-xs">
                                    <?php echo htmlspecialchars($log['tabla_afectada'] ?? 'Sistema'); ?>
                                </td>
                                <td class="p-4">
                                    <?php 
                                        $badgeClass = 'bg-surface border border-border text-text-main';
                                        if (strpos($log['accion'], 'FAIL') !== false || strpos($log['accion'], 'DELETE') !== false) {
                                            $badgeClass = 'bg-red-500/10 border-red-500/20 text-red-400';
                                        } elseif (strpos($log['accion'], 'OK') !== false || strpos($log['accion'], 'CREATE') !== false) {
                                            $badgeClass = 'bg-green-500/10 border-green-500/20 text-green-400';
                                        } elseif (strpos($log['accion'], 'UPDATE') !== false) {
                                            $badgeClass = 'bg-blue-500/10 border-blue-500/20 text-blue-400';
                                        }
                                    ?>
                                    <span class="inline-flex px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider <?php echo $badgeClass; ?>">
                                        <?php echo htmlspecialchars($log['accion']); ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <?php echo htmlspecialchars($log['nombre_usuario'] ?? $log['username_intent'] ?? 'Sistema/Anónimo'); ?>
                                </td>
                                <td class="p-4 max-w-md break-words whitespace-normal text-text-muted group-hover:text-text-main transition-colors" title="<?php echo htmlspecialchars($log['descripcion']); ?>">
                                    <?php echo htmlspecialchars($log['descripcion']); ?>
                                </td>
                                <td class="p-4 text-text-muted text-xs">
                                    <?php echo htmlspecialchars($log['ip']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php Layout::renderFooter(); ?>
