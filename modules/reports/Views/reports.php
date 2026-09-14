<?php
// modules/reports/Views/reports.php

Layout::renderHead('Centro de Reportes - NOKIA1100');
Layout::renderAdminSidebar('reportes');
?>
<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8 border border-border/50 p-6 md:p-8">
        <header class="flex justify-between items-end mb-8">
            <div>
                <h2 class="text-3xl font-display font-bold text-text-main">Centro de Reportes</h2>
                <p class="text-text-muted text-sm mt-1">Análisis Financiero, Inventario, Ventas y Taller</p>
            </div>
        </header>

        <!-- TABS Pestañas de Reportes -->
        <div class="border-b border-border mb-6">
            <nav class="flex gap-4" aria-label="Tabs">
                <button class="tab-btn px-4 py-2 border-b-2 font-medium text-sm text-primary border-primary transition-colors" data-tab="tab-finanzas">Financiero</button>
                <button class="tab-btn px-4 py-2 border-b-2 font-medium text-sm text-text-muted border-transparent hover:text-text-main hover:border-border transition-colors" data-tab="tab-inventario">Inventario</button>
                <button class="tab-btn px-4 py-2 border-b-2 font-medium text-sm text-text-muted border-transparent hover:text-text-main hover:border-border transition-colors" data-tab="tab-ventas">Ventas</button>
                <button class="tab-btn px-4 py-2 border-b-2 font-medium text-sm text-text-muted border-transparent hover:text-text-main hover:border-border transition-colors" data-tab="tab-taller">Taller</button>
            </nav>
        </div>

        <!-- CONTENIDO: FINANCIERO -->
        <div id="tab-finanzas" class="tab-content block">
            <div class="bg-surface/30 p-6 rounded-2xl border border-border/30 h-[60vh]">
                <h3 class="text-sm font-semibold tracking-wide text-text-muted uppercase mb-4">Ingresos vs Compras (Últimos 6 Meses)</h3>
                <div class="w-full h-full pb-8">
                    <canvas id="financialChart"></canvas>
                </div>
            </div>
        </div>

        <!-- CONTENIDO: INVENTARIO -->
        <div id="tab-inventario" class="tab-content hidden">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <div class="bg-surface/30 p-6 rounded-2xl border border-border/30 h-[50vh] flex flex-col items-center">
                    <h3 class="text-sm font-semibold tracking-wide text-text-muted uppercase mb-4 w-full text-left">Salud del Stock</h3>
                    <div class="w-full h-full flex items-center justify-center pb-8">
                        <canvas id="stockStateChart"></canvas>
                    </div>
                </div>
                <div class="lg:col-span-2 bg-surface/30 p-6 rounded-2xl border border-border/30 h-[50vh]">
                    <h3 class="text-sm font-semibold tracking-wide text-text-muted uppercase mb-4">Top 5 Productos por Valor de Inventario</h3>
                    <div class="w-full h-full pb-8">
                        <canvas id="topValueChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Tabla HTML: Stock Crítico -->
            <div class="bg-surface/30 p-6 rounded-2xl border border-border/30">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-sm font-semibold tracking-wide text-red-400 uppercase">Reporte de Stock Crítico</h3>
                    <div class="flex gap-2 items-center">
                        <input type="text" id="search-critical-stock" placeholder="Buscar producto..." class="bg-background border border-border text-sm rounded-lg px-3 py-1.5 focus:border-primary outline-none">
                        <button onclick="exportTableToExcel('critical-stock-table', 'StockCritico', this)" class="px-3 py-1.5 rounded-lg border border-green-500/30 bg-green-500/10 text-xs text-green-400 hover:bg-green-500/20 flex items-center gap-1.5 transition-colors">
                            <span class="material-symbols-outlined text-[16px]">download</span> Excel
                        </button>
                        <button onclick="exportTableToPDF('critical-stock-table', 'Reporte de Stock Crítico', 'stock_critico', this)" class="px-3 py-1.5 rounded-lg border border-red-500/30 bg-red-500/10 text-xs text-red-400 hover:bg-red-500/20 flex items-center gap-1.5 transition-colors">
                            <span class="material-symbols-outlined text-[16px]">download</span> PDF
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table id="critical-stock-table" class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-border text-text-muted text-sm">
                                <th class="pb-3 pr-4 font-medium">Producto</th>
                                <th class="pb-3 pr-4 font-medium">Marca</th>
                                <th class="pb-3 pr-4 font-medium">Modelo</th>
                                <th class="pb-3 pr-4 font-medium text-right">Stock Actual</th>
                                <th class="pb-3 font-medium text-right">Stock Mínimo</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php if (!empty($critical_stock)): ?>
                                <?php foreach($critical_stock as $item): ?>
                                    <tr class="border-b border-border/50 hover:bg-surface/50 transition-colors">
                                        <td class="py-3 pr-4 font-medium text-text-main"><?php echo htmlspecialchars($item['nombre']); ?></td>
                                        <td class="py-3 pr-4 text-text-muted"><?php echo htmlspecialchars($item['marca']); ?></td>
                                        <td class="py-3 pr-4 text-text-muted"><?php echo htmlspecialchars($item['modelo']); ?></td>
                                        <td class="py-3 pr-4 text-right">
                                            <span class="text-red-400 font-bold bg-red-500/10 px-2 py-1 rounded"><?php echo $item['cantidad']; ?></span>
                                        </td>
                                        <td class="py-3 text-right text-text-muted"><?php echo $item['stock_minimo']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-text-muted">No hay productos en estado crítico.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CONTENIDO: VENTAS -->
        <div id="tab-ventas" class="tab-content hidden">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-3 bg-surface/30 p-6 rounded-2xl border border-border/30 h-[40vh]">
                    <h3 class="text-sm font-semibold tracking-wide text-text-muted uppercase mb-4">Top 5 Productos Más Vendidos</h3>
                    <div class="w-full h-full pb-8">
                        <canvas id="topSalesChart"></canvas>
                    </div>
                </div>
                <div class="lg:col-span-2 bg-surface/30 p-6 rounded-2xl border border-border/30 h-[50vh]">
                    <h3 class="text-sm font-semibold tracking-wide text-text-muted uppercase mb-4">Ventas por Día (Últimos 30 días)</h3>
                    <div class="w-full h-full pb-8">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
                <div class="bg-surface/30 p-6 rounded-2xl border border-border/30 h-[50vh] flex flex-col items-center">
                    <h3 class="text-sm font-semibold tracking-wide text-text-muted uppercase mb-4 w-full text-left">Métodos de Pago</h3>
                    <div class="w-full h-full flex items-center justify-center pb-8">
                        <canvas id="methodsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- CONTENIDO: TALLER -->
        <div id="tab-taller" class="tab-content hidden">
            <div class="bg-surface/30 p-6 rounded-2xl border border-border/30">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-sm font-semibold tracking-wide text-text-muted uppercase">Historial de Reparaciones</h3>
                    <div class="flex gap-2 items-center">
                        <input type="text" id="search-repairs" placeholder="Buscar reparación..." class="bg-background border border-border text-sm rounded-lg px-3 py-1.5 focus:border-primary outline-none">
                        <button onclick="exportTableToExcel('repairs-table', 'Reparaciones', this)" class="px-3 py-1.5 rounded-lg border border-green-500/30 bg-green-500/10 text-xs text-green-400 hover:bg-green-500/20 flex items-center gap-1.5 transition-colors">
                            <span class="material-symbols-outlined text-[16px]">download</span> Excel
                        </button>
                        <button onclick="exportTableToPDF('repairs-table', 'Reporte de Reparaciones', 'reparaciones', this)" class="px-3 py-1.5 rounded-lg border border-red-500/30 bg-red-500/10 text-xs text-red-400 hover:bg-red-500/20 flex items-center gap-1.5 transition-colors">
                            <span class="material-symbols-outlined text-[16px]">download</span> PDF
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table id="repairs-table" class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-border text-text-muted text-sm">
                                <th class="pb-3 pr-4 font-medium">Código</th>
                                <th class="pb-3 pr-4 font-medium">Fecha Ingreso</th>
                                <th class="pb-3 pr-4 font-medium">Cliente</th>
                                <th class="pb-3 pr-4 font-medium">Equipo</th>
                                <th class="pb-3 pr-4 font-medium">Estado</th>
                                <th class="pb-3 font-medium text-right">Costo Total</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php if (!empty($repairs)): ?>
                                <?php foreach($repairs as $rep): ?>
                                    <tr class="border-b border-border/50 hover:bg-surface/50 transition-colors">
                                        <td class="py-3 pr-4 font-medium text-primary">#<?php echo htmlspecialchars($rep['codigo_orden']); ?></td>
                                        <td class="py-3 pr-4 text-text-muted"><?php echo date('d/m/Y', strtotime($rep['fecha_ingreso'])); ?></td>
                                        <td class="py-3 pr-4 text-text-main"><?php echo htmlspecialchars($rep['cliente_nombre']); ?></td>
                                        <td class="py-3 pr-4 text-text-muted"><?php echo htmlspecialchars($rep['equipo_marca'] . ' ' . $rep['equipo_modelo']); ?></td>
                                        <td class="py-3 pr-4">
                                            <span class="px-2 py-1 rounded border border-border text-[10px] font-medium text-text-muted uppercase bg-surface">
                                                <?php echo htmlspecialchars($rep['estado']); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 text-right font-medium">$<?php echo number_format($rep['costo_total'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-text-muted">No hay reparaciones registradas.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    window.reportsData = {
        financial: <?php echo json_encode($financial ?? []); ?>,
        stock: <?php echo json_encode($stock_stats ?? []); ?>,
        topValue: <?php echo json_encode($top_value ?? []); ?>,
        salesDaily: <?php echo json_encode($sales_daily ?? []); ?>,
        salesMethods: <?php echo json_encode($sales_methods ?? []); ?>,
        topSales: <?php echo json_encode($top_sales ?? []); ?>
    };
</script>
<script src="<?php echo BASE_URL; ?>/modules/reports/reports.js?v=<?php echo time(); ?>"></script>
<?php Layout::renderFooter(); ?>
