<?php
// modules/sales/Views/repairs_to_charge.php

Layout::renderHead('Cobrar Reparaciones - Nokia 1100');
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    Layout::renderAdminSidebar('ventas');
} else {
    Layout::renderEmployeeSidebar('venta');
}
?>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen bg-background">
    <div class="glass-card mb-8 p-6 rounded-2xl">
        <div class="flex justify-between items-center mb-8 flex-wrap gap-4 border-b border-border/50 pb-4">
            <div>
                <h2 class="text-2xl font-display font-semibold text-text-main flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-3xl">build</span> Cobrar Reparaciones
                </h2>
                <p class="text-text-muted mt-1 text-sm">Bandeja de equipos listos enviados desde el Taller.</p>
            </div>
            <div class="flex gap-4">
                <a href="<?php echo BASE_URL; ?>/modules/sales/index.php" class="px-4 py-2 rounded-lg border border-border bg-surface text-text-main hover:bg-surface-hover text-sm font-medium transition-colors">Volver a Ventas</a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-500/10 border border-green-500/30 text-green-500 p-4 rounded-xl mb-6 text-sm flex items-center gap-2">
                <span class="material-symbols-outlined">check_circle</span>
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-500/10 border border-red-500/30 text-red-500 p-4 rounded-xl mb-6 text-sm flex items-center gap-2">
                <span class="material-symbols-outlined">error</span>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <div class="flex flex-col gap-6 max-w-6xl mx-auto">
            <?php if (count($repairs) > 0): ?>
                <?php foreach ($repairs as $rep): ?>
                    <?php $total_a_cobrar = floatval($rep['presupuesto']) + floatval($rep['costo_total'] ?? 0); ?>
                    <div class="bg-surface border border-border rounded-xl overflow-hidden hover:border-primary/50 transition-colors shadow-sm flex flex-col-reverse md:flex-row">
                        
                        <!-- Lado Izquierdo: Formulario de Cobro -->
                        <div class="p-6 md:w-1/3 bg-background flex flex-col justify-center border-t md:border-t-0 md:border-r border-border">
                            <div class="bg-surface rounded-lg p-4 mb-6 border border-border/50 text-center">
                                <span class="block text-sm text-text-muted mb-1">Total a cobrar</span>
                                <span class="text-3xl font-display font-semibold text-primary">$<?php echo number_format($total_a_cobrar, 2); ?></span>
                            </div>

                            <form method="POST" action="" class="space-y-4">
                                <input type="hidden" name="action" value="charge_repair">
                                <input type="hidden" name="id_reparacion" value="<?php echo $rep['id_reparacion']; ?>">
                                <input type="hidden" name="presupuesto" value="<?php echo $total_a_cobrar; ?>">
                                
                                <div>
                                    <label class="block text-xs font-medium text-text-muted mb-1">Método de Pago</label>
                                    <select name="metodo_pago" required class="w-full bg-surface border border-border px-3 py-3 rounded-lg text-sm text-text-main focus:outline-none focus:border-primary">
                                        <option value="Efectivo">Efectivo</option>
                                        <option value="Transferencia">Transferencia / QR</option>
                                        <option value="Tarjeta">Tarjeta (Crédito/Débito)</option>
                                    </select>
                                </div>
                                
                                <button type="submit" data-confirm="¿Confirmas el cobro de $<?php echo number_format($total_a_cobrar, 2); ?> y la entrega del equipo?" data-confirm-title="Cobrar y Entregar" class="w-full bg-primary hover:bg-primary-hover text-background font-medium py-3 rounded-lg text-sm transition-colors flex items-center justify-center gap-2 mt-2 shadow-lg shadow-primary/20">
                                    <span class="material-symbols-outlined text-[20px] pointer-events-none">point_of_sale</span>
                                    Cobrar y Entregar
                                </button>
                            </form>
                        </div>

                        <!-- Lado Derecho: Datos del Equipo y Vista Previa -->
                        <div class="p-6 md:w-2/3 bg-surface-hover/10">
                            <div class="flex justify-between items-start mb-6">
                                <div>
                                    <span class="text-xs font-semibold px-2 py-1 bg-primary/20 text-primary rounded-md uppercase tracking-wider mb-2 inline-block">Orden #<?php echo $rep['codigo_orden']; ?></span>
                                    <h3 class="text-xl font-display font-medium text-text-main"><?php echo htmlspecialchars($rep['equipo_marca'] . ' ' . $rep['equipo_modelo']); ?></h3>
                                    <p class="text-sm text-text-muted flex items-center gap-1 mt-1"><span class="material-symbols-outlined text-[14px]">person</span> <?php echo htmlspecialchars($rep['cliente_nombre']); ?></p>
                                </div>
                                <div>
                                    <span class="inline-block px-3 py-1 bg-surface text-text-main text-xs font-semibold rounded border border-border">
                                        Estado: <?php echo htmlspecialchars($rep['estado']); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <?php if (!empty($rep['falla_declarada'])): ?>
                                    <div class="bg-surface rounded-lg p-4 border border-border/50">
                                        <p class="text-xs font-semibold text-text-muted uppercase tracking-wider mb-2 flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">report_problem</span> Falla Reportada</p>
                                        <p class="text-sm text-text-main leading-relaxed"><?php echo nl2br(htmlspecialchars($rep['falla_declarada'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php 
                                $parts = isset($repairs_parts[$rep['id_reparacion']]) ? $repairs_parts[$rep['id_reparacion']] : [];
                                if (count($parts) > 0): 
                                ?>
                                    <div class="bg-surface rounded-lg p-4 border border-border/50">
                                        <p class="text-xs font-semibold text-text-muted uppercase tracking-wider mb-2 flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">build</span> Repuestos Utilizados</p>
                                        <ul class="space-y-2">
                                            <?php foreach($parts as $part): ?>
                                                <li class="text-sm flex justify-between items-center text-text-main border-b border-border/30 pb-1 last:border-0 last:pb-0">
                                                    <span><?php echo htmlspecialchars($part['cantidad'] . 'x ' . $part['nombre']); ?></span>
                                                    <span class="font-medium text-text-muted">$<?php echo number_format($part['precio_unitario'] * $part['cantidad'], 2); ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php else: ?>
                                    <div class="bg-surface rounded-lg p-4 border border-border/50 flex items-center justify-center text-text-muted text-sm italic">
                                        No se utilizaron repuestos
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full py-12 text-center border-2 border-dashed border-border/50 rounded-2xl">
                    <span class="material-symbols-outlined text-text-muted/50 text-5xl mb-3 block">inventory_2</span>
                    <h3 class="text-lg font-medium text-text-main mb-1">No hay equipos para cobrar</h3>
                    <p class="text-sm text-text-muted">Cuando el taller genere una orden, aparecerá aquí para ser cobrada y entregada.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php Layout::renderFooter(); ?>
