<?php
// modules/sales/Views/sales_charts.php

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
            <div class="flex items-center gap-2">
                <button id="btnExportPdf" class="px-4 py-2 rounded-xl border border-red-500/30 bg-red-500/10 hover:bg-red-500/20 text-red-500 text-sm font-medium transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span> PDF
                </button>
                <button id="btnExportXls" class="px-4 py-2 rounded-xl border border-green-500/30 bg-green-500/10 hover:bg-green-500/20 text-green-500 text-sm font-medium transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">table_chart</span> Excel
                </button>
                <a href="<?php echo BASE_URL; ?>/modules/admin/dashboard.php" class="px-4 py-2 rounded-xl border border-border bg-surface hover:bg-surface-hover text-sm font-medium transition-colors flex items-center gap-2 ml-2">
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
