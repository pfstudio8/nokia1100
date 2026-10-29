// assets/js/pages/sales_charts.js

(function () {
    const textMain = '#15171a';
    const primary = '#0f766e';
    const border = 'rgba(21, 23, 26, 0.08)';
    const textMuted = '#4a4f55';
    const surfaceBg = '#f8f6f0';

    // Obtiene los datos pasados desde la vista
    const dates = window.salesData?.dates || [];
    const totals = window.salesData?.totals || [];
    const methods = window.salesData?.methods || [];
    const methodAmounts = window.salesData?.methodAmounts || [];
    const topNames = window.salesData?.topNames || [];
    const topQuantities = window.salesData?.topQuantities || [];

    // Referencias a las instancias de Chart.js
    let salesChart, methodsChart, topProductsChart;

    // Gráfico de líneas: ventas a lo largo del tiempo
    const salesChartElement = document.getElementById('salesChart');
    if (salesChartElement) {
        const ctx = salesChartElement.getContext('2d');
        
        const gradientLine = ctx.createLinearGradient(0, 0, 0, 400);
        gradientLine.addColorStop(0, 'rgba(14, 165, 160, 0.4)');
        gradientLine.addColorStop(1, 'rgba(14, 165, 160, 0.0)');

        salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Ventas Totales ($)',
                    data: totals,
                    backgroundColor: gradientLine,
                    borderColor: primary,
                    borderWidth: 3,
                    pointBackgroundColor: '#1F2937',
                    pointBorderColor: primary,
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: primary,
                    pointHoverBorderColor: '#fff',
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
                        backgroundColor: '#15181a',
                        titleColor: '#f8f6f0',
                        bodyColor: '#c6e24a',
                        borderColor: '#2d3339',
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
        methodsChart = new Chart(ctxMethods, {
            type: 'doughnut',
            data: {
                labels: methods,
                datasets: [{
                    data: methodAmounts,
                    backgroundColor: [
                        '#1fa855',
                        '#3b76e6',
                        '#8b5cf6',
                        '#d97706',
                        '#0ea5c6'
                    ],
                    borderColor: surfaceBg,
                    borderWidth: 3,
                    spacing: 2,
                    hoverOffset: 6,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '66%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: textMain,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 9,
                            boxHeight: 9,
                            font: { family: "'IBM Plex Sans', sans-serif", size: 12, weight: '600' },
                            padding: 18
                        }
                    },
                    tooltip: {
                        backgroundColor: '#15181a',
                        titleColor: '#f8f6f0',
                        bodyColor: '#ffffff',
                        borderColor: '#2d3339',
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

    // Gráfico de barras horizontales: Top 5 Productos
    const topProductsChartElement = document.getElementById('topProductsChart');
    if (topProductsChartElement) {
        const ctxTop = topProductsChartElement.getContext('2d');
        
        // Crear un gradiente de fondo más vibrante
        const gradientTop = ctxTop.createLinearGradient(0, 0, 400, 0);
        gradientTop.addColorStop(0, '#818CF8'); // Indigo
        gradientTop.addColorStop(1, '#C084FC'); // Purple

        topProductsChart = new Chart(ctxTop, {
            type: 'bar',
            data: {
                labels: topNames,
                datasets: [{
                    label: 'Unidades',
                    data: topQuantities,
                    backgroundColor: [
                        'rgba(14, 165, 160, 0.9)',
                        'rgba(94, 234, 212, 0.9)',
                        'rgba(129, 140, 248, 0.9)',
                        'rgba(74, 222, 128, 0.9)',
                        'rgba(251, 191, 36, 0.9)'
                    ],
                    hoverBackgroundColor: [
                        'rgba(14, 165, 160, 1)',
                        'rgba(94, 234, 212, 1)',
                        'rgba(129, 140, 248, 1)',
                        'rgba(74, 222, 128, 1)',
                        'rgba(251, 191, 36, 1)'
                    ],
                    borderWidth: 2,
                    borderColor: 'rgba(255, 255, 255, 0.05)',
                    borderRadius: 8,
                    borderSkipped: false,
                    barPercentage: 0.5,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#15181a',
                        titleColor: '#f8f6f0',
                        bodyColor: '#ffffff',
                        titleFont: { family: 'Inter', size: 12 },
                        bodyFont: { family: 'Inter', size: 14, weight: 'bold' },
                        borderColor: '#374151',
                        borderWidth: 1,
                        padding: 16,
                        cornerRadius: 12,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                return ` ${context.raw} vendidas`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { 
                            color: textMain, 
                            font: { family: 'Inter', size: 13, weight: '500' },
                            padding: 10
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: border, drawBorder: false },
                        ticks: { 
                            color: textMuted, 
                            font: { family: 'Inter', size: 12 },
                            stepSize: 1,
                            padding: 10
                        }
                    }
                },
                animation: {
                    duration: 1500,
                    easing: 'easeOutQuart'
                }
            }
        });
    }

    // Función exportar PDF
    const triggerPdfExport = async () => {
        try {
            if (!window.jspdf) {
                await new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
                    script.onload = resolve;
                    script.onerror = reject;
                    document.head.appendChild(script);
                });
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'mm', 'a4');

            doc.setFillColor(24, 30, 38); 
            doc.rect(0, 0, 210, 30, 'F');
            doc.setTextColor(14, 165, 160); 
            doc.setFontSize(18);
            doc.text("SISTEMA NOKIA 1100", 15, 14);
            doc.setTextColor(250, 250, 250); 
            doc.setFontSize(12);
            doc.text("REPORTE DE ESTADÍSTICAS", 15, 22);
            
            let yPos = 40;

            const addCanvasToPdf = (canvas, title, height) => {
                if (yPos + height + 10 > 280) {
                    doc.addPage();
                    yPos = 20;
                }
                doc.setTextColor(10, 10, 10);
                doc.setFontSize(12);
                doc.text(title, 15, yPos);
                yPos += 5;
                
                doc.setFillColor(31, 41, 55);
                doc.rect(15, yPos, 180, height, 'F');
                
                const imgData = canvas.toDataURL('image/png', 1.0);
                doc.addImage(imgData, 'PNG', 15, yPos, 180, height);
                yPos += height + 15;
            };

            if (salesChartElement) addCanvasToPdf(salesChartElement, "Ventas por Día", 60);
            if (methodsChartElement) addCanvasToPdf(methodsChartElement, "Métodos de Pago", 70);
            if (topProductsChartElement) addCanvasToPdf(topProductsChartElement, "Top 5 Productos Más Vendidos", 70);

            doc.save('nokia1100_graficos.pdf');

            if (typeof showToast === 'function') showToast('PDF generado exitosamente', 'success');
        } catch (err) {
            console.error('Error al generar PDF:', err);
            if (typeof showToast === 'function') showToast('Error al generar PDF', 'error');
        }
    };

    // Función exportar Excel
    const triggerXlsExport = async () => {
        try {
            if (typeof XLSX === 'undefined') {
                await new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
                    script.onload = resolve;
                    script.onerror = reject;
                    document.head.appendChild(script);
                });
            }

            const wb = XLSX.utils.book_new();

            let dataVentas = [['Fecha', 'Total ($)']];
            for (let i = 0; i < dates.length; i++) {
                dataVentas.push([dates[i], totals[i]]);
            }
            XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(dataVentas), "Ventas por Día");

            let dataMetodos = [['Método de Pago', 'Total ($)']];
            for (let i = 0; i < methods.length; i++) {
                dataMetodos.push([methods[i], methodAmounts[i]]);
            }
            XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(dataMetodos), "Métodos de Pago");

            let dataTop = [['Producto', 'Unidades Vendidas']];
            for (let i = 0; i < topNames.length; i++) {
                dataTop.push([topNames[i], topQuantities[i]]);
            }
            XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(dataTop), "Top Productos");

            XLSX.writeFile(wb, 'nokia1100_metricas.xlsx');

            if (typeof showToast === 'function') showToast('Excel exportado exitosamente', 'success');
        } catch (err) {
            console.error('Error al exportar a Excel:', err);
            if (typeof showToast === 'function') showToast('Error al exportar a Excel', 'error');
        }
    };

    // Delay cancelable unificado (>= 1s, clic de nuevo para cancelar)
    const setupCancellableExport = (btn, exportFn) => {
        if (!btn) return;
        btn.addEventListener('click', () => {
            if (typeof window.runCancelableExport === 'function') {
                window.runCancelableExport(btn, async ({ dismissInitToast }) => {
                    dismissInitToast();
                    await exportFn();
                });
            } else {
                exportFn();
            }
        });
    };

    const btnExportPdf = document.getElementById('btnExportPdf');
    const btnExportXls = document.getElementById('btnExportXls');
    if (btnExportPdf) setupCancellableExport(btnExportPdf, triggerPdfExport);
    if (btnExportXls) setupCancellableExport(btnExportXls, triggerXlsExport);
})();
