<?php
// modules/employee/Views/dashboard.php

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
    $iconColor = 'text-amber-400';
} elseif ($hora >= 12 && $hora < 20) {
    $saludo = 'Buenas tardes';
    $iconHora = 'wb_twilight';
    $iconColor = 'text-amber-500';
} else {
    $saludo = 'Buenas noches';
    $iconHora = 'nights_stay';
    $iconColor = 'text-secondary';
}

Layout::renderHead('NOKIA1100 | Centro de Operaciones');
Layout::renderEmployeeSidebar('dashboard');
?>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">

    <?php // Saludo superior estilizado al nivel del usuario (localStorage) ?>
    <div class="relative z-20 flex items-center gap-2 mb-4 md:-mt-[68px] md:mb-7">
        <span class="material-symbols-outlined <?php echo $iconColor; ?> text-[20px] select-none"><?php echo $iconHora; ?></span>
        <p class="text-base sm:text-lg font-display tracking-tight text-text-main">
            <span class="text-text-muted font-normal"><?php echo $saludo; ?>,</span>
            <span class="font-bold text-transparent bg-clip-text bg-gradient-to-r from-primary to-secondary"><?php echo htmlspecialchars($username); ?></span>
        </p>
    </div>

    <header class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-6">
        <div>
            <h1 class="text-3xl font-display font-medium text-text-main tracking-tight">Centro de <span
                    class="text-primary">Operaciones</span></h1>
            <p class="text-text-muted text-xs uppercase tracking-widest mt-1 font-semibold">Terminal de Empleado</p>
        </div>
        <?php if ($_SESSION['role'] !== 'guest'): ?>
            <div class="flex items-center gap-3">
                <a href="<?php echo BASE_URL; ?>/modules/sales/index.php?action=repairs_to_charge"
                    class="bg-surface hover:bg-surface-hover border border-border text-text-main font-medium px-4 py-2.5 rounded-lg flex items-center gap-2 transition-all">
                    <span class="material-symbols-outlined text-primary text-[20px]">build_circle</span>
                    Cobrar Reparaciones
                    <?php if ($ready_to_charge > 0): ?>
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30"><?php echo $ready_to_charge; ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo BASE_URL; ?>/modules/sales/index.php?action=new_sale"
                    class="bg-primary hover:bg-primary/90 text-background font-bold px-6 py-2.5 rounded-lg flex items-center gap-2 transition-all shadow-lg hover:shadow-primary/20 hover:-translate-y-0.5">
                    <span class="material-symbols-outlined">point_of_sale</span>
                    Nueva Venta
                </a>
            </div>
        <?php endif; ?>
    </header>

    <?php if ($ready_to_charge > 0): ?>
        <div class="glass-card p-5 rounded-2xl mb-6 border border-amber-500/40 bg-amber-500/10 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 animate-in fade-in slide-in-from-top-2 duration-300">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-[28px]">notifications_active</span>
                </div>
                <div>
                    <h3 class="text-base font-display font-medium text-text-main flex items-center gap-2">
                        <span>Equipos Listos para Cobrar y Entregar</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30"><?php echo $ready_to_charge; ?></span>
                    </h3>
                    <p class="text-xs text-text-muted mt-0.5">El taller técnico finalizó reparaciones que están esperando cobro en caja y retiro del cliente.</p>
                </div>
            </div>
            <a href="<?php echo BASE_URL; ?>/modules/sales/index.php?action=repairs_to_charge" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-black font-semibold text-xs transition-colors flex items-center gap-1.5 shadow-lg shadow-amber-500/20 whitespace-nowrap">
                <span class="material-symbols-outlined text-[16px]">point_of_sale</span>
                Cobrar Reparaciones
            </a>
        </div>
    <?php endif; ?>

    <?php // Banner visual compacto ?>
    <section class="mb-6 w-full overflow-hidden rounded-2xl glass-card relative border border-border/70 shadow-md">
        <div class="absolute inset-0 z-0 bg-surface/60">
            <img src="<?php echo BASE_URL; ?>/assets/img/nokia_store_banner.png" alt="Nokia Premium Store"
                class="w-full h-full object-cover opacity-15 mix-blend-luminosity filter blur-[0.5px]">
            <div class="absolute inset-0 bg-gradient-to-r from-background via-background/90 to-transparent"></div>
        </div>

        <div class="relative z-10 w-full py-4 px-5 sm:px-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-[10px] font-bold text-primary tracking-widest uppercase">Punto de Venta Activo</span>
                </div>
                <h2 class="text-lg sm:text-xl font-display font-bold text-text-main tracking-tight leading-tight">Gestiona ventas con agilidad</h2>
                <p class="text-xs text-text-muted font-normal mt-0.5 max-w-xl">Accede rápido al catálogo y registra las transacciones del día.</p>
            </div>
            <div class="hidden md:flex items-center gap-2 text-right">
                <span class="text-[11px] text-text-muted font-medium bg-surface/80 border border-border px-3 py-1.5 rounded-lg flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[15px] text-primary">schedule</span>
                    <span><?php echo date('d/m/Y'); ?></span>
                </span>
            </div>
        </div>
    </section>

    <?php // Rendimiento diario ?>
    <?php if ($_SESSION['role'] !== 'guest'): ?>
        <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div
                class="glass-card p-6 rounded-2xl flex flex-col justify-between group hover:border-border/80 transition-all">
                <div class="flex justify-between items-start mb-4">
                    <span class="text-[10px] font-semibold uppercase tracking-widest text-text-muted">Ingresos del
                        Día</span>
                    <span class="material-symbols-outlined text-primary text-xl">trending_up</span>
                </div>
                <div class="text-3xl font-display font-medium text-text-main">$<?php echo number_format($total_hoy, 2); ?>
                </div>
            </div>
            <div
                class="glass-card p-6 rounded-2xl flex flex-col justify-between group hover:border-border/80 transition-all">
                <div class="flex justify-between items-start mb-4">
                    <span class="text-[10px] font-semibold uppercase tracking-widest text-text-muted">Ventas de Hoy</span>
                    <span class="material-symbols-outlined text-secondary text-xl">receipt_long</span>
                </div>
                <div class="text-3xl font-display font-medium text-text-main"><?php echo number_format($trans_hoy); ?></div>
            </div>
        </section>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-8">
        <?php // Contenido principal: catálogo y transacciones ?>
        <div class="space-y-8">

            <?php // Catálogo de Venta Rápida (Estilo Tech Premium) ?>
            <div class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-display font-bold text-lg text-text-main tracking-tight">Catálogo de Venta Rápida</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-primary/10 text-primary border border-primary/20">Frecuentes</span>
                        </div>
                        <p class="text-xs text-text-muted mt-0.5">Artículos de mayor disponibilidad para atención ágil en caja</p>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/modules/inventory/index.php"
                        class="px-3.5 py-2 rounded-xl bg-surface hover:bg-surface-hover border border-border text-xs font-semibold text-text-muted hover:text-primary transition-colors flex items-center gap-1.5 shadow-sm self-start sm:self-auto">
                        <span>Ver inventario completo</span>
                        <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                    </a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
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
                            $catStyle = 'bg-primary/10 text-primary border-primary/20';

                            if (strpos($cat, 'celular') !== false || strpos($cat, 'smartphone') !== false || strpos($cat, 'telefono') !== false) {
                                $icon = 'smartphone';
                                $catStyle = 'bg-cyan-500/10 text-cyan-300 border-cyan-500/20';
                            } elseif (strpos($cat, 'funda') !== false || strpos($cat, 'case') !== false || strpos($cat, 'protec') !== false) {
                                $icon = 'shield';
                                $catStyle = 'bg-teal-500/10 text-teal-300 border-teal-500/20';
                            } elseif (strpos($cat, 'audio') !== false || strpos($cat, 'auricular') !== false || strpos($cat, 'parlante') !== false) {
                                $icon = 'headphones';
                                $catStyle = 'bg-fuchsia-500/10 text-fuchsia-300 border-fuchsia-500/20';
                            } elseif (strpos($cat, 'perifer') !== false || strpos($cat, 'mouse') !== false || strpos($cat, 'teclado') !== false) {
                                $icon = 'devices';
                                $catStyle = 'bg-indigo-500/10 text-indigo-300 border-indigo-500/20';
                            } elseif (strpos($cat, 'carga') !== false || strpos($cat, 'cable') !== false || strpos($cat, 'bateria') !== false) {
                                $icon = 'power';
                                $catStyle = 'bg-sky-500/10 text-sky-300 border-sky-500/20';
                            }

                            $stockBadge = $stock > 0
                                ? "<span class='inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded-full'><span class='w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse'></span>{$stock} disp.</span>"
                                : "<span class='inline-flex items-center gap-1 text-[10px] font-semibold text-rose-400 bg-rose-500/10 border border-rose-500/20 px-2 py-0.5 rounded-full'><span class='w-1.5 h-1.5 rounded-full bg-rose-400'></span>Agotado</span>";

                            $url = BASE_URL . "/modules/sales/index.php?action=new_sale&product_id={$prod_id}";

                            echo "
                            <a href='{$url}' class='group relative bg-card/60 hover:bg-card border border-border/70 hover:border-primary/50 rounded-2xl p-4 flex flex-col justify-between transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-primary/5 overflow-hidden' title='Iniciar venta de {$nombre} {$subTitle}'>
                                <div class='absolute -top-10 -right-10 w-24 h-24 rounded-full bg-primary/5 blur-xl group-hover:bg-primary/10 transition-colors pointer-events-none'></div>

                                <div class='relative z-10'>
                                    <div class='flex items-center justify-between gap-2 mb-3'>
                                        <span class='inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border {$catStyle}'>
                                            <span class='material-symbols-outlined text-[14px]'>{$icon}</span>
                                            {$cat_label}
                                        </span>
                                        {$stockBadge}
                                    </div>

                                    <div class='mb-4'>
                                        <h4 class='text-sm font-display font-bold text-text-main group-hover:text-primary transition-colors leading-snug truncate'>
                                            {$nombre}
                                        </h4>
                                        <p class='text-xs text-text-muted mt-0.5 font-medium truncate' title='{$subTitle}'>
                                            {$subTitle}
                                        </p>
                                    </div>
                                </div>

                                <div class='relative z-10 pt-3 border-t border-border/40 flex items-center justify-between mt-auto'>
                                    <div>
                                        <span class='text-[9px] uppercase font-semibold text-text-muted tracking-wider block'>Precio</span>
                                        <span class='text-base font-bold text-text-main font-mono tracking-tight'>\${$prod_precio}</span>
                                    </div>
                                    <div class='px-3 py-1.5 rounded-xl bg-surface group-hover:bg-primary border border-border group-hover:border-primary text-text-muted group-hover:text-background text-xs font-semibold flex items-center gap-1.5 transition-all duration-200 shadow-sm'>
                                        <span>Vender</span>
                                        <span class='material-symbols-outlined text-[15px]'>add_shopping_cart</span>
                                    </div>
                                </div>
                            </a>";
                        }
                    } else {
                        echo "<div class='col-span-full p-6 rounded-2xl border border-dashed border-border/60 bg-surface/20 text-center text-xs text-text-muted'>No hay productos registrados en catálogo</div>";
                    }
                    ?>
                </div>
            </div>

            <?php // Últimas transacciones ?>
            <?php if ($_SESSION['role'] !== 'guest'): ?>
                <div class="space-y-6">
                    <h3 class="font-display font-medium text-lg text-text-main">Últimas Transacciones</h3>
                    <div class="glass-card rounded-2xl overflow-hidden p-0">
                        <div class="divide-y divide-border/30">
                            <?php
                            if (count($ventas_recientes) > 0) {
                                foreach ($ventas_recientes as $v) {
                                    $monto = number_format((float)($v['total'] ?? 0), 2);
                                    $metodo = htmlspecialchars($v['metodo_de_pago'] ? $v['metodo_de_pago'] : 'N/A');
                                    $id_tx = (int)($v['id_venta'] ?? 0);
                                    echo "<div class='p-4 hover:bg-surface/30 transition-colors flex justify-between items-center'>
                                                        <div>
                                                            <div class='text-sm font-medium text-primary'>#TX-{$id_tx}</div>
                                                            <div class='text-[10px] font-semibold uppercase tracking-wider text-text-muted mt-1'>{$metodo}</div>
                                                        </div>
                                                        <div class='text-sm font-medium text-text-main text-right'>\${$monto}</div>
                                                    </div>";
                                }
                            } else {
                                echo "<div class='p-4 text-sm text-text-muted'>No hay transacciones</div>";
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