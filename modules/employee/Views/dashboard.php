<?php
// modules/employee/Views/dashboard.php - Nokia 1100 Design System

// Variables inyectadas por EmployeeController::dashboard()
// @var float $total_hoy
// @var int $trans_hoy
// @var array $ventas_recientes
// @var array $productos
// @var int $ready_to_charge
$total_hoy = $total_hoy ?? 0.0;
$trans_hoy = $trans_hoy ?? 0;
$ventas_recientes = $ventas_recientes ?? [];
$productos = $productos ?? [];
$ready_to_charge = $ready_to_charge ?? 0;

$username = $_SESSION['username'] ?? 'Operario';
$hora = (int)date('G');
if ($hora >= 5 && $hora < 12) {
    $saludo = 'Buen día';
    $iconHora = 'light_mode';
    $iconColor = 'text-[#8a5200]';
} elseif ($hora >= 12 && $hora < 20) {
    $saludo = 'Buenas tardes';
    $iconHora = 'wb_twilight';
    $iconColor = 'text-[#8a5200]';
} else {
    $saludo = 'Buenas noches';
    $iconHora = 'nights_stay';
    $iconColor = 'text-[#15171a]';
}

Layout::renderHead('NOKIA1100 | Centro de Operaciones');
Layout::renderEmployeeSidebar('dashboard');
?>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen bg-[#eeebe3] text-[#15171a]">

    <?php // Saludo superior ?>
    <div class="relative z-20 flex items-center gap-2 mb-4">
        <span class="material-symbols-outlined <?php echo $iconColor; ?> text-[18px] select-none"><?php echo $iconHora; ?></span>
        <p class="text-sm sm:text-base font-sans tracking-tight text-[#15171a]">
            <span class="text-[#5c6165]"><?php echo $saludo; ?>,</span>
            <span class="font-bold text-[#15171a] font-mono ml-0.5"><?php echo htmlspecialchars($username); ?></span>
        </p>
    </div>

    <header class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-display font-bold text-[#15171a] tracking-tight">Centro de Operaciones</h1>
            <p class="text-xs text-[#5c6165] mt-0.5 font-medium">Gestión ágil de transacciones, cobros de taller y disponibilidad de catálogo</p>
        </div>
        <?php if ($_SESSION['role'] !== 'guest'): ?>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="<?php echo BASE_URL; ?>/modules/sales/index.php?action=repairs_to_charge"
                    class="btn text-xs">
                    <span class="material-symbols-outlined text-[16px]">build_circle</span>
                    Cobrar Reparaciones
                    <?php if ($ready_to_charge > 0): ?>
                        <span class="px-1.5 py-0.2 rounded-[2px] text-[10px] font-mono font-bold bg-[#c6e24a] text-[#15171a] ml-1"><?php echo $ready_to_charge; ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo BASE_URL; ?>/modules/sales/index.php?action=new_sale"
                    class="btn btn-lcd text-xs">
                    <span class="material-symbols-outlined text-[16px]">point_of_sale</span>
                    Nueva Venta
                </a>
            </div>
        <?php endif; ?>
    </header>

    <?php if ($ready_to_charge > 0): ?>
        <div class="p-4 rounded-[2px] mb-6 border border-[#8a5200] border-l-4 border-l-[#8a5200] bg-[#f6e8c8] flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-[2px] bg-[#8a5200] text-[#f8f6f0] flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-[20px]">notifications_active</span>
                </div>
                <div>
                    <h3 class="text-sm font-display font-bold text-[#8a5200] flex items-center gap-2">
                        <span>Equipos Listos para Cobrar y Entregar</span>
                        <span class="px-1.5 py-0.2 rounded-[2px] text-[10px] font-mono font-bold bg-[#8a5200] text-[#f8f6f0]"><?php echo $ready_to_charge; ?></span>
                    </h3>
                    <p class="text-xs text-[#8a5200]/90 mt-0.5">El taller técnico finalizó reparaciones que están pendientes de cobro en caja y entrega al cliente.</p>
                </div>
            </div>
            <a href="<?php echo BASE_URL; ?>/modules/sales/index.php?action=repairs_to_charge" class="btn btn-lcd text-xs whitespace-nowrap">
                <span class="material-symbols-outlined text-[15px]">point_of_sale</span>
                Cobrar Reparaciones
            </a>
        </div>
    <?php endif; ?>

    <?php // Banner visual compacto estilo Nokia 1100 ?>
    <section class="mb-6 w-full rounded-[2px] bg-[#15181a] border border-[#222729] shadow-sm relative overflow-hidden">
        <div class="relative z-10 w-full py-4 px-5 sm:px-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#c6e24a]"></span>
                    <span class="text-[10px] font-mono font-bold text-[#c6e24a] tracking-wider uppercase">Punto de Venta Activo</span>
                </div>
                <h2 class="text-lg font-display font-bold text-[#e9e6db] tracking-tight leading-tight">Gestión Inmediata en Mostrador</h2>
                <p class="text-xs text-[#9ba19f] mt-0.5 max-w-xl">Acceso directo a artículos de alta rotación y registro rápido de tickets comerciales.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
                <div class="px-3 py-2 bg-[#222729] border border-[#30363a] rounded-[2px]">
                    <div class="flex items-center gap-1 font-mono text-[9px] text-[#9ba19f] uppercase tracking-wider">
                        <span class="material-symbols-outlined text-[12px] text-[#c6e24a]">calendar_today</span>
                        <span>Fecha</span>
                    </div>
                    <div class="font-mono text-xs font-bold text-[#e9e6db] mt-0.5 tabular-nums" data-live-date><?php echo date('d/m/Y'); ?></div>
                </div>
                <div class="px-3 py-2 bg-[#222729] border border-[#30363a] rounded-[2px]">
                    <div class="flex items-center gap-1 font-mono text-[9px] text-[#9ba19f] uppercase tracking-wider">
                        <span class="material-symbols-outlined text-[12px] text-[#c6e24a]">schedule</span>
                        <span>Hora</span>
                    </div>
                    <div class="font-mono text-xs font-bold text-[#c6e24a] mt-0.5 tabular-nums" data-live-clock><?php echo date('H:i:s'); ?></div>
                </div>
            </div>
        </div>
    </section>

    <?php // Rendimiento diario ?>
    <?php if ($_SESSION['role'] !== 'guest'): ?>
        <section class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-[#f8f6f0] p-5 rounded-[2px] border border-[#b5af9c] flex flex-col justify-between shadow-sm">
                <div class="flex justify-between items-start mb-3">
                    <span class="font-mono text-[11px] font-bold uppercase tracking-wider text-[#5c6165]">Ingresos del Día</span>
                    <span class="material-symbols-outlined text-[#15171a] text-xl">trending_up</span>
                </div>
                <div class="text-3xl font-display font-bold font-mono text-[#15171a]">$<?php echo number_format($total_hoy, 2); ?></div>
                <p class="text-[11px] font-mono text-[#5c6165] mt-1">Caja comercial de hoy</p>
            </div>
            <div class="bg-[#f8f6f0] p-5 rounded-[2px] border border-[#b5af9c] flex flex-col justify-between shadow-sm">
                <div class="flex justify-between items-start mb-3">
                    <span class="font-mono text-[11px] font-bold uppercase tracking-wider text-[#5c6165]">Ventas de Hoy</span>
                    <span class="material-symbols-outlined text-[#15171a] text-xl">receipt_long</span>
                </div>
                <div class="text-3xl font-display font-bold font-mono text-[#15171a]"><?php echo number_format($trans_hoy); ?></div>
                <p class="text-[11px] font-mono text-[#5c6165] mt-1">Operaciones procesadas</p>
            </div>
        </section>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-6">
        <?php // Contenido principal: catálogo y transacciones ?>
        <div class="space-y-6">

            <?php // Catálogo de Venta Rápida ?>
            <div class="space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-display font-bold text-base text-[#15171a] tracking-tight">Catálogo de Venta Rápida</h3>
                            <span class="px-2 py-0.5 rounded-[2px] text-[10px] font-mono font-bold bg-[#15181a] text-[#c6e24a]">Frecuentes</span>
                        </div>
                        <p class="text-xs text-[#5c6165] mt-0.5">Artículos de mayor disponibilidad para atención ágil en caja</p>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/modules/inventory/index.php"
                        class="btn text-xs self-start sm:self-auto">
                        <span>Ver inventario completo</span>
                        <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3.5">
                    <?php
                    if (count($productos) > 0) {
                        foreach ($productos as $p) {
                            $prod_id = (int)($p['id_producto'] ?? 0);
                            $nombre = htmlspecialchars(trim((string)($p['nombre'] ?? '')));
                            $marca = htmlspecialchars(trim((string)($p['marca'] ?? '')));
                            $modelo = htmlspecialchars(trim((string)($p['modelo'] ?? '')));
                            $prod_precio = number_format((float)($p['precio'] ?? 0), 2);
                            $cat = mb_strtolower((string)($p['categoria'] ?? ''));
                            $cat_label = htmlspecialchars((string)($p['categoria'] ?? 'General'));
                            $stock = (int)($p['stock'] ?? 0);

                            // Subtítulo con marca y modelo específico
                            $subTitle = trim("{$marca} {$modelo}");
                            if (empty($subTitle)) {
                                $subTitle = $cat_label;
                            }

                            // Colores e íconos por categoría
                            $icon = 'inventory_2';
                            if (strpos($cat, 'celular') !== false || strpos($cat, 'smartphone') !== false || strpos($cat, 'telefono') !== false) {
                                $icon = 'smartphone';
                            } elseif (strpos($cat, 'funda') !== false || strpos($cat, 'case') !== false || strpos($cat, 'protec') !== false) {
                                $icon = 'shield';
                            } elseif (strpos($cat, 'audio') !== false || strpos($cat, 'auricular') !== false || strpos($cat, 'parlante') !== false) {
                                $icon = 'headphones';
                            } elseif (strpos($cat, 'perifer') !== false || strpos($cat, 'mouse') !== false || strpos($cat, 'teclado') !== false) {
                                $icon = 'devices';
                            } elseif (strpos($cat, 'carga') !== false || strpos($cat, 'cable') !== false || strpos($cat, 'bateria') !== false) {
                                $icon = 'power';
                            }

                            $stock_min = (int)($p['stock_minimo'] ?? 5);
                            if ($stock <= 0) {
                                $stockBadge = "<span class='inline-flex items-center gap-1 text-[10px] font-mono font-bold text-[#cc1f1f] bg-[#fde2e2] border border-[#dc2626]/50 px-1.5 py-0.5 rounded-[2px]'><span class='w-1 h-1 rounded-full bg-[#cc1f1f]'></span>Sin stock</span>";
                            } elseif ($stock <= $stock_min) {
                                $stockBadge = "<span class='inline-flex items-center gap-1 text-[10px] font-mono font-bold text-[#a87700] bg-[#fcf3cf] border border-[#d49a00]/60 px-1.5 py-0.5 rounded-[2px]'><span class='w-1 h-1 rounded-full bg-[#dea000]'></span>{$stock} bajo</span>";
                            } else {
                                $stockBadge = "<span class='inline-flex items-center gap-1 text-[10px] font-mono font-bold text-[#1d6a38] bg-[#dfeee3] border border-[#1d6a38]/30 px-1.5 py-0.5 rounded-[2px]'><span class='w-1 h-1 rounded-full bg-[#1d6a38]'></span>{$stock} disp.</span>";
                            }

                            $url = BASE_URL . "/modules/sales/index.php?action=new_sale&product_id={$prod_id}";

                            echo "
                            <div class='bg-[#f8f6f0] hover:bg-[#e5e1d6]/50 border border-[#b5af9c] rounded-[2px] p-4 flex flex-col justify-between transition-colors shadow-sm' title='Iniciar venta de {$nombre} {$subTitle}'>
                                <div>
                                    <div class='flex items-center justify-between gap-2 mb-2.5'>
                                        <span class='inline-flex items-center gap-1 px-1.5 py-0.5 rounded-[2px] text-[10px] font-mono font-bold uppercase tracking-wider bg-[#e5e1d6] border border-[#b5af9c] text-[#15171a]'>
                                            <span class='material-symbols-outlined text-[13px]'>{$icon}</span>
                                            {$cat_label}
                                        </span>
                                        {$stockBadge}
                                    </div>

                                    <div class='mb-3'>
                                        <h4 class='text-sm font-display font-bold text-[#15171a] leading-snug truncate'>
                                            {$nombre}
                                        </h4>
                                        <p class='text-xs text-[#5c6165] mt-0.5 font-medium truncate' title='{$subTitle}'>
                                            {$subTitle}
                                        </p>
                                    </div>
                                </div>

                                <div class='pt-2.5 border-t border-[#d3cebf] flex items-center justify-between mt-auto'>
                                    <div>
                                        <span class='text-[9px] font-mono uppercase font-bold text-[#5c6165] tracking-wider block'>Precio</span>
                                        <span class='text-base font-bold text-[#15171a] font-mono tracking-tight'>\${$prod_precio}</span>
                                    </div>
                                    <a href='{$url}' class='btn btn-lcd text-xs py-1 px-2.5 rounded-[2px] flex items-center gap-1'>
                                        <span>Vender</span>
                                        <span class='material-symbols-outlined text-[14px]'>add_shopping_cart</span>
                                    </a>
                                </div>
                            </div>";
                        }
                    } else {
                        echo "<div class='col-span-full p-6 rounded-[2px] border border-dashed border-[#b5af9c] bg-[#f8f6f0] text-center text-xs font-mono text-[#5c6165]'>No hay productos registrados en catálogo</div>";
                    }
                    ?>
                </div>
            </div>

            <?php // Últimas transacciones ?>
            <?php if ($_SESSION['role'] !== 'guest'): ?>
                <div class="space-y-3">
                    <h3 class="font-display font-bold text-base text-[#15171a] tracking-tight">Últimas Transacciones</h3>
                    <div class="bg-[#f8f6f0] rounded-[2px] border border-[#b5af9c] overflow-hidden shadow-sm">
                        <div class="divide-y divide-[#d3cebf]">
                            <?php
                            if (count($ventas_recientes) > 0) {
                                foreach ($ventas_recientes as $v) {
                                    $monto = number_format((float)($v['total'] ?? 0), 2);
                                    $paymentBadgeHtml = Layout::renderPaymentBadge($v['metodo_de_pago'] ?? 'N/A');
                                    $id_tx = (int)($v['id_venta'] ?? 0);
                                    echo "<div class='p-3.5 hover:bg-[#e5e1d6]/50 transition-colors flex justify-between items-center'>
                                            <div>
                                                <div class='text-xs font-mono font-bold text-[#15171a]'>#TX-{$id_tx}</div>
                                                <div class='mt-1'>{$paymentBadgeHtml}</div>
                                            </div>
                                            <div class='text-sm font-mono font-bold text-[#15171a] text-right'>\${$monto}</div>
                                        </div>";
                                }
                            } else {
                                echo "<div class='p-4 text-xs font-mono text-[#5c6165] text-center'>No hay transacciones registradas</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

</main>
<?php Layout::renderFooter(); ?>