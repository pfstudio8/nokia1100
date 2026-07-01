<?php
// modules/suppliers/Views/new_purchase.php

Layout::renderHead('Registrar Compra - NOKIA1100');
Layout::renderAdminSidebar('proveedores');
?>
<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8 border border-border/50">
        <div class="flex justify-between items-center mb-8 pb-4 border-b border-border/50">
            <div>
                <h2 class="text-2xl font-display font-medium text-text-main">Registrar Compra</h2>
                <p class="text-text-muted text-sm mt-1">Gestión de abastecimiento e ingreso de mercadería</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/modules/admin/dashboard.php" class="px-4 py-2 rounded-xl border border-border bg-surface hover:bg-surface-hover text-sm font-medium transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span> Volver
            </a>
        </div>

        <div class="mb-6">
            <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Proveedor</label>
            <select id="id_proveedor" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main appearance-none">
                <option value="">Seleccione un proveedor...</option>
                <?php foreach($suppliers as $s): ?>
                    <option value="<?php echo $s['id_proveedor']; ?>"><?php echo htmlspecialchars($s['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div>
                <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Descripción</label>
                <input type="text" id="descripcion" placeholder="Ej: Lote mensual" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
            </div>
            <div>
                <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Tiempo Entrega</label>
                <input type="text" id="tiempo_entrega" placeholder="Ej: 30 días" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
            </div>
            <div>
                <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">IVA (%)</label>
                <input type="number" id="iva" min="0" step="0.01" value="0" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
            </div>
            <div>
                <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Autorizado Por</label>
                <input type="text" id="autorizado_por" placeholder="Ej: Pedro P." class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end mb-8 bg-surface/30 p-6 rounded-2xl border border-border/30">
            <div class="md:col-span-3">
                <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Producto / Nro Parte</label>
                <input type="text" id="product_nombre" placeholder="Nombre" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Marca</label>
                <input type="text" id="product_marca" placeholder="Opcional" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Modelo</label>
                <input type="text" id="product_modelo" placeholder="Opcional" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Costo Unit.</label>
                <input type="number" id="costo" min="0" step="0.01" value="0" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
            </div>
            <div class="md:col-span-1">
                <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Cant.</label>
                <input type="number" id="cantidad" min="1" value="1" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
            </div>
            <div class="md:col-span-2 flex">
                <button type="button" class="w-full bg-primary/10 text-primary border border-primary/20 hover:bg-primary hover:text-background font-medium py-3 rounded-xl transition-all flex justify-center items-center gap-2" onclick="addToCart()">
                    <span class="material-symbols-outlined text-[18px]">add_box</span> Añadir
                </button>
            </div>
        </div>

        <h3 class="text-xs uppercase font-semibold tracking-widest text-text-muted mb-4 px-2">Items a Ingresar</h3>
        <div class="overflow-x-auto bg-surface/20 rounded-2xl border border-border/50 mb-8">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface/50 border-b border-border/50 text-xs uppercase tracking-wider text-text-muted">
                        <th class="p-4 font-semibold">Producto</th>
                        <th class="p-4 font-semibold text-right">Costo Unit.</th>
                        <th class="p-4 font-semibold text-center">Cant.</th>
                        <th class="p-4 font-semibold text-right">Subtotal</th>
                        <th class="p-4 font-semibold text-center">Acción</th>
                    </tr>
                </thead>
                <tbody id="cart-body" class="divide-y divide-border/30">
                    <tr><td colspan="5" class="p-8 text-center text-text-muted text-sm border-none">Aún no hay productos en la orden de compra</td></tr>
                </tbody>
                <tfoot>
                    <tr class="border-t border-border/50 bg-surface/30">
                        <td colspan="3" class="p-4 text-right font-medium text-text-muted">TOTAL COMPRA:</td>
                        <td id="cart-total" class="p-4 text-right font-display text-xl font-semibold text-primary">$0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <button type="button" onclick="submitPurchase()" class="w-full bg-text-main text-background hover:bg-text-muted font-medium py-4 px-6 rounded-xl transition-all flex justify-center items-center gap-2">
            <span class="material-symbols-outlined text-[20px]">save</span> Efectuar e Ingresar al Inventario
        </button>
    </div>
</main>
<?php Layout::renderFooter(); ?>

<script>
    window.BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<script src="<?php echo BASE_URL; ?>/modules/suppliers/new_purchase.js?v=<?php echo time(); ?>"></script>
