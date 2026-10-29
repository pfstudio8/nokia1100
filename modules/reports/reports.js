// modules/reports/reports.js - Nokia 1100 Tactile Analytics Suite

document.addEventListener('DOMContentLoaded', () => {
    try {
        const INK = '#15171a';
        const TEXT_MUTED = '#4a4f55';
        const TEXT_SOFT = '#6b7075';
        const SURFACE = '#f8f6f0';
        const GRID_LINE = 'rgba(21, 23, 26, 0.08)';
        const FONT_BODY = "'IBM Plex Sans', -apple-system, BlinkMacSystemFont, sans-serif";
        const FONT_MONO = "'IBM Plex Mono', monospace";
        const FONT_DISPLAY = "'Bricolage Grotesque', 'IBM Plex Sans', sans-serif";

        // Paleta editorial de alto contraste sobre crema cálido (#f8f6f0)
        const PALETTE = [
            '#0f766e', // Teal profundo
            '#2563eb', // Azul cobalto
            '#7c9a14', // Verde oliva Nokia
            '#d97706', // Ámbar dorado
            '#7c3aed', // Violeta
            '#dc2626', // Rojo vivo
            '#0891b2'  // Cian pizarra
        ];

        const arsFull = new Intl.NumberFormat('es-AR', {
            style: 'currency',
            currency: 'ARS',
            maximumFractionDigits: 0
        });

        const arsCompact = new Intl.NumberFormat('es-AR', {
            style: 'currency',
            currency: 'ARS',
            notation: 'compact',
            maximumFractionDigits: 1
        });

        const numFmt = new Intl.NumberFormat('es-AR');

        // Plugin para mostrar total central limpio dentro de gráficos de dona
        const centerDoughnutPlugin = {
            id: 'nokiaCenterText',
            afterDraw(chart) {
                const cfg = chart.options?.plugins?.nokiaCenterText;
                if (!cfg || !cfg.value) return;
                const meta = chart.getDatasetMeta(0);
                if (!meta || !meta.data || !meta.data.length) return;

                const { ctx } = chart;
                const centerX = meta.data[0].x;
                const centerY = meta.data[0].y;

                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';

                if (cfg.label) {
                    ctx.font = `600 9.5px ${FONT_MONO}`;
                    ctx.fillStyle = TEXT_SOFT;
                    ctx.fillText(String(cfg.label).toUpperCase(), centerX, centerY - 12);
                }

                ctx.font = `700 18px ${FONT_DISPLAY}`;
                ctx.fillStyle = INK;
                ctx.fillText(String(cfg.value), centerX, cfg.label ? centerY + 8 : centerY);
                ctx.restore();
            }
        };

        const baseTooltip = {
            backgroundColor: '#15181a',
            titleColor: '#f8f6f0',
            bodyColor: '#e5e7eb',
            borderColor: '#2d3339',
            borderWidth: 1,
            cornerRadius: 4,
            padding: 11,
            boxPadding: 5,
            usePointStyle: true,
            titleFont: { family: FONT_BODY, size: 12, weight: '700' },
            bodyFont: { family: FONT_MONO, size: 11.5, weight: '500' }
        };

        function hasNonZeroData(arr) {
            return Array.isArray(arr) && arr.some(v => Number(v) > 0);
        }

        function renderEmptyState(canvasId, message) {
            const canvas = document.getElementById(canvasId);
            if (!canvas || !canvas.parentElement) return;
            const parent = canvas.parentElement;
            canvas.style.display = 'none';
            if (!parent.querySelector('.chart-empty-msg')) {
                const div = document.createElement('div');
                div.className = 'chart-empty-msg flex flex-col items-center justify-center h-full text-center text-xs font-mono text-[#6b7075] py-8';
                div.innerHTML = `<span class="material-symbols-outlined text-[28px] mb-1.5 opacity-50">bar_chart</span><span>${message}</span>`;
                parent.appendChild(div);
            }
        }

        function truncateLabel(str, maxLen = 24) {
            const clean = String(str || '').trim();
            return clean.length > maxLen ? clean.slice(0, maxLen - 1) + '…' : clean;
        }

        function formatMonthLabel(ym) {
            const parts = String(ym || '').split('-');
            if (parts.length !== 2) return ym;
            const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            const mIdx = parseInt(parts[1], 10) - 1;
            return (months[mIdx] || parts[1]) + ' ' + parts[0].slice(-2);
        }

        function getPaymentMethodColor(methodName, idx) {
            const m = String(methodName || '').toLowerCase();
            if (m.includes('efectivo') || m.includes('contado')) return '#1fa855';
            if (m.includes('tarjeta') || m.includes('debito') || m.includes('débito') || m.includes('credito') || m.includes('crédito')) return '#3b76e6';
            if (m.includes('transf') || m.includes('banco') || m.includes('deposito') || m.includes('depósito')) return '#8b5cf6';
            if (m.includes('mercado') || m.includes('mp') || m.includes('qr')) return '#0ea5c6';
            return PALETTE[idx % PALETTE.length];
        }

        function buildDoughnutOptions({ centerLabel, centerValue, formatValue }) {
            return {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '66%',
                layout: { padding: { top: 6, bottom: 4, left: 6, right: 6 } },
                plugins: {
                    nokiaCenterText: {
                        label: centerLabel,
                        value: centerValue
                    },
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: INK,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 9,
                            boxHeight: 9,
                            padding: 16,
                            font: { family: FONT_BODY, size: 12, weight: '600' },
                            generateLabels(chart) {
                                const ds = chart.data.datasets[0] || { data: [], backgroundColor: [] };
                                const total = (ds.data || []).reduce((acc, n) => acc + (Number(n) || 0), 0);
                                return (chart.data.labels || []).map((lbl, i) => {
                                    const val = Number(ds.data[i]) || 0;
                                    const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                                    const hidden = !chart.getDataVisibility(i);
                                    return {
                                        text: `${lbl} (${pct}%)`,
                                        fillStyle: Array.isArray(ds.backgroundColor) ? ds.backgroundColor[i] : ds.backgroundColor,
                                        strokeStyle: 'transparent',
                                        lineWidth: 0,
                                        pointStyle: 'circle',
                                        hidden,
                                        index: i,
                                        fontColor: INK
                                    };
                                });
                            }
                        }
                    },
                    tooltip: {
                        ...baseTooltip,
                        callbacks: {
                            label(context) {
                                const raw = Number(context.raw) || 0;
                                const total = (context.dataset.data || []).reduce((a, b) => a + (Number(b) || 0), 0);
                                const pct = total > 0 ? ((raw / total) * 100).toFixed(1) : '0.0';
                                const formatted = typeof formatValue === 'function' ? formatValue(raw) : numFmt.format(raw);
                                return ` ${context.label}: ${formatted} (${pct}%)`;
                            }
                        }
                    }
                }
            };
        }

        const data = window.reportsData || {};
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');
        const charts = {};

        // 1. FINANCIERO
        function renderFinancial() {
            if (!charts.financialBalance && document.getElementById('financialBalanceChart')) {
                const fin = data.financial || {};
                const ingresos = (fin.ingresos || []).map(Number);
                const gastos = (fin.gastos || []).map(Number);

                if (Array.isArray(fin.labels) && fin.labels.length > 0 && (hasNonZeroData(ingresos) || hasNonZeroData(gastos))) {
                    charts.financialBalance = new Chart(document.getElementById('financialBalanceChart').getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: fin.labels.map(formatMonthLabel),
                            datasets: [
                                {
                                    label: 'Ingresos por Ventas',
                                    data: ingresos,
                                    backgroundColor: 'rgba(15, 118, 110, 0.85)',
                                    hoverBackgroundColor: '#0f766e',
                                    borderColor: '#0f766e',
                                    borderWidth: 1,
                                    borderRadius: 4,
                                    maxBarThickness: 34
                                },
                                {
                                    label: 'Compras a Proveedores',
                                    data: gastos,
                                    backgroundColor: 'rgba(217, 119, 6, 0.78)',
                                    hoverBackgroundColor: '#d97706',
                                    borderColor: '#b45309',
                                    borderWidth: 1,
                                    borderRadius: 4,
                                    maxBarThickness: 34
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            plugins: {
                                legend: {
                                    position: 'top',
                                    align: 'end',
                                    labels: {
                                        color: INK,
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        boxWidth: 8,
                                        boxHeight: 8,
                                        padding: 16,
                                        font: { family: FONT_BODY, size: 12, weight: '600' }
                                    }
                                },
                                tooltip: {
                                    ...baseTooltip,
                                    callbacks: {
                                        label: (ctx) => ` ${ctx.dataset.label}: ${arsFull.format(Number(ctx.raw) || 0)}`
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    border: { display: false },
                                    grid: { color: GRID_LINE },
                                    ticks: {
                                        color: TEXT_MUTED,
                                        font: { family: FONT_MONO, size: 11 },
                                        callback: (v) => arsCompact.format(v)
                                    }
                                },
                                x: {
                                    border: { color: 'rgba(21, 23, 26, 0.14)' },
                                    grid: { display: false },
                                    ticks: {
                                        color: INK,
                                        font: { family: FONT_BODY, size: 12, weight: '600' }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    renderEmptyState('financialBalanceChart', 'Sin movimientos financieros recientes para graficar.');
                }
            }

            if (!charts.revenueCategory && document.getElementById('revenueCategoryChart')) {
                const labels = data.revenueCategory?.labels || [];
                const values = (data.revenueCategory?.values || []).map(Number);

                if (labels.length > 0 && hasNonZeroData(values)) {
                    charts.revenueCategory = new Chart(document.getElementById('revenueCategoryChart').getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [{
                                label: 'Ingresos',
                                data: values,
                                backgroundColor: labels.map((_, i) => PALETTE[i % PALETTE.length] + 'D9'),
                                hoverBackgroundColor: labels.map((_, i) => PALETTE[i % PALETTE.length]),
                                borderColor: labels.map((_, i) => PALETTE[i % PALETTE.length]),
                                borderWidth: 1,
                                borderRadius: 4,
                                maxBarThickness: 44
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    ...baseTooltip,
                                    callbacks: {
                                        label: (ctx) => ` Ingresos: ${arsFull.format(Number(ctx.raw) || 0)}`
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    border: { display: false },
                                    grid: { color: GRID_LINE },
                                    ticks: {
                                        color: TEXT_MUTED,
                                        font: { family: FONT_MONO, size: 11 },
                                        callback: (v) => arsCompact.format(v)
                                    }
                                },
                                x: {
                                    border: { color: 'rgba(21, 23, 26, 0.14)' },
                                    grid: { display: false },
                                    ticks: {
                                        color: INK,
                                        font: { family: FONT_BODY, size: 12, weight: '600' }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    renderEmptyState('revenueCategoryChart', 'No hay ingresos por categoría registrados.');
                }
            }

            if (!charts.topProfitable && document.getElementById('topProfitableChart')) {
                const fullNames = data.topProfitable?.names || [];
                const values = (data.topProfitable?.values || []).map(Number);

                if (fullNames.length > 0 && hasNonZeroData(values)) {
                    charts.topProfitable = new Chart(document.getElementById('topProfitableChart').getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: fullNames.map(n => truncateLabel(n, 24)),
                            datasets: [{
                                label: 'Rentabilidad',
                                data: values,
                                backgroundColor: 'rgba(37, 99, 235, 0.82)',
                                hoverBackgroundColor: '#2563eb',
                                borderColor: '#1d4ed8',
                                borderWidth: 1,
                                borderRadius: 4,
                                maxBarThickness: 28
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    ...baseTooltip,
                                    callbacks: {
                                        title: (items) => fullNames[items[0]?.dataIndex] || items[0]?.label || '',
                                        label: (ctx) => ` Margen neto: ${arsFull.format(Number(ctx.raw) || 0)}`
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    border: { display: false },
                                    grid: { color: GRID_LINE },
                                    ticks: {
                                        maxRotation: 0,
                                        minRotation: 0,
                                        color: TEXT_MUTED,
                                        font: { family: FONT_MONO, size: 11 },
                                        callback: (v) => arsCompact.format(v)
                                    }
                                },
                                y: {
                                    border: { color: 'rgba(21, 23, 26, 0.14)' },
                                    grid: { display: false },
                                    ticks: {
                                        color: INK,
                                        font: { family: FONT_BODY, size: 12, weight: '600' }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    renderEmptyState('topProfitableChart', 'No hay datos de rentabilidad disponibles.');
                }
            }
        }

        // 2. INVENTARIO
        function renderInventory() {
            if (!charts.inventoryBrand && document.getElementById('stockByBrandChart')) {
                const labels = data.stockBrand?.labels || [];
                const values = (data.stockBrand?.values || []).map(Number);

                if (labels.length > 0 && hasNonZeroData(values)) {
                    const totalUnits = values.reduce((a, b) => a + b, 0);
                    charts.inventoryBrand = new Chart(document.getElementById('stockByBrandChart').getContext('2d'), {
                        type: 'doughnut',
                        plugins: [centerDoughnutPlugin],
                        data: {
                            labels,
                            datasets: [{
                                data: values,
                                backgroundColor: labels.map((_, i) => PALETTE[i % PALETTE.length]),
                                borderColor: SURFACE,
                                borderWidth: 3,
                                spacing: 2,
                                borderRadius: 4,
                                hoverOffset: 6
                            }]
                        },
                        options: buildDoughnutOptions({
                            centerLabel: 'Unidades',
                            centerValue: numFmt.format(totalUnits),
                            formatValue: (v) => `${numFmt.format(v)} un.`
                        })
                    });
                } else {
                    renderEmptyState('stockByBrandChart', 'Sin stock activo por marca para mostrar.');
                }
            }

            if (!charts.inventoryTop && document.getElementById('topValueChart')) {
                const fullProducts = data.topValue?.products || [];
                const values = (data.topValue?.values || []).map(Number);

                if (fullProducts.length > 0 && hasNonZeroData(values)) {
                    charts.inventoryTop = new Chart(document.getElementById('topValueChart').getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: fullProducts.map(n => truncateLabel(n, 26)),
                            datasets: [{
                                label: 'Unidades en Stock',
                                data: values,
                                backgroundColor: fullProducts.map((_, i) => PALETTE[i % PALETTE.length] + 'D9'),
                                hoverBackgroundColor: fullProducts.map((_, i) => PALETTE[i % PALETTE.length]),
                                borderColor: fullProducts.map((_, i) => PALETTE[i % PALETTE.length]),
                                borderWidth: 1,
                                borderRadius: 4,
                                maxBarThickness: 52
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    ...baseTooltip,
                                    callbacks: {
                                        title: (items) => fullProducts[items[0]?.dataIndex] || items[0]?.label || '',
                                        label: (ctx) => ` En depósito: ${numFmt.format(Number(ctx.raw) || 0)} unidades`
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    border: { color: 'rgba(21, 23, 26, 0.14)' },
                                    grid: { display: false },
                                    ticks: {
                                        color: INK,
                                        font: { family: FONT_BODY, size: 12, weight: '600' }
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    border: { display: false },
                                    grid: { color: GRID_LINE },
                                    ticks: {
                                        precision: 0,
                                        color: TEXT_MUTED,
                                        font: { family: FONT_MONO, size: 11 }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    renderEmptyState('topValueChart', 'No hay productos en inventario para graficar.');
                }
            }
        }

        // 3. VENTAS
        function renderSales() {
            if (!charts.salesCategory && document.getElementById('salesCategoryChart')) {
                const labels = data.salesCategory?.labels || [];
                const values = (data.salesCategory?.values || []).map(Number);

                if (labels.length > 0 && hasNonZeroData(values)) {
                    charts.salesCategory = new Chart(document.getElementById('salesCategoryChart').getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [{
                                label: 'Unidades',
                                data: values,
                                backgroundColor: labels.map((_, i) => PALETTE[i % PALETTE.length] + 'D9'),
                                hoverBackgroundColor: labels.map((_, i) => PALETTE[i % PALETTE.length]),
                                borderColor: labels.map((_, i) => PALETTE[i % PALETTE.length]),
                                borderWidth: 1,
                                borderRadius: 4,
                                maxBarThickness: 46
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    ...baseTooltip,
                                    callbacks: {
                                        label: (ctx) => ` Vendidas: ${numFmt.format(Number(ctx.raw) || 0)} unidades`
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    border: { display: false },
                                    grid: { color: GRID_LINE },
                                    ticks: {
                                        precision: 0,
                                        color: TEXT_MUTED,
                                        font: { family: FONT_MONO, size: 11 }
                                    }
                                },
                                x: {
                                    border: { color: 'rgba(21, 23, 26, 0.14)' },
                                    grid: { display: false },
                                    ticks: {
                                        color: INK,
                                        font: { family: FONT_BODY, size: 12, weight: '600' }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    renderEmptyState('salesCategoryChart', 'No hay ventas registradas por categoría.');
                }
            }

            if (!charts.salesMethods && document.getElementById('methodsChart')) {
                const methods = data.salesMethods?.methods || [];
                const amounts = (data.salesMethods?.amounts || []).map(Number);

                if (methods.length > 0 && hasNonZeroData(amounts)) {
                    const totalAmount = amounts.reduce((a, b) => a + b, 0);
                    const sliceColors = methods.map((m, idx) => getPaymentMethodColor(m, idx));

                    charts.salesMethods = new Chart(document.getElementById('methodsChart').getContext('2d'), {
                        type: 'doughnut',
                        plugins: [centerDoughnutPlugin],
                        data: {
                            labels: methods,
                            datasets: [{
                                data: amounts,
                                backgroundColor: sliceColors,
                                borderColor: SURFACE,
                                borderWidth: 3,
                                spacing: 2,
                                borderRadius: 4,
                                hoverOffset: 6
                            }]
                        },
                        options: buildDoughnutOptions({
                            centerLabel: 'Facturado',
                            centerValue: arsCompact.format(totalAmount),
                            formatValue: (v) => arsFull.format(v)
                        })
                    });
                } else {
                    renderEmptyState('methodsChart', 'Sin cobros registrados por medio de pago.');
                }
            }

            if (!charts.salesTop && document.getElementById('topSalesChart')) {
                const fullNames = data.topSales?.names || [];
                const quantities = (data.topSales?.quantities || []).map(Number);

                if (fullNames.length > 0 && hasNonZeroData(quantities)) {
                    charts.salesTop = new Chart(document.getElementById('topSalesChart').getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: fullNames.map(n => truncateLabel(n, 30)),
                            datasets: [{
                                label: 'Unidades Vendidas',
                                data: quantities,
                                backgroundColor: 'rgba(15, 118, 110, 0.85)',
                                hoverBackgroundColor: '#0f766e',
                                borderColor: '#0f766e',
                                borderWidth: 1,
                                borderRadius: 4,
                                maxBarThickness: 28
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    ...baseTooltip,
                                    callbacks: {
                                        title: (items) => fullNames[items[0]?.dataIndex] || items[0]?.label || '',
                                        label: (ctx) => ` Unidades vendidas: ${numFmt.format(Number(ctx.raw) || 0)}`
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    border: { display: false },
                                    grid: { color: GRID_LINE },
                                    ticks: {
                                        precision: 0,
                                        color: TEXT_MUTED,
                                        font: { family: FONT_MONO, size: 11 }
                                    }
                                },
                                y: {
                                    border: { color: 'rgba(21, 23, 26, 0.14)' },
                                    grid: { display: false },
                                    ticks: {
                                        color: INK,
                                        font: { family: FONT_BODY, size: 12, weight: '600' }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    renderEmptyState('topSalesChart', 'No hay unidades vendidas para construir el ranking.');
                }
            }
        }

        // 4. TALLER
        function renderWorkshop() {
            if (!charts.workshopStatus && document.getElementById('repairStatusChart')) {
                const labels = data.repairStatus?.labels || [];
                const counts = (data.repairStatus?.counts || []).map(Number);

                if (labels.length > 0 && hasNonZeroData(counts)) {
                    const statusColors = {
                        'Recibido': '#3b76e6',
                        'En diagnóstico': '#8b5cf6',
                        'En reparación': '#dea000',
                        'Listo': '#1fa855',
                        'Entregado': '#0f766e',
                        'Cancelado': '#cc1f1f'
                    };
                    const colors = labels.map((l, idx) => statusColors[l] || PALETTE[idx % PALETTE.length]);
                    const totalOrders = counts.reduce((a, b) => a + b, 0);

                    charts.workshopStatus = new Chart(document.getElementById('repairStatusChart').getContext('2d'), {
                        type: 'doughnut',
                        plugins: [centerDoughnutPlugin],
                        data: {
                            labels,
                            datasets: [{
                                data: counts,
                                backgroundColor: colors,
                                borderColor: SURFACE,
                                borderWidth: 3,
                                spacing: 2,
                                borderRadius: 4,
                                hoverOffset: 6
                            }]
                        },
                        options: buildDoughnutOptions({
                            centerLabel: 'Órdenes',
                            centerValue: numFmt.format(totalOrders),
                            formatValue: (v) => `${numFmt.format(v)} órd.`
                        })
                    });
                } else {
                    renderEmptyState('repairStatusChart', 'Sin órdenes de reparación registradas.');
                }
            }

            if (!charts.workshopBrand && document.getElementById('repairByBrandChart')) {
                const labels = data.repairBrand?.labels || [];
                const counts = (data.repairBrand?.counts || []).map(Number);

                if (labels.length > 0 && hasNonZeroData(counts)) {
                    charts.workshopBrand = new Chart(document.getElementById('repairByBrandChart').getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [{
                                label: 'Reparaciones',
                                data: counts,
                                backgroundColor: labels.map((_, i) => PALETTE[i % PALETTE.length] + 'D9'),
                                hoverBackgroundColor: labels.map((_, i) => PALETTE[i % PALETTE.length]),
                                borderColor: labels.map((_, i) => PALETTE[i % PALETTE.length]),
                                borderWidth: 1,
                                borderRadius: 4,
                                maxBarThickness: 46
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    ...baseTooltip,
                                    callbacks: {
                                        label: (ctx) => ` Órdenes ingresadas: ${numFmt.format(Number(ctx.raw) || 0)}`
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    border: { display: false },
                                    grid: { color: GRID_LINE },
                                    ticks: {
                                        precision: 0,
                                        color: TEXT_MUTED,
                                        font: { family: FONT_MONO, size: 11 }
                                    }
                                },
                                x: {
                                    border: { color: 'rgba(21, 23, 26, 0.14)' },
                                    grid: { display: false },
                                    ticks: {
                                        color: INK,
                                        font: { family: FONT_BODY, size: 12, weight: '600' }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    renderEmptyState('repairByBrandChart', 'Sin reparaciones agrupadas por marca.');
                }
            }
        }

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                tabBtns.forEach(b => {
                    b.classList.remove('text-[#15171a]', 'border-[#15171a]');
                    b.classList.add('text-[#6b7075]', 'border-transparent');
                });
                btn.classList.add('text-[#15171a]', 'border-[#15171a]');
                btn.classList.remove('text-[#6b7075]', 'border-transparent');

                const targetId = btn.getAttribute('data-tab');
                tabContents.forEach(content => {
                    if (content.id === targetId) {
                        content.classList.remove('hidden');
                        content.classList.add('block');

                        if (targetId === 'tab-finanzas') renderFinancial();
                        if (targetId === 'tab-inventario') renderInventory();
                        if (targetId === 'tab-ventas') renderSales();
                        if (targetId === 'tab-taller') renderWorkshop();
                    } else {
                        content.classList.add('hidden');
                        content.classList.remove('block');
                    }
                });
            });
        });

        renderFinancial();
    } catch (error) {
        console.error('Error inicializando los gráficos de reportes:', error);
    }
});
