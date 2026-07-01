<?php
// modules/workshop/Views/print_receipt.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Reparación #<?php echo htmlspecialchars($repair['codigo_orden']); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/workshop/print_receipt.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; background: #222; color: #fff; border: none; border-radius: 4px;">Imprimir Comprobante</button>
    </div>

    <div class="receipt-container">
        <div class="header">
            <div>
                <h1 class="logo">NOKIA<span>1100</span></h1>
                <p>Servicio Técnico de Reparaciones</p>
                <p>Dirección de ejemplo 123</p>
                <p>Tel: +54 9 11 1234-5678</p>
            </div>
            <div class="info-header">
                <h2>ORDEN DE REPARACIÓN</h2>
                <p><strong>Nro:</strong> #<?php echo htmlspecialchars($repair['codigo_orden']); ?></p>
                <p><strong>Fecha Ingreso:</strong> <?php echo date('d/m/Y H:i', strtotime($repair['fecha_ingreso'])); ?></p>
                <p><strong>Estado Actual:</strong> <?php echo htmlspecialchars($repair['estado']); ?></p>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <h3>Datos del Cliente</h3>
                <p><strong>Nombre:</strong> <?php echo htmlspecialchars($repair['cliente_nombre']); ?></p>
                <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($repair['cliente_telefono']); ?></p>
            </div>
            <div class="col">
                <h3>Datos del Equipo</h3>
                <p><strong>Marca y Modelo:</strong> <?php echo htmlspecialchars($repair['equipo_marca'] . ' ' . $repair['equipo_modelo']); ?></p>
                <p><strong>IMEI / Serie:</strong> <?php echo htmlspecialchars($repair['equipo_imei']); ?></p>
            </div>
        </div>

        <div class="box">
            <h3>Falla Declarada</h3>
            <p><?php echo nl2br(htmlspecialchars($repair['falla_declarada'])); ?></p>
        </div>

        <?php if($repair['observaciones']) { ?>
        <div class="box" style="background: #fff;">
            <h3>Condiciones físicas (Ingreso)</h3>
            <p><?php echo nl2br(htmlspecialchars($repair['observaciones'])); ?></p>
        </div>
        <?php } ?>

        <div class="row">
            <div class="col">
                <h3>Presupuesto Estimado</h3>
                <p style="font-size: 24px; font-weight: bold; margin-top: 10px;">
                    <?php echo $repair['presupuesto'] ? '$' . number_format($repair['presupuesto'], 2) : 'A Confirmar'; ?>
                </p>
                <?php if($repair['costo_total'] > 0) { ?>
                    <p style="color:#666; font-size: 12px; margin-top:10px;">Costo repuestos asignados: $<?php echo number_format($repair['costo_total'], 2); ?></p>
                <?php } ?>
            </div>
        </div>

        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">Firma del Cliente (Entrega del equipo)</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Firma del Técnico (Recepción)</div>
            </div>
        </div>

        <div class="terms">
            <strong>Términos y Condiciones:</strong> 1. El diagnóstico puede tener un costo que será informado. 2. Los repuestos están sujetos a disponibilidad de stock. 3. Pasados los 90 días de la notificación de reparación finalizada, si el equipo no es retirado, la empresa podrá disponer del mismo según la ley vigente. 4. Todo equipo debe ingresar sin chips, memorias ni fundas; la empresa no se responsabiliza por pérdida de los mismos ni de información personal.
        </div>
    </div>
</body>
</html>
