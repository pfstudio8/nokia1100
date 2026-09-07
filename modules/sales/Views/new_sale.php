<?php
// modules/sales/Views/new_sale.php

Layout::renderHead('Nueva Venta - NOKIA1100');
if ($_SESSION['role'] === 'admin') Layout::renderAdminSidebar('ventas');
else Layout::renderEmployeeSidebar('ventas');
?>
<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8 border border-border/30">
        <div class="flex justify-between items-center mb-8 pb-4 border-b border-border/30">
            <div>
                <h2 class="text-2xl font-display font-medium text-text-main">Nueva Venta</h2>
                <p class="text-text-muted text-sm mt-1">Terminal de Punto de Venta (POS)</p>
            </div>
            <a href="<?php echo BASE_URL . ($_SESSION['role'] === 'admin' ? '/modules/admin/dashboard.php' : '/modules/employee/dashboard.php'); ?>"
               class="px-4 py-2 rounded-xl border border-border bg-surface hover:bg-surface-hover text-sm font-medium text-text-main transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span> Volver
            </a>
        </div>

        <div id="alert-box" style="display:none;"></div>

        <!-- Selector de producto -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-8 bg-surface/30 p-6 rounded-2xl border border-border/30">
            <div class="md:col-span-8">
                <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Producto</label>
                <select id="id_producto" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main appearance-none">
                    <option value="">Seleccione un producto...</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?php echo $p['id_producto']; ?>"
                                data-nombre="<?php echo htmlspecialchars($p['nombre'] . " " . $p['marca'] . " " . $p['modelo']); ?>"
                                data-precio="<?php echo $p['precio']; ?>"
                                data-stock="<?php echo $p['cantidad']; ?>">
                            <?php echo htmlspecialchars($p['nombre'] . " - " . $p['marca'] . " " . $p['modelo'] . " ($" . number_format($p['precio'], 2) . ") - Stock: " . $p['cantidad']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Cantidad</label>
                <input type="number" id="cantidad" min="1" value="1"
                    class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
            </div>
            <div class="md:col-span-2 flex items-end">
                <button type="button" onclick="addToCart()"
                    class="w-full bg-primary/10 text-primary border border-primary/20 hover:bg-primary hover:text-background font-medium py-3 rounded-xl transition-all flex justify-center items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">add_shopping_cart</span> Agregar
                </button>
            </div>
        </div>

        <h3 class="text-xs uppercase font-semibold tracking-widest text-text-muted mb-4 px-2">Carrito</h3>
        <div class="overflow-x-auto bg-surface/20 rounded-2xl border border-border/30 mb-8">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface/50 border-b border-border/30 text-xs uppercase tracking-wider text-text-muted">
                        <th class="p-4">Producto</th>
                        <th class="p-4 text-right">Precio Unit.</th>
                        <th class="p-4 text-center">Cant.</th>
                        <th class="p-4 text-right">Subtotal</th>
                        <th class="p-4 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody id="cart-body" class="divide-y divide-border/30">
                    <tr><td colspan="5" class="p-8 text-center text-text-muted text-sm border-none">El carrito está vacío</td></tr>
                </tbody>
                <tfoot>
                    <tr class="border-t border-border/30 bg-surface/30">
                        <td colspan="3" class="p-4 text-right font-medium text-text-muted">TOTAL A COBRAR:</td>
                        <td id="cart-total" class="p-4 text-right font-display text-xl font-semibold text-primary">$0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="flex justify-end gap-6 items-end">
            <div class="w-full md:w-1/2 lg:w-1/3">
                <button type="button" onclick="openPaymentModal()"
                    class="w-full bg-primary text-background hover:bg-primary-hover font-medium py-4 px-6 rounded-xl transition-all flex justify-center items-center gap-2 shadow-lg shadow-primary/20">
                    <span class="material-symbols-outlined text-[22px]">point_of_sale</span> Procesar Venta
                </button>
            </div>
        </div>
    </div>
</main>

<!-- Modal Procesar Venta -->
<div id="payment-modal" class="fixed inset-0 bg-background/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-surface border border-border/50 rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="p-6 border-b border-border/30 flex justify-between items-center bg-surface-hover/30">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-primary/20 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined">payments</span>
                </div>
                <div>
                    <h3 class="text-xl font-display font-semibold text-text-main flex items-center gap-2">
                        Procesar Venta <span class="text-[10px] bg-primary/20 text-primary px-2 py-0.5 rounded-md tracking-widest font-bold uppercase">POS #01</span>
                    </h3>
                    <p class="text-text-muted text-xs mt-1">Verificación de compra y liquidación de pago</p>
                </div>
            </div>
            <button onclick="closePaymentModal()" class="text-text-muted hover:text-text-main transition-colors p-2 rounded-lg hover:bg-surface-hover">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Body -->
        <div class="p-4 md:p-6 overflow-y-auto space-y-4 md:space-y-5">
            <!-- Detalle de Compra -->
            <div>
                <div class="flex justify-between items-center mb-1">
                    <h4 class="text-[10px] font-bold text-text-muted uppercase tracking-widest">Detalle de Compra</h4>
                    <span id="modal-items-count" class="text-[11px] text-text-muted">0 artículos</span>
                </div>
                <div class="bg-background/50 border border-border/30 rounded-lg p-3 flex justify-between items-center">
                    <div>
                        <p id="modal-first-item-name" class="font-medium text-text-main text-sm">--</p>
                        <p id="modal-first-item-desc" class="text-[11px] text-text-muted mt-0.5">--</p>
                    </div>
                    <div id="modal-first-item-price" class="font-display font-semibold text-text-main text-sm">
                        $0.00
                    </div>
                </div>
            </div>

            <!-- Total a Pagar -->
            <div>
                <div class="bg-surface-hover border border-primary/30 rounded-lg p-4 flex justify-between items-center shadow-[inset_0_0_15px_rgba(0,0,0,0.2)]">
                    <div>
                        <h4 class="text-xs font-bold text-text-muted uppercase tracking-wider mb-0.5">Total a Pagar</h4>
                        <p class="text-[10px] text-primary/80">Impuestos e IVA incluidos</p>
                    </div>
                    <div id="modal-total-amount" class="text-2xl font-display font-bold text-primary">
                        $0.00
                    </div>
                </div>
            </div>

            <!-- Método de Pago -->
            <div>
                <h4 class="text-[10px] font-bold text-text-muted uppercase tracking-widest mb-2">Seleccione Método de Pago</h4>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" onclick="selectPaymentMethod('Efectivo')" id="btn-pay-efectivo" class="payment-method-btn flex flex-col items-center justify-center py-3 px-2 rounded-lg border border-primary bg-primary/10 text-primary transition-all">
                        <span class="material-symbols-outlined mb-1 text-[20px]">payments</span>
                        <span class="text-[11px] font-semibold">Efectivo</span>
                    </button>
                    <button type="button" onclick="selectPaymentMethod('Transferencia')" id="btn-pay-transferencia" class="payment-method-btn flex flex-col items-center justify-center py-3 px-2 rounded-lg border border-border/50 bg-background hover:bg-surface-hover text-text-muted transition-all">
                        <span class="material-symbols-outlined mb-1 text-[20px]">qr_code_scanner</span>
                        <span class="text-[11px] font-semibold text-center leading-tight">Transferencia<br>/ QR</span>
                    </button>
                    <button type="button" onclick="selectPaymentMethod('Tarjeta')" id="btn-pay-tarjeta" class="payment-method-btn flex flex-col items-center justify-center py-3 px-2 rounded-lg border border-border/50 bg-background hover:bg-surface-hover text-text-muted transition-all">
                        <span class="material-symbols-outlined mb-1 text-[20px]">credit_card</span>
                        <span class="text-[11px] font-semibold">Tarjeta Crédito</span>
                    </button>
                </div>
                <input type="hidden" id="modal_metodo_pago" value="Efectivo">
            </div>

            <!-- Liquidación Efectivo (Condicional) -->
            <div id="cash-liquidation-block" class="border border-border/30 rounded-lg p-4 bg-background/30 mt-2 transition-all duration-300">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="text-[10px] font-bold text-primary uppercase tracking-widest flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">price_check</span> Liquidación Efectivo
                    </h4>
                    <span id="cash-status-badge" class="text-[9px] px-1.5 py-0.5 rounded-sm font-bold uppercase tracking-wider bg-surface border border-border text-text-muted">Pendiente</span>
                </div>
                
                <label class="block text-[11px] font-semibold text-text-muted mb-1.5">Monto recibido:</label>
                <div class="relative mb-3">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-text-main font-bold text-sm">$</span>
                    <input type="number" id="monto_recibido" oninput="calculateChange()" class="w-full bg-surface border border-border/50 rounded-lg py-2 pl-7 pr-10 text-base font-semibold text-text-main focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all" placeholder="0">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted text-[10px] font-bold">ARS</span>
                </div>

                <div class="mb-4">
                    <p class="text-[9px] text-text-muted mb-1.5">Sugerencias:</p>
                    <div class="flex flex-wrap gap-1.5" id="quick-suggestions">
                        <!-- Generado dinámicamente -->
                    </div>
                </div>

                <div class="bg-surface-hover border border-border/50 rounded-lg p-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div id="change-icon-container" class="w-6 h-6 rounded-full bg-surface flex items-center justify-center text-text-muted transition-colors">
                            <span class="material-symbols-outlined text-[14px]">trending_up</span>
                        </div>
                        <div>
                            <h4 class="text-[10px] font-bold text-text-muted uppercase tracking-wider mb-0.5">Vuelto a entregar</h4>
                            <p id="change-hint" class="text-[9px] text-text-muted">Esperando monto...</p>
                        </div>
                    </div>
                    <div id="modal-change-amount" class="text-lg font-display font-bold text-text-muted">
                        $0.00
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-4 border-t border-border/30 bg-surface-hover/50 flex justify-end gap-3 items-center">
            <button type="button" onclick="closePaymentModal()" class="px-4 py-2 rounded-lg text-xs font-medium text-text-muted hover:text-text-main transition-colors">
                Cancelar
            </button>
            <button type="button" onclick="submitSaleFromModal()" id="btn-confirm-sale" class="bg-primary text-background hover:bg-primary-hover font-medium py-2 px-5 rounded-lg transition-all flex items-center gap-2 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                <span class="material-symbols-outlined text-[16px]">receipt_long</span> Confirmar Venta
            </button>
        </div>
    </div>
</div>
<?php Layout::renderFooter(); ?>

<script src="<?php echo BASE_URL; ?>/modules/sales/new_sale.js?v=<?php echo time(); ?>"></script>
