<?php
// modules/sales/Views/new_sale.php

Layout::renderHead('Nueva Venta - NOKIA1100');
if ($_SESSION['role'] === 'admin') Layout::renderAdminSidebar('ventas');
else Layout::renderEmployeeSidebar('ventas');
?>
<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8 border border-border/50">
        <div class="flex justify-between items-center mb-8 pb-4 border-b border-border/50">
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
        <div class="overflow-x-auto bg-surface/20 rounded-2xl border border-border/50 mb-8">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface/50 border-b border-border/50 text-xs uppercase tracking-wider text-text-muted">
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
                    <tr class="border-t border-border/50 bg-surface/30">
                        <td colspan="3" class="p-4 text-right font-medium text-text-muted">TOTAL A COBRAR:</td>
                        <td id="cart-total" class="p-4 text-right font-display text-xl font-semibold text-primary">$0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
            <div>
                <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Método de Pago</label>
                <select id="metodo_pago" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main appearance-none">
                    <option value="Efectivo">Efectivo</option>
                    <option value="Tarjeta">Tarjeta de Crédito/Débito</option>
                    <option value="Transferencia">Transferencia Bancaria</option>
                </select>
            </div>
            <div>
                <button type="button" onclick="submitSale()"
                    class="w-full bg-text-main text-background hover:bg-text-muted font-medium py-3 px-6 rounded-xl transition-all flex justify-center items-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">point_of_sale</span> Confirmar y Registrar Venta
                </button>
            </div>
        </div>
    </div>
</main>
<?php Layout::renderFooter(); ?>

<script src="<?php echo BASE_URL; ?>/modules/sales/new_sale.js?v=<?php echo time(); ?>"></script>
