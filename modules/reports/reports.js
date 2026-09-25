document.addEventListener('DOMContentLoaded', () => {
    try {
        const computedStyle = getComputedStyle(document.body);
        const textMain = computedStyle.getPropertyValue('--tw-text-opacity') ? `rgba(250, 250, 250, 1)` : '#FAFAFA';
        const primary = '#0EA5A0'; // Acento
        const primaryAlpha = 'rgba(14, 165, 160, 0.8)';
        const secondary = '#5EEAD4'; // Acento 2
        const secondaryAlpha = 'rgba(94, 234, 212, 0.8)';
        const cardBg = '#263347';
        const border = 'rgba(255, 255, 255, 0.05)';
        const textMuted = '#9CA3AF';
        
        // Colores para gráficos circulares
        const doughnutColors = ['#0EA5A0', '#5EEAD4', '#4ADE80', '#FBBF24', '#F87171'];

        const data = window.reportsData;
        console.log("Reports Data:", data);

        // --- LOGICA DE TABS ---
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');

        // Almacenar instancias de gráficos
        const charts = {};

        function renderFinancial() {
            if (charts.revenueCategory) return;
            
            if (document.getElementById('revenueCategoryChart') && data.revenueCategory && data.revenueCategory.labels) {
                charts.revenueCategory = new Chart(document.getElementById('revenueCategoryChart').getContext('2d'), {
                    type: 'bar',
                    data: { 
                        labels: data.revenueCategory.labels, 
                        datasets: [{ 
                            label: 'Ingresos', 
                            data: data.revenueCategory.values, 
                            backgroundColor: primaryAlpha, 
                            borderColor: primary, 
                            borderWidth: 1, 
                            borderRadius: 6 
                        }] 
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1F2937', titleColor: '#FAFAFA', bodyColor: secondary, borderColor: '#374151', borderWidth: 1, padding: 12, callbacks: { label: function(context) { return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(context.raw); } } } }, scales: { y: { grid: { color: border }, ticks: { color: textMuted, callback: function(value) { return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', notation: 'compact' }).format(value); } } }, x: { grid: { display: false }, ticks: { color: textMuted, font: { family: 'Outfit, sans-serif' } } } } }
                });
            }

            if (document.getElementById('topProfitableChart') && data.topProfitable && data.topProfitable.names) {
                charts.topProfitable = new Chart(document.getElementById('topProfitableChart').getContext('2d'), {
                    type: 'bar',
                    data: { 
                        labels: data.topProfitable.names, 
                        datasets: [{ 
                            label: 'Rentabilidad', 
                            data: data.topProfitable.values, 
                            backgroundColor: secondaryAlpha, 
                            borderColor: secondary, 
                            borderWidth: 1, 
                            borderRadius: 6 
                        }] 
                    },
                    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1F2937', titleColor: '#FAFAFA', bodyColor: secondary, borderColor: '#374151', borderWidth: 1, padding: 12, callbacks: { label: function(context) { return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(context.raw); } } } }, scales: { x: { grid: { color: border }, ticks: { maxRotation: 0, minRotation: 0, color: textMuted, callback: function(value) { return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', notation: 'compact' }).format(value); } } }, y: { grid: { display: false }, ticks: { color: textMuted, font: { family: 'Outfit, sans-serif' } } } } }
                });
            }
        }

        function renderInventory() {
            if (charts.inventory2) return;
            
            if (document.getElementById('stockByBrandChart') && data.stockBrand && data.stockBrand.labels) {
                charts.inventory1 = new Chart(document.getElementById('stockByBrandChart').getContext('2d'), {
                    type: 'doughnut',
                    data: { labels: data.stockBrand.labels, datasets: [{ data: data.stockBrand.values, backgroundColor: doughnutColors, borderColor: cardBg, borderWidth: 3 }] },
                    options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { tooltip: { backgroundColor: '#1F2937', titleColor: '#FAFAFA', bodyColor: secondary, borderColor: '#374151', borderWidth: 1, padding: 12 }, legend: { position: 'bottom', labels: { color: textMuted, font: { family: 'Outfit, sans-serif', size: 12 }, padding: 20 } } } }
                });
            }

            if (document.getElementById('topValueChart') && data.topValue && data.topValue.products) {
                charts.inventory2 = new Chart(document.getElementById('topValueChart').getContext('2d'), {
                    type: 'bar',
                    data: { labels: data.topValue.products.map(name => { if(name.length > 12 && name.indexOf(' ') !== -1) { let p = name.split(' '); return [p[0], p.slice(1).join(' ').substring(0, 12) + (p.slice(1).join(' ').length > 12 ? '...' : '')]; } return name; }), datasets: [{ label: 'Cantidad en Stock', data: data.topValue.values, backgroundColor: secondaryAlpha, borderColor: secondary, borderWidth: 1, borderRadius: 6 }] },
                    options: { indexAxis: 'x', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1F2937', titleColor: '#FAFAFA', bodyColor: secondary, borderColor: '#374151', borderWidth: 1, padding: 12, callbacks: { label: (c) => c.raw } } }, scales: { x: { grid: { display: false }, ticks: { color: textMuted, font: { family: 'Outfit, sans-serif' } } }, y: { grid: { color: border }, ticks: { color: textMuted, font: { family: 'Outfit, sans-serif' } } } } }
                });
            }
        }

        function renderSales() {
            if (charts.sales1) return;
            if (document.getElementById('topSalesChart') && data.topSales && data.topSales.names) {
                charts.sales1 = new Chart(document.getElementById('topSalesChart').getContext('2d'), {
                    type: 'bar',
                    data: { labels: data.topSales.names, datasets: [{ label: 'Unidades Vendidas', data: data.topSales.quantities, backgroundColor: primaryAlpha, borderColor: primary, borderWidth: 1, borderRadius: 6 }] },
                    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1F2937', titleColor: '#FAFAFA', bodyColor: secondary, borderColor: '#374151', borderWidth: 1, padding: 12 } }, scales: { x: { grid: { color: border }, ticks: { color: textMuted, font: { family: 'Outfit, sans-serif' }, precision: 0 } }, y: { grid: { display: false }, ticks: { color: textMuted, font: { family: 'Outfit, sans-serif' } } } } }
                });
            }

            if (document.getElementById('salesCategoryChart') && data.salesCategory && data.salesCategory.labels) {
                charts.sales2 = new Chart(document.getElementById('salesCategoryChart').getContext('2d'), {
                    type: 'bar',
                    data: { labels: data.salesCategory.labels, datasets: [{ label: 'Unidades', data: data.salesCategory.values, backgroundColor: secondaryAlpha, borderColor: secondary, borderWidth: 1, borderRadius: 6 }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1F2937', titleColor: '#FAFAFA', bodyColor: secondary, borderColor: '#374151', borderWidth: 1, padding: 12 } }, scales: { y: { grid: { color: border }, ticks: { color: textMuted } }, x: { grid: { display: false }, ticks: { color: textMuted, font: { family: 'Outfit, sans-serif' } } } } }
                });
            }

            if (document.getElementById('methodsChart') && data.salesMethods && data.salesMethods.methods) {
                charts.sales3 = new Chart(document.getElementById('methodsChart').getContext('2d'), {
                    type: 'doughnut',
                    data: { labels: data.salesMethods.methods, datasets: [{ data: data.salesMethods.amounts, backgroundColor: doughnutColors, borderColor: cardBg, borderWidth: 3 }] },
                    options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { tooltip: { backgroundColor: '#1F2937', titleColor: '#FAFAFA', bodyColor: secondary, borderColor: '#374151', borderWidth: 1, padding: 12 }, legend: { position: 'bottom', labels: { color: textMuted, font: { family: 'Outfit, sans-serif', size: 12 }, padding: 20 } } } }
                });
            }
        }

        function renderWorkshop() {
            if (charts.workshop1) return;
            if (document.getElementById('repairStatusChart') && data.repairStatus && data.repairStatus.labels) {
                const statusColors = {
                    'Recibido': '#0EA5A0', 
                    'En diagnóstico': '#5EEAD4', 
                    'En reparación': '#FBBF24',
                    'Listo': '#4ADE80', 
                    'Entregado': '#0EA5A0', 
                    'Cancelado': '#F87171'
                };
                const colors = data.repairStatus.labels.map(l => statusColors[l] || '#A1A1AA');
                charts.workshop1 = new Chart(document.getElementById('repairStatusChart').getContext('2d'), {
                    type: 'doughnut',
                    data: { labels: data.repairStatus.labels, datasets: [{ data: data.repairStatus.counts, backgroundColor: colors, borderColor: cardBg, borderWidth: 3 }] },
                    options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { tooltip: { backgroundColor: '#1F2937', titleColor: '#FAFAFA', bodyColor: secondary, borderColor: '#374151', borderWidth: 1, padding: 12 }, legend: { position: 'bottom', labels: { color: textMuted, font: { family: 'Outfit, sans-serif', size: 12 }, padding: 20 } } } }
                });
            }

            if (document.getElementById('repairByBrandChart') && data.repairBrand && data.repairBrand.labels) {
                charts.workshop2 = new Chart(document.getElementById('repairByBrandChart').getContext('2d'), {
                    type: 'bar',
                    data: { labels: data.repairBrand.labels, datasets: [{ label: 'Reparaciones', data: data.repairBrand.counts, backgroundColor: primaryAlpha, borderColor: primary, borderWidth: 1, borderRadius: 6 }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1F2937', titleColor: '#FAFAFA', bodyColor: secondary, borderColor: '#374151', borderWidth: 1, padding: 12 } }, scales: { y: { grid: { color: border }, ticks: { color: textMuted, precision: 0 } }, x: { grid: { display: false }, ticks: { color: textMuted, font: { family: 'Outfit, sans-serif' } } } } }
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
                        if(targetId === 'tab-taller') renderWorkshop();
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
