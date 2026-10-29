<?php
// help.php - Nokia 1100 Ayuda Rápida & Manual de Uso
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/classes/Layout.php';
require_once __DIR__ . '/classes/ServiceProduct.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

// 1. Inicializar variables con datos por defecto
$realProducts = [];
$realRepairs = [];
$realSales = [];
$totalProds = 0;

if (isset($conn)) {
    $excludeServices = ServiceProduct::excludeSql('p', 'pd');
    $resCount = $conn->query("SELECT COUNT(*) as c FROM producto p LEFT JOIN producto_detalle pd ON p.id_producto = pd.id_producto WHERE p.is_active = 1 AND $excludeServices");
    if ($resCount) {
        $totalProds = (int)$resCount->fetch_assoc()['c'];
    }

    $queryProds = "SELECT p.id_producto, p.nombre, p.precio, COALESCE(i.cantidad, 0) as cantidad 
                   FROM producto p 
                   LEFT JOIN producto_detalle pd ON p.id_producto = pd.id_producto
                   LEFT JOIN inventario i ON p.id_producto = i.id_producto 
                   WHERE p.is_active = 1 AND $excludeServices
                   ORDER BY i.cantidad DESC 
                   LIMIT 4";
    $resProds = $conn->query($queryProds);
    if ($resProds) {
        while ($row = $resProds->fetch_assoc()) {
            $realProducts[] = $row;
        }
    }

    // 2. Consultar órdenes reales de taller
    $queryReps = "SELECT r.id_reparacion, r.codigo_orden, r.equipo_marca, r.equipo_modelo, r.estado, r.presupuesto, r.falla_declarada, COALESCE(c.nombre, 'Consumidor Final') as cliente
                  FROM reparacion r
                  LEFT JOIN cliente c ON r.id_cliente = c.id_cliente
                  ORDER BY r.id_reparacion DESC
                  LIMIT 3";
    $resReps = $conn->query($queryReps);
    if ($resReps) {
        while ($row = $resReps->fetch_assoc()) {
            $realRepairs[] = $row;
        }
    }

    // 3. Consultar ventas recientes
    $querySales = "SELECT id_venta, fecha, total, metodo_de_pago, estado 
                   FROM venta 
                   WHERE estado != 'anulada' 
                   ORDER BY fecha DESC 
                   LIMIT 3";
    $resSales = $conn->query($querySales);
    if ($resSales) {
        while ($row = $resSales->fetch_assoc()) {
            $realSales[] = $row;
        }
    }
}

// Fallbacks de demostración si la base estuviera vacía en alguna tabla
if (empty($realProducts)) {
    $realProducts = [
        ['id_producto' => 1, 'nombre' => 'Módulo Display iPhone 11 Incell', 'precio' => 38500, 'cantidad' => 14],
        ['id_producto' => 2, 'nombre' => 'Batería Original Moto G30', 'precio' => 14200, 'cantidad' => 2],
        ['id_producto' => 3, 'nombre' => 'Pin de Carga Tipo-C Universal (x10)', 'precio' => 4800, 'cantidad' => 0],
    ];
    $totalProds = 3;
}

if (empty($realRepairs)) {
    $realRepairs = [
        ['codigo_orden' => '26106974', 'equipo_marca' => 'Apple', 'equipo_modelo' => 'iPhone 14', 'cliente' => 'Juan Pérez', 'estado' => 'Listo', 'falla_declarada' => 'Cambio de módulo', 'presupuesto' => 300000],
        ['codigo_orden' => '26104177', 'equipo_marca' => 'Motorola', 'equipo_modelo' => 'Moto G52', 'cliente' => 'Mariana Ruiz', 'estado' => 'En reparación', 'falla_declarada' => 'Pin de carga sulfatado', 'presupuesto' => 25000],
        ['codigo_orden' => '26101189', 'equipo_marca' => 'Samsung', 'equipo_modelo' => 'Galaxy A32', 'cliente' => 'Carlos Sosa', 'estado' => 'Recibido', 'falla_declarada' => 'No enciende tras golpe', 'presupuesto' => null],
    ];
}

if (empty($realSales)) {
    $realSales = [
        ['id_venta' => 101, 'fecha' => date('Y-m-d H:i:s'), 'total' => 38500, 'metodo_de_pago' => 'Efectivo', 'estado' => 'completada'],
        ['id_venta' => 100, 'fecha' => date('Y-m-d H:i:s', strtotime('-2 hours')), 'total' => 14200, 'metodo_de_pago' => 'Transferencia', 'estado' => 'completada'],
        ['id_venta' => 99, 'fecha' => date('Y-m-d H:i:s', strtotime('-5 hours')), 'total' => 25000, 'metodo_de_pago' => 'Tarjeta', 'estado' => 'completada'],
    ];
}

Layout::renderHead('NOKIA1100 | Ayuda Rápida & Manual de Uso');

// Renderizar el sidebar según el rol del usuario
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    Layout::renderAdminSidebar('ayuda');
} else {
    Layout::renderEmployeeSidebar('ayuda');
}
?>

<main class="flex-1 md:ml-64 p-6 md:p-8 pt-20 md:pt-8 min-h-screen bg-background text-text-main selection:bg-primary/20 selection:text-primary">
    <div class="max-w-7xl mx-auto w-full space-y-8">
        
        <?php // HERO HEADER: Ayuda Rápida ?>
        <div class="relative overflow-hidden rounded-3xl border border-border bg-gradient-to-b from-card to-surface p-6 md:p-10 shadow-lg">
            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="space-y-3 max-w-3xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 border border-primary/30 text-primary text-xs font-bold uppercase tracking-wider">
                        <span class="material-symbols-outlined text-[16px]">menu_book</span>
                        <span>Manual de Uso &amp; Soporte Técnico</span>
                    </div>
                    <h1 class="text-3xl md:text-5xl font-display font-bold text-text-main tracking-tight">
                        Ayuda Rápida
                    </h1>
                    <p class="text-sm md:text-base text-text-muted max-w-2xl leading-relaxed">
                        Guía interactiva paso a paso para dominar las funciones centrales del sistema NOKIA 1100, acelerar el control de stock y optimizar la administración de tu taller.
                    </p>
                </div>
            </div>
        </div>

        <?php // MODULES FILTER TABS ?>
        <div class="flex items-center gap-2.5 overflow-x-auto pb-2 scrollbar-none" id="help-tabs-bar">
            <?php // Tab 1: Inventario ?>
            <button onclick="switchTab('inventario')" id="tab-btn-inventario" class="help-tab-btn flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 whitespace-nowrap bg-primary border border-primary text-white shadow-sm font-display" type="button">
                <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                <span>1. Inventario y Stock</span>
            </button>
            <?php // Tab 2: Taller ?>
            <button onclick="switchTab('taller')" id="tab-btn-taller" class="help-tab-btn flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 whitespace-nowrap bg-card border border-border text-text-muted hover:text-text-main hover:border-primary font-display" type="button">
                <span class="material-symbols-outlined text-[18px]">home_repair_service</span>
                <span>2. Taller y Reparaciones</span>
            </button>
            <?php // Tab 3: Ventas ?>
            <button onclick="switchTab('ventas')" id="tab-btn-ventas" class="help-tab-btn flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 whitespace-nowrap bg-card border border-border text-text-muted hover:text-text-main hover:border-primary font-display" type="button">
                <span class="material-symbols-outlined text-[18px]">point_of_sale</span>
                <span>3. Ventas y Cobros</span>
            </button>
            <?php // Tab 4: Clientes ?>
            <button onclick="switchTab('clientes')" id="tab-btn-clientes" class="help-tab-btn flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 whitespace-nowrap bg-card border border-border text-text-muted hover:text-text-main hover:border-primary font-display" type="button">
                <span class="material-symbols-outlined text-[18px]">group</span>
                <span>4. Clientes y Cuentas</span>
            </button>
            <?php // Tab 5: Proveedores ?>
            <button onclick="switchTab('proveedores')" id="tab-btn-proveedores" class="help-tab-btn flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 whitespace-nowrap bg-card border border-border text-text-muted hover:text-text-main hover:border-primary font-display" type="button">
                <span class="material-symbols-outlined text-[18px]">local_shipping</span>
                <span>5. Proveedores y Reportes</span>
            </button>
        </div>

        <?php // MAIN WORKSPACE 2-COLUMN GRID ?>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <?php // LEFT PRIMARY GUIDE CONTAINER (8 Cols) ?>
            <section class="lg:col-span-8 space-y-6">

                <?php // ==================== PESTAÑA 1: INVENTARIO ==================== ?>
                <div id="tab-pane-inventario" class="help-pane space-y-6 block">
                    <article class="rounded-3xl border border-border bg-card overflow-hidden shadow-lg transition-all">
                        <?php // Step Header Bar ?>
                        <div class="p-6 md:p-8 border-b border-border flex items-start justify-between gap-4 bg-surface/40">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl border border-primary/40 bg-primary/10 flex items-center justify-center text-primary font-display font-bold text-xl">
                                    01
                                </div>
                                <div>
                                    <h2 class="text-xl md:text-2xl font-display font-bold text-text-main tracking-tight">
                                        Inventario y Stock
                                    </h2>
                                    <p class="text-xs md:text-sm text-text-muted">
                                        Control de existencias, precios de venta, repuestos y alertas por bajo stock.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <?php // Descriptive Body ?>
                        <div class="p-6 md:p-8 space-y-6">
                            <p class="text-sm md:text-base text-text-muted leading-relaxed">
                                El módulo de inventario te permite supervisar la disponibilidad en tiempo real de todos los productos y repuestos disponibles en el local físico o taller central.
                            </p>

                            <?php // Feature Points ?>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="p-4 rounded-2xl bg-surface border border-border hover:border-primary/40 transition-colors">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shrink-0">
                                            <span class="material-symbols-outlined text-[20px]">search</span>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-display font-bold text-text-main mb-1">Consulta Rápida</h3>
                                            <p class="text-xs text-text-muted leading-relaxed">
                                                Visualiza stock general con búsqueda en tiempo real por <strong class="text-text-main">Nombre</strong>, <strong class="text-text-main">ID</strong> o código.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-4 rounded-2xl bg-surface border border-border hover:border-[#9db82a] transition-colors">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-[#eef6cf] border border-[#c6e24a] flex items-center justify-center text-[#4d5e0a] shrink-0">
                                            <span class="material-symbols-outlined text-[20px]">edit_note</span>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-display font-bold text-text-main mb-1">Actualizar Stock y Precios</h3>
                                            <p class="text-xs text-text-muted leading-relaxed">
                                                Haz clic en <span class="text-primary font-semibold">Editar</span> en cualquier producto para registrar ingresos, ajustar costos o modificar el margen.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-4 rounded-2xl bg-surface border border-border hover:border-[#d4b54a] transition-colors">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-[#fcf3cf] border border-[#e6cf7a] flex items-center justify-center text-[#7a5600] shrink-0">
                                            <span class="material-symbols-outlined text-[20px]">toggle_on</span>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-display font-bold text-text-main mb-1">Baja Lógica (Activo / Inactivo)</h3>
                                            <p class="text-xs text-text-muted leading-relaxed">
                                                Haz clic en el botón de estado <span class="text-success font-semibold">Activo</span> de la tabla para pausar un producto sin borrar su historial. Cambiará a <span class="text-error font-semibold">Inactivo</span> y no figurará en ventas.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php // ACCORDION: Step by step walkthrough ?>
                            <div class="rounded-2xl border border-border bg-surface/70 overflow-hidden">
                                <button onclick="toggleAccordion('acc-step-inventario')" class="w-full p-4 md:p-5 flex items-center justify-between text-left hover:bg-card/50 transition-colors" type="button">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-primary/10 border border-primary/20 flex items-center justify-center text-primary">
                                            <span class="material-symbols-outlined text-[18px]">add_box</span>
                                        </div>
                                        <div>
                                            <span class="text-sm md:text-base font-display font-bold text-text-main">¿Cómo agregar un producto o repuesto paso a paso?</span>
                                            <span class="block text-xs text-text-muted">3 pasos guiados para el alta en catálogo</span>
                                        </div>
                                    </div>
                                    <span id="acc-icon-acc-step-inventario" class="material-symbols-outlined text-text-muted text-[22px] transition-transform duration-200">expand_more</span>
                                </button>
                                
                                <div id="acc-step-inventario" class="hidden p-5 pt-2 border-t border-border space-y-4">
                                    <div class="flex items-start gap-3.5 relative pb-4 before:absolute before:left-3.5 before:top-8 before:bottom-0 before:w-px before:bg-border">
                                        <div class="w-7 h-7 rounded-full bg-card border border-primary text-primary flex items-center justify-center text-xs font-bold shrink-0 z-10">1</div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-text-main">Ingresar al sub-módulo de Inventario</p>
                                            <p class="text-xs text-text-muted">Desde el menú lateral, pulsa en "Inventario" y ubica el botón <span class="text-primary font-semibold">+ Nuevo Producto</span> en la esquina superior derecha.</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3.5 relative pb-4 before:absolute before:left-3.5 before:top-8 before:bottom-0 before:w-px before:bg-border">
                                        <div class="w-7 h-7 rounded-full bg-card border border-border text-text-muted flex items-center justify-center text-xs font-bold shrink-0 z-10">2</div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-text-main">Completar datos técnicos y precio</p>
                                            <p class="text-xs text-text-muted">Indica Nombre descriptivo (ej. <em>Funda Silicona</em>, <em>Módulo Display</em>), Precio de venta al público y define el Stock inicial disponible.</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3.5">
                                        <div class="w-7 h-7 rounded-full bg-card border border-border text-text-muted flex items-center justify-center text-xs font-bold shrink-0">3</div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-text-main">Guardar y Sincronizar</p>
                                            <p class="text-xs text-text-muted">Al guardar, el producto estará inmediatamente disponible para ser vendido en el mostrador o asignado a órdenes de reparación en taller.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php // MOCKUP PREVIEW CONTAINER ?>
                            <div class="rounded-2xl border border-border-dark bg-background overflow-hidden shadow-inner">
                                <div class="px-4 py-3 bg-sunk border-b border-border-dark flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-error"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-success"></span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-primary/10 text-primary border border-primary/20">DATOS REALES DEL LOCAL</span>
                                </div>

                                <div class="p-4 space-y-3">
                                    <div class="flex items-center justify-between pb-1">
                                        <div class="flex items-center gap-2">
                                            <span class="material-symbols-outlined text-primary text-[18px]">format_list_bulleted</span>
                                            <span class="text-xs font-semibold text-text-main">Existencias en Tiempo Real</span>
                                        </div>
                                        <span class="text-xs text-text-muted"><?php echo count($realProducts); ?> de <?php echo $totalProds; ?> registros</span>
                                    </div>

                                    <div class="space-y-2">
                                        <?php foreach ($realProducts as $idx => $p): 
                                            $cant = (int)$p['cantidad'];
                                            if ($cant > 5) {
                                                $badgeClass = 'bg-[#e3f6ea] border-[#9adbb3] text-[#156835]';
                                                $badgeText = 'Disponible';
                                            } elseif ($cant > 0) {
                                                $badgeClass = 'bg-[#fcf3cf] border-[#e6cf7a] text-[#7a5600]';
                                                $badgeText = 'Crítico';
                                            } else {
                                                $badgeClass = 'bg-error/10 border-error/30 text-error';
                                                $badgeText = 'Agotado';
                                            }
                                        ?>
                                        <div class="flex items-center justify-between p-3 rounded-xl bg-surface border border-border hover:border-primary/40 transition-colors">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-background border border-border flex items-center justify-center text-primary text-xs font-mono font-bold">
                                                    #<?php echo htmlspecialchars($p['id_producto']); ?>
                                                </div>
                                                <div>
                                                    <p class="text-xs md:text-sm font-medium text-text-main"><?php echo htmlspecialchars($p['nombre']); ?></p>
                                                    <p class="text-[11px] text-text-muted">SKU: ART-<?php echo str_pad($p['id_producto'], 4, '0', STR_PAD_LEFT); ?> • Almacén Central</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg border <?php echo $badgeClass; ?>">
                                                    <span class="text-xs font-bold font-mono"><?php echo $cant; ?></span>
                                                    <span class="text-[11px] font-medium"><?php echo $badgeText; ?></span>
                                                </div>
                                                <span class="text-xs md:text-sm font-mono text-text-main font-semibold">$<?php echo number_format((float)$p['precio'], 2, ',', '.'); ?></span>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php // Card Footer Controls ?>
                        <div class="px-6 md:px-8 py-4 bg-surface/50 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div class="flex items-center gap-2 text-xs text-text-muted">
                                <span class="material-symbols-outlined text-[18px] text-primary">lightbulb</span>
                                <span>Tip: Presiona <kbd class="px-1.5 py-0.5 rounded bg-card border border-border text-[11px] font-mono text-text-main">F3</kbd> en cualquier pantalla para saltar al inventario.</span>
                            </div>
                            <a href="<?php echo BASE_URL; ?>/modules/inventory/index.php" class="px-4 py-2 rounded-xl bg-primary text-white font-bold text-xs hover:bg-primary-hover transition-colors flex items-center gap-1.5">
                                <span>Ir al Inventario</span>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </a>
                        </div>
                    </article>

                    <?php // Preview Cards to switch to other modules ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div onclick="switchTab('taller')" class="rounded-2xl border border-border bg-card p-5 flex items-center justify-between hover:border-primary/40 transition-all cursor-pointer group">
                            <div class="flex items-center gap-3.5">
                                <div class="w-10 h-10 rounded-xl border border-border bg-surface flex items-center justify-center text-text-muted group-hover:text-primary transition-colors font-display font-bold">02</div>
                                <div>
                                    <h3 class="text-sm font-display font-bold text-text-main group-hover:text-primary transition-colors">Taller y Reparaciones</h3>
                                    <p class="text-xs text-text-muted">Recepción de equipos, órdenes y diagnósticos.</p>
                                </div>
                            </div>
                            <span class="material-symbols-outlined text-primary group-hover:translate-x-1 transition-transform">arrow_forward</span>
                        </div>

                        <div onclick="switchTab('ventas')" class="rounded-2xl border border-border bg-card p-5 flex items-center justify-between hover:border-primary/40 transition-all cursor-pointer group">
                            <div class="flex items-center gap-3.5">
                                <div class="w-10 h-10 rounded-xl border border-border bg-surface flex items-center justify-center text-text-muted group-hover:text-primary transition-colors font-display font-bold">03</div>
                                <div>
                                    <h3 class="text-sm font-display font-bold text-text-main group-hover:text-primary transition-colors">Ventas y Facturación</h3>
                                    <p class="text-xs text-text-muted">Cobro en mostrador y medios de pago.</p>
                                </div>
                            </div>
                            <span class="material-symbols-outlined text-primary group-hover:translate-x-1 transition-transform">arrow_forward</span>
                        </div>
                    </div>
                </div>

                <?php // ==================== PESTAÑA 2: TALLER ==================== ?>
                <div id="tab-pane-taller" class="help-pane space-y-6 hidden">
                    <article class="rounded-3xl border border-border bg-card overflow-hidden shadow-lg transition-all">
                        <div class="p-6 md:p-8 border-b border-border flex items-start justify-between gap-4 bg-surface/40">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl border border-primary/40 bg-primary/10 flex items-center justify-center text-primary font-display font-bold text-xl">
                                    02
                                </div>
                                <div>
                                    <h2 class="text-xl md:text-2xl font-display font-bold text-text-main tracking-tight">
                                        Taller y Reparaciones
                                    </h2>
                                    <p class="text-xs md:text-sm text-text-muted">
                                        Ciclo completo de servicio técnico: ingreso, diagnóstico, repuestos y entrega.
                                    </p>
                                </div>
                            </div>
                            <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary/10 border border-primary/30 text-primary text-xs font-semibold">
                                <span class="material-symbols-outlined text-[16px]">build</span>
                                Servicio Técnico
                            </span>
                        </div>

                        <div class="p-6 md:p-8 space-y-6">
                            <p class="text-sm md:text-base text-text-muted leading-relaxed">
                                Controla cada orden con código único de seguimiento, asociando el cliente, modelo del teléfono, repuestos necesarios y presupuesto acordado.
                            </p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="p-4 rounded-2xl bg-surface border border-border hover:border-primary/40 transition-colors">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shrink-0">
                                            <span class="material-symbols-outlined text-[20px]">assignment_turned_in</span>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-display font-bold text-text-main mb-1">Boleta y Estados</h3>
                                            <p class="text-xs text-text-muted leading-relaxed">
                                                Emite el comprobante para el cliente y avanza el estado: <strong class="text-text-main">Recibido</strong>, <strong class="text-text-main">En reparación</strong> y <strong class="text-text-main">Listo</strong>.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-4 rounded-2xl bg-surface border border-border hover:border-[#9db82a] transition-colors">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-[#eef6cf] border border-[#c6e24a] flex items-center justify-center text-[#4d5e0a] shrink-0">
                                            <span class="material-symbols-outlined text-[20px]">extension</span>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-display font-bold text-text-main mb-1">Asignación de Repuestos</h3>
                                            <p class="text-xs text-text-muted leading-relaxed">
                                                Descuenta del inventario los componentes utilizados (módulos, pines, baterías) sumándolos al costo del trabajo.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php // MOCKUP PREVIEW TALLER ?>
                            <div class="rounded-2xl border border-border-dark bg-background overflow-hidden shadow-inner">
                                <div class="px-4 py-3 bg-sunk border-b border-border-dark flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-error"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-success"></span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-primary/10 text-primary border border-primary/20">ÓRDENES RECIENTES</span>
                                </div>

                                <div class="p-4 space-y-3">
                                    <?php foreach ($realRepairs as $r): 
                                        $st = $r['estado'];
                                        $color = match($st) {
                                            'Listo' => 'repair-status repair-status-listo',
                                            'Entregado' => 'repair-status repair-status-entregado',
                                            'En reparación' => 'repair-status repair-status-reparacion',
                                            'En diagnóstico' => 'repair-status repair-status-diagnostico',
                                            'Cancelado' => 'repair-status repair-status-cancelado',
                                            default => 'repair-status repair-status-recibido'
                                        };
                                    ?>
                                    <div class="flex items-center justify-between p-3 rounded-xl bg-surface border border-border hover:border-primary/40 transition-colors">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-background border border-border flex items-center justify-center text-primary text-xs font-mono font-bold">
                                                #<?php echo htmlspecialchars(substr($r['codigo_orden'], -4)); ?>
                                            </div>
                                            <div>
                                                <p class="text-xs md:text-sm font-medium text-text-main"><?php echo htmlspecialchars($r['equipo_marca'] . ' ' . $r['equipo_modelo']); ?> <span class="text-[11px] text-text-muted">• <?php echo htmlspecialchars($r['cliente']); ?></span></p>
                                                <p class="text-[11px] text-text-muted">Falla: <?php echo htmlspecialchars($r['falla_declarada'] ?: 'Revisión técnica'); ?></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold border <?php echo $color; ?>">
                                                <?php echo htmlspecialchars($st); ?>
                                            </span>
                                            <span class="text-xs md:text-sm font-mono font-semibold text-text-main">
                                                <?php echo $r['presupuesto'] ? '$' . number_format((float)$r['presupuesto'], 2, ',', '.') : 'S/P'; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 md:px-8 py-4 bg-surface/50 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div class="flex items-center gap-2 text-xs text-text-muted">
                                <span class="material-symbols-outlined text-[18px] text-primary">lightbulb</span>
                                <span>Tip: Presiona <kbd class="px-1.5 py-0.5 rounded bg-card border border-border text-[11px] font-mono text-text-main">F4</kbd> en cualquier pantalla para saltar a Taller.</span>
                            </div>
                            <a href="<?php echo BASE_URL; ?>/modules/workshop/index.php" class="px-4 py-2 rounded-xl bg-primary text-white font-bold text-xs hover:bg-primary-hover transition-colors flex items-center gap-1.5">
                                <span>Ir a Taller</span>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </a>
                        </div>
                    </article>
                </div>

                <?php // ==================== PESTAÑA 3: VENTAS ==================== ?>
                <div id="tab-pane-ventas" class="help-pane space-y-6 hidden">
                    <article class="rounded-3xl border border-border bg-card overflow-hidden shadow-lg transition-all">
                        <div class="p-6 md:p-8 border-b border-border flex items-start justify-between gap-4 bg-surface/40">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl border border-primary/40 bg-primary/10 flex items-center justify-center text-primary font-display font-bold text-xl">
                                    03
                                </div>
                                <div>
                                    <h2 class="text-xl md:text-2xl font-display font-bold text-text-main tracking-tight">
                                        Ventas y Facturación de Mostrador
                                    </h2>
                                    <p class="text-xs md:text-sm text-text-muted">
                                        Punto de venta (POS) ágil, múltiples medios de pago y cobro de reparaciones de taller.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 md:p-8 space-y-6">
                            <p class="text-sm md:text-base text-text-muted leading-relaxed">
                                Gestiona las operaciones de mostrador en tiempo récord. El módulo permite cobrar productos directos del inventario y también liquidar y entregar las órdenes finalizadas del taller de reparaciones.
                            </p>

                            <?php // Feature Points ?>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="p-4 rounded-2xl bg-surface border border-border hover:border-primary/40 transition-colors">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shrink-0">
                                            <span class="material-symbols-outlined text-[20px]">add_shopping_cart</span>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-display font-bold text-text-main mb-1">Venta de Mostrador</h3>
                                            <p class="text-xs text-text-muted leading-relaxed">
                                                Cobro directo de accesorios y productos del inventario con cálculo automático de totales.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-4 rounded-2xl bg-surface border border-border hover:border-blue-300 transition-colors">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-200 flex items-center justify-center text-blue-700 shrink-0">
                                            <span class="material-symbols-outlined text-[20px]">build</span>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-display font-bold text-text-main mb-1">¿Dónde está el cobro de Taller?</h3>
                                            <p class="text-xs text-text-muted leading-relaxed">
                                                En <strong class="text-text-main">Ventas</strong>, botón azul superior <strong class="text-blue-700 font-semibold">"Cobrar Reparaciones"</strong> junto a "+ Nueva Venta".
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-4 rounded-2xl bg-surface border border-border hover:border-[#9db82a] transition-colors">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-[#eef6cf] border border-[#c6e24a] flex items-center justify-center text-[#4d5e0a] shrink-0">
                                            <span class="material-symbols-outlined text-[20px]">payments</span>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-display font-bold text-text-main mb-1">Medios de Pago</h3>
                                            <p class="text-xs text-text-muted leading-relaxed">
                                                Acepta <strong class="text-text-main">Efectivo</strong>, <strong class="text-text-main">Transferencia</strong> y <strong class="text-text-main">Tarjeta</strong> con balance en el arqueo diario.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php // ACCORDION: Cobro de Reparaciones Paso a Paso ?>
                            <div class="rounded-2xl border border-blue-200 bg-surface/80 overflow-hidden shadow-sm">
                                <button onclick="toggleAccordion('acc-step-ventas-reparacion')" class="w-full p-4 md:p-5 flex items-center justify-between text-left hover:bg-card/50 transition-colors" type="button">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-200 flex items-center justify-center text-blue-700">
                                            <span class="material-symbols-outlined text-[20px]">handyman</span>
                                        </div>
                                        <div>
                                            <span class="text-sm md:text-base font-display font-bold text-text-main">¿Dónde está y cómo cobrar una reparación de taller paso a paso?</span>
                                            <span class="block text-xs text-text-muted">Guía completa de entrega y cobro de equipos terminados</span>
                                        </div>
                                    </div>
                                    <span id="acc-icon-acc-step-ventas-reparacion" class="material-symbols-outlined text-text-muted text-[22px] transition-transform duration-200" style="transform: rotate(180deg);">expand_more</span>
                                </button>
                                
                                <div id="acc-step-ventas-reparacion" class="p-5 pt-3 border-t border-border space-y-4">
                                    <?php // Paso 1 ?>
                                    <div class="flex items-start gap-3.5 relative pb-4 before:absolute before:left-3.5 before:top-8 before:bottom-0 before:w-px before:bg-border">
                                        <div class="w-7 h-7 rounded-full bg-card border border-primary text-primary flex items-center justify-center text-xs font-bold shrink-0 z-10">1</div>
                                        <div class="flex-1">
                                            <p class="text-sm font-semibold text-text-main">La orden debe estar en estado "Listo" en Taller</p>
                                            <p class="text-xs text-text-muted mt-0.5 leading-relaxed">
                                                En el módulo <strong class="text-text-main">Taller</strong>, el técnico debe marcar la orden como <span class="px-2 py-0.5 rounded text-[11px] font-bold border border-[#92d5e8] bg-[#e0f4fa] text-[#0d5c73]">Listo</span>. Mientras la orden esté en <em>Recibido</em>, <em>En diagnóstico</em> o <em>En reparación</em> no figurará en la bandeja de cobro.
                                            </p>
                                        </div>
                                    </div>

                                    <?php // Paso 2 ?>
                                    <div class="flex items-start gap-3.5 relative pb-4 before:absolute before:left-3.5 before:top-8 before:bottom-0 before:w-px before:bg-border">
                                        <div class="w-7 h-7 rounded-full bg-card border border-blue-600 text-blue-700 flex items-center justify-center text-xs font-bold shrink-0 z-10">2</div>
                                        <div class="flex-1">
                                            <p class="text-sm font-semibold text-text-main">Abrir "Cobrar Reparaciones" desde el módulo Ventas</p>
                                            <p class="text-xs text-text-muted mt-0.5 leading-relaxed">
                                                Ve al menú lateral y haz clic en <strong class="text-text-main">Ventas</strong>. En la esquina superior derecha, junto al botón verde "+ Nueva Venta", pulsa en el botón azul <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold border border-blue-200 bg-blue-50 text-blue-700"><span class="material-symbols-outlined text-[13px]">build</span> Cobrar Reparaciones</span>.
                                            </p>
                                        </div>
                                    </div>

                                    <?php // Paso 3 ?>
                                    <div class="flex items-start gap-3.5 relative pb-4 before:absolute before:left-3.5 before:top-8 before:bottom-0 before:w-px before:bg-border">
                                        <div class="w-7 h-7 rounded-full bg-card border border-border text-text-muted flex items-center justify-center text-xs font-bold shrink-0 z-10">3</div>
                                        <div class="flex-1">
                                            <p class="text-sm font-semibold text-text-main">Verificar el Total a Cobrar (Mano de obra + Repuestos)</p>
                                            <p class="text-xs text-text-muted mt-0.5 leading-relaxed">
                                                En la bandeja de equipos listos, localiza la tarjeta de la orden por su número (#ID) o nombre del cliente. El sistema suma automáticamente:
                                                <code class="px-2 py-0.5 rounded bg-background text-[11px] font-mono text-primary border border-border block mt-1.5 w-fit">Total a Cobrar = Presupuesto Base + Costo de Repuestos asignados</code>
                                            </p>
                                        </div>
                                    </div>

                                    <?php // Paso 4 ?>
                                    <div class="flex items-start gap-3.5 relative pb-4 before:absolute before:left-3.5 before:top-8 before:bottom-0 before:w-px before:bg-border">
                                        <div class="w-7 h-7 rounded-full bg-card border border-border text-text-muted flex items-center justify-center text-xs font-bold shrink-0 z-10">4</div>
                                        <div class="flex-1">
                                            <p class="text-sm font-semibold text-text-main">Seleccionar Método de Pago y confirmar</p>
                                            <p class="text-xs text-text-muted mt-0.5 leading-relaxed">
                                                Elige el medio de pago acordado (<strong class="text-text-main">Efectivo</strong>, <strong class="text-text-main">Transferencia</strong> o <strong class="text-text-main">Tarjeta</strong>) y haz clic en el botón verde <span class="px-2.5 py-0.5 rounded text-[11px] font-bold bg-primary text-white">Cobrar y Entregar</span>.
                                            </p>
                                        </div>
                                    </div>

                                    <?php // Paso 5 ?>
                                    <div class="flex items-start gap-3.5">
                                        <div class="w-7 h-7 rounded-full bg-card border border-success text-success flex items-center justify-center text-xs font-bold shrink-0">5</div>
                                        <div class="flex-1">
                                            <p class="text-sm font-semibold text-text-main">Entrega y Cierre Contable Automático</p>
                                            <p class="text-xs text-text-muted mt-0.5 leading-relaxed">
                                                Al confirmar, el sistema asienta el ingreso en el historial de ventas bajo el concepto de <em>Servicio Técnico Taller</em> y cambia automáticamente la orden a <span class="px-2 py-0.5 rounded text-[11px] font-bold border border-[#9adbb3] bg-[#e3f6ea] text-[#156835]">Entregado</span> con la fecha y hora exacta del retiro.
                                            </p>
                                            <a href="<?php echo BASE_URL; ?>/modules/sales/index.php?action=repairs_to_charge" class="inline-flex items-center gap-1.5 mt-3 px-3.5 py-1.5 rounded-xl border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs font-bold transition-colors">
                                                <span class="material-symbols-outlined text-[16px]">build</span>
                                                <span>Ir directo a la Bandeja de Cobrar Reparaciones</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php // MOCKUP LISTADO DE VENTAS ?>
                            <div class="rounded-2xl border border-border-dark bg-background overflow-hidden p-4 space-y-3">
                                <div class="flex items-center justify-between pb-2 border-b border-border/50">
                                    <span class="text-xs font-semibold text-text-main flex items-center gap-2">
                                        <span class="material-symbols-outlined text-primary text-[18px]">receipt</span>
                                        Últimas Operaciones Asentadas
                                    </span>
                                </div>
                                <div class="space-y-2">
                                    <?php foreach ($realSales as $s): ?>
                                    <div class="flex items-center justify-between p-3 rounded-xl bg-surface border border-border">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-background border border-border flex items-center justify-center text-primary text-xs font-mono font-bold">
                                                #<?php echo $s['id_venta']; ?>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="text-xs font-medium text-text-main">Venta Registrada</span>
                                                    <?php echo Layout::renderPaymentBadge($s['metodo_de_pago'] ?? 'N/A'); ?>
                                                </div>
                                                <p class="text-[11px] text-text-muted mt-0.5"><?php echo htmlspecialchars($s['fecha']); ?></p>
                                            </div>
                                        </div>
                                        <span class="text-xs md:text-sm font-mono font-bold text-primary">$<?php echo number_format((float)$s['total'], 2, ',', '.'); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 md:px-8 py-4 bg-surface/50 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div class="flex items-center gap-2 text-xs text-text-muted">
                                <span class="material-symbols-outlined text-[18px] text-primary">lightbulb</span>
                                <span>Tip: Presiona <kbd class="px-1.5 py-0.5 rounded bg-card border border-border text-[11px] font-mono text-text-main">F2</kbd> en cualquier pantalla para abrir Nueva Venta.</span>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <a href="<?php echo BASE_URL; ?>/modules/sales/index.php?action=repairs_to_charge" class="px-3.5 py-2 rounded-xl border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs font-bold transition-colors flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[16px]">build</span>
                                    <span>Cobrar Reparaciones</span>
                                </a>
                                <a href="<?php echo BASE_URL; ?>/modules/sales/index.php" class="px-4 py-2 rounded-xl bg-primary text-white font-bold text-xs hover:bg-primary-hover transition-colors flex items-center gap-1.5">
                                    <span>Ir a Ventas</span>
                                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                                </a>
                            </div>
                        </div>
                    </article>
                </div>

                <?php // ==================== PESTAÑA 4: CLIENTES ==================== ?>
                <div id="tab-pane-clientes" class="help-pane space-y-6 hidden">
                    <article class="rounded-3xl border border-border bg-card overflow-hidden shadow-lg transition-all">
                        <div class="p-6 md:p-8 border-b border-border flex items-start justify-between gap-4 bg-surface/40">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl border border-primary/40 bg-primary/10 flex items-center justify-center text-primary font-display font-bold text-xl">
                                    04
                                </div>
                                <div>
                                    <h2 class="text-xl md:text-2xl font-display font-bold text-text-main tracking-tight">
                                        Clientes y Cuentas
                                    </h2>
                                    <p class="text-xs md:text-sm text-text-muted">
                                        Padrón centralizado de contactos, vinculación con órdenes y compras.
                                    </p>
                                </div>
                            </div>
                            <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary/10 border border-primary/30 text-primary text-xs font-semibold">
                                <span class="material-symbols-outlined text-[16px]">person</span>
                                Directorio
                            </span>
                        </div>

                        <div class="p-6 md:p-8 space-y-6">
                            <p class="text-sm md:text-base text-text-muted leading-relaxed">
                                Mantén un registro ordenado de todos los clientes. Al ingresar un equipo a reparación o realizar una venta, los datos quedan automáticamente vinculados a su historial.
                            </p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="p-4 rounded-2xl bg-surface border border-border hover:border-primary/40 transition-colors">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shrink-0">
                                            <span class="material-symbols-outlined text-[20px]">badge</span>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-display font-bold text-text-main mb-1">Identificación por DNI</h3>
                                            <p class="text-xs text-text-muted leading-relaxed">
                                                Evita duplicados registrando DNI, teléfono y correo para enviar comprobantes y avisos de entrega.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-4 rounded-2xl bg-surface border border-border hover:border-[#9db82a] transition-colors">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-[#eef6cf] border border-[#c6e24a] flex items-center justify-center text-[#4d5e0a] shrink-0">
                                            <span class="material-symbols-outlined text-[20px]">history</span>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-display font-bold text-text-main mb-1">Historial de Dispositivos</h3>
                                            <p class="text-xs text-text-muted leading-relaxed">
                                                Revisa al instante las reparaciones anteriores y garantías activas de cada cliente.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 md:px-8 py-4 bg-surface/50 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div class="flex items-center gap-2 text-xs text-text-muted">
                                <span class="material-symbols-outlined text-[18px] text-primary">lightbulb</span>
                                <span>Tip: Presiona <kbd class="px-1.5 py-0.5 rounded bg-card border border-border text-[11px] font-mono text-text-main">Ctrl + B</kbd> para abrir el módulo Clientes.</span>
                            </div>
                            <a href="<?php echo BASE_URL; ?>/modules/clients/index.php" class="px-4 py-2 rounded-xl bg-primary text-white font-bold text-xs hover:bg-primary-hover transition-colors flex items-center gap-1.5">
                                <span>Ir a Clientes</span>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </a>
                        </div>
                    </article>
                </div>

                <?php // ==================== PESTAÑA 5: PROVEEDORES ==================== ?>
                <div id="tab-pane-proveedores" class="help-pane space-y-6 hidden">
                    <article class="rounded-3xl border border-border bg-card overflow-hidden shadow-lg transition-all">
                        <div class="p-6 md:p-8 border-b border-border flex items-start justify-between gap-4 bg-surface/40">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl border border-primary/40 bg-primary/10 flex items-center justify-center text-primary font-display font-bold text-xl">
                                    05
                                </div>
                                <div>
                                    <h2 class="text-xl md:text-2xl font-display font-bold text-text-main tracking-tight">
                                        Proveedores y Reportes Financieros
                                    </h2>
                                    <p class="text-xs md:text-sm text-text-muted">
                                        Compras de insumos, auditoría de operaciones y gráficos de ingresos vs egresos.
                                    </p>
                                </div>
                            </div>
                            <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary/10 border border-primary/30 text-primary text-xs font-semibold">
                                <span class="material-symbols-outlined text-[16px]">analytics</span>
                                Administración
                            </span>
                        </div>

                        <div class="p-6 md:p-8 space-y-6">
                            <p class="text-sm md:text-base text-text-muted leading-relaxed">
                                Administra las relaciones comerciales con importadores y distribuidores de repuestos. Las compras registradas se reflejan automáticamente como gasto en los balances mensuales del Dashboard.
                            </p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="p-4 rounded-2xl bg-surface border border-border hover:border-primary/40 transition-colors">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shrink-0">
                                            <span class="material-symbols-outlined text-[20px]">add_shopping_cart</span>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-display font-bold text-text-main mb-1">Nueva Compra de Repuestos</h3>
                                            <p class="text-xs text-text-muted leading-relaxed">
                                                Carga facturas de compra para incrementar el stock de los productos y registrar el costo exacto de adquisición.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-4 rounded-2xl bg-surface border border-border hover:border-[#9db82a] transition-colors">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-[#eef6cf] border border-[#c6e24a] flex items-center justify-center text-[#4d5e0a] shrink-0">
                                            <span class="material-symbols-outlined text-[20px]">query_stats</span>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-display font-bold text-text-main mb-1">Auditoría y Reportes</h3>
                                            <p class="text-xs text-text-muted leading-relaxed">
                                                El sistema registra cada acción de usuarios en el historial de actividades y permite exportar balances a Excel y PDF con un clic.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 md:px-8 py-4 bg-surface/50 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div class="flex items-center gap-2 text-xs text-text-muted">
                                <span class="material-symbols-outlined text-[18px] text-primary">lightbulb</span>
                                <span>Tip: Los administradores pueden consultar el historial de actividades desde el Dashboard de administración.</span>
                            </div>
                            <a href="<?php echo BASE_URL; ?>/modules/suppliers/index.php" class="px-4 py-2 rounded-xl bg-primary text-white font-bold text-xs hover:bg-primary-hover transition-colors flex items-center gap-1.5">
                                <span>Ir a Proveedores</span>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </a>
                        </div>
                    </article>
                </div>

            </section>

            <?php // RIGHT COMPANION RAIL (4 Cols) ?>
            <aside class="lg:col-span-4 space-y-6">

                <?php // CARD: ATAJOS DE TECLADO ?>
                <div class="rounded-3xl border border-border bg-card p-6 space-y-4 shadow-lg">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-primary/10 border border-primary/20 flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined text-[18px]">bolt</span>
                        </div>
                        <h3 class="text-base font-display font-bold text-text-main">Atajos de Teclado</h3>
                    </div>

                    <p class="text-xs text-text-muted leading-relaxed">
                        Agiliza la atención en mostrador sin usar el mouse. Haz clic en cualquiera o presiona la tecla directamente:
                    </p>

                    <div class="space-y-2">
                        <?php // F2: Venta ?>
                        <a href="<?php echo BASE_URL; ?>/modules/sales/index.php?action=new_sale" class="flex items-center justify-between p-3 rounded-2xl bg-surface border border-border hover:border-primary/50 transition-all group">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-text-muted group-hover:text-primary transition-colors text-[18px]">add_shopping_cart</span>
                                <span class="text-xs font-medium text-text-main group-hover:text-primary transition-colors">Nueva Venta Mostrador</span>
                            </div>
                            <kbd class="px-2 py-1 rounded-lg bg-card border border-border font-mono text-xs text-primary font-bold shadow-sm">F2</kbd>
                        </a>

                        <?php // F3: Inventario ?>
                        <a href="<?php echo BASE_URL; ?>/modules/inventory/index.php" class="flex items-center justify-between p-3 rounded-2xl bg-surface border border-border hover:border-[#9db82a] transition-all group">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-text-muted group-hover:text-[#4d5e0a] transition-colors text-[18px]">search</span>
                                <span class="text-xs font-medium text-text-main group-hover:text-[#4d5e0a] transition-colors">Buscar Repuesto / Stock</span>
                            </div>
                            <kbd class="px-2 py-1 rounded-lg bg-card border border-border font-mono text-xs text-[#4d5e0a] font-bold shadow-sm">F3</kbd>
                        </a>

                        <?php // F4: Taller ?>
                        <a href="<?php echo BASE_URL; ?>/modules/workshop/index.php" class="flex items-center justify-between p-3 rounded-2xl bg-surface border border-border hover:border-primary/50 transition-all group">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-text-muted group-hover:text-primary transition-colors text-[18px]">build</span>
                                <span class="text-xs font-medium text-text-main group-hover:text-primary transition-colors">Nueva Orden de Taller</span>
                            </div>
                            <kbd class="px-2 py-1 rounded-lg bg-card border border-border font-mono text-xs text-primary font-bold shadow-sm">F4</kbd>
                        </a>

                        <?php // Ctrl + B: Clientes ?>
                        <a href="<?php echo BASE_URL; ?>/modules/clients/index.php" class="flex items-center justify-between p-3 rounded-2xl bg-surface border border-border hover:border-border/80 transition-all group">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-text-muted group-hover:text-text-main transition-colors text-[18px]">group</span>
                                <span class="text-xs font-medium text-text-main">Filtro Rápido de Clientes</span>
                            </div>
                            <kbd class="px-2 py-1 rounded-lg bg-card border border-border font-mono text-xs text-text-muted font-bold shadow-sm">Ctrl + B</kbd>
                        </a>
                    </div>
                </div>

                <?php // CARD: PREGUNTAS FRECUENTES (FAQ) ?>
                <div class="rounded-3xl border border-border bg-card p-6 space-y-4 shadow-lg">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-[#eef6cf] border border-[#c6e24a] flex items-center justify-center text-[#4d5e0a]">
                            <span class="material-symbols-outlined text-[18px]">quiz</span>
                        </div>
                        <h3 class="text-base font-display font-bold text-text-main">Preguntas Frecuentes</h3>
                    </div>

                    <div class="space-y-2.5 text-xs">
                        <?php // FAQ 1 ?>
                        <details class="group rounded-2xl bg-surface border border-border overflow-hidden">
                            <summary class="p-3.5 font-medium text-text-main flex items-center justify-between cursor-pointer list-none hover:text-primary transition-colors">
                                <span>¿Cómo reimprimir una orden de taller?</span>
                                <span class="material-symbols-outlined text-text-muted text-[18px] group-open:rotate-180 transition-transform">expand_more</span>
                            </summary>
                            <div class="p-3.5 pt-0 text-text-muted border-t border-border/40 mt-1 leading-relaxed">
                                Ingresa al módulo <strong class="text-text-main">Taller</strong>, localiza el número de ticket o equipo del cliente y presiona el botón <span class="text-primary font-semibold">Imprimir Comprobante</span> para generar la orden en formato térmico o PDF.
                            </div>
                        </details>

                        <?php // FAQ 2 ?>
                        <details class="group rounded-2xl bg-surface border border-border overflow-hidden">
                            <summary class="p-3.5 font-medium text-text-main flex items-center justify-between cursor-pointer list-none hover:text-primary transition-colors">
                                <span>¿Dónde se cobra una reparación finalizada?</span>
                                <span class="material-symbols-outlined text-text-muted text-[18px] group-open:rotate-180 transition-transform">expand_more</span>
                            </summary>
                            <div class="p-3.5 pt-0 text-text-muted border-t border-border/40 mt-1 leading-relaxed">
                                Cuando una orden pasa a estado <strong class="text-text-main">"Listo"</strong>, se cobra desde <strong class="text-text-main">Ventas > Reparaciones a Cobrar</strong>. Al ingresar el pago, el equipo cambia automáticamente a <strong class="text-text-main">"Entregado"</strong> y se asienta el ingreso.
                            </div>
                        </details>

                        <?php // FAQ 3 ?>
                        <details class="group rounded-2xl bg-surface border border-border overflow-hidden">
                            <summary class="p-3.5 font-medium text-text-main flex items-center justify-between cursor-pointer list-none hover:text-primary transition-colors">
                                <span>¿Qué hacer si un stock queda en nivel crítico?</span>
                                <span class="material-symbols-outlined text-text-muted text-[18px] group-open:rotate-180 transition-transform">expand_more</span>
                            </summary>
                            <div class="p-3.5 pt-0 text-text-muted border-t border-border/40 mt-1 leading-relaxed">
                                Cuando las existencias son iguales o menores a 5 unidades, el sistema activa una alerta visual roja. Puedes registrar una nueva compra desde <strong class="text-text-main">Proveedores</strong> o editar la cantidad directamente en <strong class="text-text-main">Inventario</strong>.
                            </div>
                        </details>

                        <?php // FAQ 4 ?>
                        <details class="group rounded-2xl bg-surface border border-border overflow-hidden">
                            <summary class="p-3.5 font-medium text-text-main flex items-center justify-between cursor-pointer list-none hover:text-primary transition-colors">
                                <span>¿Cómo anular una venta y emitir nota de crédito?</span>
                                <span class="material-symbols-outlined text-text-muted text-[18px] group-open:rotate-180 transition-transform">expand_more</span>
                            </summary>
                            <div class="p-3.5 pt-0 text-text-muted border-t border-border/40 mt-1 leading-relaxed">
                                Desde el listado de <strong class="text-text-main">Ventas</strong>, busca la transacción y pulsa el botón de anulación (requiere confirmación). Los artículos volverán al stock automáticamente y podrás descargar la <strong class="text-text-main">Nota de Crédito</strong> asociada.
                            </div>
                        </details>
                    </div>
                </div>


            </aside>
        </div>

    </div>
</main>

<script>
// Manejador interactivo de cambio de pestañas de ayuda
function switchTab(tabId) {
    // 1. Actualizar estilos de los botones de pestañas
    const tabs = ['inventario', 'taller', 'ventas', 'clientes', 'proveedores'];
    tabs.forEach(t => {
        const btn = document.getElementById('tab-btn-' + t);
        const pane = document.getElementById('tab-pane-' + t);
        
        if (t === tabId) {
            if (btn) {
                btn.className = 'help-tab-btn flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 whitespace-nowrap bg-primary border border-primary text-white shadow-sm font-display';
            }
            if (pane) {
                pane.classList.remove('hidden');
                pane.classList.add('block');
            }
        } else {
            if (btn) {
                btn.className = 'help-tab-btn flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 whitespace-nowrap bg-card border border-border text-text-muted hover:text-text-main hover:border-primary font-display';
            }
            if (pane) {
                pane.classList.remove('block');
                pane.classList.add('hidden');
            }
        }
    });

    // Guardar preferencia en sesión local
    try {
        sessionStorage.setItem('help_active_tab', tabId);
    } catch(e) {}
}

// Alternar acordeón paso a paso
function toggleAccordion(id) {
    const el = document.getElementById(id);
    const icon = document.getElementById('acc-icon-' + id);
    if (!el) return;

    if (el.classList.contains('hidden')) {
        el.classList.remove('hidden');
        if (icon) icon.style.transform = 'rotate(180deg)';
    } else {
        el.classList.add('hidden');
        if (icon) icon.style.transform = 'rotate(0deg)';
    }
}

// Restaurar pestaña activa al recargar
document.addEventListener('DOMContentLoaded', () => {
    try {
        const saved = sessionStorage.getItem('help_active_tab');
        if (saved && ['inventario', 'taller', 'ventas', 'clientes', 'proveedores'].includes(saved)) {
            switchTab(saved);
        }
    } catch(e) {}
});
</script>

<?php
Layout::renderFooter();
?>
