<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'empleado') {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}
require_once __DIR__ . '/../../classes/Layout.php';

// 1. Daily Metrics
$hoy = date('Y-m-d');
$resVentasHoy = $conn->query("SELECT SUM(total) as total_hoy, COUNT(*) as transacciones FROM venta WHERE DATE(fecha) = '$hoy'");
$hoyData = $resVentasHoy ? $resVentasHoy->fetch_assoc() : ['total_hoy' => 0, 'transacciones' => 0];
$totalHoy = $hoyData['total_hoy'] ? $hoyData['total_hoy'] : 0;
$transHoy = $hoyData['transacciones'] ? $hoyData['transacciones'] : 0;

// 2. Recent Transactions (last 5)
$ventasRecientes = $conn->query("SELECT id_venta, fecha, total, metodo_de_pago FROM venta ORDER BY fecha DESC LIMIT 5");

// 3. Products for Quick Add / Views
$productos = $conn->query("SELECT id_producto, nombre, precio FROM producto WHERE is_active = 1 LIMIT 4");

Layout::renderHead('NOKIA1100 | Centro de Operaciones');
Layout::renderEmployeeSidebar('dashboard');
?>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    
    <header class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10">
        <div>
            <h1 class="text-3xl font-display font-medium text-text-main tracking-tight">Centro de <span class="text-primary">Operaciones</span></h1>
            <p class="text-text-muted text-xs uppercase tracking-widest mt-1 font-semibold">Terminal de Empleado</p>
        </div>
        <a href="<?php echo BASE_URL; ?>/modules/sales/new_sale.php" class="bg-text-main text-background font-medium px-6 py-2.5 rounded-lg flex items-center gap-2 hover:bg-text-muted transition-colors">
            <span class="material-symbols-outlined">add</span>
            Nueva Venta Rápida
        </a>
    </header>

    <!-- Daily Performance -->
    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
        <div class="glass-card p-6 rounded-2xl flex flex-col justify-between group hover:border-border/80 transition-all">
            <div class="flex justify-between items-start mb-4">
                <span class="text-[10px] font-semibold uppercase tracking-widest text-text-muted">Ingresos del Día</span>
                <span class="material-symbols-outlined text-primary text-xl">trending_up</span>
            </div>
            <div class="text-3xl font-display font-medium text-text-main">$<?php echo number_format($totalHoy, 2); ?></div>
        </div>
        <div class="glass-card p-6 rounded-2xl flex flex-col justify-between group hover:border-border/80 transition-all">
            <div class="flex justify-between items-start mb-4">
                <span class="text-[10px] font-semibold uppercase tracking-widest text-text-muted">Ventas de Hoy</span>
                <span class="material-symbols-outlined text-secondary text-xl">receipt_long</span>
            </div>
            <div class="text-3xl font-display font-medium text-text-main"><?php echo number_format($transHoy); ?></div>
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Products -->
        <div class="lg:col-span-8 space-y-6">
            <div class="flex items-center justify-between">
                <h3 class="font-display font-medium text-lg text-text-main">Catálogo Destacado</h3>
                <a href="<?php echo BASE_URL; ?>/modules/inventory/inventory.php" class="text-[10px] font-semibold uppercase tracking-widest text-primary hover:underline">Ver todo</a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <?php
if ($productos && $productos->num_rows > 0) {
    while ($p = $productos->fetch_assoc()) {
        echo "<div class='glass-card p-4 rounded-xl hover:bg-surface-hover/50 transition-colors'>
                            <div class='aspect-video rounded-lg bg-surface flex items-center justify-center mb-4 text-text-muted'>
                                <span class='material-symbols-outlined text-4xl opacity-50'>smartphone</span>
                            </div>
                            <div class='text-sm font-medium text-text-main truncate'>{$p['nombre']}</div>
                            <div class='text-primary font-medium text-sm mt-1'>$" . number_format($p['precio'], 2) . "</div>
                        </div>";
    }
}
else {
    echo "<p class='text-sm text-text-muted'>No hay productos registrados</p>";
}
?>
            </div>
        </div>

        <!-- Recent flow -->
        <div class="lg:col-span-4 space-y-6">
            <h3 class="font-display font-medium text-lg text-text-main">Últimas Transacciones</h3>
            <div class="glass-card rounded-2xl overflow-hidden p-0">
                <div class="divide-y divide-border/50">
                    <?php
if ($ventasRecientes && $ventasRecientes->num_rows > 0) {
    while ($v = $ventasRecientes->fetch_assoc()) {
        $monto = number_format($v['total'], 2);
        $metodo = $v['metodo_de_pago'] ? $v['metodo_de_pago'] : 'N/A';
        echo "<div class='p-4 hover:bg-surface/30 transition-colors flex justify-between items-center'>
                                <div>
                                    <div class='text-sm font-medium text-primary'>#TX-{$v['id_venta']}</div>
                                    <div class='text-[10px] font-semibold uppercase tracking-wider text-text-muted mt-1'>{$metodo}</div>
                                </div>
                                <div class='text-sm font-medium text-text-main text-right'>$$monto</div>
                            </div>";
    }
}
else {
    echo "<div class='p-4 text-sm text-text-muted'>No hay transacciones</div>";
}
?>
                </div>
            </div>
        </div>
    </div>

</main>
<?php Layout::renderFooter(); ?>