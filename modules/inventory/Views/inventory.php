<?php
// modules/inventory/Views/inventory.php

// Injected variables from InventoryController::inventory()
/** @var array $items */
/** @var string $search */
/** @var string $filter */
/** @var string $categoria */
/** @var array $categorias */
/** @var int $page */
/** @var int $total_pages */
/** @var int $total_items */
/** @var int $limit */
$items = isset($items) && is_array($items) ? $items : [];
$search = isset($search) ? (string)$search : '';
$filter = isset($filter) ? (string)$filter : '';
$categoria = isset($categoria) ? (string)$categoria : '';
$categorias = isset($categorias) && is_array($categorias) ? $categorias : [];
$page = isset($page) ? (int)$page : 1;
$total_items = isset($total_items) ? (int)$total_items : count($items);
$limit = isset($limit) && (int)$limit > 0 ? (int)$limit : 10;
$total_pages = isset($total_pages) ? (int)$total_pages : max(1, (int)ceil($total_items / $limit));

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
            <div class="flex items-center gap-3 flex-wrap">
                <!-- Botones de Exportación -->
                <button type="button" onclick="exportTableToExcel('inventory-table', 'inventario', this)" class="px-3 py-2 rounded-xl border border-green-500/30 bg-green-500/10 hover:bg-green-500/20 text-xs font-medium text-green-400 hover:text-green-300 transition-colors flex items-center gap-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-[16px]">download</span> Excel
                </button>
                <button type="button" onclick="exportTableToPDF('inventory-table', 'Listado de Inventario', 'inventario', this)" class="px-3 py-2 rounded-xl border border-red-500/30 bg-red-500/10 hover:bg-red-500/20 text-xs font-medium text-red-400 hover:text-red-300 transition-colors flex items-center gap-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-[16px]">download</span> PDF
                </button>




                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="index.php?action=add_product" class="bg-primary text-background hover:bg-primary-hover px-4 py-2 rounded-lg font-medium text-sm transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">add</span> Añadir
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php
        $categorias_catalogo = [
            'Celulares' => 'Celulares / Smartphones',
            'Fundas' => 'Fundas / Protectores',
            'Audio' => 'Audio / Auriculares',
            'Cargadores' => 'Cargadores / Cables',
            'Perifericos' => 'Computación / Periféricos',
            'Accesorios' => 'Otros Accesorios',
            'Repuestos' => 'Repuestos'
        ];

        $category_styles = [
            'Celulares' => ['label' => 'Celulares', 'class' => 'bg-sky-500/20 text-sky-300 border-sky-400/40'],
            'Fundas' => ['label' => 'Fundas', 'class' => 'bg-purple-500/20 text-purple-300 border-purple-400/40'],
            'Audio' => ['label' => 'Audio', 'class' => 'bg-pink-500/20 text-pink-300 border-pink-400/40'],
            'Cargadores' => ['label' => 'Cargadores', 'class' => 'bg-slate-700/60 text-slate-100 border-slate-400/50'],
            'Repuestos' => ['label' => 'Repuestos', 'class' => 'bg-teal-500/20 text-teal-300 border-teal-400/40'],
            'Perifericos' => ['label' => 'Periféricos', 'class' => 'bg-lime-500/15 text-lime-300 border-lime-400/40'],
            'Accesorios' => ['label' => 'Accesorios', 'class' => 'bg-indigo-500/20 text-indigo-300 border-indigo-400/40'],
            'Servicios' => ['label' => 'Servicios', 'class' => 'bg-cyan-500/20 text-cyan-300 border-cyan-400/40']
        ];
        ?>

        <form method="GET" action="" class="flex gap-3 mb-6 items-center flex-wrap">
            <div class="relative flex-1 min-w-[240px] max-w-md">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-muted text-[20px]">search</span>
                <input type="text" id="search-input" name="search" placeholder="Buscar por producto, marca, modelo, SKU..." value="<?php echo htmlspecialchars($search); ?>" class="w-full bg-surface border border-border pl-10 pt-2.5 pb-2.5 pr-4 rounded-xl text-sm text-text-main focus:outline-none focus:border-primary transition-colors">
            </div>

            <div class="relative min-w-[190px]">
                <select name="categoria" onchange="this.form.submit()" class="w-full bg-surface border border-border px-3.5 py-2.5 rounded-xl text-sm text-text-main focus:outline-none focus:border-primary transition-colors cursor-pointer appearance-none pr-9">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias_catalogo as $cat_key => $cat_lbl): ?>
                        <option value="<?php echo htmlspecialchars($cat_key); ?>" <?php echo ($categoria === $cat_key) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat_lbl); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">expand_more</span>
            </div>

            <button type="submit" class="bg-surface hover:bg-surface-hover border border-border px-4 py-2.5 rounded-xl text-sm font-medium transition-colors text-text-main flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">filter_list</span> Filtrar
            </button>

            <?php if (!empty($search) || !empty($categoria)): ?>
                <a href="<?php echo BASE_URL; ?>/modules/inventory/index.php" class="text-red-400 hover:text-red-300 text-sm font-medium ml-2 transition-colors flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">close</span> Limpiar filtros
                </a>
            <?php endif; ?>
        </form>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'has_sales'): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6 text-sm flex gap-3 items-center">
                <span class="material-symbols-outlined">error</span> No se puede eliminar este producto porque tiene ventas asociadas.
            </div>
        <?php endif; ?>

        <?php // TABLE VIEW ?>
        <div id="view-table" class="table-container rounded-2xl border border-border overflow-x-auto">
            <table class="w-full text-left" id="inventory-table">
                <thead class="bg-surface/50 border-b border-border text-xs uppercase text-text-muted tracking-wider">
                    <tr>
                        <th class="p-4 font-semibold">Producto</th>
                        <th class="p-4 font-semibold text-center">Categoría</th>
                        <th class="p-4 font-semibold text-center">Stock</th>
                        <th class="p-4 font-semibold">Precio</th>
                        <th class="p-4 font-semibold text-center">Estado</th>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <th class="p-4 font-semibold text-right">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/50">
                    <?php if (count($items) > 0): ?>
                        <?php foreach($items as $row): 
                            $cat_key = $row['categoria'] ?? '';
                            $cat_data = $category_styles[$cat_key] ?? null;
                            $cat_label = $cat_data['label'] ?? ($cat_key ?: '');
                            $cat_class = $cat_data['class'] ?? 'bg-surface border-border text-text-muted';
                            $marca_modelo = trim(($row['marca'] ?? '') . ' ' . ($row['modelo'] ?? ''));
                            
                            $cant = (int)$row['cantidad'];
                            $min = isset($row['stock_minimo']) ? (int)$row['stock_minimo'] : 2;
                            if ($cant <= 0) {
                                $stock_class = 'bg-rose-500/15 text-rose-400 border-rose-500/30';
                                $stock_tag = 'Agotado';
                            } elseif ($cant <= $min) {
                                $stock_class = 'bg-amber-500/15 text-amber-400 border-amber-500/30';
                                $stock_tag = 'Bajo';
                            } else {
                                $stock_class = 'bg-emerald-500/10 text-emerald-400 border-emerald-500/25';
                                $stock_tag = null;
                            }
                        ?>
                            <tr class="hover:bg-surface-hover/30 transition-colors">
                                <td class="p-4">
                                    <div class="font-medium text-text-main text-sm leading-snug">
                                        <?php echo htmlspecialchars($row['nombre']); ?>
                                    </div>
                                    <?php 
                                    $marca = trim($row['marca'] ?? '');
                                    $modelo = trim($row['modelo'] ?? '');
                                    $codigo = trim($row['codigo'] ?? '');
                                    ?>
                                    <?php if (!empty($marca) || !empty($modelo) || !empty($codigo)): ?>
                                        <div class="text-xs text-text-muted mt-0.5 flex items-center gap-1.5 flex-wrap">
                                            <?php if (!empty($marca)): ?>
                                                <span class="text-text-main/90 font-medium"><?php echo htmlspecialchars($marca); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($marca) && !empty($modelo)): ?>
                                                <span class="text-text-muted/40">·</span>
                                            <?php endif; ?>
                                            <?php if (!empty($modelo)): ?>
                                                <span class="text-text-muted/90 font-normal"><?php echo htmlspecialchars($modelo); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($codigo)): ?>
                                                <?php if (!empty($marca) || !empty($modelo)): ?>
                                                    <span class="text-text-muted/40">•</span>
                                                <?php endif; ?>
                                                <span class="font-mono text-[11px] text-text-muted/60">SKU: <?php echo htmlspecialchars($codigo); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center whitespace-nowrap">
                                    <?php if (!empty($cat_label)): ?>
                                        <span class="inline-flex items-center justify-center min-w-[96px] px-3 py-1 rounded-md text-xs font-semibold tracking-wide border <?php echo $cat_class; ?>">
                                            <?php echo htmlspecialchars($cat_label); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-text-muted/40 text-xs italic">Sin categoría</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center justify-center min-w-[85px] gap-1.5 px-3 py-1 rounded-full text-sm font-display border <?php echo $stock_class; ?>">
                                        <span class="font-semibold"><?php echo $cant; ?></span>
                                        <span class="text-xs opacity-60">un.</span>
                                        <?php if ($stock_tag): ?>
                                            <span class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-current/15 ml-1">
                                                <?php echo $stock_tag; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="p-4 text-text-main font-medium whitespace-nowrap">$<?php echo number_format($row['precio'], 2); ?></td>
                                <td class="p-4 text-center whitespace-nowrap">
                                    <?php if ($_SESSION['role'] === 'admin'): ?>
                                        <form method="POST" action="<?php echo BASE_URL; ?>/api/change_status.php" class="inline-block">
                                            <input type="hidden" name="id" value="<?php echo $row['id_producto']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['session_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit" class="px-2.5 py-1 rounded text-[10px] uppercase font-bold tracking-widest transition-colors <?php echo $row['is_active'] ? 'bg-green-500/10 text-green-500 border border-green-500/20 hover:bg-green-500/20' : 'bg-red-500/10 text-red-500 border border-red-500/20 hover:bg-red-500/20'; ?>">
                                                <?php echo $row['is_active'] ? 'Activo' : 'Inactivo'; ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="inline-block px-2.5 py-1 rounded text-[10px] uppercase font-bold tracking-widest <?php echo $row['is_active'] ? 'bg-green-500/10 text-green-500 border border-green-500/20' : 'bg-red-500/10 text-red-500 border border-red-500/20'; ?>">
                                            <?php echo $row['is_active'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                <td class="p-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="index.php?action=edit_stock&id=<?php echo $row['id_producto']; ?>" class="w-8 h-8 rounded-lg flex items-center justify-center bg-primary/10 text-primary border border-primary/20 hover:bg-primary hover:text-background transition-colors" title="Editar">
                                            <span class="material-symbols-outlined text-[16px]">edit</span>
                                        </a>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="<?php echo ($_SESSION['role'] === 'admin') ? 6 : 5; ?>" class="p-12 text-center text-text-muted">No hay productos en inventario<?php if (!empty($categoria) || !empty($search)): ?> con los filtros aplicados<?php endif; ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php 
        $total_items = $total_items ?? count($items ?? []);
        $limit = $limit ?? 10;
        $page = $page ?? 1;
        $total_pages = $total_pages ?? max(1, ceil($total_items / $limit));
        $count_current = count($items ?? []);
        $from = ($total_items > 0) ? (($page - 1) * $limit + 1) : 0;
        $to = ($total_items > 0) ? min($from + $count_current - 1, $total_items) : 0;

        $query_args = [];
        if (!empty($search)) $query_args['search'] = $search;
        if (!empty($categoria)) $query_args['categoria'] = $categoria;
        if (!empty($filter)) $query_args['filter'] = $filter;
        ?>

        <div class="mt-6 pt-4 border-t border-border/50 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-text-muted">
            <div>
                <?php if ($total_items > 0): 
                    $cat_display = (!empty($categoria) && isset($categorias_catalogo[$categoria])) ? $categorias_catalogo[$categoria] : $categoria;
                ?>
                    Mostrando <strong class="text-text-main font-semibold"><?php echo $from; ?>–<?php echo $to; ?></strong> de <strong class="text-text-main font-semibold"><?php echo $total_items; ?></strong> productos<?php if (!empty($categoria)): ?> (Categoría: <strong class="text-text-main"><?php echo htmlspecialchars($cat_display); ?></strong>)<?php endif; ?>
                    <span class="mx-1.5 opacity-40">•</span>
                    Página <strong class="text-primary font-semibold"><?php echo $page; ?></strong> de <strong class="text-text-main font-semibold"><?php echo $total_pages; ?></strong>
                <?php else: ?>
                    <span>No hay productos en inventario</span>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="flex items-center gap-1.5 flex-wrap justify-center">
                    <?php if ($page > 1): 
                        $prev_args = $query_args;
                        $prev_args['page'] = $page - 1;
                    ?>
                        <a href="?<?php echo http_build_query($prev_args); ?>" class="px-2.5 py-1.5 border border-border rounded-lg bg-surface hover:bg-surface-hover text-text-main transition-colors flex items-center gap-1" title="Página anterior">
                            <span class="material-symbols-outlined text-[16px]">chevron_left</span>
                            <span class="hidden md:inline">Anterior</span>
                        </a>
                    <?php else: ?>
                        <span class="px-2.5 py-1.5 border border-border/40 rounded-lg bg-surface/30 text-text-muted/40 cursor-not-allowed flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">chevron_left</span>
                            <span class="hidden md:inline">Anterior</span>
                        </span>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): 
                        $num_args = $query_args;
                        $num_args['page'] = $i;
                        $isCurrent = ($i === $page);
                    ?>
                        <a href="?<?php echo http_build_query($num_args); ?>" class="min-w-[32px] h-8 flex items-center justify-center px-2.5 rounded-lg border text-xs font-semibold transition-all <?php echo $isCurrent ? 'bg-primary text-background border-primary shadow-sm shadow-primary/20' : 'bg-surface border-border text-text-muted hover:text-text-main hover:bg-surface-hover'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): 
                        $next_args = $query_args;
                        $next_args['page'] = $page + 1;
                    ?>
                        <a href="?<?php echo http_build_query($next_args); ?>" class="px-2.5 py-1.5 border border-border rounded-lg bg-surface hover:bg-surface-hover text-text-main transition-colors flex items-center gap-1" title="Página siguiente">
                            <span class="hidden md:inline">Siguiente</span>
                            <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                        </a>
                    <?php else: ?>
                        <span class="px-2.5 py-1.5 border border-border/40 rounded-lg bg-surface/30 text-text-muted/40 cursor-not-allowed flex items-center gap-1">
                            <span class="hidden md:inline">Siguiente</span>
                            <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<script src="<?php echo BASE_URL; ?>/modules/inventory/inventory.js?v=<?php echo time(); ?>"></script>

<?php Layout::renderFooter(); ?>
