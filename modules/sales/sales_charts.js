// assets/js/pages/sales_charts.js

(function () {
    const computedStyle = getComputedStyle(document.body);
    const textMain = computedStyle.getPropertyValue('--tw-text-opacity') ? `rgba(250, 250, 250, 1)` : '#FAFAFA';
    const primary = '#4FE0E5';
    const border = 'rgba(255, 255, 255, 0.1)';
    const textMuted = '#A1A1AA';

    // Obtiene los datos pasados desde la vista
    const dates = window.salesData?.dates || [];
    const totals = window.salesData?.totals || [];
    const methods = window.salesData?.methods || [];
    const methodAmounts = window.salesData?.methodAmounts || [];

    // Gráfico de líneas: ventas a lo largo del tiempo
    const salesChartElement = document.getElementById('salesChart');
    if (salesChartElement) {
        const ctx = salesChartElement.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Ventas Totales ($)',
                    data: totals,
                    backgroundColor: 'rgba(79, 224, 229, 0.2)',
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
                            label: function (context) {
                                return 'Total: $' + context.raw.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: border },
                        ticks: { color: textMuted, font: { family: 'Inter' } }
                    },
                    x: {
                        grid: { color: border },
                        ticks: { color: textMuted, font: { family: 'Inter' } }
                    }
                }
            }
        });
    }

    // Gráfico de dona: métodos de pago
    const methodsChartElement = document.getElementById('methodsChart');
    if (methodsChartElement) {
        const ctxMethods = methodsChartElement.getContext('2d');
        new Chart(ctxMethods, {
            type: 'doughnut',
            data: {
                labels: methods,
                datasets: [{
                    data: methodAmounts,
                    backgroundColor: [
                        '#4FE0E5',
                        '#F472B6',
                        '#818CF8',
                        '#FBBF24',
                        '#34D399'
                    ],
                    borderColor: '#111113',
                    borderWidth: 4,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: textMuted,
                            font: { family: 'Inter', size: 12 },
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(17, 17, 19, 0.9)',
                        titleColor: textMain,
                        bodyColor: '#fff',
                        borderColor: border,
                        borderWidth: 1,
                        padding: 12,
                        callbacks: {
                            label: function (context) {
                                return ' $' + context.raw.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    }
})();
