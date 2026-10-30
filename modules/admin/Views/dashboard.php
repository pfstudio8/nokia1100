<?php
Layout::renderHead('NOKIA1100 | Admin Panel');
Layout::renderAdminSidebar('dashboard');
?>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen flex flex-col bg-[#eeebe3]">
    <div class="max-w-7xl mx-auto w-full flex-grow">
        
        <header class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl sm:text-3xl font-display font-bold text-[#15171a] tracking-tight">
                    Resumen Operativo
                </h1>
                <p class="text-xs text-[#5c6165] mt-1 font-medium">Supervisión integral de rendimiento, flujo comercial y auditoría de inventario</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?php echo BASE_URL; ?>/modules/inventory/index.php" class="btn btn-lcd text-xs">
                    <span class="material-symbols-outlined text-[16px]">inventory_2</span>
                    Gestionar Inventario
                </a>
            </div>
        </header>

        <?php // Banner visual estilo Nokia 1100 ?>
        <section class="mb-8 w-full rounded-[2px] bg-[#15181a] border border-[#222729] shadow-sm relative overflow-hidden">
            <div class="p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                <div class="max-w-2xl">
                    <h2 class="text-xl sm:text-2xl font-display font-bold text-[#e9e6db] mb-2 tracking-tight">Bienvenido a Nokia 1100</h2>
                    <p class="text-xs sm:text-sm text-[#9ba19f] font-normal leading-relaxed">
                        Controla el stock en tiempo real, supervisa ventas registradas y audita las actividades operativas de la sucursal con máxima precisión técnica.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5 self-start md:self-auto">
                    <div class="px-3.5 py-2.5 bg-[#222729] border border-[#30363a] rounded-[2px] min-w-[124px]">
                        <div class="flex items-center gap-1.5 font-mono text-[10px] text-[#9ba19f] uppercase tracking-wider">
                            <span class="material-symbols-outlined text-[13px] text-[#c6e24a]">calendar_today</span>
                            <span>Fecha</span>
                        </div>
                        <div class="font-mono text-sm font-bold text-[#e9e6db] mt-0.5 tabular-nums" data-live-date><?php echo date('d/m/Y'); ?></div>
                    </div>
                    <div class="px-3.5 py-2.5 bg-[#222729] border border-[#30363a] rounded-[2px] min-w-[124px]">
                        <div class="flex items-center gap-1.5 font-mono text-[10px] text-[#9ba19f] uppercase tracking-wider">
                            <span class="material-symbols-outlined text-[13px] text-[#c6e24a]">schedule</span>
                            <span>Hora</span>
                        </div>
                        <div class="font-mono text-sm font-bold text-[#c6e24a] mt-0.5 tabular-nums" data-live-clock><?php echo date('H:i:s'); ?></div>
                    </div>
                </div>
            </div>
        </section>

        <?php // Tarjetas de Métricas KPI ?>
        <section class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
            
            <?php // Ventas Totales ?>
            <div class="bg-[#f8f6f0] p-5 rounded-[2px] border border-[#b5af9c] flex flex-col justify-between shadow-sm">
                <div class="flex justify-between items-start mb-4">
                    <span class="font-mono text-[11px] font-bold text-[#5c6165] uppercase tracking-wider">Ingresos Registrados</span>
                    <span class="material-symbols-outlined text-[20px] text-[#15171a]">payments</span>
                </div>
                <div>
                    <div class="text-3xl font-display font-bold text-[#15171a] font-mono tracking-tight">$<span id="metric-ventas" class="skeleton px-6 rounded-[2px]">0</span></div>
                    <p class="text-[11px] font-mono text-[#5c6165] mt-1">Total acumulado de ventas</p>
                </div>
            </div>

            <?php // Usuarios Activos ?>
            <div class="bg-[#f8f6f0] p-5 rounded-[2px] border border-[#b5af9c] flex flex-col justify-between shadow-sm">
                <div class="flex justify-between items-start mb-4">
                    <span class="font-mono text-[11px] font-bold text-[#5c6165] uppercase tracking-wider">Usuarios Activos</span>
                    <span class="material-symbols-outlined text-[20px] text-[#15171a]">group</span>
                </div>
                <div>
                    <div class="text-3xl font-display font-bold text-[#15171a] font-mono tracking-tight"><span id="metric-usuarios" class="skeleton px-5 rounded-[2px]">0</span></div>
                    <p class="text-[11px] font-mono text-[#5c6165] mt-1">Cuentas habilitadas</p>
                </div>
            </div>

            <?php // Alertas de Stock ?>
            <div class="bg-[#f8f6f0] p-5 rounded-[2px] border border-[#b5af9c] flex flex-col justify-between shadow-sm cursor-pointer hover:bg-[#e5e1d6]/50 transition-colors" onclick="window.location.href='<?php echo BASE_URL; ?>/modules/inventory/index.php?filter=low_stock';" title="Ver artículos con bajo stock">
                <div class="flex justify-between items-start mb-4">
                    <span class="font-mono text-[11px] font-bold text-[#5c6165] uppercase tracking-wider">Alertas de Stock</span>
                    <div id="stock-badge-container">
                        <span class="material-symbols-outlined text-[20px] text-[#15171a]">warning</span>
                    </div>
                </div>
                <div>
                    <div id="metric-stock-container" class="text-3xl font-display font-bold text-[#15171a] font-mono tracking-tight">
                        <span id="metric-stock" class="skeleton px-4 rounded-[2px]">0</span>
                    </div>
                    <p class="text-[11px] font-mono text-[#5c6165] mt-1">Artículos con bajo stock</p>
                </div>
            </div>
            
        </section>
        
        <?php // Ventas Recientes ?>
        <section class="bg-[#f8f6f0] rounded-[2px] border border-[#b5af9c] shadow-sm overflow-hidden">
            <div class="p-5 border-b border-[#b5af9c] bg-[#e5e1d6]/60 flex justify-between items-center">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-[20px] text-[#15171a]">receipt_long</span>
                    <h3 class="font-display text-base font-bold text-[#15171a] tracking-tight">Ventas Recientes</h3>
                </div>
                <a href="<?php echo BASE_URL; ?>/modules/sales/index.php" class="text-xs font-semibold text-[#15171a] hover:underline flex items-center gap-1">
                    Ver todas <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-[#e5e1d6] border-b border-[#b5af9c]">
                            <th class="px-6 py-3.5 text-xs font-mono font-bold text-[#5c6165] uppercase tracking-wider">ID Venta</th>
                            <th class="px-6 py-3.5 text-xs font-mono font-bold text-[#5c6165] uppercase tracking-wider">Fecha / Hora</th>
                            <th class="px-6 py-3.5 text-xs font-mono font-bold text-[#5c6165] uppercase tracking-wider">Método de Pago</th>
                            <th class="px-6 py-3.5 text-xs font-mono font-bold text-[#5c6165] uppercase tracking-wider text-right">Monto Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#d3cebf]">
                        <?php // Skeleton Rows ?>
                        <tr id="sales-skeleton-1" class="skeleton-row">
                            <td class="px-6 py-4"><div class="skeleton h-3.5 w-16 rounded-[2px]"></div></td>
                            <td class="px-6 py-4"><div class="skeleton h-3.5 w-24 rounded-[2px]"></div></td>
                            <td class="px-6 py-4"><div class="skeleton h-5 w-16 rounded-[2px]"></div></td>
                            <td class="px-6 py-4 flex justify-end"><div class="skeleton h-3.5 w-16 rounded-[2px]"></div></td>
                        </tr>
                        <tr id="sales-skeleton-2" class="skeleton-row">
                            <td class="px-6 py-4"><div class="skeleton h-3.5 w-16 rounded-[2px]"></div></td>
                            <td class="px-6 py-4"><div class="skeleton h-3.5 w-24 rounded-[2px]"></div></td>
                            <td class="px-6 py-4"><div class="skeleton h-5 w-16 rounded-[2px]"></div></td>
                            <td class="px-6 py-4 flex justify-end"><div class="skeleton h-3.5 w-16 rounded-[2px]"></div></td>
                        </tr>
                        <tr id="sales-skeleton-3" class="skeleton-row">
                            <td class="px-6 py-4"><div class="skeleton h-3.5 w-16 rounded-[2px]"></div></td>
                            <td class="px-6 py-4"><div class="skeleton h-3.5 w-24 rounded-[2px]"></div></td>
                            <td class="px-6 py-4"><div class="skeleton h-5 w-16 rounded-[2px]"></div></td>
                            <td class="px-6 py-4 flex justify-end"><div class="skeleton h-3.5 w-16 rounded-[2px]"></div></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</main>

<script>
function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[ch]));
}

function getPaymentBadge(method) {
    const raw = String(method || '').trim() || 'N/A';
    const m = raw.toLowerCase();
    let badgeClass = 'badge-payment badge-payment-default';
    if (m.includes('efectivo') || m.includes('contado')) {
        badgeClass = 'badge-payment badge-payment-efectivo';
    } else if (m.includes('tarjeta') || m.includes('debito') || m.includes('débito') || m.includes('credito') || m.includes('crédito') || m.includes('posnet')) {
        badgeClass = 'badge-payment badge-payment-tarjeta';
    } else if (m.includes('transf') || m.includes('banco') || m.includes('deposito') || m.includes('depósito') || m.includes('cbu') || m.includes('cvu') || m.includes('alias')) {
        badgeClass = 'badge-payment badge-payment-transferencia';
    } else if (m.includes('mercado') || /\bmp\b/.test(m) || m.includes('qr') || m.includes('billetera')) {
        badgeClass = 'badge-payment badge-payment-mp';
    }
    return `<span class="${badgeClass}">${escapeHtml(raw)}</span>`;
}

document.addEventListener('DOMContentLoaded', () => {
    fetch('<?php echo BASE_URL; ?>/modules/admin/index.php?action=get_dashboard_data')
        .then(response => {
            if (response.status === 401 || response.status === 403) {
                window.location.href = '<?php echo BASE_URL; ?>/index.php?error=' + encodeURIComponent('Sesión expirada o no autorizada');
                return null;
            }
            if (!response.ok) {
                throw new Error('HTTP error ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (!data) return;
            // Actualizar Métricas (Remover skeleton y asignar valor)
            const mVentas = document.getElementById('metric-ventas');
            mVentas.textContent = data.totalVentas;
            mVentas.classList.remove('skeleton', 'px-6', 'rounded-[2px]');

            const mUsuarios = document.getElementById('metric-usuarios');
            mUsuarios.textContent = data.totalUsuarios;
            mUsuarios.classList.remove('skeleton', 'px-5', 'rounded-[2px]');

            const mStock = document.getElementById('metric-stock');
            mStock.textContent = data.bajoStock;
            mStock.classList.remove('skeleton', 'px-4', 'rounded-[2px]');

            // Actualizar diseño de Alerta de Stock si hay bajo stock
            if (parseInt(data.bajoStock) > 0) {
                const stockContainer = document.getElementById('metric-stock-container');
                stockContainer.classList.add('text-[#a82a1d]');

                document.getElementById('stock-badge-container').innerHTML = `<span class="text-[10px] font-mono uppercase font-bold text-[#a82a1d] tracking-wider bg-[#f6dcd7] px-2 py-0.5 rounded-[2px] border border-[#a82a1d]/30">Atención</span>`;
            }

            // Actualizar Tabla de Ventas Recientes
            const tbody = document.querySelector('tbody');
            // Remover skeletons
            document.querySelectorAll('.skeleton-row').forEach(row => row.remove());

            if (data.ventasRecientes && data.ventasRecientes.length > 0) {
                data.ventasRecientes.forEach(v => {
                    const dateObj = new Date(v.fecha);
                    const dateStr = dateObj.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).replace(',', '');
                    const totalFormatted = parseFloat(v.total).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-[#e5e1d6]/50 transition-colors border-b border-[#d3cebf]';
                    tr.innerHTML = `
                        <td class='px-6 py-3.5 text-xs font-mono font-bold text-[#15171a]'>#TX-${escapeHtml(v.id_venta)}</td>
                        <td class='px-6 py-3.5 text-xs text-[#464b4f] font-medium'>${dateStr}</td>
                        <td class='px-6 py-3.5 text-xs'>${getPaymentBadge(v.metodo_de_pago)}</td>
                        <td class='px-6 py-3.5 text-xs font-mono font-bold text-[#15171a] text-right'>$${totalFormatted}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = `<tr>
                    <td colspan='4' class='px-6 py-12 text-center'>
                        <div class='flex flex-col items-center justify-center gap-2'>
                            <span class='material-symbols-outlined text-4xl text-[#5c6165]/40'>receipt_long</span>
                            <p class='text-xs font-mono text-[#5c6165]'>No hay transacciones registradas</p>
                        </div>
                    </td>
                </tr>`;
            }
        })
        .catch(err => console.error('Error fetching dashboard data:', err));
});
</script>

<?php Layout::renderFooter(); ?>
