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

// 6. Chart Data
$chartDataQuery = $conn->query("
    SELECT DATE(fecha) as dia, SUM(total) as total_dia 
    FROM venta 
    WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(fecha)
    ORDER BY DATE(fecha) ASC
");
$labels = [];
$data = [];
if ($chartDataQuery && $chartDataQuery->num_rows > 0) {
    while ($row = $chartDataQuery->fetch_assoc()) {
        $labels[] = date('d M', strtotime($row['dia']));
        $data[] = $row['total_dia'];
    }
}

Layout::renderHead('NOKIA1100 | Admin Panel');
Layout::renderAdminSidebar('dashboard');
?>

<main class="md:ml-64 p-8 min-h-screen">
    
    <header class="flex justify-between items-end mb-10">
        <div>
            <h1 class="text-3xl font-display font-medium text-text-main tracking-tight">Resumen Operativo</h1>
        </div>
        <div class="flex items-center gap-3">
            <div class="px-3 py-1.5 border border-border rounded-full flex items-center gap-2 bg-surface">
                <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
            </div>
        </div>
    </header>

    <!-- Metrics -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        
        <div class="glass-card p-6 rounded-2xl flex flex-col justify-between group hover:border-border/80 transition-all">
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

        <div class="glass-card p-6 rounded-2xl flex flex-col justify-between group hover:border-border/80 transition-all">
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

        <div class="glass-card p-6 rounded-2xl flex flex-col justify-between cursor-pointer hover:bg-surface-hover/50 transition-all" onclick="window.location.href='inventory.php';">
            <div class="flex justify-between items-start mb-4">
                <div class="p-2.5 bg-red-500/10 text-red-500 rounded-xl">
                    <span class="material-symbols-outlined text-xl">warning</span>
                </div>
                <?php if ($bajoStock > 0): ?>
                    <span class="text-[10px] uppercase font-semibold text-red-400 tracking-widest bg-red-500/10 px-2 py-1 rounded">Atención</span>
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
        
        <div class="col-span-12 lg:col-span-8 glass-card rounded-2xl overflow-hidden p-0">
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

        <div class="col-span-12 lg:col-span-4 flex flex-col gap-6">
            <div class="glass-card rounded-2xl p-6 flex-1 flex flex-col">
                <h3 class="font-display text-lg font-medium mb-6">Análisis de Ventas</h3>
                <div class="flex-1 w-full min-h-[250px] relative">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>

    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Ingresos ($)',
                    data: <?php echo json_encode($data); ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#0f172a',
                    pointBorderColor: '#10b981',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(148, 163, 184, 0.1)' },
                        ticks: { color: '#64748b' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b' }
                    }
                }
            }
        });
    });
    </script>

</main>
<?php Layout::renderFooter(); ?>