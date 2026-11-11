<?php
// modules/workshop/Views/print_receipt.php
require_once __DIR__ . '/../../../classes/QRCodeSVG.php';

/**
 * Variables inyectadas por WorkshopController::print_receipt()
 * @var array<string, mixed> $repair
 */
$repair = $repair ?? [];

$codigo_orden = (string)($repair['codigo_orden'] ?? '');
$id_reparacion = (int)($repair['id_reparacion'] ?? 0);
$cliente_nombre = trim((string)($repair['cliente_nombre'] ?? 'Cliente Particular'));
$cliente_telefono = trim((string)($repair['cliente_telefono'] ?? ''));
$cliente_email = trim((string)($repair['cliente_email'] ?? ''));
$equipo_completo = trim(($repair['equipo_marca'] ?? '') . ' ' . ($repair['equipo_modelo'] ?? ''));
$equipo_imei = trim((string)($repair['equipo_imei'] ?? ''));
$falla_declarada = trim((string)($repair['falla_declarada'] ?? 'Sin especificar'));
$observaciones = trim((string)($repair['observaciones'] ?? ''));
$fecha_ingreso_str = !empty($repair['fecha_ingreso'])
    ? date('d/m/Y H:i', strtotime($repair['fecha_ingreso']))
    : date('d/m/Y H:i');

// Construir URL absoluta al portal de seguimiento público (track.php)
$appUrl = rtrim((string)($_ENV['APP_URL'] ?? ''), '/');
if ($appUrl !== '' && filter_var($appUrl, FILTER_VALIDATE_URL)) {
    $basePublicUrl = $appUrl . BASE_URL;
} else {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443);
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
    $basePublicUrl = $scheme . '://' . $host . BASE_URL;
}

$trackingUrl = $basePublicUrl . '/track.php?code=' . urlencode($codigo_orden);
$shortPortalDisplay = ltrim(BASE_URL, '/') . '/track.php';

// Presupuesto total estimado (mano de obra + repuestos cargados)
$presupuesto_total = floatval($repair['presupuesto'] ?? 0) + floatval($repair['costo_total'] ?? 0);
$sena_inicial = isset($_GET['sena']) && is_numeric($_GET['sena']) ? max(0, floatval($_GET['sena'])) : 0.0;
$saldo_estimado = max(0, $presupuesto_total - $sena_inicial);

// Primer nombre del cliente para saludo cercano en WhatsApp
$partes_nombre = preg_split('/\s+/', $cliente_nombre);
$primer_nombre = !empty($partes_nombre[0]) ? $partes_nombre[0] : $cliente_nombre;

// Mensaje de WhatsApp dinámico
$waMessage = "¡Hola {$primer_nombre}! Tu equipo {$equipo_completo} ingresó al taller técnico. Podés seguir el diagnóstico y avance en tiempo real desde este link:\n{$trackingUrl}";

// Normalizar teléfono para enlace wa.me
$digitsPhone = preg_replace('/[^0-9]/', '', $cliente_telefono);
if ($digitsPhone !== '') {
    // Si tiene 10 dígitos (ej. 3704123456 en Argentina), anteponer 549
    if (strlen($digitsPhone) === 10 && substr($digitsPhone, 0, 2) !== '54') {
        $digitsPhone = '549' . $digitsPhone;
    } elseif (strlen($digitsPhone) === 11 && substr($digitsPhone, 0, 1) === '0') {
        $digitsPhone = '549' . substr($digitsPhone, 1);
    }
    $waHref = "https://wa.me/{$digitsPhone}?text=" . rawurlencode($waMessage);
} else {
    $waHref = "https://api.whatsapp.com/send?text=" . rawurlencode($waMessage);
}

// Generar QR vectorial en el servidor (100% autónomo y offline)
$qrSvg = QRCodeSVG::render($trackingUrl, 138, 1, '#15171a', '#ffffff');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden #<?php echo htmlspecialchars($codigo_orden); ?> - Comprobante de Ingreso y Custodia</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/workshop/print_receipt.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- Barra Superior de Acciones (Solo en pantalla, no se imprime) -->
    <header class="screen-topbar no-print">
        <div class="topbar-left">
            <?php if ($id_reparacion > 0): ?>
                <a href="index.php?action=view&id=<?php echo $id_reparacion; ?>" class="btn-back" title="Volver al detalle de la orden">
                    <span>← Volver</span>
                </a>
            <?php endif; ?>
            <h1 class="topbar-order-title">Orden #<?php echo htmlspecialchars($codigo_orden); ?></h1>
            <span class="screen-only-badge">SOLO EN PANTALLA, NO SE IMPRIME</span>
        </div>

        <div class="topbar-actions">
            <button type="button" onclick="window.print()" class="action-btn action-btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                <span>Imprimir Ticket / A4</span>
            </button>

            <a href="<?php echo htmlspecialchars($waHref, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="action-btn action-btn-wa">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                </svg>
                <span>Enviar por WhatsApp</span>
            </a>

            <button type="button" id="copyLinkBtn" data-url="<?php echo htmlspecialchars($trackingUrl, ENT_QUOTES, 'UTF-8'); ?>" class="action-btn action-btn-outline">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                </svg>
                <span id="copyLinkText">Copiar link</span>
            </button>
        </div>
    </header>

    <div class="workspace-grid">
        <!-- COLUMNA IZQUIERDA: Hoja Oficial Imprimible -->
        <main class="receipt-container">
            <div class="receipt-header">
                <div>
                    <h2 class="company-title">NOKIA 1100 · SERVICIO TÉCNICO</h2>
                    <p class="company-meta">Av. Ejemplo 1234 - Formosa</p>
                    <p class="company-meta">Tel: +54 9 11 1234-5678</p>
                </div>
                <div class="order-meta-right">
                    <p class="order-type-label">ORDEN DE INGRESO Y CUSTODIA</p>
                    <p class="order-number">N° #<?php echo htmlspecialchars($codigo_orden); ?></p>
                    <p class="order-date">Fecha: <?php echo htmlspecialchars($fecha_ingreso_str); ?> hs</p>
                </div>
            </div>

            <!-- Datos del Cliente y del Equipo -->
            <section class="party-grid">
                <div>
                    <p class="field-caption">CLIENTE</p>
                    <p class="party-primary"><?php echo htmlspecialchars($cliente_nombre); ?></p>
                    <p class="party-sub">Tel: <?php echo htmlspecialchars($cliente_telefono !== '' ? $cliente_telefono : 'No registrado'); ?></p>
                    <?php if ($cliente_email !== ''): ?>
                        <p class="party-sub">Email: <?php echo htmlspecialchars($cliente_email); ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <p class="field-caption">EQUIPO</p>
                    <p class="party-primary"><?php echo htmlspecialchars($equipo_completo); ?></p>
                    <p class="party-sub-mono">IMEI / Serie: <?php echo htmlspecialchars($equipo_imei !== '' ? $equipo_imei : 'No registrado'); ?></p>
                </div>
            </section>

            <!-- Falla Declarada y Estado Estético -->
            <section class="technical-section">
                <div class="tech-block">
                    <p class="field-caption">FALLA DECLARADA</p>
                    <p class="tech-value"><?php echo htmlspecialchars($falla_declarada); ?></p>
                </div>
                <div class="tech-block">
                    <p class="field-caption">ESTADO ESTÉTICO</p>
                    <p class="tech-value"><?php echo htmlspecialchars($observaciones !== '' ? $observaciones : 'Equipo recibido sujeto a revisión técnica. Sin chip SIM, memoria ni funda.'); ?></p>
                </div>
            </section>

            <!-- Cuadro Económico: Presupuesto / Seña / Saldo -->
            <section class="financial-grid" id="financialGrid" data-presupuesto="<?php echo htmlspecialchars((string)$presupuesto_total, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="financial-cell">
                    <p class="financial-label">PRESUPUESTO ESTIMADO</p>
                    <p class="financial-amount" id="presupuestoDisplay">
                        <?php echo $presupuesto_total > 0 ? '$ ' . number_format($presupuesto_total, 2, ',', '.') : 'A Confirmar'; ?>
                    </p>
                </div>
                <div class="financial-cell">
                    <p class="financial-label">SEÑA RECIBIDA</p>
                    <p class="financial-amount" id="senaDisplay">
                        $ <?php echo number_format($sena_inicial, 2, ',', '.'); ?>
                    </p>
                </div>
                <div class="financial-cell">
                    <p class="financial-label">SALDO ESTIMADO</p>
                    <p class="financial-amount" id="saldoDisplay">
                        <?php echo $presupuesto_total > 0 ? '$ ' . number_format($saldo_estimado, 2, ',', '.') : 'A Confirmar'; ?>
                    </p>
                </div>
            </section>

            <!-- Términos y Condiciones de Custodia -->
            <section class="terms-section">
                <p class="field-caption">TÉRMINOS Y CONDICIONES</p>
                <p class="terms-text">
                    Custodia: el taller conserva el equipo en resguardo mientras dure el diagnóstico y la reparación. Retiro: el equipo debe retirarse dentro de los 90 días del aviso de reparación terminada (pasado dicho plazo se considerará abandonado conforme a la ley vigente). Garantía de repuestos: 30 días desde la entrega, sujeta al uso normal del equipo. Todo equipo ingresa sin chip SIM, memoria ni funda; el taller no se responsabiliza por pérdida de accesorios no declarados.
                </p>
            </section>

            <!-- Firmas y Bloque QR de Seguimiento en Tiempo Real -->
            <section class="bottom-custody-grid">
                <div class="signatures-stack">
                    <div class="signature-card">
                        <p class="signature-title">FIRMA DEL CLIENTE</p>
                        <div class="signature-line-rule"></div>
                    </div>
                    <div class="signature-card">
                        <p class="signature-title">FIRMA DEL TÉCNICO</p>
                        <div class="signature-line-rule"></div>
                    </div>
                </div>

                <div class="qr-tracking-card">
                    <div class="qr-code-wrapper">
                        <?php echo $qrSvg; ?>
                    </div>
                    <div class="qr-info-col">
                        <p class="qr-badge-title">SEGUIMIENTO ONLINE<br>EN TIEMPO REAL</p>
                        <p class="qr-desc">
                            Escaneá con tu celular para ver el avance técnico, fotos del desarme y estado.
                        </p>
                        <p class="qr-fallback-label">O ingresá a:</p>
                        <p class="qr-fallback-url"><?php echo htmlspecialchars($shortPortalDisplay); ?></p>
                        <p class="qr-fallback-code">Código: <?php echo htmlspecialchars($codigo_orden); ?></p>
                    </div>
                </div>
            </section>
        </main>

        <!-- COLUMNA DERECHA: Vista Previa de WhatsApp y Ajustes Rápidos (Solo en pantalla) -->
        <aside class="aside-panel no-print">
            <div>
                <p class="aside-section-title">MENSAJE QUE ARMA EL BOTÓN DE WHATSAPP</p>
                <div class="wa-mockup-card">
                    <div class="wa-header">
                        <p class="wa-contact-name"><?php echo htmlspecialchars($cliente_nombre); ?></p>
                        <p class="wa-contact-phone"><?php echo htmlspecialchars($cliente_telefono !== '' ? $cliente_telefono : 'Sin teléfono registrado'); ?></p>
                    </div>
                    <div class="wa-chat-area">
                        <div class="wa-bubble">
                            <p>
                                ¡Hola <?php echo htmlspecialchars($primer_nombre); ?>! Tu equipo <?php echo htmlspecialchars($equipo_completo); ?> ingresó al taller técnico. Podés seguir el diagnóstico y avance en tiempo real desde este link:
                            </p>
                            <a href="<?php echo htmlspecialchars($trackingUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="wa-bubble-link">
                                <?php echo htmlspecialchars($trackingUrl); ?>
                            </a>
                            <p class="wa-timestamp"><?php echo date('H:i'); ?></p>
                        </div>
                    </div>
                </div>
                <p class="aside-helper-note">
                    El mensaje usa el nombre, el equipo y el código de la orden. El teléfono del cliente sale de la ficha. Al escanear el QR o abrir el link, el cliente ve la línea de tiempo <strong>Recibido, Diagnóstico, Reparación, Listo, Entregado</strong>.
                </p>
            </div>

            <?php if ($presupuesto_total > 0): ?>
            <div class="deposit-config-card">
                <p class="aside-section-title" style="margin-bottom: 4px;">AJUSTAR SEÑA EN COMPROBANTE (OPCIONAL)</p>
                <p style="font-size: 11.5px; color: #5c6165; margin: 0 0 8px 0;">
                    Si el cliente deja una seña ahora, ingresala acá para que se refleje en el comprobante antes de imprimir:
                </p>
                <div class="deposit-input-row">
                    <span style="font-family: 'IBM Plex Mono', monospace; font-weight: 700;">$</span>
                    <input
                        type="number"
                        id="senaInput"
                        class="deposit-input"
                        min="0"
                        max="<?php echo htmlspecialchars((string)$presupuesto_total, ENT_QUOTES, 'UTF-8'); ?>"
                        step="100"
                        value="<?php echo htmlspecialchars((string)$sena_inicial, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="0.00"
                    >
                </div>
            </div>
            <?php endif; ?>
        </aside>
    </div>

    <script>
        (function() {
            // Botón Copiar Link de Seguimiento
            const copyBtn = document.getElementById('copyLinkBtn');
            const copyText = document.getElementById('copyLinkText');
            if (copyBtn && copyText) {
                copyBtn.addEventListener('click', async function() {
                    const url = copyBtn.getAttribute('data-url') || '';
                    try {
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            await navigator.clipboard.writeText(url);
                        } else {
                            const tempInput = document.createElement('input');
                            tempInput.value = url;
                            document.body.appendChild(tempInput);
                            tempInput.select();
                            document.execCommand('copy');
                            document.body.removeChild(tempInput);
                        }
                        const original = copyText.textContent;
                        copyText.textContent = '¡Link copiado!';
                        setTimeout(function() {
                            copyText.textContent = original;
                        }, 2000);
                    } catch (err) {
                        prompt('Copiá el link de seguimiento:', url);
                    }
                });
            }

            // Actualización en vivo de Seña Recibida y Saldo Estimado antes de imprimir
            const senaInput = document.getElementById('senaInput');
            const financialGrid = document.getElementById('financialGrid');
            const senaDisplay = document.getElementById('senaDisplay');
            const saldoDisplay = document.getElementById('saldoDisplay');

            function formatARS(val) {
                return '$ ' + Number(val).toLocaleString('es-AR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            if (senaInput && financialGrid && senaDisplay && saldoDisplay) {
                const presupuesto = parseFloat(financialGrid.getAttribute('data-presupuesto') || '0');
                senaInput.addEventListener('input', function() {
                    let sena = parseFloat(this.value);
                    if (isNaN(sena) || sena < 0) sena = 0;
                    if (sena > presupuesto) sena = presupuesto;
                    const saldo = Math.max(0, presupuesto - sena);
                    senaDisplay.textContent = formatARS(sena);
                    saldoDisplay.textContent = formatARS(saldo);
                });
            }
        })();
    </script>
</body>
</html>
