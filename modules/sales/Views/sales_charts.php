<?php
// modules/sales/Views/sales_charts.php

/**
 * Variables inyectadas por SalesController::sales_charts()
 * @var array $dates
 * @var array $totals
 * @var array $methods
 * @var array $method_amounts
 * @var array $top_names
 * @var array $top_quantities
 */
$dates = $dates ?? [];
$totals = $totals ?? [];
$methods = $methods ?? [];
$method_amounts = $method_amounts ?? [];
$top_names = $top_names ?? [];
$top_quantities = $top_quantities ?? [];

Layout::renderHead('Estadísticas de Ventas - NOKIA1100');
Layout::renderAdminSidebar('graficos');
?>
<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8 border border-border/50">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8 pb-4 border-b border-border/50">
            <div>
                <h2 class="text-2xl font-display font-medium text-text-main">Estadísticas de Ventas</h2>
                <p class="text-text-muted text-sm mt-1">Reporte financiero y métricas de desempeño (Últimos 30 días)</p>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap">
                <button type="button" id="btnExportXls" class="btn-export" title="Exportar a Excel">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M4 21h16"/></svg>Excel
                </button>
                <button type="button" id="btnExportPdf" class="btn-export" title="Exportar a PDF">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M4 21h16"/></svg>PDF
                </button>
                <a href="<?php echo BASE_URL; ?>/modules/admin/index.php" class="px-4 py-2 rounded-xl border border-border bg-surface hover:bg-surface-hover text-sm font-medium transition-colors flex items-center gap-2 ml-2">
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span> Volver al Dashboard
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-8" id="chartsContainer">
            <div class="lg:col-span-2 bg-surface/30 p-6 rounded-2xl border border-border/30 h-[50vh]">
                <h3 class="text-sm font-semibold tracking-wide text-text-muted uppercase mb-4">Ventas por Día</h3>
                <canvas id="salesChart"></canvas>
            </div>
            <div class="bg-surface/30 p-6 rounded-2xl border border-border/30 h-[50vh] flex flex-col items-center">
                <h3 class="text-sm font-semibold tracking-wide text-text-muted uppercase mb-4 w-full text-left">Métodos de Pago</h3>
                <div class="w-full h-full flex items-center justify-center pb-8">
                    <canvas id="methodsChart"></canvas>
                </div>
            </div>
            <div class="lg:col-span-3 bg-surface/30 p-6 rounded-2xl border border-border/30 h-[60vh]">
                <h3 class="text-sm font-semibold tracking-wide text-text-muted uppercase mb-4">Top 5 Productos Más Vendidos</h3>
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>
    </div>
</main>
<?php Layout::renderFooter(); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    window.salesData = {
        dates: <?php echo json_encode($dates); ?>,
        totals: <?php echo json_encode($totals); ?>,
        methods: <?php echo json_encode($methods); ?>,
        methodAmounts: <?php echo json_encode($method_amounts); ?>,
        topNames: <?php echo json_encode($top_names); ?>,
        topQuantities: <?php echo json_encode($top_quantities); ?>
    };
</script>
<script src="<?php echo BASE_URL; ?>/modules/sales/sales_charts.js?v=<?php echo time(); ?>"></script>
