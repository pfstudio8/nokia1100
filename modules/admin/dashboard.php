<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}
require_once __DIR__ . '/../../classes/Layout.php';

// 1. Total Sales
$resVentas = $conn->query("SELECT SUM(total) as total_ventas FROM venta");
$totalVentas = ($resVentas && $resVentas->num_rows > 0) ? $resVentas->fetch_assoc()['total_ventas'] : 0;
if (!$totalVentas)
    $totalVentas = 0;

// 2. Transacciones 
$resTrans = $conn->query("SELECT COUNT(*) as total_transacciones FROM venta");
$totalTrans = ($resTrans && $resTrans->num_rows > 0) ? $resTrans->fetch_assoc()['total_transacciones'] : 0;

// 3. Active Users
$resUsuarios = $conn->query("SELECT COUNT(*) as total_usuarios FROM usuario");
$totalUsuarios = ($resUsuarios && $resUsuarios->num_rows > 0) ? $resUsuarios->fetch_assoc()['total_usuarios'] : 0;

// 4. Low Stock Alerts (<= 5)
$resStock = $conn->query("SELECT COUNT(*) as bajo_stock FROM inventario i INNER JOIN producto p ON i.id_producto = p.id_producto WHERE i.cantidad <= 5 AND p.is_active = 1");
$bajoStock = ($resStock && $resStock->num_rows > 0) ? $resStock->fetch_assoc()['bajo_stock'] : 0;

// 5. Recent Sales
$ventasRecientes = $conn->query("
    SELECT id_venta, fecha, total, metodo_de_pago
    FROM venta 
    ORDER BY fecha DESC LIMIT 5
");



Layout::renderHead('NOKIA1100 | Admin Panel');
Layout::renderAdminSidebar('dashboard');
?>

<main class="md:ml-64 p-8 min-h-screen">
    
    <header class="flex justify-between items-end mb-8">
        <div>
            <h1 class="text-3xl font-display font-medium text-text-main tracking-tight">Resumen Operativo</h1>
        </div>
        <div class="flex items-center gap-3">
            <div class="px-3 py-1.5 border border-border rounded-full flex items-center gap-2 bg-surface">
                <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
            </div>
        </div>
    </header>

    <!-- Visual Banner -->
    <section class="mb-10 w-full overflow-hidden rounded-2xl glass-card relative h-48 sm:h-56 flex items-center shadow-lg border-border">
        <div class="absolute inset-0 z-0 bg-surface">
            <!-- Background Image with Blend -->
            <img src="<?php echo BASE_URL; ?>/assets/img/nokia_store_banner.png" alt="Nokia Premium Store" class="w-full h-full object-cover opacity-30 mix-blend-lighten filter brightness-110 saturate-150 transition-all duration-700 hover:scale-105 hover:opacity-40">
            <!-- Gradient Overlay for Contrast -->
            <div class="absolute inset-0 bg-gradient-to-r from-background via-background/60 to-transparent"></div>
        </div>
        
        <div class="relative z-10 w-full p-8 md:p-10 flex flex-col justify-center h-full max-w-3xl">
            <span class="text-[10px] font-bold text-primary tracking-widest uppercase mb-2">Panel Global de Tienda</span>
            <h2 class="text-3xl md:text-4xl font-display font-bold text-text-main mb-3 drop-shadow-md">Bienvenido a la Central Nokia</h2>
            <p class="text-sm text-text-muted font-medium max-w-md leading-relaxed">Supervisa todas las operaciones, controla el inventario y analiza el rendimiento general del sistema corporativo.</p>
        </div>
    </section>

    <!-- Metrics -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        
        <div class="glass-card hover-3d-target p-6 rounded-2xl flex flex-col justify-between group hover:border-border/80 transition-all">
            <div class="flex justify-between items-start mb-4">
                <div class="p-2.5 bg-primary/10 text-primary rounded-xl">
                    <span class="material-symbols-outlined text-xl">payments</span>
                </div>
                <span class="text-[10px] uppercase font-semibold text-primary/80 tracking-widest bg-primary/5 px-2 py-1 rounded">Ventas Totales</span>
            </div>
            <div>
                <p class="text-xs text-text-muted mb-1 font-medium">Ingresos Registrados</p>
                <h3 class="text-3xl font-display font-medium text-text-main">$<?php echo number_format($totalVentas, 2); ?></h3>
            </div>
        </div>

        <div class="glass-card hover-3d-target p-6 rounded-2xl flex flex-col justify-between group hover:border-border/80 transition-all">
            <div class="flex justify-between items-start mb-4">
                <div class="p-2.5 bg-secondary/10 text-secondary rounded-xl">
                    <span class="material-symbols-outlined text-xl">group</span>
                </div>
                <span class="text-[10px] uppercase font-semibold text-secondary/80 tracking-widest bg-secondary/5 px-2 py-1 rounded">Cuentas</span>
            </div>
            <div>
                <p class="text-xs text-text-muted mb-1 font-medium">Usuarios Activos</p>
                <h3 class="text-3xl font-display font-medium text-text-main"><?php echo number_format($totalUsuarios); ?></h3>
            </div>
        </div>

        <div class="glass-card hover-3d-target p-6 rounded-2xl flex flex-col justify-between cursor-pointer hover:bg-surface-hover/50 transition-all" onclick="window.location.href='<?php echo BASE_URL; ?>/modules/inventory/inventory.php?filter=low_stock';">
            <div class="flex justify-between items-start mb-4">
                <div class="p-2.5 bg-red-500/10 text-red-500 rounded-xl">
                    <span class="material-symbols-outlined text-xl">warning</span>
                </div>
                <?php if ($bajoStock > 0): ?>
                    <span class="text-[10px] uppercase font-semibold text-red-400 tracking-widest bg-red-500/10 px-2 py-1 rounded pulse-badge">Atención</span>
                <?php
endif; ?>
            </div>
            <div>
                <p class="text-xs text-text-muted mb-1 font-medium">Alertas de Stock</p>
                <h3 class="text-3xl font-display font-medium <?php echo $bajoStock > 0 ? 'text-red-400' : 'text-text-main'; ?>">
                    <?php echo number_format($bajoStock); ?>
                </h3>
            </div>
        </div>
        
    </section>

    <!-- Tables -->
    <section class="grid grid-cols-12 gap-6">
        
        <div class="col-span-12 glass-card rounded-2xl overflow-hidden p-0">
            <div class="p-6 border-b border-border flex justify-between items-center">
                <h3 class="font-display text-lg font-medium">Ventas Recientes</h3>
                <a href="<?php echo BASE_URL; ?>/modules/sales/sales.php" class="text-xs font-medium text-primary hover:underline">Ver todas</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-surface/50 border-b border-border">
                            <th class="px-6 py-4">ID Venta</th>
                            <th class="px-6 py-4">Fecha</th>
                            <th class="px-6 py-4">Método</th>
                            <th class="px-6 py-4 text-right">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        <?php
if ($ventasRecientes && $ventasRecientes->num_rows > 0) {
    while ($v = $ventasRecientes->fetch_assoc()) {
        $date = date("d M Y H:i", strtotime($v['fecha']));
        $metodo = $v['metodo_de_pago'] ? $v['metodo_de_pago'] : 'N/A';
        echo "<tr class='hover:bg-surface/30 transition-colors'>
                                    <td class='px-6 py-4 text-sm font-medium text-primary'>#TX-{$v['id_venta']}</td>
                                    <td class='px-6 py-4 text-sm text-text-muted'>{$date}</td>
                                    <td class='px-6 py-4 text-sm'>
                                        <span class='px-2 py-1 rounded border border-border text-[10px] font-medium text-text-muted uppercase tracking-wider'>{$metodo}</span>
                                    </td>
                                    <td class='px-6 py-4 text-sm font-medium text-text-main text-right'>$" . number_format($v['total'], 2) . "</td>
                                </tr>";
    }
}
else {
    echo "<tr><td colspan='4' class='px-6 py-8 text-center text-sm text-text-muted'>No hay transacciones registradas</td></tr>";
}
?>
                    </tbody>
                </table>
            </div>
        </div>

    </section>

</main>
<?php Layout::renderFooter(); ?>