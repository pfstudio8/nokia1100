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
    const topNames = window.salesData?.topNames || [];
    const topQuantities = window.salesData?.topQuantities || [];

    // Referencias a las instancias de Chart.js
    let salesChart, methodsChart, topProductsChart;

    // Gráfico de líneas: ventas a lo largo del tiempo
    const salesChartElement = document.getElementById('salesChart');
    if (salesChartElement) {
        const ctx = salesChartElement.getContext('2d');
        
        const gradientLine = ctx.createLinearGradient(0, 0, 0, 400);
        gradientLine.addColorStop(0, 'rgba(79, 224, 229, 0.4)');
        gradientLine.addColorStop(1, 'rgba(79, 224, 229, 0.0)');

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
                    pointBackgroundColor: '#111113',
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
        methodsChart = new Chart(ctxMethods, {
            type: 'doughnut',
            data: {
                labels: methods,
                datasets: [{
                    data: methodAmounts,
                    backgroundColor: [
                        'rgba(79, 224, 229, 0.9)',
                        'rgba(244, 114, 182, 0.9)',
                        'rgba(129, 140, 248, 0.9)',
                        'rgba(251, 191, 36, 0.9)',
                        'rgba(52, 211, 153, 0.9)'
                    ],
                    borderColor: '#18181B', // Darker border matching background
                    borderWidth: 6,
                    hoverOffset: 8,
                    borderRadius: 5
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
                        'rgba(79, 224, 229, 0.9)',
                        'rgba(129, 140, 248, 0.9)',
                        'rgba(244, 114, 182, 0.9)',
                        'rgba(52, 211, 153, 0.9)',
                        'rgba(251, 191, 36, 0.9)'
                    ],
                    hoverBackgroundColor: [
                        'rgba(79, 224, 229, 1)',
                        'rgba(129, 140, 248, 1)',
                        'rgba(244, 114, 182, 1)',
                        'rgba(52, 211, 153, 1)',
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
                        backgroundColor: 'rgba(17, 17, 19, 0.95)',
                        titleColor: textMuted,
                        bodyColor: '#fff',
                        titleFont: { family: 'Inter', size: 12 },
                        bodyFont: { family: 'Inter', size: 14, weight: 'bold' },
                        borderColor: border,
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

    const btnExportPdf = document.getElementById('btnExportPdf');
    let pdfExportTimeout = null;
    let isPdfExporting = false;
    let originalHtmlPdf = '';

    if (btnExportPdf) {
        btnExportPdf.addEventListener('click', async () => {
            if (isPdfExporting) {
                clearTimeout(pdfExportTimeout);
                isPdfExporting = false;
                btnExportPdf.innerHTML = originalHtmlPdf;
                btnExportPdf.classList.remove('bg-red-500/20', 'border-red-500');
                if (typeof showToast === 'function') showToast('Exportación PDF cancelada', 'info');
                return;
            }

            originalHtmlPdf = btnExportPdf.innerHTML;
            isPdfExporting = true;
            btnExportPdf.innerHTML = '<span class="material-symbols-outlined text-[16px] spin" style="animation: spin 1s linear infinite;">autorenew</span> Cancelar...';
            btnExportPdf.classList.add('bg-red-500/20', 'border-red-500');

            if (typeof showToast === 'function') showToast('Iniciando exportación... Clic de nuevo para cancelar', 'info');

            pdfExportTimeout = setTimeout(async () => {
                btnExportPdf.disabled = true;
                btnExportPdf.innerHTML = '<span class="material-symbols-outlined text-[16px] spin" style="animation: spin 1s linear infinite;">autorenew</span> Generando...';
                
                try {
                    if (typeof showToast === 'function') showToast('Generando PDF de gráficos...', 'info');

                    if (!window.jspdf) {
                        await new Promise((resolve) => {
                            const script = document.createElement('script');
                            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
                            script.onload = resolve;
                            document.head.appendChild(script);
                        });
                    }

                    const { jsPDF } = window.jspdf;
                    const doc = new jsPDF('p', 'mm', 'a4');

                    doc.setFillColor(17, 17, 19); 
                    doc.rect(0, 0, 210, 30, 'F');
                    doc.setTextColor(33, 184, 189); 
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
                        
                        doc.setFillColor(24, 24, 27);
                        doc.rect(15, yPos, 180, height, 'F');
                        
                        const imgData = canvas.toDataURL('image/png', 1.0);
                        doc.addImage(imgData, 'PNG', 15, yPos, 180, height);
                        yPos += height + 15;
                    };

                    if (salesChartElement) addCanvasToPdf(salesChartElement, "Ventas por Día", 60);
                    if (methodsChartElement) addCanvasToPdf(methodsChartElement, "Métodos de Pago", 70);
                    if (topProductsChartElement) addCanvasToPdf(topProductsChartElement, "Top 5 Productos Más Vendidos", 70);

                    doc.save('nokia1100_graficos.pdf');

                    if (typeof showToast === 'function') showToast('PDF exportado exitosamente', 'success');
                } catch (err) {
                    console.error(err);
                    if (typeof showToast === 'function') showToast('Error al generar PDF', 'error');
                } finally {
                    isPdfExporting = false;
                    btnExportPdf.innerHTML = originalHtmlPdf;
                    btnExportPdf.disabled = false;
                    btnExportPdf.classList.remove('bg-red-500/20', 'border-red-500');
                }
            }, 2000); // 2 second delay to cancel
        });
    }

    const btnExportXls = document.getElementById('btnExportXls');
    let xlsExportTimeout = null;
    let isXlsExporting = false;
    let originalHtmlXls = '';

    if (btnExportXls) {
        btnExportXls.addEventListener('click', async () => {
            if (isXlsExporting) {
                clearTimeout(xlsExportTimeout);
                isXlsExporting = false;
                btnExportXls.innerHTML = originalHtmlXls;
                btnExportXls.classList.remove('bg-green-500/20', 'border-green-500');
                if (typeof showToast === 'function') showToast('Exportación Excel cancelada', 'info');
                return;
            }

            originalHtmlXls = btnExportXls.innerHTML;
            isXlsExporting = true;
            btnExportXls.innerHTML = '<span class="material-symbols-outlined text-[16px] spin" style="animation: spin 1s linear infinite;">autorenew</span> Cancelar...';
            btnExportXls.classList.add('bg-green-500/20', 'border-green-500');
            
            if (typeof showToast === 'function') showToast('Iniciando exportación... Clic de nuevo para cancelar', 'info');

            xlsExportTimeout = setTimeout(async () => {
                btnExportXls.disabled = true;
                btnExportXls.innerHTML = '<span class="material-symbols-outlined text-[16px] spin" style="animation: spin 1s linear infinite;">autorenew</span> Generando...';
                
                try {
                    if (typeof showToast === 'function') showToast('Generando archivo Excel...', 'info');

                    if (typeof XLSX === 'undefined') {
                        await new Promise((resolve) => {
                            const script = document.createElement('script');
                            script.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
                            script.onload = resolve;
                            document.head.appendChild(script);
                        });
                    }

                    const wb = XLSX.utils.book_new();

                    let dataVentas = [['Fecha', 'Total ($)']];
                    for(let i=0; i<dates.length; i++){
                        dataVentas.push([dates[i], totals[i]]);
                    }
                    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(dataVentas), "Ventas por Día");

                    let dataMetodos = [['Método de Pago', 'Total ($)']];
                    for(let i=0; i<methods.length; i++){
                        dataMetodos.push([methods[i], methodAmounts[i]]);
                    }
                    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(dataMetodos), "Métodos de Pago");

                    let dataTop = [['Producto', 'Unidades Vendidas']];
                    for(let i=0; i<topNames.length; i++){
                        dataTop.push([topNames[i], topQuantities[i]]);
                    }
                    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(dataTop), "Top Productos");

                    XLSX.writeFile(wb, 'nokia1100_metricas.xlsx');

                    if (typeof showToast === 'function') showToast('Excel exportado exitosamente', 'success');
                } catch (err) {
                    console.error(err);
                    if (typeof showToast === 'function') showToast('Error al exportar a Excel', 'error');
                } finally {
                    isXlsExporting = false;
                    btnExportXls.innerHTML = originalHtmlXls;
                    btnExportXls.disabled = false;
                    btnExportXls.classList.remove('bg-green-500/20', 'border-green-500');
                }
            }, 2000); // 2 second delay to cancel
        });
    }
})();
