<?php
// modules/inventory/Views/edit_stock.php

Layout::renderHead('Editar Stock - NOKIA1100');
Layout::renderAdminSidebar('inventario');
?>
<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8 border border-border/50 max-w-3xl mx-auto">
        <div class="flex justify-between items-center mb-8 pb-4 border-b border-border/50">
            <div>
                <h2 class="text-2xl font-display font-medium text-text-main">Editar Producto</h2>
                <p class="text-text-muted text-sm mt-1">Modificar detalles y existencias del inventario</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/modules/inventory/index.php" class="px-4 py-2 rounded-xl border border-border bg-surface hover:bg-surface-hover text-sm font-medium transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span> Volver
            </a>
        </div>



        <?php if (!empty($_GET['success'])): ?>
            <div class="bg-green-500/10 border border-green-500/20 text-green-400 p-4 rounded-xl mb-6 text-sm flex gap-3 items-center">
                <span class="material-symbols-outlined text-[18px]">check_circle</span>
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php elseif (!empty($_GET['error'])): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-400 p-4 rounded-xl mb-6 text-sm flex gap-3 items-center">
                <span class="material-symbols-outlined text-[18px]">error</span>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['session_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="nombre" class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Nombre del Producto</label>
                    <input type="text" id="nombre" name="nombre" required value="<?php echo isset($nombre) ? htmlspecialchars($nombre) : ''; ?>" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
                </div>
                <div>
                    <label for="categoria" class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Categoría</label>
                    <div class="relative">
                        <select id="categoria" name="categoria" required class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main appearance-none pr-9 cursor-pointer">
                            <option value="">Seleccione una categoría...</option>
                            <?php
                            $cat_opts = [
                                'Celulares' => 'Celulares / Smartphones',
                                'Fundas' => 'Fundas / Protectores',
                                'Audio' => 'Audio / Auriculares',
                                'Cargadores' => 'Cargadores / Cables',
                                'Perifericos' => 'Computación / Periféricos',
                                'Accesorios' => 'Otros Accesorios',
                                'Repuestos' => 'Repuestos'
                            ];
                            $curr_cat = $categoria ?? '';
                            foreach ($cat_opts as $c_val => $c_name):
                            ?>
                                <option value="<?php echo htmlspecialchars($c_val); ?>" <?php echo ($curr_cat === $c_val) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">expand_more</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="marca" class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Marca</label>
                    <input type="text" id="marca" name="marca" required value="<?php echo isset($marca) ? htmlspecialchars($marca) : ''; ?>" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
                </div>
                <div>
                    <label for="modelo" class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Modelo / Referencia</label>
                    <input type="text" id="modelo" name="modelo" required value="<?php echo isset($modelo) ? htmlspecialchars($modelo) : ''; ?>" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pb-6 border-b border-border/30">
                <div>
                    <label for="precio" class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Precio Unitario ($)</label>
                    <input type="number" id="precio" name="precio" step="0.01" min="0" required value="<?php echo isset($precio) ? htmlspecialchars($precio) : ''; ?>" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main font-mono">
                </div>
                <div>
                    <label for="cantidad" class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Cantidad Total (Stock Físico)</label>
                    <input type="number" id="cantidad" name="cantidad" min="0" required value="<?php echo isset($cantidad) ? htmlspecialchars($cantidad) : ''; ?>" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main font-mono">
                </div>
            </div>

            <button type="submit" class="w-full bg-primary/10 text-primary border border-primary/20 hover:bg-primary hover:text-background font-medium py-3 rounded-xl transition-all flex justify-center items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">save</span> Actualizar Producto
            </button>
        </form>
    </div>
</main>
<?php Layout::renderFooter(); ?>
