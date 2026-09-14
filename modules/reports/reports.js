document.addEventListener('DOMContentLoaded', () => {
    try {
        const computedStyle = getComputedStyle(document.body);
        const textMain = computedStyle.getPropertyValue('--tw-text-opacity') ? `rgba(250, 250, 250, 1)` : '#FAFAFA';
        const primary = '#4FE0E5';
        const border = 'rgba(255, 255, 255, 0.1)';
        const textMuted = '#A1A1AA';

        const data = window.reportsData;
        console.log("Reports Data:", data);

        // --- LOGICA DE TABS ---
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');

        // Almacenar instancias de gráficos
        const charts = {};

        function renderFinancial() {
            if (charts.financial) return;
            if (document.getElementById('financialChart') && data.financial && data.financial.labels) {
                charts.financial = new Chart(document.getElementById('financialChart').getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: data.financial.labels,
                        datasets: [
                            { label: 'Ingresos (Ventas)', data: data.financial.ingresos, borderColor: '#4FE0E5', backgroundColor: 'rgba(79, 224, 229, 0.1)', borderWidth: 2, fill: true, tension: 0.4 },
                            { label: 'Gastos (Compras)', data: data.financial.gastos, borderColor: '#F472B6', backgroundColor: 'rgba(244, 114, 182, 0.1)', borderWidth: 2, fill: true, tension: 0.4 }
                        ]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: textMuted, font: { family: 'Inter' } } } }, scales: { y: { grid: { color: border }, ticks: { color: textMuted } }, x: { grid: { color: border }, ticks: { color: textMuted } } } }
                });
            }
        }

        function renderInventory() {
            if (charts.inventory1) return;
            if (document.getElementById('stockStateChart') && data.stock && typeof data.stock.sano !== 'undefined') {
                charts.inventory1 = new Chart(document.getElementById('stockStateChart').getContext('2d'), {
                    type: 'doughnut',
                    data: { labels: ['Sano', 'Crítico', 'Agotado'], datasets: [{ data: [data.stock.sano, data.stock.bajo, data.stock.agotado], backgroundColor: ['#34D399', '#FBBF24', '#EF4444'], borderColor: '#111113', borderWidth: 4 }] },
                    options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom', labels: { color: textMuted, font: { family: 'Inter', size: 12 }, padding: 20 } }, tooltip: { backgroundColor: 'rgba(17, 17, 19, 0.9)', titleColor: textMain, bodyColor: '#fff', borderColor: border, borderWidth: 1, padding: 12 } } }
                });
            }

            if (document.getElementById('topValueChart') && data.topValue && data.topValue.products) {
                charts.inventory2 = new Chart(document.getElementById('topValueChart').getContext('2d'), {
                    type: 'bar',
                    data: { labels: data.topValue.products, datasets: [{ label: 'Valor en Stock ($)', data: data.topValue.values, backgroundColor: 'rgba(244, 114, 182, 0.8)', borderColor: '#F472B6', borderWidth: 1, borderRadius: 4 }] },
                    options: { indexAxis: 'x', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { backgroundColor: 'rgba(17, 17, 19, 0.9)', titleColor: textMain, bodyColor: '#F472B6', borderColor: border, borderWidth: 1, padding: 12, callbacks: { label: (c) => '$' + c.raw.toFixed(2) } } }, scales: { x: { grid: { display: false }, ticks: { color: textMuted, font: { family: 'Inter' } } }, y: { grid: { color: border }, ticks: { color: textMuted, font: { family: 'Inter' } } } } }
                });
            }
        }

        function renderSales() {
            if (charts.sales1) return;
            if (document.getElementById('topSalesChart') && data.topSales && data.topSales.products) {
                charts.sales1 = new Chart(document.getElementById('topSalesChart').getContext('2d'), {
                    type: 'bar',
                    data: { labels: data.topSales.products, datasets: [{ label: 'Unidades Vendidas', data: data.topSales.amounts, backgroundColor: 'rgba(79, 224, 229, 0.8)', borderColor: '#4FE0E5', borderWidth: 1, borderRadius: 4 }] },
                    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { color: border }, ticks: { color: textMuted, font: { family: 'Inter' }, precision: 0 } }, y: { grid: { display: false }, ticks: { color: textMuted, font: { family: 'Inter' } } } } }
                });
            }

            if (document.getElementById('salesChart') && data.salesDaily && data.salesDaily.dates) {
                charts.sales2 = new Chart(document.getElementById('salesChart').getContext('2d'), {
                    type: 'line',
                    data: { labels: data.salesDaily.dates, datasets: [{ label: 'Ventas Totales ($)', data: data.salesDaily.totals, backgroundColor: 'rgba(79, 224, 229, 0.2)', borderColor: primary, borderWidth: 2, fill: true, tension: 0.4 }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { grid: { color: border }, ticks: { color: textMuted } }, x: { grid: { color: border }, ticks: { color: textMuted } } } }
                });
            }

            if (document.getElementById('methodsChart') && data.salesMethods && data.salesMethods.methods) {
                charts.sales3 = new Chart(document.getElementById('methodsChart').getContext('2d'), {
                    type: 'doughnut',
                    data: { labels: data.salesMethods.methods, datasets: [{ data: data.salesMethods.amounts, backgroundColor: ['#4FE0E5', '#F472B6', '#818CF8', '#FBBF24', '#34D399'], borderColor: '#111113', borderWidth: 4 }] },
                    options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom', labels: { color: textMuted, font: { family: 'Inter', size: 12 }, padding: 20 } } } }
                });
            }
        }

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                tabBtns.forEach(b => {
                    b.classList.remove('text-primary', 'border-primary');
                    b.classList.add('text-text-muted', 'border-transparent');
                });
                btn.classList.add('text-primary', 'border-primary');
                btn.classList.remove('text-text-muted', 'border-transparent');

                const targetId = btn.getAttribute('data-tab');
                tabContents.forEach(content => {
                    if(content.id === targetId) {
                        content.classList.remove('hidden');
                        content.classList.add('block');
                        
                        // Renderizar los gráficos de esta pestaña si aún no se han renderizado
                        if(targetId === 'tab-finanzas') renderFinancial();
                        if(targetId === 'tab-inventario') renderInventory();
                        if(targetId === 'tab-ventas') renderSales();
                    } else {
                        content.classList.add('hidden');
                        content.classList.remove('block');
                    }
                });
            });
        });

        
        // --- BUSQUEDA EN TABLAS ---
        const searchStock = document.getElementById('search-critical-stock');
        const stockTableBody = document.querySelector('#critical-stock-table tbody');
        if (searchStock && stockTableBody) {
            searchStock.addEventListener('input', function(e) {
                const term = e.target.value.toLowerCase();
                const rows = stockTableBody.querySelectorAll('tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(term) ? '' : 'none';
                });
            });
        }

        const searchRepairs = document.getElementById('search-repairs');
        const repairsTableBody = document.querySelector('#repairs-table tbody');
        if (searchRepairs && repairsTableBody) {
            searchRepairs.addEventListener('input', function(e) {
                const term = e.target.value.toLowerCase();
                const rows = repairsTableBody.querySelectorAll('tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(term) ? '' : 'none';
                });
            });
        }

        // Iniciar con la primera pestaña
        renderFinancial();

    } catch (error) {
        console.error("Error inicializando los gráficos de reportes:", error);
    }
});
