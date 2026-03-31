<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}
require_once __DIR__ . '/../../classes/Layout.php';

// Obtener inventario con nombres de productos
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

$where_parts = [];
if ($search) {
    $where_parts[] = "(p.nombre LIKE '%$search%' OR d.marca LIKE '%$search%' OR d.modelo LIKE '%$search%')";
}
if ($filter === 'low_stock') {
    $where_parts[] = "i.cantidad <= 5";
}

$where_clause = count($where_parts) > 0 ? " WHERE " . implode(" AND ", $where_parts) : "";

$sql = "SELECT p.id_producto, p.nombre, d.marca, d.modelo, i.cantidad, p.precio, p.is_active
        FROM inventario i
        JOIN producto p ON i.id_producto = p.id_producto
        JOIN producto_detalle d ON p.id_producto = d.id_producto
        $where_clause
        ORDER BY p.nombre ASC";

$result = $conn->query($sql);

$items = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

Layout::renderHead('Inventario - Nokia 1100');

if ($_SESSION['role'] === 'admin') {
    Layout::renderAdminSidebar('inventario');
} else {
    Layout::renderEmployeeSidebar('inventario');
}
?>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8">
        <div class="dashboard-header border-b border-border/50 pb-6 mb-6">
            <div>
                <h2 class="text-3xl font-display font-medium text-text-main">Inventario</h2>
                <p class="text-text-muted mt-1 text-sm">Control de stock, repuestos y equipos tecnológicos</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex bg-surface border border-border rounded-lg p-1">
                    <button onclick="toggleView('table')" id="btn-view-table" class="px-3 py-1.5 rounded text-sm font-medium transition-colors bg-primary/20 text-primary">
                        <span class="material-symbols-outlined text-[18px] align-middle">table_rows</span>
                    </button>
                    <button onclick="toggleView('grid')" id="btn-view-grid" class="px-3 py-1.5 rounded text-sm font-medium transition-colors text-text-muted hover:text-text-main">
                        <span class="material-symbols-outlined text-[18px] align-middle">grid_view</span>
                    </button>
                </div>
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
            <table class="w-full text-left">
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
                                        <a href="change_status.php?id=<?php echo $row['id_producto']; ?>" class="inline-block px-2.5 py-1 rounded text-[10px] uppercase font-bold tracking-widest transition-colors <?php echo $row['is_active'] ? 'bg-green-500/10 text-green-500 border border-green-500/20 hover:bg-green-500/20' : 'bg-red-500/10 text-red-500 border border-red-500/20 hover:bg-red-500/20'; ?>">
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
                                        <a href="delete_product.php?id=<?php echo $row['id_producto']; ?>" class="w-8 h-8 rounded-lg flex items-center justify-center bg-red-500/10 text-red-500 border border-red-500/20 hover:bg-red-500 hover:text-white transition-colors" data-confirm="¿Estás seguro de que deseas eliminar este producto?" data-confirm-title="Eliminar Producto" title="Eliminar">
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
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

        <!-- GRID VIEW -->
        <div id="view-grid" class="hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 hover-3d-perspective">
            <?php if (count($items) > 0): ?>
                <?php foreach($items as $row): 
                    // Determinar icono
                    $lowerName = strtolower($row['nombre']);
                    $icon = 'smartphone';
                    if (str_contains($lowerName, 'bater')) $icon = 'battery_5_bar';
                    else if (str_contains($lowerName, 'pantalla') || str_contains($lowerName, 'display')) $icon = 'stay_current_portrait';
                    else if (str_contains($lowerName, 'funda') || str_contains($lowerName, 'vidrio')) $icon = 'shield';
                    else if (str_contains($lowerName, 'cable') || str_contains($lowerName, 'cargador')) $icon = 'cable';
                ?>
                <div class="hover-3d-target bg-surface/40 border border-border/80 rounded-2xl p-5 relative group overflow-hidden flex flex-col backdrop-blur-md transition-colors hover:bg-surface">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-primary/5 rounded-full blur-2xl group-hover:bg-primary/20 transition-all pointer-events-none"></div>
                    
                    <div class="flex justify-between items-start mb-4 relative z-10">
                        <div class="w-12 h-12 rounded-xl bg-surface border border-border flex items-center justify-center text-text-main shadow-sm">
                            <span class="material-symbols-outlined text-[24px]"><?php echo $icon; ?></span>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <?php if ($row['cantidad'] <= 5): ?>
                                <span class="bg-red-500/10 text-red-500 border border-red-500/20 px-2 py-0.5 rounded text-[9px] uppercase font-bold tracking-widest pulse-badge">Bajo Stock</span>
                            <?php else: ?>
                                <span class="bg-green-500/10 text-green-500 border border-green-500/20 px-2 py-0.5 rounded text-[9px] uppercase font-bold tracking-widest">En Stock</span>
                            <?php endif; ?>
                            <?php if (!$row['is_active']): ?>
                                <span class="bg-surface border border-border text-text-muted px-2 py-0.5 rounded text-[9px] uppercase font-bold">Inactivo</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-5 relative z-10 flex-1">
                        <h3 class="text-base font-display font-medium text-text-main line-clamp-2 leading-tight mb-1"><?php echo htmlspecialchars($row['nombre']); ?></h3>
                        <p class="text-xs text-text-muted"><?php echo htmlspecialchars($row['marca'] . ' ' . $row['modelo']); ?></p>
                    </div>

                    <div class="flex justify-between items-center relative z-10 pt-4 border-t border-border/50 mt-auto">
                        <div>
                            <p class="text-[10px] text-text-muted uppercase font-bold tracking-wider mb-0.5">Stock</p>
                            <p class="text-sm font-display font-medium <?php echo $row['cantidad']<=5?'text-red-400':'text-text-main'; ?>"><?php echo $row['cantidad']; ?> u.</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] text-text-muted uppercase font-bold tracking-wider mb-0.5">Precio</p>
                            <p class="text-lg font-display font-semibold text-primary">$<?php echo number_format($row['precio'], 2); ?></p>
                        </div>
                    </div>

                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <div class="absolute inset-x-0 bottom-0 top-0 bg-background/90 backdrop-blur-sm opacity-0 group-hover:opacity-100 flex items-center justify-center gap-3 transition-opacity duration-200 z-20">
                        <a href="edit_stock.php?id=<?php echo $row['id_producto']; ?>" class="w-10 h-10 rounded-full bg-primary text-background hover:scale-110 flex items-center justify-center transition-transform shadow-lg" title="Editar">
                            <span class="material-symbols-outlined text-[18px]">edit</span>
                        </a>
                        <a href="delete_product.php?id=<?php echo $row['id_producto']; ?>" class="w-10 h-10 rounded-full bg-surface border border-border text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-all shadow-lg" data-confirm="¿Estás seguro de que deseas eliminar este producto?" data-confirm-title="Eliminar Producto" title="Eliminar">
                            <span class="material-symbols-outlined text-[18px]">delete</span>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-12 p-12 text-center text-text-muted bg-surface/30 rounded-2xl border border-border">No hay productos en inventario</div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    function toggleView(viewType) {
        const table = document.getElementById('view-table');
        const grid = document.getElementById('view-grid');
        const btnTable = document.getElementById('btn-view-table');
        const btnGrid = document.getElementById('btn-view-grid');

        if (viewType === 'grid') {
            table.classList.add('hidden');
            grid.classList.remove('hidden');
            btnGrid.classList.replace('bg-transparent', 'bg-primary/20');
            btnGrid.classList.replace('text-text-muted', 'text-primary');
            btnTable.classList.replace('bg-primary/20', 'bg-transparent');
            btnTable.classList.replace('text-primary', 'text-text-muted');
        } else {
            grid.classList.add('hidden');
            table.classList.remove('hidden');
            btnTable.classList.replace('bg-transparent', 'bg-primary/20');
            btnTable.classList.replace('text-text-muted', 'text-primary');
            btnGrid.classList.replace('bg-primary/20', 'bg-transparent');
            btnGrid.classList.replace('text-primary', 'text-text-muted');
        }
    }
</script>

<?php Layout::renderFooter(); ?>
