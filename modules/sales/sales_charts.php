<?php
session_start();
require_once __DIR__ . "/../../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

// Fetch sales data grouped by date
$sql = "SELECT DATE(fecha) as sale_date, SUM(total) as daily_total 
        FROM venta 
        GROUP BY DATE(fecha) 
        ORDER BY sale_date ASC 
        LIMIT 30"; // Last 30 days

$result = $conn->query($sql);

$dates = [];
$totals = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $dates[] = $row['sale_date'];
        $totals[] = $row['daily_total'];
    }
}
?>
<?php
require_once __DIR__ . '/../../classes/Layout.php';
Layout::renderHead('Estadísticas de Ventas - NOKIA1100');
Layout::renderAdminSidebar('ventas');
?>
<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8 border border-border/50">
        <div class="flex justify-between items-center mb-8 pb-4 border-b border-border/50">
            <div>
                <h2 class="text-2xl font-display font-medium text-text-main">Estadísticas de Ventas</h2>
                <p class="text-text-muted text-sm mt-1">Reporte financiero y métricas de desempeño (Últimos 30 días)</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/modules/admin/dashboard.php" class="px-4 py-2 rounded-xl border border-border bg-surface hover:bg-surface-hover text-sm font-medium transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span> Volver al Dashboard
            </a>
        </div>

        <div class="h-[60vh] mt-8 bg-surface/30 p-6 rounded-2xl border border-border/30">
            <canvas id="salesChart"></canvas>
        </div>
    </div>
</main>
<?php Layout::renderFooter(); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    const computedStyle = getComputedStyle(document.body);
    const textMain = computedStyle.getPropertyValue('--tw-text-opacity') ? `rgba(250, 250, 250, 1)` : '#FAFAFA'; // approximate for dark mode
    const primary = '#4FE0E5';
    const border = 'rgba(255, 255, 255, 0.1)';

    const salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'Ventas Totales ($)',
                data: <?php echo json_encode($totals); ?>,
                backgroundColor: 'rgba(79, 224, 229, 0.2)', // primary with opacity
                borderColor: primary,
                borderWidth: 2,
                pointBackgroundColor: primary,
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: primary,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { color: textMain, font: { family: 'Inter', size: 13 } }
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 17, 19, 0.9)',
                    titleColor: textMain,
                    bodyColor: primary,
                    borderColor: border,
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return 'Total: $' + context.raw.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: border },
                    ticks: { color: '#A1A1AA', font: { family: 'Inter' } }
                },
                x: {
                    grid: { color: border },
                    ticks: { color: '#A1A1AA', font: { family: 'Inter' } }
                }
            }
        }
    });
</script>
