<?php
// modules/inventory/Views/inventory.php

Layout::renderHead('Inventario - Nokia 1100');

if ($_SESSION['role'] === 'admin') {
    Layout::renderAdminSidebar('inventario');
} else {
    Layout::renderEmployeeSidebar('inventario');
}
?>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8">
        <div class="dashboard-header border-b border-border/30 pb-6 mb-6">
            <div>
                <h2 class="text-3xl font-display font-medium text-text-main">Inventario</h2>
                <p class="text-text-muted mt-1 text-sm">Control de stock, repuestos y equipos tecnológicos</p>
            </div>
            <div class="flex items-center gap-4">
                <!-- Botones de Exportación -->
                <button type="button" onclick="exportTableToExcel('inventory-table', 'inventario', this)" class="px-3 py-2 rounded-xl border border-green-500/30 bg-green-500/10 hover:bg-green-500/20 text-xs font-medium text-green-400 hover:text-green-300 transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">download</span> Excel
                </button>
                <button type="button" onclick="exportTableToPDF('inventory-table', 'Listado de Inventario', 'inventario', this)" class="px-3 py-2 rounded-xl border border-red-500/30 bg-red-500/10 hover:bg-red-500/20 text-xs font-medium text-red-400 hover:text-red-300 transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">download</span> PDF
                </button>


                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="add_product.php" class="bg-primary text-background hover:bg-primary-hover px-4 py-2 rounded-lg font-medium text-sm transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">add</span> Añadir
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <form method="GET" action="" class="flex gap-3 mb-6 items-center flex-wrap">
            <div class="relative flex-1 max-w-md">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-muted text-[20px]">search</span>
                <input type="text" id="search-input" name="search" placeholder="Buscar baterías, pantallas, modelos..." value="<?php echo htmlspecialchars($search); ?>" class="w-full bg-surface border border-border pl-10 pt-2.5 pb-2.5 pr-4 rounded-xl text-sm text-text-main focus:outline-none focus:border-primary transition-colors">
            </div>
            <button type="submit" class="bg-surface hover:bg-surface-hover border border-border px-4 py-2.5 rounded-xl text-sm font-medium transition-colors text-text-main">Buscar</button>
            <?php if ($search): ?>
                <a href="<?php echo BASE_URL; ?>/modules/inventory/inventory.php" class="text-red-400 hover:text-red-300 text-sm font-medium ml-2 transition-colors">Limpiar</a>
            <?php endif; ?>
        </form>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'has_sales'): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6 text-sm flex gap-3 items-center">
                <span class="material-symbols-outlined">error</span> No se puede eliminar este producto porque tiene ventas asociadas.
            </div>
        <?php endif; ?>

        <!-- TABLE VIEW -->
        <div id="view-table" class="table-container rounded-2xl border border-border overflow-x-auto">
            <table class="w-full text-left" id="inventory-table">
                <thead class="bg-surface/50 border-b border-border text-xs uppercase text-text-muted tracking-wider">
                    <tr>
                        <th class="p-4 font-semibold">Producto</th>
                        <th class="p-4 font-semibold">Categoría/Modelo</th>
                        <th class="p-4 font-semibold">Stock</th>
                        <th class="p-4 font-semibold">Precio</th>
                        <th class="p-4 font-semibold text-center">Estado</th>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <th class="p-4 font-semibold text-right">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/50">
                    <?php if (count($items) > 0): ?>
                        <?php foreach($items as $row): ?>
                            <tr class="hover:bg-surface-hover/30 transition-colors">
                                <td class="p-4 font-medium text-text-main"><?php echo htmlspecialchars($row['nombre']); ?></td>
                                <td class="p-4 text-text-muted text-sm"><?php echo htmlspecialchars($row['marca'] . ' ' . $row['modelo']); ?></td>
                                <td class="p-4">
                                    <div class="inline-flex items-center gap-2 bg-surface border border-border px-3 py-1 rounded-full text-sm font-display text-text-main">
                                        <?php echo $row['cantidad']; ?> <span class="text-text-muted text-xs">un.</span>
                                    </div>
                                </td>
                                <td class="p-4 text-text-main font-medium">$<?php echo number_format($row['precio'], 2); ?></td>
                                <td class="p-4 text-center">
                                    <?php if ($_SESSION['role'] === 'admin'): ?>
                                        <?php // Enlace para cambiar el estado (activar/desactivar) del producto mediante la API ?>
                                        <a href="<?php echo BASE_URL; ?>/api/change_status.php?id=<?php echo $row['id_producto']; ?>" class="inline-block px-2.5 py-1 rounded text-[10px] uppercase font-bold tracking-widest transition-colors <?php echo $row['is_active'] ? 'bg-green-500/10 text-green-500 border border-green-500/20 hover:bg-green-500/20' : 'bg-red-500/10 text-red-500 border border-red-500/20 hover:bg-red-500/20'; ?>">
                                            <?php echo $row['is_active'] ? 'Activo' : 'Inactivo'; ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="inline-block px-2.5 py-1 rounded text-[10px] uppercase font-bold tracking-widest <?php echo $row['is_active'] ? 'bg-green-500/10 text-green-500 border border-green-500/20' : 'bg-red-500/10 text-red-500 border border-red-500/20'; ?>">
                                            <?php echo $row['is_active'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="edit_stock.php?id=<?php echo $row['id_producto']; ?>" class="w-8 h-8 rounded-lg flex items-center justify-center bg-primary/10 text-primary border border-primary/20 hover:bg-primary hover:text-background transition-colors" title="Editar">
                                            <span class="material-symbols-outlined text-[16px]">edit</span>
                                        </a>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="p-12 text-center text-text-muted">No hay productos en inventario</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>


    </div>
</main>

<script src="<?php echo BASE_URL; ?>/modules/inventory/inventory.js?v=<?php echo time(); ?>"></script>

<?php Layout::renderFooter(); ?>
