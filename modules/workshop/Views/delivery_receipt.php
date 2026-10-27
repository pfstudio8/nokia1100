<?php
// modules/workshop/Views/delivery_receipt.php

/**
 * Variables inyectadas por WorkshopController::delivery_receipt()
 * @var array $repair
 * @var array $repuestos
 * @var array $historial
 * @var array|null $venta
 */
$repair = $repair ?? [];
$repuestos = $repuestos ?? [];
$historial = $historial ?? [];
$venta = $venta ?? null;

$presupuesto_mano_obra = floatval($repair['presupuesto'] ?? 0);
$costo_repuestos = floatval($repair['costo_total'] ?? 0);
$total_pagado = $venta ? floatval($venta['total']) : ($presupuesto_mano_obra + $costo_repuestos);
$metodo_pago = $venta['metodo_de_pago'] ?? 'Efectivo';
$cajero = $venta['cajero'] ?? 'Caja';
$fecha_entrega = !empty($repair['fecha_entrega']) ? date('d/m/Y H:i', strtotime($repair['fecha_entrega'])) : date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Entrega y Garantía #<?php echo htmlspecialchars($repair['codigo_orden'] ?? ''); ?> - NOKIA 1100</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/workshop/print_receipt.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <style>
        .warranty-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-left: 4px solid #16a34a;
            padding: 14px 18px;
            margin: 20px 0;
            border-radius: 6px;
        }
        .warranty-title {
            color: #15803d;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .warranty-text {
            font-size: 11px;
            color: #166534;
            line-height: 1.5;
            margin: 0;
        }
        .cost-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 13px;
        }
        .cost-table th {
            text-align: left;
            background: #f8fafc;
            padding: 8px 10px;
            border-bottom: 2px solid #e2e8f0;
            font-size: 11px;
            text-transform: uppercase;
            color: #64748b;
        }
        .cost-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #f1f5f9;
        }
        .btn-print {
            padding: 10px 22px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            background: #0ea5a0;
            color: #fff;
            border: none;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }
        .btn-print:hover {
            background: #0d9488;
        }
        .btn-close {
            padding: 10px 18px;
            font-size: 14px;
            cursor: pointer;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            margin-right: 10px;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            .receipt-container { border: none; padding: 20px; max-width: 100%; }
        }
    </style>
</head>
<body onload="setTimeout(() => window.print(), 500)">
    <div class="no-print" style="text-align: center; margin-bottom: 25px;">
        <button onclick="window.close()" class="btn-close">Cerrar</button>
        <button onclick="window.print()" class="btn-print">
            <span class="material-symbols-outlined" style="font-size: 18px;">print</span> Imprimir Comprobante de Garantía
        </button>
    </div>

    <div class="receipt-container">
        <div class="header">
            <div>
                <h1 class="logo">NOKIA<span>1100</span></h1>
                <p style="font-weight: 600; margin-top: 3px;">Servicio Técnico Especializado</p>
                <p>Dirección Comercial: Av. Principal 1234</p>
                <p>WhatsApp / Soporte: +54 9 11 1234-5678</p>
            </div>
            <div class="info-header">
                <h2 style="color: #0f766e; font-size: 18px;">ENTREGA Y GARANTÍA</h2>
                <p><strong>Orden:</strong> #<?php echo htmlspecialchars($repair['codigo_orden'] ?? ''); ?></p>
                <?php if ($venta && !empty($venta['id_venta'])): ?>
                    <p><strong>Factura / Recibo:</strong> #TX-<?php echo str_pad($venta['id_venta'], 5, '0', STR_PAD_LEFT); ?></p>
                <?php endif; ?>
                <p><strong>Fecha Entrega:</strong> <?php echo $fecha_entrega; ?></p>
                <p><strong>Despachado por:</strong> <?php echo htmlspecialchars($cajero); ?></p>
            </div>
        </div>

        <div class="row">
            <div class="col" style="padding-right: 15px;">
                <h3>Titular del Equipo</h3>
                <p><strong>Nombre y Apellido:</strong> <?php echo htmlspecialchars($repair['cliente_nombre'] ?? 'Consumidor Final'); ?></p>
                <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($repair['cliente_telefono'] ?? 'No especificado'); ?></p>
                <?php if (!empty($repair['cliente_email'])): ?>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($repair['cliente_email']); ?></p>
                <?php endif; ?>
            </div>
            <div class="col" style="padding-left: 15px; border-left: 1px solid #eee;">
                <h3>Dispositivo Retirado</h3>
                <p><strong>Marca y Modelo:</strong> <?php echo htmlspecialchars(($repair['equipo_marca'] ?? '') . ' ' . ($repair['equipo_modelo'] ?? '')); ?></p>
                <p><strong>Nro. Serie / IMEI:</strong> <?php echo htmlspecialchars($repair['equipo_imei'] ?? 'S/N'); ?></p>
                <p><strong>Ingreso original:</strong> <?php echo date('d/m/Y H:i', strtotime($repair['fecha_ingreso'] ?? 'now')); ?></p>
            </div>
        </div>

        <div class="box" style="margin-bottom: 15px;">
            <h3>Diagnóstico y Trabajo Realizado</h3>
            <p><strong>Falla Reportada:</strong> <?php echo nl2br(htmlspecialchars($repair['falla_declarada'] ?? 'Revisión técnica')); ?></p>
            <?php 
                // Buscar última nota técnica en el historial
                $notaTecnica = '';
                foreach ($historial as $h) {
                    if (str_starts_with($h['nota'], 'Finalizado por técnico')) {
                        $notaTecnica = $h['nota'];
                        break;
                    }
                }
                if ($notaTecnica): 
            ?>
                <p style="margin-top: 8px; color: #334155;"><strong>Resolución Técnica:</strong> <?php echo htmlspecialchars($notaTecnica); ?></p>
            <?php endif; ?>
        </div>

        <table class="cost-table">
            <thead>
                <tr>
                    <th>Concepto / Detalle del Servicio</th>
                    <th style="text-align: center; width: 80px;">Cant.</th>
                    <th style="text-align: right; width: 120px;">Unitario</th>
                    <th style="text-align: right; width: 120px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Mano de Obra Calificada / Reparación Técnica</td>
                    <td style="text-align: center;">1</td>
                    <td style="text-align: right;">$<?php echo number_format($presupuesto_mano_obra, 2); ?></td>
                    <td style="text-align: right;">$<?php echo number_format($presupuesto_mano_obra, 2); ?></td>
                </tr>
                <?php foreach ($repuestos as $r): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['nombre']); ?> (Repuesto instalado)</td>
                        <td style="text-align: center;"><?php echo $r['cantidad']; ?></td>
                        <td style="text-align: right;">$<?php echo number_format($r['precio_unitario'], 2); ?></td>
                        <td style="text-align: right;">$<?php echo number_format($r['cantidad'] * $r['precio_unitario'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr style="border-top: 2px solid #222; font-weight: bold; font-size: 14px;">
                    <td colspan="3" style="text-align: right; padding-top: 10px;">TOTAL ABONADO (<?php echo strtoupper(htmlspecialchars($metodo_pago)); ?>):</td>
                    <td style="text-align: right; color: #0f766e; padding-top: 10px;">$<?php echo number_format($total_pagado, 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="warranty-box">
            <div class="warranty-title">
                <span class="material-symbols-outlined" style="font-size: 16px;">verified</span>
                Póliza de Garantía Oficial (90 Días Corridos)
            </div>
            <p class="warranty-text">
                El presente servicio técnico cuenta con una garantía de <strong>90 días</strong> a partir de la fecha de entrega sobre el trabajo realizado y los repuestos nuevos instalados detallados en este comprobante.
                <br>
                <strong>Exclusiones de Garantía:</strong> La garantía queda sin efecto si el equipo presenta roturas posteriores, golpes, rajaduras en display/táctil, ingreso de humedad o líquidos, evidencias de sulfatación o desarme/manipulación técnica por personas ajenas a nuestro taller.
            </p>
        </div>

        <div class="signature-section" style="margin-top: 40px;">
            <div class="signature-box">
                <div class="signature-line">
                    <strong>Firma y Aclaración del Cliente</strong><br>
                    <span style="font-size: 11px; color: #888;">Retiro conforme del equipo y accesorios</span>
                </div>
            </div>
            <div class="signature-box">
                <div class="signature-line">
                    <strong>Firma y Sello del Local</strong><br>
                    <span style="font-size: 11px; color: #888;">Nokia 1100 - Servicio Técnico Oficial</span>
                </div>
            </div>
        </div>

        <p class="terms" style="text-align: center; margin-top: 30px;">
            Gracias por confiar en <strong>NOKIA 1100</strong>. Guarde este documento como comprobante oficial para cualquier reclamo de garantía.
        </p>
    </div>
</body>
</html>
