<?php
// modules/sales/Views/repairs_to_charge.php

// Variables inyectadas por SalesController::repairs_to_charge()
// @var array $repairs
// @var array $repairs_parts
$repairs = $repairs ?? [];
$repairs_parts = $repairs_parts ?? [];

Layout::renderHead('Cobrar Reparaciones - Nokia 1100');
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    Layout::renderAdminSidebar('cobrar_reparaciones');
} else {
    Layout::renderEmployeeSidebar('cobrar_reparaciones');
}
?>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen bg-background">
    <div class="glass-card mb-8 p-6 rounded-2xl">
        <div class="flex justify-between items-center mb-8 flex-wrap gap-4 border-b border-border/50 pb-4">
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h2 class="text-2xl font-display font-semibold text-text-main flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-3xl">build</span> Cobrar Reparaciones
                    </h2>
                </div>
                <p class="text-text-muted mt-1 text-sm">Bandeja de equipos listos enviados desde el Taller para su cobro y entrega.</p>
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

        <?php if (isset($_GET['charged_id'])): ?>
            <?php 
                $charged_id = (int)$_GET['charged_id'];
                $id_venta = (int)($_GET['id_venta'] ?? 0);
            ?>
            <div id="modal-charge-success" class="fixed inset-0 bg-background/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-surface border border-border/80 rounded-2xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col text-center p-8 animate-in fade-in zoom-in-95 duration-200">
                    <div class="w-20 h-20 rounded-full bg-green-500/10 text-green-500 flex items-center justify-center mx-auto mb-6">
                        <span class="material-symbols-outlined text-[44px]">check_circle</span>
                    </div>
                    <h3 class="text-2xl font-display font-semibold text-text-main mb-2">¡Cobro Registrado!</h3>
                    <p class="text-text-muted text-sm mb-6">La reparación ha sido cobrada e ingresada a la caja diaria. El equipo figura como <strong class="text-text-main">Entregado</strong>.</p>
                    
                    <div class="flex flex-col gap-3">
                        <a href="<?php echo BASE_URL; ?>/modules/workshop/index.php?action=delivery_receipt&id=<?php echo $charged_id; ?>" target="_blank" class="w-full bg-primary text-background hover:bg-primary-hover font-semibold py-3 px-6 rounded-xl transition-all shadow-lg shadow-primary/20 flex justify-center items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">print</span> Imprimir Comprobante y Garantía
                        </a>
                        <button type="button" onclick="document.getElementById('modal-charge-success').remove()" class="w-full bg-surface-hover text-text-main hover:bg-border/50 font-medium py-3 px-6 rounded-xl border border-border/50 transition-all">
                            Continuar en Caja
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex flex-col gap-6 max-w-6xl mx-auto">
            <?php if (count($repairs) > 0): ?>
                <?php foreach ($repairs as $rep): ?>
                    <?php 
                        $presupuesto_base = floatval($rep['presupuesto']);
                        $costo_repuestos = floatval($rep['costo_total'] ?? 0);
                        $total_a_cobrar = $presupuesto_base + $costo_repuestos; 
                        $parts = isset($repairs_parts[$rep['id_reparacion']]) ? $repairs_parts[$rep['id_reparacion']] : [];
                        $fecha_ingreso = !empty($rep['fecha_ingreso']) ? date('d/m/Y - H:i', strtotime($rep['fecha_ingreso'])) : 'N/D';
                        $resolucion = !empty($rep['resolucion_tecnica']) ? $rep['resolucion_tecnica'] : null;
                        $tecnico = !empty($rep['tecnico_nombre']) ? $rep['tecnico_nombre'] : 'Taller';
                    ?>
                    <div class="bg-surface border border-border/80 rounded-2xl overflow-hidden hover:border-primary/50 transition-all shadow-lg shadow-black/20 flex flex-col-reverse lg:flex-row">
                        
                        <?php // Lado Izquierdo: Terminal de Cobro ?>
                        <div class="p-6 lg:w-[320px] bg-background/90 flex flex-col justify-between border-t lg:border-t-0 lg:border-r border-border shrink-0">
                            <div>
                                <div class="bg-surface/80 rounded-2xl p-5 mb-5 border border-border/60 text-center relative overflow-hidden shadow-inner">
                                    <span class="block text-[11px] font-bold text-text-muted uppercase tracking-widest mb-1">Monto Total a Cobrar</span>
                                    <span class="text-3xl font-display font-bold text-primary tracking-tight">$<?php echo number_format($total_a_cobrar, 2); ?></span>
                                    
                                    <div class="mt-3 pt-3 border-t border-border/40 grid grid-cols-2 gap-2 text-[11px]">
                                        <div class="text-left bg-background/50 p-2 rounded-lg border border-border/30">
                                            <span class="block text-text-muted text-[10px]">Mano de obra</span>
                                            <strong class="text-text-main font-semibold">$<?php echo number_format($presupuesto_base, 2); ?></strong>
                                        </div>
                                        <div class="text-right bg-background/50 p-2 rounded-lg border border-border/30">
                                            <span class="block text-text-muted text-[10px]">Repuestos</span>
                                            <strong class="text-text-main font-semibold">$<?php echo number_format($costo_repuestos, 2); ?></strong>
                                        </div>
                                    </div>
                                </div>

                                <form method="POST" action="" class="space-y-4">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['session_token'] ?? ''); ?>">
                                    <input type="hidden" name="action" value="charge_repair">
                                    <input type="hidden" name="id_reparacion" value="<?php echo $rep['id_reparacion']; ?>">
                                    <input type="hidden" name="presupuesto" value="<?php echo $total_a_cobrar; ?>">
                                    
                                    <div>
                                        <label class="block text-xs font-semibold text-text-muted uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-[16px] text-primary">account_balance_wallet</span>
                                            Método de Pago
                                        </label>
                                        <div class="grid grid-cols-3 gap-2">
                                            <button type="button" onclick="selectPayMethod('Efectivo', this)" class="pay-method-btn flex flex-col items-center justify-center py-2.5 px-1.5 rounded-xl border border-primary bg-primary/15 text-primary transition-all shadow-sm">
                                                <span class="material-symbols-outlined text-[20px] mb-1">payments</span>
                                                <span class="text-[11px] font-semibold">Efectivo</span>
                                            </button>
                                            <button type="button" onclick="selectPayMethod('Transferencia', this)" class="pay-method-btn flex flex-col items-center justify-center py-2.5 px-1.5 rounded-xl border border-border/60 bg-surface hover:bg-surface-hover text-text-muted transition-all">
                                                <span class="material-symbols-outlined text-[20px] mb-1">qr_code_scanner</span>
                                                <span class="text-[11px] font-semibold text-center leading-tight">Transf. / QR</span>
                                            </button>
                                            <button type="button" onclick="selectPayMethod('Tarjeta', this)" class="pay-method-btn flex flex-col items-center justify-center py-2.5 px-1.5 rounded-xl border border-border/60 bg-surface hover:bg-surface-hover text-text-muted transition-all">
                                                <span class="material-symbols-outlined text-[20px] mb-1">credit_card</span>
                                                <span class="text-[11px] font-semibold">Tarjeta</span>
                                            </button>
                                        </div>
                                        <input type="hidden" name="metodo_pago" value="Efectivo" class="input-metodo-pago">
                                    </div>
                                    
                                    <button type="submit" data-confirm="¿Confirmas el cobro de $<?php echo number_format($total_a_cobrar, 2); ?> y la entrega del equipo al cliente?" data-confirm-title="Cobrar y Entregar Equipo" class="w-full bg-primary hover:bg-primary-hover text-background font-bold py-3.5 px-4 rounded-xl text-sm transition-all flex items-center justify-center gap-2 shadow-lg shadow-primary/25 hover:shadow-primary/40 active:scale-[0.98]">
                                        <span class="material-symbols-outlined text-[20px] pointer-events-none">point_of_sale</span>
                                        Cobrar y Entregar
                                    </button>
                                </form>
                            </div>

                        </div>

                        <?php // Lado Derecho: Especificaciones Técnicas y Diagnóstico ?>
                        <div class="p-6 lg:p-7 flex-1 bg-surface-hover/5 flex flex-col justify-between">
                            <div>
                                <?php // Barra Superior: Cabecera del Dispositivo ?>
                                <div class="flex justify-between items-start mb-6 flex-wrap gap-4 pb-5 border-b border-border/50">
                                    <div class="flex items-start gap-4">
                                        <div class="w-12 h-12 rounded-2xl bg-primary/10 border border-primary/20 text-primary flex items-center justify-center shrink-0 shadow-inner">
                                            <span class="material-symbols-outlined text-[28px]">smartphone</span>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                                <span class="text-[11px] font-bold px-2.5 py-0.5 bg-primary/15 text-primary border border-primary/30 rounded-md font-mono tracking-wider">
                                                    ORDEN #<?php echo htmlspecialchars($rep['codigo_orden']); ?>
                                                </span>
                                                <span class="text-[11px] font-semibold px-2.5 py-0.5 bg-emerald-500/10 text-emerald-400 border border-emerald-500/25 rounded-md flex items-center gap-1">
                                                    Listo para Entrega
                                                </span>
                                                <?php if (!empty($rep['equipo_marca'])): ?>
                                                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-surface border border-border text-text-muted">
                                                        <?php echo htmlspecialchars($rep['equipo_marca']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <h3 class="text-2xl font-display font-bold text-text-main tracking-tight">
                                                <?php echo htmlspecialchars($rep['equipo_marca'] . ' ' . $rep['equipo_modelo']); ?>
                                            </h3>

                                            <div class="flex items-center gap-4 mt-2 flex-wrap text-xs text-text-muted">
                                                <span class="inline-flex items-center gap-1.5 font-medium text-text-main bg-surface px-2.5 py-1 rounded-lg border border-border/60">
                                                    <span class="material-symbols-outlined text-[15px] text-primary">person</span>
                                                    <?php echo htmlspecialchars($rep['cliente_nombre']); ?>
                                                    <?php if (!empty($rep['cliente_telefono'])): ?>
                                                        <span class="text-text-muted ml-1 font-mono">
                                                            (<?php echo htmlspecialchars($rep['cliente_telefono']); ?>)
                                                        </span>
                                                    <?php endif; ?>
                                                </span>

                                                <?php if (!empty($rep['equipo_imei'])): ?>
                                                    <span class="inline-flex items-center gap-1 font-mono bg-surface px-2.5 py-1 rounded-lg border border-border/60">
                                                        <span class="material-symbols-outlined text-[14px] text-text-muted">fingerprint</span>
                                                        IMEI: <?php echo htmlspecialchars($rep['equipo_imei']); ?>
                                                    </span>
                                                <?php endif; ?>

                                                <span class="inline-flex items-center gap-1 bg-surface px-2.5 py-1 rounded-lg border border-border/60">
                                                    <span class="material-symbols-outlined text-[14px] text-text-muted">schedule</span>
                                                    Ingreso: <?php echo $fecha_ingreso; ?> hs
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-col items-end gap-2">
                                        <a href="<?php echo BASE_URL; ?>/modules/workshop/index.php?action=view&id=<?php echo $rep['id_reparacion']; ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-surface hover:bg-surface-hover border border-border text-text-main hover:text-primary transition-all shadow-sm">
                                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                                            Ver Ficha Técnica
                                        </a>
                                    </div>
                                </div>

                                <?php // Grid de Especificaciones: 3 Bloques Técnicos ?>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    
                                    <?php // Especificación 1: Falla y Diagnóstico Inicial ?>
                                    <div class="bg-surface/80 rounded-xl p-4 border border-amber-500/20 flex flex-col justify-between shadow-sm">
                                        <div>
                                            <div class="flex items-center gap-2 mb-2">
                                                <div class="w-6 h-6 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center">
                                                    <span class="material-symbols-outlined text-[15px]">report_problem</span>
                                                </div>
                                                <span class="text-[11px] font-bold text-amber-400 uppercase tracking-wider">Falla de Ingreso</span>
                                            </div>
                                            <p class="text-xs text-text-main font-medium leading-relaxed bg-background/50 p-2.5 rounded-lg border border-border/40">
                                                "<?php echo !empty($rep['falla_declarada']) ? htmlspecialchars(trim($rep['falla_declarada'])) : 'Sin detalle de falla especificado'; ?>"
                                            </p>
                                        </div>
                                        <?php if (!empty($rep['observaciones'])): ?>
                                            <div class="mt-2 text-[10px] text-text-muted border-t border-border/30 pt-1.5">
                                                <span class="font-semibold text-text-main">Obs:</span> <?php echo htmlspecialchars($rep['observaciones']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php // Especificación 2: Trabajo Realizado por el Técnico ?>
                                    <div class="bg-surface/80 rounded-xl p-4 border border-emerald-500/20 flex flex-col justify-between shadow-sm">
                                        <div>
                                            <div class="flex items-center justify-between mb-2">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-6 h-6 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center">
                                                        <span class="material-symbols-outlined text-[15px]">check_circle</span>
                                                    </div>
                                                    <span class="text-[11px] font-bold text-emerald-400 uppercase tracking-wider">Trabajo Realizado</span>
                                                </div>
                                                <span class="text-[10px] text-text-muted">Por: <strong class="text-text-main"><?php echo htmlspecialchars($tecnico); ?></strong></span>
                                            </div>
                                            <div class="text-xs text-text-main font-medium leading-relaxed bg-background/50 p-2.5 rounded-lg border border-border/40">
                                                <?php if (!empty($resolucion)): ?>
                                                    <?php echo htmlspecialchars($resolucion); ?>
                                                <?php else: ?>
                                                    Servicio técnico finalizado, diagnóstico completado y control de calidad aprobado en taller.
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="mt-2 text-[10px] text-emerald-400/90 flex items-center gap-1 font-medium">
                                            <span class="material-symbols-outlined text-[13px]">verified</span>
                                            Aprobado para entrega y cobro
                                        </div>
                                    </div>

                                    <?php // Especificación 3: Repuestos e Insumos ?>
                                    <div class="bg-surface/80 rounded-xl p-4 border border-cyan-500/20 flex flex-col justify-between shadow-sm">
                                        <div>
                                            <div class="flex items-center justify-between mb-2">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-6 h-6 rounded-lg bg-cyan-500/10 text-cyan-400 flex items-center justify-center">
                                                        <span class="material-symbols-outlined text-[15px]">memory</span>
                                                    </div>
                                                    <span class="text-[11px] font-bold text-cyan-400 uppercase tracking-wider">Repuestos</span>
                                                </div>
                                                <span class="text-[10px] font-semibold text-text-muted"><?php echo count($parts); ?> pieza(s)</span>
                                            </div>
                                            
                                            <?php if (count($parts) > 0): ?>
                                                <ul class="space-y-1.5 max-h-24 overflow-y-auto custom-scrollbar bg-background/50 p-2 rounded-lg border border-border/40">
                                                    <?php foreach($parts as $part): ?>
                                                        <li class="text-[11px] flex justify-between items-center text-text-main">
                                                            <span class="truncate pr-1"><?php echo htmlspecialchars($part['cantidad'] . 'x ' . $part['nombre']); ?></span>
                                                            <strong class="font-mono text-cyan-400 shrink-0">$<?php echo number_format($part['precio_unitario'] * $part['cantidad'], 2); ?></strong>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <div class="bg-background/50 p-2.5 rounded-lg border border-border/40 flex items-start gap-2">
                                                    <span class="material-symbols-outlined text-[16px] text-cyan-400 mt-0.5">engineering</span>
                                                    <div>
                                                        <p class="text-xs font-semibold text-text-main">Solo Mano de Obra</p>
                                                        <p class="text-[10px] text-text-muted mt-0.5">Reparación sin sustitución de piezas (limpieza, ajuste o diagnóstico).</p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mt-2 text-[10px] text-text-muted flex justify-between items-center border-t border-border/30 pt-1.5">
                                            <span>Subtotal piezas:</span>
                                            <strong class="text-text-main font-mono">$<?php echo number_format($costo_repuestos, 2); ?></strong>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full py-16 text-center border-2 border-dashed border-border/50 rounded-2xl bg-surface/20">
                    <span class="material-symbols-outlined text-text-muted/40 text-6xl mb-3 block">inventory_2</span>
                    <h3 class="text-lg font-medium text-text-main mb-1">No hay reparaciones listas para cobrar</h3>
                    <p class="text-sm text-text-muted max-w-md mx-auto">Cuando el técnico en el taller finalice un trabajo y presione <strong class="text-text-main">"Terminar y Enviar a Caja"</strong>, aparecerá aquí inmediatamente para su cobro y entrega al cliente.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
// Selector interactivo de método de pago con íconos Material Symbols
function selectPayMethod(method, btn) {
    const form = btn.closest('form');
    if (!form) return;
    form.querySelectorAll('.pay-method-btn').forEach(function(b) {
        b.className = 'pay-method-btn flex flex-col items-center justify-center py-2.5 px-1.5 rounded-xl border border-border/60 bg-surface hover:bg-surface-hover text-text-muted transition-all';
    });
    btn.className = 'pay-method-btn flex flex-col items-center justify-center py-2.5 px-1.5 rounded-xl border border-primary bg-primary/15 text-primary transition-all shadow-sm';
    const hidden = form.querySelector('.input-metodo-pago');
    if (hidden) hidden.value = method;
}

// Monitoreo en vivo de la bandeja de Cobrar Reparaciones
(function() {
    const initialIds = <?php echo json_encode(array_values(array_map('intval', array_column($repairs, 'id_reparacion')))); ?>;
    let knownIds = new Set(initialIds);
    let pollInterval = null;

    function playCashierChime() {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            if (ctx.state === 'suspended') ctx.resume();
            const notes = [
                { freq: 698.46, time: 0, dur: 0.15 },
                { freq: 880.00, time: 0.14, dur: 0.3 }
            ];
            notes.forEach(function(n) {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(n.freq, ctx.currentTime + n.time);
                gain.gain.setValueAtTime(0.001, ctx.currentTime + n.time);
                gain.gain.exponentialRampToValueAtTime(0.25, ctx.currentTime + n.time + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + n.time + n.dur);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(ctx.currentTime + n.time);
                osc.stop(ctx.currentTime + n.time + n.dur);
            });
        } catch(e) {}
    }

    function checkQueue() {
        fetch('index.php?action=check_ready_repairs', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (!data || !data.success) return;

            const incomingRepairs = data.repairs || [];
            const incomingIds = incomingRepairs.map(function(r) { return r.id; });

            // Detectar si ingresó alguna orden nueva
            const newOrders = incomingRepairs.filter(function(r) { return !knownIds.has(r.id); });
            
            // Detectar si alguna orden que estaba se cobró desde otra pestaña o ventana
            const removedOrders = Array.from(knownIds).filter(function(id) { return !incomingIds.includes(id); });

            if (newOrders.length > 0) {
                clearInterval(pollInterval);
                playCashierChime();
                const latest = newOrders[newOrders.length - 1];
                if (typeof showToast === 'function') {
                    showToast('¡Nueva orden recibida del Taller! #' + latest.codigo + ' (' + latest.cliente + ') - Total: ' + latest.total_formatted, 'success');
                }
                setTimeout(function() {
                    window.location.reload();
                }, 1200);
            } else if (removedOrders.length > 0) {
                clearInterval(pollInterval);
                window.location.reload();
            }
        })
        .catch(function() {});
    }

    // Escucha instantánea entre pestañas / ventanas en tiempo real (0ms de retraso)
    window.addEventListener('storage', function(e) {
        if (e.key === 'nokia1100_order_ready_ping') {
            checkQueue();
        }
    });
    if (window.BroadcastChannel) {
        try {
            const bc = new BroadcastChannel('nokia1100_repairs');
            bc.onmessage = function(ev) {
                if (ev.data && ev.data.type === 'order_ready') {
                    checkQueue();
                }
            };
        } catch(e) {}
    }

    pollInterval = setInterval(checkQueue, 1200);
})();
</script>

<?php Layout::renderFooter(); ?>
