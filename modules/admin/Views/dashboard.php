<?php
// modules/admin/Views/dashboard.php

Layout::renderHead('NOKIA1100 | Admin Panel');
Layout::renderAdminSidebar('dashboard');
?>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen flex flex-col">
    <div class="max-w-7xl mx-auto w-full flex-grow">
        
        <header class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-display font-bold text-text-main tracking-tight flex items-center gap-3">
                    Resumen Operativo
                </h1>
                <p class="text-sm text-text-muted mt-2 font-medium">Vista general del rendimiento y operaciones de la sucursal</p>
            </div>
        </header>

        <?php // Banner visual ?>
        <section class="mb-10 w-full overflow-hidden rounded-3xl relative h-56 sm:h-64 flex items-center shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-border/30">
            <div class="absolute inset-0 z-0 bg-background">
                <?php // Imagen de fondo con mezcla ?>
                <img src="<?php echo BASE_URL; ?>/assets/img/nokia_store_banner.png" alt="Nokia Premium Store" class="w-full h-full object-cover opacity-30 mix-blend-lighten filter brightness-110 saturate-150 transition-all duration-1000 hover:scale-105 hover:opacity-40">
                <?php // Capa de gradiente para contraste ?>
                <div class="absolute inset-0 bg-gradient-to-r from-background via-background/80 to-transparent"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-background via-transparent to-transparent opacity-60"></div>
            </div>
            
            <div class="relative z-10 w-full p-8 md:p-12 flex flex-col justify-center h-full max-w-3xl">
                <h2 class="text-4xl md:text-5xl font-display font-bold text-text-main mb-4 drop-shadow-md tracking-tight">Bienvenido a Nokia1100</h2>
                <p class="text-sm md:text-base text-text-muted font-medium max-w-lg leading-relaxed">Supervisa todas las operaciones, controla el inventario en tiempo real y analiza el rendimiento corporativo con precisión.</p>
            </div>
        </section>

        <!-- Metrics -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            
            <div class="glass-card p-6 rounded-3xl flex flex-col justify-between group hover:border-primary/40 transition-all duration-300 hover:shadow-lg hover:-translate-y-1 relative overflow-hidden bg-surface/50 border-border/80">
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-primary/10 rounded-full blur-3xl transition-all group-hover:bg-primary/20"></div>
                <div class="flex justify-between items-start mb-6 relative z-10">
                    <div class="p-3 bg-gradient-to-br from-primary/20 to-primary/5 text-primary rounded-2xl border border-primary/10 shadow-inner">
                        <span class="material-symbols-outlined text-2xl">payments</span>
                    </div>
                    <span class="text-[10px] uppercase font-bold text-primary tracking-widest bg-primary/10 px-3 py-1.5 rounded-full border border-primary/10">Ventas Totales</span>
                </div>
                <div class="relative z-10">
                    <p class="text-xs text-text-muted mb-2 font-semibold uppercase tracking-wider">Ingresos Registrados</p>
                    <h3 class="text-4xl font-display font-bold text-text-main tracking-tight">$<span id="metric-ventas" class="skeleton px-8 rounded">0</span></h3>
                </div>
            </div>

            <div class="glass-card p-6 rounded-3xl flex flex-col justify-between group hover:border-secondary/40 transition-all duration-300 hover:shadow-lg hover:-translate-y-1 relative overflow-hidden bg-surface/50 border-border/80">
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-secondary/10 rounded-full blur-3xl transition-all group-hover:bg-secondary/20"></div>
                <div class="flex justify-between items-start mb-6 relative z-10">
                    <div class="p-3 bg-gradient-to-br from-secondary/20 to-secondary/5 text-secondary rounded-2xl border border-secondary/10 shadow-inner">
                        <span class="material-symbols-outlined text-2xl">group</span>
                    </div>
                    <span class="text-[10px] uppercase font-bold text-secondary tracking-widest bg-secondary/10 px-3 py-1.5 rounded-full border border-secondary/10">Cuentas</span>
                </div>
                <div class="relative z-10">
                    <p class="text-xs text-text-muted mb-2 font-semibold uppercase tracking-wider">Usuarios Activos</p>
                    <h3 class="text-4xl font-display font-bold text-text-main tracking-tight"><span id="metric-usuarios" class="skeleton px-6 rounded">0</span></h3>
                </div>
            </div>

            <div class="glass-card p-6 rounded-3xl flex flex-col justify-between cursor-pointer hover:border-red-500/40 transition-all duration-300 hover:shadow-lg hover:-translate-y-1 relative overflow-hidden bg-surface/50 border-border/80 group" onclick="window.location.href='<?php echo BASE_URL; ?>/modules/inventory/inventory.php?filter=low_stock';">
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-red-500/10 rounded-full blur-3xl transition-all group-hover:bg-red-500/20"></div>
                <div class="flex justify-between items-start mb-6 relative z-10">
                    <div class="p-3 bg-gradient-to-br from-red-500/20 to-red-500/5 text-red-500 rounded-2xl border border-red-500/10 shadow-inner">
                        <span class="material-symbols-outlined text-2xl">warning</span>
                    </div>
                    <div id="stock-badge-container"></div>
                </div>
                <div class="relative z-10">
                    <p class="text-xs text-text-muted mb-2 font-semibold uppercase tracking-wider">Alertas de Stock</p>
                    <h3 id="metric-stock-container" class="text-4xl font-display font-bold tracking-tight text-text-main">
                        <span id="metric-stock" class="skeleton px-4 rounded">0</span>
                    </h3>
                </div>
            </div>
            
        </section>
        
        <?php // Tablas de datos ?>
        <section class="grid grid-cols-1 gap-6">
            
            <!-- Ventas Recientes -->
            <div class="col-span-12 glass-card rounded-3xl overflow-hidden p-0 flex flex-col justify-between bg-surface/30 border-border/80 shadow-lg">
                <div>
                    <div class="p-6 md:p-8 border-b border-border/30 flex justify-between items-center bg-surface/50 backdrop-blur-sm">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-primary/10 rounded-xl text-primary border border-primary/20">
                                <span class="material-symbols-outlined text-xl">receipt_long</span>
                            </div>
                            <h3 class="font-display text-xl font-bold text-text-main tracking-tight">Ventas Recientes</h3>
                        </div>
                        <a href="<?php echo BASE_URL; ?>/modules/sales/sales.php" class="text-sm font-bold text-primary hover:text-primary-light hover:underline transition-colors flex items-center gap-1 bg-primary/5 px-4 py-2 rounded-full border border-primary/10">Ver todas <span class="material-symbols-outlined text-sm">arrow_forward</span></a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-surface/20">
                                    <th class="px-6 md:px-8 py-5 text-xs font-bold text-text-muted uppercase tracking-widest">ID Venta</th>
                                    <th class="px-6 md:px-8 py-5 text-xs font-bold text-text-muted uppercase tracking-widest">Fecha</th>
                                    <th class="px-6 md:px-8 py-5 text-xs font-bold text-text-muted uppercase tracking-widest">Método</th>
                                    <th class="px-6 md:px-8 py-5 text-xs font-bold text-text-muted uppercase tracking-widest text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/30">
                                <!-- Skeleton Rows -->
                                <tr id="sales-skeleton-1" class="skeleton-row">
                                    <td class="px-6 md:px-8 py-5"><div class="skeleton h-4 w-16 rounded"></div></td>
                                    <td class="px-6 md:px-8 py-5"><div class="skeleton h-4 w-24 rounded"></div></td>
                                    <td class="px-6 md:px-8 py-5"><div class="skeleton h-6 w-12 rounded-full"></div></td>
                                    <td class="px-6 md:px-8 py-5 flex justify-end"><div class="skeleton h-4 w-16 rounded"></div></td>
                                </tr>
                                <tr id="sales-skeleton-2" class="skeleton-row">
                                    <td class="px-6 md:px-8 py-5"><div class="skeleton h-4 w-16 rounded"></div></td>
                                    <td class="px-6 md:px-8 py-5"><div class="skeleton h-4 w-24 rounded"></div></td>
                                    <td class="px-6 md:px-8 py-5"><div class="skeleton h-6 w-12 rounded-full"></div></td>
                                    <td class="px-6 md:px-8 py-5 flex justify-end"><div class="skeleton h-4 w-16 rounded"></div></td>
                                </tr>
                                <tr id="sales-skeleton-3" class="skeleton-row">
                                    <td class="px-6 md:px-8 py-5"><div class="skeleton h-4 w-16 rounded"></div></td>
                                    <td class="px-6 md:px-8 py-5"><div class="skeleton h-4 w-24 rounded"></div></td>
                                    <td class="px-6 md:px-8 py-5"><div class="skeleton h-6 w-12 rounded-full"></div></td>
                                    <td class="px-6 md:px-8 py-5 flex justify-end"><div class="skeleton h-4 w-16 rounded"></div></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </section>

    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    fetch('<?php echo BASE_URL; ?>/modules/admin/index.php?action=get_dashboard_data')
        .then(response => response.json())
        .then(data => {
            // Actualizar Métricas (Remover skeleton y asignar valor)
            const mVentas = document.getElementById('metric-ventas');
            mVentas.textContent = data.totalVentas;
            mVentas.classList.remove('skeleton', 'px-8', 'rounded');

            const mUsuarios = document.getElementById('metric-usuarios');
            mUsuarios.textContent = data.totalUsuarios;
            mUsuarios.classList.remove('skeleton', 'px-6', 'rounded');

            const mStock = document.getElementById('metric-stock');
            mStock.textContent = data.bajoStock;
            mStock.classList.remove('skeleton', 'px-4', 'rounded');

            // Actualizar diseño de Alerta de Stock si hay bajo stock
            if (parseInt(data.bajoStock) > 0) {
                const stockContainer = document.getElementById('metric-stock-container');
                stockContainer.classList.remove('text-text-main');
                stockContainer.classList.add('text-red-400', 'drop-shadow-[0_0_8px_rgba(248,113,113,0.5)]');

                document.getElementById('stock-badge-container').innerHTML = `<span class="text-[10px] uppercase font-bold text-red-400 tracking-widest bg-red-500/10 px-3 py-1.5 rounded-full border border-red-500/20 pulse-badge">Atención</span>`;
            }

            // Actualizar Tabla de Ventas Recientes
            const tbody = document.querySelector('tbody');
            // Remover skeletons
            document.querySelectorAll('.skeleton-row').forEach(row => row.remove());

            if (data.ventasRecientes && data.ventasRecientes.length > 0) {
                data.ventasRecientes.forEach(v => {
                    const dateObj = new Date(v.fecha);
                    const dateStr = dateObj.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).replace(',', '');
                    const metodo = v.metodo_de_pago ? v.metodo_de_pago : 'N/A';
                    const totalFormatted = parseFloat(v.total).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-surface/50 transition-colors group';
                    tr.innerHTML = `
                        <td class='px-6 md:px-8 py-5 text-sm font-bold text-primary'>#TX-${v.id_venta}</td>
                        <td class='px-6 md:px-8 py-5 text-sm text-text-muted group-hover:text-text-main transition-colors font-medium'>${dateStr}</td>
                        <td class='px-6 md:px-8 py-5 text-sm'>
                            <span class='px-3 py-1.5 rounded-full border border-border/30 bg-surface/80 text-[10px] font-bold text-text-muted uppercase tracking-widest shadow-sm'>${metodo}</span>
                        </td>
                        <td class='px-6 md:px-8 py-5 text-sm font-bold text-text-main text-right'>$${totalFormatted}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = `<tr>
                    <td colspan='4' class='px-6 py-16 text-center'>
                        <div class='flex flex-col items-center justify-center gap-3'>
                            <span class='material-symbols-outlined text-5xl text-text-muted/30'>receipt_long</span>
                            <p class='text-sm font-medium text-text-muted'>No hay transacciones registradas</p>
                        </div>
                    </td>
                </tr>`;
            }
        })
        .catch(err => console.error('Error fetching dashboard data:', err));
});
</script>

<?php Layout::renderFooter(); ?>
