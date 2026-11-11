<?php
// modules/workshop/Views/index.php

$search = $search ?? '';
$estado_filter = $estado_filter ?? '';
$repairs = $repairs ?? [];
$received_repairs = $received_repairs ?? [];
$page = $page ?? 1;
$total_pages = $total_pages ?? 1;
$total_items = $total_items ?? 0;
$limit = $limit ?? 10;
$received_count = count($received_repairs);
$new_intake_id = isset($_GET['new_intake']) ? (int)$_GET['new_intake'] : 0;

Layout::renderHead('Taller - Nokia 1100');

if ($_SESSION['role'] === 'admin') {
    Layout::renderAdminSidebar('taller');
} else {
    Layout::renderEmployeeSidebar('taller');
}
?>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <?php if ($received_count > 0): 
        $latest_received = $received_repairs[0];
        // Si venimos de registrar un equipo puntual, destacamos ese equipo en la notificación
        if ($new_intake_id > 0) {
            foreach ($received_repairs as $rec_item) {
                if ((int)$rec_item['id_reparacion'] === $new_intake_id) {
                    $latest_received = $rec_item;
                    break;
                }
            }
        }
        $latest_id = (int)($latest_received['id_reparacion'] ?? 0);
        $latest_code = htmlspecialchars((string)($latest_received['codigo_orden'] ?? ''));
        $latest_device = htmlspecialchars(trim(($latest_received['equipo_marca'] ?? '') . ' ' . ($latest_received['equipo_modelo'] ?? '')));
        $latest_client = htmlspecialchars((string)($latest_received['cliente_nombre'] ?? ''));
        $latest_operario = htmlspecialchars((string)($latest_received['operario_nombre'] ?? 'Empleado'));
        $latest_falla = htmlspecialchars((string)($latest_received['falla_declarada'] ?? ''));
    ?>
        <div class="py-2.5 px-3.5 rounded-[2px] mb-4 border border-[#8a5200] border-l-4 border-l-[#8a5200] bg-[#f6e8c8] flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2.5 shadow-sm">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-7 h-7 rounded-[2px] bg-[#15181a] text-[#c6e24a] border border-[#222729] flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-[16px]">notifications_active</span>
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="px-1.5 py-0.2 rounded-[2px] text-[9px] font-mono font-bold uppercase tracking-wider bg-[#15181a] text-[#c6e24a]">Nuevo Ingreso</span>
                        <h3 class="text-xs sm:text-sm font-display font-bold text-[#15171a]">
                            <?php echo $received_count === 1 ? '¡Tienes un equipo que ha ingresado al Taller!' : "¡Tienes {$received_count} equipos recién ingresados en espera de diagnóstico!"; ?>
                        </h3>
                        <span class="px-1.5 py-0.2 rounded-[2px] text-[10px] font-mono font-bold bg-[#8a5200] text-[#f8f6f0]"><?php echo $received_count; ?></span>
                    </div>
                    <p class="text-[11px] text-[#8a5200] font-medium mt-0.5 truncate">
                        Último: <strong class="font-mono text-[#15171a]">#<?php echo $latest_code; ?></strong> — 
                        <strong class="text-[#15171a]"><?php echo $latest_device; ?></strong> (<?php echo $latest_client; ?>) · 
                        Por <span class="font-mono font-bold text-[#15171a]"><?php echo $latest_operario; ?></span>
                        <?php if ($latest_falla !== ''): ?>
                            · <em>"<?php echo mb_strimwidth($latest_falla, 0, 55, '...'); ?>"</em>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <?php if ($received_count > 1 && $estado_filter !== 'Recibido'): ?>
                <a href="index.php?estado=Recibido" class="px-2.5 py-1 rounded-[2px] text-[11px] font-mono font-semibold bg-[#f8f6f0] border border-[#8a5200] text-[#8a5200] hover:bg-[#e5e1d6] transition-colors flex items-center gap-1 shrink-0">
                    <span class="material-symbols-outlined text-[13px]">filter_alt</span>
                    Ver los <?php echo $received_count; ?> recibidos
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="glass-card mb-8">
        <div class="dashboard-header border-b border-border/50 pb-6 mb-6">
            <div>
                <h2 class="text-3xl font-display font-medium text-text-main">Taller de Reparaciones</h2>
                <p class="text-text-muted mt-1 text-sm">Gestión de órdenes, presupuestos y control de equipos</p>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap">
                <!-- Botones de Exportación -->
                <?php echo Layout::renderExportButtons('workshop-table', 'Órdenes de Reparación', 'taller'); ?>

                <a href="index.php?action=history" class="bg-surface text-text-main hover:bg-surface-hover px-4 py-2 border border-border rounded-lg font-medium text-sm transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">history</span> Historial de Cobros
                </a>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="index.php?action=add" class="bg-primary text-background hover:bg-primary-hover px-4 py-2 rounded-lg font-medium text-sm transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">add</span> Nueva Orden
                </a>
                <?php endif; ?>
            </div>
        </div>

        <form method="GET" action="" class="flex gap-3 mb-6 items-center flex-wrap">
            <div class="relative flex-1 max-w-md">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-muted text-[20px]">search</span>
                <input type="text" id="search-input" name="search" placeholder="Buscar por código, cliente, modelo..." value="<?php echo htmlspecialchars($search); ?>" class="w-full bg-surface border border-border pl-10 pt-2.5 pb-2.5 pr-4 rounded-xl text-sm text-text-main focus:outline-none focus:border-primary transition-colors">
            </div>
            <select name="estado" class="bg-surface border border-border px-4 py-2.5 rounded-xl text-sm text-text-main focus:outline-none focus:border-primary transition-colors">
                <option value="">Todos los estados</option>
                <option value="Recibido" <?php if($estado_filter==='Recibido') echo 'selected'; ?>>Recibido</option>
                <option value="En diagnóstico" <?php if($estado_filter==='En diagnóstico') echo 'selected'; ?>>En diagnóstico</option>
                <option value="En reparación" <?php if($estado_filter==='En reparación') echo 'selected'; ?>>En reparación</option>
                <option value="Listo" <?php if($estado_filter==='Listo') echo 'selected'; ?>>Listo</option>
                <option value="Entregado" <?php if($estado_filter==='Entregado') echo 'selected'; ?>>Entregado</option>
                <option value="Cancelado" <?php if($estado_filter==='Cancelado') echo 'selected'; ?>>Cancelado</option>
            </select>
            <button type="submit" class="bg-surface hover:bg-surface-hover border border-border px-4 py-2.5 rounded-xl text-sm font-medium transition-colors text-text-main">Filtrar</button>
            <?php if ($search || $estado_filter): ?>
                <a href="index.php" class="text-red-400 hover:text-red-300 text-sm font-medium ml-2 transition-colors">Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="table-container rounded-2xl border border-border overflow-x-auto">
            <table class="w-full text-left" id="workshop-table">
                <thead class="bg-surface/50 border-b border-border text-xs uppercase text-text-muted tracking-wider">
                    <tr>
                        <th class="p-4 font-semibold">Orden</th>
                        <th class="p-4 font-semibold">Cliente</th>
                        <th class="p-4 font-semibold">Equipo</th>
                        <th class="p-4 font-semibold">Estado</th>
                        <th class="p-4 font-semibold">Fecha Ingreso</th>
                        <th class="p-4 font-semibold">Presupuesto</th>
                        <th class="p-4 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/50">
                    <?php if (count($repairs) > 0): ?>
                        <?php foreach($repairs as $r): 
                            $statusMap = [
                                'Recibido'       => 'repair-status-recibido',
                                'En diagnóstico' => 'repair-status-diagnostico',
                                'En reparación'  => 'repair-status-reparacion',
                                'Listo'          => 'repair-status-listo',
                                'Entregado'      => 'repair-status-entregado',
                                'Cancelado'      => 'repair-status-cancelado',
                            ];
                            $statusClass = 'repair-status ' . ($statusMap[$r['estado']] ?? 'repair-status-default');
                        ?>
                            <tr class="hover:bg-surface-hover/30 transition-colors <?php echo ($r['estado'] === 'Recibido') ? 'bg-[#f6e8c8]/35' : ''; ?>">
                                <td class="p-4 font-display font-medium text-primary">
                                    <div class="flex items-center gap-2">
                                        <span>#<?php echo htmlspecialchars($r['codigo_orden']); ?></span>
                                        <?php if ($r['estado'] === 'Recibido'): ?>
                                            <span class="px-1.5 py-0.5 rounded-[2px] text-[9px] font-mono font-bold uppercase tracking-wider bg-[#c6e24a] text-[#15171a] border border-[#15171a]/20" title="Equipo recién ingresado pendiente de revisión">NUEVO</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="p-4 font-medium text-text-main"><?php echo htmlspecialchars($r['cliente_nombre']); ?></td>
                                <td class="p-4 text-text-muted text-sm"><?php echo htmlspecialchars($r['equipo_marca'] . ' ' . $r['equipo_modelo']); ?></td>
                                <td class="p-4">
                                    <span class="inline-block px-2.5 py-1 rounded text-[10px] uppercase font-bold tracking-widest <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($r['estado']); ?>
                                    </span>
                                </td>
                                <td class="p-4 text-text-muted text-sm"><?php echo date('d/m/Y H:i', strtotime($r['fecha_ingreso'])); ?></td>
                                <td class="p-4 text-text-main font-medium">
                                    <?php 
                                        $total_list = floatval($r['presupuesto']) + floatval($r['costo_total'] ?? 0);
                                        echo $total_list > 0 ? '$' . number_format($total_list, 2) : '<span class="text-text-muted text-xs">Pte.</span>'; 
                                    ?>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="index.php?action=print_receipt&id=<?php echo $r['id_reparacion']; ?>" target="_blank" class="px-2.5 py-1.5 rounded-[2px] flex items-center gap-1.5 bg-surface border border-border text-text-main hover:bg-surface-hover font-mono text-xs font-semibold transition-colors whitespace-nowrap" title="Imprimir Comprobante y QR">
                                            <span class="material-symbols-outlined text-[15px]">print</span>
                                            <span>Recibo / QR</span>
                                        </a>
                                        <a href="index.php?action=view&id=<?php echo $r['id_reparacion']; ?>" class="w-8 h-8 rounded-lg flex items-center justify-center bg-surface border border-border text-text-main hover:bg-primary-hover hover:text-white transition-colors" title="Ver / Gestionar">
                                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="p-12 text-center text-text-muted">No se encontraron órdenes de reparación.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php
        $total_items = (int)($total_items ?? count($repairs ?? []));
        $limit = (int)($limit ?? 10);
        $page = (int)($page ?? 1);
        $total_pages = (int)($total_pages ?? max(1, ceil($total_items / max(1, $limit))));

        $query_args = [];
        if (!empty($estado_filter)) $query_args['estado'] = $estado_filter;
        if (!empty($search)) $query_args['search'] = $search;

        echo Layout::renderPagination($page, $total_pages, $total_items, 'orden', 'órdenes', $query_args);
        ?>
    </div>
</main>

<?php Layout::renderFooter(); ?>
