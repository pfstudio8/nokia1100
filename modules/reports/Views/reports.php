<?php
// modules/reports/Views/reports.php

Layout::renderHead('Centro de Reportes - NOKIA1100');
Layout::renderAdminSidebar('reportes');

function renderRepairStatusBadge(string $estado): string
{
    $e = mb_strtolower(trim($estado), 'UTF-8');
    $style = 'background:#ece9e0; border-color:#c8c2b2; color:#4a4f55;';
    if (str_contains($e, 'listo')) {
        $style = 'background:#dcf2e3; border-color:#8ed0a6; color:#156835;';
    } elseif (str_contains($e, 'entregado')) {
        $style = 'background:#def4f0; border-color:#8ad4c8; color:#0f5c52;';
    } elseif (str_contains($e, 'reparaci')) {
        $style = 'background:#fcf3cf; border-color:#dfb13b; color:#946800;';
    } elseif (str_contains($e, 'diagn')) {
        $style = 'background:#f1ebfe; border-color:#ccb6fa; color:#5b2199;';
    } elseif (str_contains($e, 'recibido')) {
        $style = 'background:#e5effe; border-color:#a6c4f7; color:#1a4396;';
    } elseif (str_contains($e, 'cancelado')) {
        $style = 'background:#fde2e2; border-color:#ef9a9a; color:#b91c1c;';
    }
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-[3px] border text-[10.5px] font-mono font-bold uppercase tracking-wider" style="' . $style . '">' . htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') . '</span>';
}
?>
<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8 border border-[#d3cebf] p-6 md:p-8">
        <header class="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4 mb-6 pb-4 border-b border-[#ded9cb]">
            <div>
                <h2 class="text-3xl font-display font-bold text-[#15171a] tracking-tight">Centro de Reportes</h2>
                <p class="text-[#5c6165] text-sm mt-1">Análisis visual de finanzas, stock, ventas y servicio técnico</p>
            </div>
        </header>

        <?php // Pestañas de Reportes ?>
        <div class="border-b border-[#d3cebf] mb-7">
            <nav class="flex flex-wrap gap-2 sm:gap-5" aria-label="Pestañas de reportes">
                <button type="button" class="tab-btn inline-flex items-center gap-2 px-3.5 py-2.5 border-b-2 font-semibold text-sm text-[#15171a] border-[#15171a] transition-colors" data-tab="tab-finanzas">
                    <span class="material-symbols-outlined text-[18px]">monitoring</span>Financiero
                </button>
                <button type="button" class="tab-btn inline-flex items-center gap-2 px-3.5 py-2.5 border-b-2 font-semibold text-sm text-[#6b7075] border-transparent hover:text-[#15171a] hover:border-[#b5af9c] transition-colors" data-tab="tab-inventario">
                    <span class="material-symbols-outlined text-[18px]">inventory_2</span>Inventario
                </button>
                <button type="button" class="tab-btn inline-flex items-center gap-2 px-3.5 py-2.5 border-b-2 font-semibold text-sm text-[#6b7075] border-transparent hover:text-[#15171a] hover:border-[#b5af9c] transition-colors" data-tab="tab-ventas">
                    <span class="material-symbols-outlined text-[18px]">payments</span>Ventas
                </button>
                <button type="button" class="tab-btn inline-flex items-center gap-2 px-3.5 py-2.5 border-b-2 font-semibold text-sm text-[#6b7075] border-transparent hover:text-[#15171a] hover:border-[#b5af9c] transition-colors" data-tab="tab-taller">
                    <span class="material-symbols-outlined text-[18px]">home_repair_service</span>Taller
                </button>
            </nav>
        </div>

        <?php // CONTENIDO: FINANCIERO ?>
        <div id="tab-finanzas" class="tab-content block">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
                <div class="lg:col-span-12 bg-[#f8f6f0] p-6 rounded-[4px] border border-[#d3cebf] flex flex-col">
                    <div class="flex flex-wrap items-baseline justify-between gap-2 mb-4">
                        <div>
                            <h3 class="text-xs font-mono font-bold tracking-wider text-[#15171a] uppercase">Balance Mensual · Ingresos vs. Compras</h3>
                            <p class="text-xs text-[#6b7075] mt-0.5">Comparativa de facturación y reposición de mercadería en los últimos 6 meses</p>
                        </div>
                    </div>
                    <div class="relative w-full h-[300px]">
                        <canvas id="financialBalanceChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-[#f8f6f0] p-6 rounded-[4px] border border-[#d3cebf] flex flex-col">
                    <div class="mb-4">
                        <h3 class="text-xs font-mono font-bold tracking-wider text-[#15171a] uppercase">Ingresos por Categoría</h3>
                        <p class="text-xs text-[#6b7075] mt-0.5">Facturación acumulada según rubro de catálogo</p>
                    </div>
                    <div class="relative w-full h-[320px]">
                        <canvas id="revenueCategoryChart"></canvas>
                    </div>
                </div>

                <div class="bg-[#f8f6f0] p-6 rounded-[4px] border border-[#d3cebf] flex flex-col">
                    <div class="mb-4">
                        <h3 class="text-xs font-mono font-bold tracking-wider text-[#15171a] uppercase">Top 5 Productos Más Rentables</h3>
                        <p class="text-xs text-[#6b7075] mt-0.5">Artículos con mayor margen neto generado</p>
                    </div>
                    <div class="relative w-full h-[320px]">
                        <canvas id="topProfitableChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <?php // CONTENIDO: INVENTARIO ?>
        <div id="tab-inventario" class="tab-content hidden">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
                <div class="lg:col-span-5 bg-[#f8f6f0] p-6 rounded-[4px] border border-[#d3cebf] flex flex-col justify-between">
                    <div class="mb-4">
                        <h3 class="text-xs font-mono font-bold tracking-wider text-[#15171a] uppercase">Salud del Stock</h3>
                        <p class="text-xs text-[#6b7075] mt-0.5">Estado general de disponibilidad en catálogo</p>
                    </div>
                    <div class="w-full flex-1 flex flex-col gap-3 justify-center">
                        <?php // Item Sano ?>
                        <div class="flex items-center justify-between p-4 rounded-[4px] border border-[#b7dfc4] bg-[#eef8f1] transition-colors">
                            <div class="flex items-center gap-3.5">
                                <div class="w-10 h-10 rounded-[3px] bg-[#dcf2e3] border border-[#8ed0a6] flex items-center justify-center text-[#156835] shrink-0">
                                    <span class="material-symbols-outlined">check_circle</span>
                                </div>
                                <div>
                                    <h4 class="text-[#15171a] font-semibold text-sm">Stock Óptimo</h4>
                                    <p class="text-[#5c6165] text-xs mt-0.5">Unidades suficientes para operar</p>
                                </div>
                            </div>
                            <span class="text-2xl font-mono font-bold text-[#156835]"><?php echo (int)($stock_stats['sano'] ?? 0); ?></span>
                        </div>

                        <?php // Item Crítico ?>
                        <div class="flex items-center justify-between p-4 rounded-[4px] border border-[#e5c158] bg-[#fdf8e2] transition-colors">
                            <div class="flex items-center gap-3.5">
                                <div class="w-10 h-10 rounded-[3px] bg-[#fcf3cf] border border-[#d49a00] flex items-center justify-center text-[#a87700] shrink-0">
                                    <span class="material-symbols-outlined">warning</span>
                                </div>
                                <div>
                                    <h4 class="text-[#15171a] font-semibold text-sm">Stock Bajo</h4>
                                    <p class="text-[#5c6165] text-xs mt-0.5">En nivel mínimo, conviene reponer</p>
                                </div>
                            </div>
                            <span class="text-2xl font-mono font-bold text-[#a87700]"><?php echo (int)($stock_stats['bajo'] ?? 0); ?></span>
                        </div>

                        <?php // Item Agotado ?>
                        <div class="flex items-center justify-between p-4 rounded-[4px] border border-[#ef9a9a] bg-[#fdf0f0] transition-colors">
                            <div class="flex items-center gap-3.5">
                                <div class="w-10 h-10 rounded-[3px] bg-[#fde2e2] border border-[#dc2626] flex items-center justify-center text-[#cc1f1f] shrink-0">
                                    <span class="material-symbols-outlined">block</span>
                                </div>
                                <div>
                                    <h4 class="text-[#15171a] font-semibold text-sm">Sin Stock</h4>
                                    <p class="text-[#5c6165] text-xs mt-0.5">Agotados, requieren compra urgente</p>
                                </div>
                            </div>
                            <span class="text-2xl font-mono font-bold text-[#cc1f1f]"><?php echo (int)($stock_stats['agotado'] ?? 0); ?></span>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-7 bg-[#f8f6f0] p-6 rounded-[4px] border border-[#d3cebf] flex flex-col">
                    <div class="mb-4">
                        <h3 class="text-xs font-mono font-bold tracking-wider text-[#15171a] uppercase">Distribución de Stock por Marca</h3>
                        <p class="text-xs text-[#6b7075] mt-0.5">Participación de unidades físicas según fabricante principal</p>
                    </div>
                    <div class="relative w-full flex-1 min-h-[310px] flex items-center justify-center">
                        <canvas id="stockByBrandChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="bg-[#f8f6f0] p-6 rounded-[4px] border border-[#d3cebf] mb-8">
                <div class="mb-4">
                    <h3 class="text-xs font-mono font-bold tracking-wider text-[#15171a] uppercase">Top 5 Productos con Mayor Stock</h3>
                    <p class="text-xs text-[#6b7075] mt-0.5">Artículos físicos con mayor disponibilidad actual en depósito</p>
                </div>
                <div class="relative w-full h-[300px]">
                    <canvas id="topValueChart"></canvas>
                </div>
            </div>

            <?php // Tabla HTML: Stock Crítico ?>
            <div class="bg-[#f8f6f0] p-6 rounded-[4px] border border-[#d3cebf]">
                <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
                    <div>
                        <h3 class="text-xs font-mono font-bold tracking-wider text-[#cc1f1f] uppercase">Reporte de Stock Crítico</h3>
                        <p class="text-xs text-[#6b7075] mt-0.5">Productos con poco stock o agotados</p>
                    </div>
                    <div class="flex flex-wrap gap-2 items-center">
                        <input type="text" id="search-critical-stock" placeholder="Buscar producto..." class="bg-[#ffffff] border border-[#b5af9c] text-sm rounded-[3px] px-3 py-1.5 focus:border-[#15171a] outline-none">
                        <?php echo Layout::renderExportButtons('critical-stock-table', 'Reporte de Stock Crítico', 'stock_critico'); ?>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table id="critical-stock-table" class="w-full text-left border-collapse" data-paginate="10" data-entity-singular="producto" data-entity-plural="productos" data-search-input="search-critical-stock">
                        <thead>
                            <tr class="border-b border-[#d3cebf] text-[#5c6165] text-xs uppercase font-mono">
                                <th class="pb-3 pr-4 font-semibold">Producto</th>
                                <th class="pb-3 pr-4 font-semibold">Marca</th>
                                <th class="pb-3 pr-4 font-semibold">Modelo</th>
                                <th class="pb-3 pr-4 font-semibold text-right">Stock Actual</th>
                                <th class="pb-3 font-semibold text-right">Stock Mínimo</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php if (!empty($critical_stock)): ?>
                                <?php foreach ($critical_stock as $item): ?>
                                    <tr class="border-b border-[#e5e1d6] hover:bg-[#eeebe3]/60 transition-colors">
                                        <td class="py-3 pr-4 font-medium text-[#15171a]"><?php echo htmlspecialchars($item['nombre']); ?></td>
                                        <td class="py-3 pr-4 text-[#5c6165]"><?php echo htmlspecialchars($item['marca']); ?></td>
                                        <td class="py-3 pr-4 text-[#5c6165]"><?php echo htmlspecialchars($item['modelo']); ?></td>
                                        <td class="py-3 pr-4 text-right">
                                            <?php if ((int)$item['cantidad'] <= 0): ?>
                                                <span class="font-mono text-xs text-[#cc1f1f] font-bold bg-[#fde2e2] border border-[#ef4444]/60 px-2 py-0.5 rounded-[2px]"><?php echo $item['cantidad']; ?></span>
                                            <?php else: ?>
                                                <span class="font-mono text-xs text-[#a87700] font-bold bg-[#fcf3cf] border border-[#d49a00]/60 px-2 py-0.5 rounded-[2px]"><?php echo $item['cantidad']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 text-right font-mono text-[#5c6165]"><?php echo $item['stock_minimo']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-[#6b7075]">No hay productos en estado crítico.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php // CONTENIDO: VENTAS ?>
        <div id="tab-ventas" class="tab-content hidden">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
                <div class="lg:col-span-7 bg-[#f8f6f0] p-6 rounded-[4px] border border-[#d3cebf] flex flex-col">
                    <div class="mb-4">
                        <h3 class="text-xs font-mono font-bold tracking-wider text-[#15171a] uppercase">Ventas por Categoría (Unidades)</h3>
                        <p class="text-xs text-[#6b7075] mt-0.5">Volumen de artículos despachados por rubro</p>
                    </div>
                    <div class="relative w-full flex-1 min-h-[310px]">
                        <canvas id="salesCategoryChart"></canvas>
                    </div>
                </div>

                <div class="lg:col-span-5 bg-[#f8f6f0] p-6 rounded-[4px] border border-[#d3cebf] flex flex-col">
                    <div class="mb-4">
                        <h3 class="text-xs font-mono font-bold tracking-wider text-[#15171a] uppercase">Métodos de Pago</h3>
                        <p class="text-xs text-[#6b7075] mt-0.5">Distribución de ingresos según medio de cobro</p>
                    </div>
                    <div class="relative w-full flex-1 min-h-[310px] flex items-center justify-center">
                        <canvas id="methodsChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="bg-[#f8f6f0] p-6 rounded-[4px] border border-[#d3cebf]">
                <div class="mb-4">
                    <h3 class="text-xs font-mono font-bold tracking-wider text-[#15171a] uppercase">Top 5 Productos Más Vendidos</h3>
                    <p class="text-xs text-[#6b7075] mt-0.5">Ranking de productos con mayor rotación de unidades</p>
                </div>
                <div class="relative w-full h-[300px]">
                    <canvas id="topSalesChart"></canvas>
                </div>
            </div>
        </div>

        <?php // CONTENIDO: TALLER ?>
        <div id="tab-taller" class="tab-content hidden">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">
                <div class="lg:col-span-5 bg-[#f8f6f0] p-6 rounded-[4px] border border-[#d3cebf] flex flex-col">
                    <div class="mb-4">
                        <h3 class="text-xs font-mono font-bold tracking-wider text-[#15171a] uppercase">Estado de Reparaciones</h3>
                        <p class="text-xs text-[#6b7075] mt-0.5">Proporción actual de órdenes según etapa técnica</p>
                    </div>
                    <div class="relative w-full flex-1 min-h-[310px] flex items-center justify-center">
                        <canvas id="repairStatusChart"></canvas>
                    </div>
                </div>

                <div class="lg:col-span-7 bg-[#f8f6f0] p-6 rounded-[4px] border border-[#d3cebf] flex flex-col">
                    <div class="mb-4">
                        <h3 class="text-xs font-mono font-bold tracking-wider text-[#15171a] uppercase">Reparaciones por Marca de Equipo</h3>
                        <p class="text-xs text-[#6b7075] mt-0.5">Cantidad de ingresos técnicos registrados por marca</p>
                    </div>
                    <div class="relative w-full flex-1 min-h-[310px]">
                        <canvas id="repairByBrandChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="bg-[#f8f6f0] p-6 rounded-[4px] border border-[#d3cebf]">
                <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
                    <div>
                        <h3 class="text-xs font-mono font-bold tracking-wider text-[#15171a] uppercase">Historial de Reparaciones</h3>
                        <p class="text-xs text-[#6b7075] mt-0.5">Detalle consolidado de órdenes de servicio técnico</p>
                    </div>
                    <div class="flex flex-wrap gap-2 items-center">
                        <input type="text" id="search-repairs" placeholder="Buscar reparación..." class="bg-[#ffffff] border border-[#b5af9c] text-sm rounded-[3px] px-3 py-1.5 focus:border-[#15171a] outline-none">
                        <?php echo Layout::renderExportButtons('repairs-table', 'Reporte de Reparaciones', 'reparaciones'); ?>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table id="repairs-table" class="w-full text-left border-collapse" data-paginate="10" data-entity-singular="reparación" data-entity-plural="reparaciones" data-search-input="search-repairs">
                        <thead>
                            <tr class="border-b border-[#d3cebf] text-[#5c6165] text-xs uppercase font-mono">
                                <th class="pb-3 pr-4 font-semibold">Código</th>
                                <th class="pb-3 pr-4 font-semibold">Fecha Ingreso</th>
                                <th class="pb-3 pr-4 font-semibold">Cliente</th>
                                <th class="pb-3 pr-4 font-semibold">Equipo</th>
                                <th class="pb-3 pr-4 font-semibold">Estado</th>
                                <th class="pb-3 font-semibold text-right">Costo Total</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php if (!empty($repairs)): ?>
                                <?php foreach ($repairs as $rep): ?>
                                    <tr class="border-b border-[#e5e1d6] hover:bg-[#eeebe3]/60 transition-colors">
                                        <td class="py-3 pr-4 font-mono font-semibold text-[#15171a]">#<?php echo htmlspecialchars($rep['codigo_orden']); ?></td>
                                        <td class="py-3 pr-4 text-[#5c6165]"><?php echo date('d/m/Y', strtotime($rep['fecha_ingreso'])); ?></td>
                                        <td class="py-3 pr-4 text-[#15171a] font-medium"><?php echo htmlspecialchars($rep['cliente_nombre']); ?></td>
                                        <td class="py-3 pr-4 text-[#5c6165]"><?php echo htmlspecialchars($rep['equipo_marca'] . ' ' . $rep['equipo_modelo']); ?></td>
                                        <td class="py-3 pr-4">
                                            <?php echo renderRepairStatusBadge((string)($rep['estado'] ?? '')); ?>
                                        </td>
                                        <td class="py-3 text-right font-mono font-semibold text-[#15171a]">$<?php echo number_format($rep['costo_total'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-[#6b7075]">No hay reparaciones registradas.</td>
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
        revenueCategory: <?php echo json_encode($revenue_category ?? []); ?>,
        topProfitable: <?php echo json_encode($top_profitable ?? []); ?>,
        stock: <?php echo json_encode($stock_stats ?? []); ?>,
        topValue: <?php echo json_encode($top_value ?? []); ?>,
        stockBrand: <?php echo json_encode($stock_brand ?? []); ?>,
        salesDaily: <?php echo json_encode($sales_daily ?? []); ?>,
        salesMethods: <?php echo json_encode($sales_methods ?? []); ?>,
        topSales: <?php echo json_encode($top_sales ?? []); ?>,
        salesCategory: <?php echo json_encode($sales_category ?? []); ?>,
        repairStatus: <?php echo json_encode($repair_status ?? []); ?>,
        repairBrand: <?php echo json_encode($repair_brand ?? []); ?>
    };
</script>
<script src="<?php echo BASE_URL; ?>/modules/reports/reports.js?v=<?php echo time(); ?>"></script>
<?php Layout::renderFooter(); ?>
