<?php
// modules/workshop/Views/track.php

/**
 * Variables inyectadas por WorkshopController::track()
 * @var string $codigo
 * @var bool $order_found
 * @var array<string, mixed>|null $repair
 * @var array<int, array<string, mixed>> $historial
 * @var array<int, array<string, mixed>> $images
 */
$codigo = $codigo ?? '';
$order_found = $order_found ?? false;
$repair = $repair ?? [];
$historial = $historial ?? [];
$images = $images ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Seguimiento de Reparación - NOKIA 1100</title>
    <script>
        (function() {
            try {
                var theme = localStorage.getItem("nokia1100_theme");
                if (theme === "dark") {
                    document.documentElement.classList.add("dark");
                    document.documentElement.setAttribute("data-theme", "dark");
                } else {
                    document.documentElement.classList.remove("dark");
                    document.documentElement.setAttribute("data-theme", "light");
                }
            } catch (e) {}
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700&family=IBM+Plex+Mono:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="<?php echo BASE_URL; ?>/assets/js/tailwind_config.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/theme.js?v=<?php echo time(); ?>"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/workshop/track.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/nokia-tracker.css?v=<?php echo time(); ?>">
    <script src="<?php echo BASE_URL; ?>/assets/js/nokia-tracker.js?v=<?php echo time(); ?>"></script>
</head>
<body class="font-sans antialiased text-text-main bg-background min-h-screen flex flex-col items-center justify-center p-4 sm:p-6">

    <!-- Botón flotante para alternar Modo Claro / Modo Oscuro (Estética Oficial Nokia 1100) -->
    <div class="fixed top-4 right-4 z-50">
        <button id="themeToggleBtn" type="button" class="sys-theme-toggle w-10 h-10 rounded-[2px] bg-surface border border-ink text-ink hover:bg-sunk flex items-center justify-center transition-all shadow-sm cursor-pointer" title="Cambiar tema (Claro / Oscuro)">
            <span class="material-symbols-outlined theme-icon-dark text-[20px]">dark_mode</span>
            <span class="material-symbols-outlined theme-icon-light text-[20px] hidden">light_mode</span>
        </button>
    </div>

    <div class="w-full max-w-3xl my-6">
        <!-- Encabezado de Marca -->
        <div class="track-header-center mb-7">
            <div class="inline-flex items-center gap-2 mb-1">
                <span class="text-2xl sm:text-3xl font-bold font-display tracking-tight text-text-main">SISTEMA NOKIA</span>
                <span class="px-2 py-0.5 rounded-[2px] bg-lcd text-[#15171a] border border-[#15171a] font-mono font-bold text-xs">1100</span>
            </div>
            <p class="text-text-muted text-[11px] font-mono uppercase tracking-widest font-semibold">Portal de Seguimiento Técnico</p>
        </div>

        <?php if (!$order_found && $codigo): ?>
            <div class="bg-error-bg border border-error text-error p-4 rounded-[3px] mb-6 text-sm flex gap-3 items-center justify-center font-medium">
                <span class="material-symbols-outlined text-[20px]">error</span>
                <span>No encontramos ninguna orden con el código <strong>#<?php echo htmlspecialchars($codigo); ?></strong></span>
            </div>
        <?php endif; ?>

        <?php if (!$codigo || (!$order_found && $codigo)): ?>
            <div class="nk-track-card p-8 track-header-center sm:mx-10 relative overflow-hidden">
                <h2 class="text-xl font-display font-bold text-text-main mb-2">Consultar Estado de mi Equipo</h2>
                <p class="text-xs text-text-muted mb-6">Ingresá el número de orden que figura en tu comprobante de ingreso</p>
                
                <form action="<?php echo BASE_URL; ?>/track.php" method="GET" class="max-w-sm mx-auto">
                    <div class="relative mb-4">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-text-muted">search</span>
                        <input type="text" name="code" placeholder="Ej: 26114413" required class="w-full bg-background border border-border-dark pl-12 pr-4 py-3 rounded-[2px] text-center text-text-main focus:outline-none focus:border-ink transition-all text-lg font-mono uppercase tracking-widest">
                    </div>
                    <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-surface font-mono uppercase tracking-wider text-xs font-bold py-3.5 rounded-[2px] transition-all cursor-pointer">
                        Consultar Orden →
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($order_found): 
            $estados = ['Recibido', 'En diagnóstico', 'En reparación', 'Listo', 'Entregado'];
            $estado_normalizado = ($repair['estado'] === 'Listo para Retirar') ? 'Listo' : $repair['estado'];
            $step_map = [
                'Recibido' => 1,
                'En diagnóstico' => 2,
                'En reparación' => 3,
                'Listo' => 4,
                'Listo para Retirar' => 4,
                'Entregado' => 5
            ];
            $step_number = $step_map[$estado_normalizado] ?? 1;
            
            $is_cancelado = ($repair['estado'] === 'Cancelado');
            $total_estimado = floatval($repair['presupuesto'] ?? 0) + floatval($repair['costo_total'] ?? 0);
        ?>
            <!-- Aviso sutil cuando cambia el estado en tiempo real -->
            <div id="liveTransitionBanner" class="nk-live-notice">
                <div class="p-3.5 rounded-[3px] bg-surface border-2 border-ink flex items-center justify-between gap-3 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 rounded-[2px] bg-lcd text-[#15171a] border border-[#15171a] font-mono text-[10px] font-bold uppercase tracking-wider">
                            NUEVO AVANCE
                        </span>
                        <p class="text-xs sm:text-sm font-semibold text-text-main" id="liveTransitionText">
                            El estado de tu equipo se actualizó.
                        </p>
                    </div>
                    <button type="button" onclick="document.getElementById('liveTransitionBanner').classList.remove('is-open')" class="text-text-muted hover:text-text-main p-1 cursor-pointer">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                    </button>
                </div>
            </div>

            <div id="mainTrackCard" class="nk-track-card p-6 sm:p-10 relative overflow-hidden space-y-8"
                 data-code="<?php echo htmlspecialchars($repair['codigo_orden'], ENT_QUOTES, 'UTF-8'); ?>"
                 data-current-status="<?php echo htmlspecialchars($repair['estado'], ENT_QUOTES, 'UTF-8'); ?>"
                 data-current-step="<?php echo (int)$step_number; ?>">

                <!-- Barra superior de la ficha -->
                <div class="flex items-center justify-between flex-wrap gap-2 pb-4 border-b border-border">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-[2px] text-[11px] font-mono uppercase tracking-widest bg-sunk border border-border-dark text-text-main font-bold">
                        <span class="material-symbols-outlined text-[15px]">receipt_long</span>
                        ORDEN #<?php echo htmlspecialchars($repair['codigo_orden']); ?>
                    </span>

                    <span class="inline-flex items-center gap-2 px-2.5 py-1 rounded-[2px] text-[10px] font-mono uppercase tracking-wider bg-sunk border border-border-dark text-text-main font-bold" title="Actualización automática en tiempo real">
                        <span class="w-2 h-2 rounded-full bg-lcd border border-[#15171a]"></span>
                        SEGUIMIENTO ACTIVO
                    </span>
                </div>

                <!-- Cabecera Centrada del Equipo -->
                <div class="track-header-center pt-1">
                    <h2 class="text-3xl sm:text-4xl font-display font-bold text-text-main mb-1.5 tracking-tight">
                        <?php echo htmlspecialchars($repair['equipo_marca'] . ' ' . $repair['equipo_modelo']); ?>
                    </h2>
                    <?php if (!empty($repair['fecha_ingreso'])): ?>
                        <p class="text-text-muted text-xs font-mono">
                            Ingresado el <?php echo date('d/m/Y H:i', strtotime($repair['fecha_ingreso'])); ?> hs
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Contenedor Cancelado -->
                <div id="canceledStateBlock" class="py-8 track-header-center <?php echo $is_cancelado ? '' : 'hidden'; ?>">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-[3px] bg-error-bg border-2 border-error mb-4">
                        <span class="material-symbols-outlined text-[34px] text-error">cancel</span>
                    </div>
                    <h3 class="text-xl font-display font-bold text-error mb-1">Orden Cancelada</h3>
                    <p class="text-text-muted text-xs">Por favor, comuníquese con el taller para más información.</p>
                </div>

                <!-- Rediseño de Line Tracking v2.0 Nokia 1100 -->
                <div id="timelineBlock" class="<?php echo $is_cancelado ? 'hidden' : ''; ?> space-y-6">
                    <div class="max-w-2xl mx-auto py-2">
                        <div class="nkt" id="repairTracker" data-nkt data-current="<?php echo (int)$step_number; ?>"
                             aria-label="Estado de tu equipo"
                             data-deliver-title="¡Equipo Entregado!" data-deliver-text="Tu equipo ya fue reparado y entregado con éxito.">
                            <ol class="nkt__steps">
                                <li class="nkt__step" data-icon="received"><span class="nkt__label">Recibido</span></li>
                                <li class="nkt__step" data-icon="diag"><span class="nkt__label">En diagnóstico</span></li>
                                <li class="nkt__step" data-icon="repair"><span class="nkt__label">En reparación</span></li>
                                <li class="nkt__step" data-icon="ready"><span class="nkt__label">Listo</span></li>
                                <li class="nkt__step" data-icon="delivered"><span class="nkt__label">Entregado</span></li>
                            </ol>
                        </div>
                    </div>

                    <!-- Etiqueta de Estado Actual Estilo Nokia 1100 -->
                    <div class="track-header-center pt-2">
                        <div id="currentStatusBadge" class="inline-flex items-center gap-2.5 px-5 py-2 rounded-[3px] border-[1.5px] border-ink bg-sunk text-text-main font-mono text-xs uppercase tracking-wider font-semibold transition-all duration-300">
                            <span class="w-2.5 h-2.5 rounded-[1px] bg-lcd border border-[#15171a]"></span>
                            <span>ESTADO ACTUAL: <strong id="currentStatusText" class="font-bold"><?php echo htmlspecialchars($repair['estado']); ?></strong></span>
                        </div>
                    </div>
                </div>

                <!-- Tarjetas de Detalle Técnico y Presupuesto -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div class="nk-subcard p-5">
                        <span class="text-[11px] font-mono font-bold uppercase tracking-wider text-text-muted flex items-center gap-1.5 mb-2">
                            <span class="material-symbols-outlined text-[16px] text-text-main">build_circle</span>
                            FALLA DECLARADA
                        </span>
                        <p class="text-sm font-medium text-text-main leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($repair['falla_declarada'] ?? 'Sin detalle')); ?>
                        </p>
                    </div>

                    <div class="nk-subcard p-5 flex flex-col justify-between">
                        <div>
                            <span class="text-[11px] font-mono font-bold uppercase tracking-wider text-text-muted flex items-center gap-1.5 mb-2">
                                <span class="material-symbols-outlined text-[16px] text-text-main">payments</span>
                                PRESUPUESTO ESTIMADO
                            </span>
                            <p id="budgetDisplayText" class="text-2xl font-mono font-bold text-text-main">
                                <?php echo $total_estimado > 0 ? '$ ' . number_format($total_estimado, 2, ',', '.') : 'En evaluación'; ?>
                            </p>
                        </div>
                        <?php if (!empty($repair['equipo_imei'])): ?>
                            <p class="text-xs font-mono text-text-muted mt-2">
                                IMEI/Serie: <span class="text-text-main font-semibold"><?php echo htmlspecialchars($repair['equipo_imei']); ?></span>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Galería de Fotos del Equipo / Desarme -->
                <div id="imagesSectionWrapper" class="pt-1 <?php echo !empty($images) ? '' : 'hidden'; ?>">
                    <h3 class="text-xs font-mono font-bold uppercase tracking-wider text-text-muted flex items-center gap-1.5 mb-3">
                        <span class="material-symbols-outlined text-[16px] text-text-main">photo_camera</span>
                        <span>FOTOS DEL EQUIPO Y DIAGNÓSTICO (<span id="imagesCountText"><?php echo count($images); ?></span>)</span>
                    </h3>
                    <div id="imagesGridContainer" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <?php foreach ($images as $img): 
                            $imgUrl = BASE_URL . '/assets/img/reparaciones/' . rawurlencode($img['ruta_archivo']);
                        ?>
                            <a href="<?php echo htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="group relative rounded-[3px] overflow-hidden border border-border-dark bg-background aspect-video block">
                                <img src="<?php echo htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Foto del equipo" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                <span class="absolute bottom-2 left-2 px-2 py-0.5 rounded-[2px] bg-[#15171a] text-lcd text-[10px] font-mono font-bold uppercase">
                                    <?php echo htmlspecialchars($img['tipo'] ?? 'Taller'); ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Historial de Avances Técnicos -->
                <?php if (!empty($historial)): ?>
                <div class="pt-1">
                    <h3 class="text-xs font-mono font-bold uppercase tracking-wider text-text-muted flex items-center gap-1.5 mb-3">
                        <span class="material-symbols-outlined text-[16px] text-text-main">history</span>
                        AVANCES REGISTRADOS EN TALLER
                    </h3>
                    <div id="historyListContainer" class="space-y-2.5">
                        <?php foreach ($historial as $h): ?>
                            <div class="nk-subcard p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div>
                                    <span class="inline-block text-xs font-mono font-bold text-text-main uppercase tracking-wider">
                                        <?php echo htmlspecialchars($h['estado_nuevo']); ?>
                                    </span>
                                    <?php if (!empty($h['nota'])): ?>
                                        <p class="text-xs text-text-muted mt-1 font-medium"><?php echo htmlspecialchars($h['nota']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <span class="text-xs font-mono text-text-muted shrink-0">
                                    <?php echo date('d/m/Y H:i', strtotime($h['fecha_cambio'])); ?> hs
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php
                    $wsOrder = !empty($repair['codigo_orden']) ? $repair['codigo_orden'] : ($codigo ?? '');
                    $wsEquipo = !empty($repair['equipo_marca']) ? ($repair['equipo_marca'] . ' ' . ($repair['equipo_modelo'] ?? '')) : '';
                    $wsText = "Hola! Quisiera consultar sobre el estado de la orden de reparación" . ($wsOrder ? " #$wsOrder" : "") . ($wsEquipo ? " ($wsEquipo)" : "");
                    $wsPhone = preg_replace('/[^0-9]/', '', $_ENV['WHATSAPP_PHONE'] ?? '5491112345678');
                    $wsLink = "https://wa.me/{$wsPhone}?text=" . rawurlencode($wsText);
                ?>
                <div class="pt-6 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-text-muted text-xs sm:text-sm flex items-center justify-center gap-2">
                        <span>¿Tenés alguna consulta?</span>
                        <a href="<?php echo htmlspecialchars($wsLink, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 text-text-main hover:underline font-mono text-xs font-bold uppercase tracking-wider">
                            <span class="material-symbols-outlined text-[16px]">chat</span> WhatsApp al Taller
                        </a>
                    </p>
                    <a href="<?php echo BASE_URL; ?>/track.php" class="text-xs font-mono font-semibold text-text-muted hover:text-text-main underline decoration-border underline-offset-4">
                        Consultar otro código
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
    </div>

    <script>
        (function() {
            const card = document.getElementById('mainTrackCard');
            if (!card) return;

            const orderCode = card.getAttribute('data-code') || '';
            let currentStatus = card.getAttribute('data-current-status') || '';

            const stepMap = {
                'Recibido': 1,
                'En diagnóstico': 2,
                'En reparación': 3,
                'Listo': 4,
                'Listo para Retirar': 4,
                'Entregado': 5
            };

            // Sondeo en tiempo real cada 4 segundos para detectar avances desde el taller
            if (orderCode) {
                setInterval(async () => {
                    try {
                        const res = await fetch(`<?php echo BASE_URL; ?>/track.php?code=${encodeURIComponent(orderCode)}&ajax=1`, {
                            headers: { 'Accept': 'application/json' },
                            cache: 'no-store'
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        if (!data || !data.success) return;

                        // Actualizar presupuesto si cambió
                        const budgetEl = document.getElementById('budgetDisplayText');
                        if (budgetEl && data.total_formatted) {
                            budgetEl.textContent = data.total_formatted;
                        }

                        // Si cambió el estado, actualizar componente de seguimiento Nokia
                        if (data.estado && data.estado !== currentStatus) {
                            const prevStatus = currentStatus;
                            currentStatus = data.estado;

                            const nextStep = stepMap[currentStatus] || 1;
                            const canceledBlock = document.getElementById('canceledStateBlock');
                            const timelineBlock = document.getElementById('timelineBlock');

                            if (currentStatus === 'Cancelado') {
                                if (timelineBlock) timelineBlock.classList.add('hidden');
                                if (canceledBlock) canceledBlock.classList.remove('hidden');
                            } else {
                                if (canceledBlock) canceledBlock.classList.add('hidden');
                                if (timelineBlock) timelineBlock.classList.remove('hidden');
                                if (window.NKTracker) {
                                    window.NKTracker.set('#repairTracker', nextStep);
                                }
                            }

                            const statusTextEl = document.getElementById('currentStatusText');
                            if (statusTextEl) {
                                statusTextEl.textContent = currentStatus;
                            }

                            // Mostrar aviso sutil de actualización
                            const banner = document.getElementById('liveTransitionBanner');
                            const bannerText = document.getElementById('liveTransitionText');
                            if (banner && bannerText) {
                                bannerText.textContent = `Estado actualizado: de "${prevStatus}" a "${currentStatus}".`;
                                banner.classList.add('is-open');
                            }
                        }

                        // Actualizar historial si se agregaron avances
                        const historyContainer = document.getElementById('historyListContainer');
                        if (historyContainer && Array.isArray(data.historial)) {
                            const currentCount = historyContainer.children.length;
                            if (data.historial.length !== currentCount) {
                                historyContainer.innerHTML = data.historial.map((h, i) => `
                                    <div class="nk-subcard p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2 ${i === 0 ? 'history-item-new' : ''}">
                                        <div>
                                            <span class="inline-block text-xs font-mono font-bold text-text-main uppercase tracking-wider"></span>
                                            ${h.nota ? '<p class="text-xs text-text-muted mt-1 font-medium hist-note"></p>' : ''}
                                        </div>
                                        <span class="text-xs font-mono text-text-muted shrink-0 hist-date"></span>
                                    </div>
                                `).join('');

                                Array.from(historyContainer.children).forEach((row, idx) => {
                                    const item = data.historial[idx];
                                    const stSpan = row.querySelector('span');
                                    const noteP = row.querySelector('.hist-note');
                                    const dateSpan = row.querySelector('.hist-date');
                                    if (stSpan) stSpan.textContent = item.estado_nuevo;
                                    if (noteP) noteP.textContent = item.nota;
                                    if (dateSpan) dateSpan.textContent = item.fecha_cambio;
                                });
                            }
                        }
                    } catch (e) {
                        // Ignorar errores de red temporales
                    }
                }, 4000);
            }
        })();
    </script>
                }, 4000);
            }
        })();
    </script>
</body>
</html>
