<?php
// modules/sales/Views/credit_note.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nota de Crédito #NC-<?php echo $venta['id_venta']; ?> - NOKIA 1100</title>
    <!-- Incluir iconos Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/sales/sales_invoice.css?v=<?php echo time(); ?>">
    <style>
        .invoice-title-section h2 {
            color: #dc2626 !important; /* Rojo para nota de crédito */
        }
        .text-red {
            color: #dc2626;
        }
    </style>
</head>
<body onload="setTimeout(() => window.print(), 500)">
    <div class="invoice-wrapper">
        <div class="no-print">
            <button onclick="window.close()" class="btn btn-secondary">
                <span class="material-symbols-outlined" style="font-size: 18px;">close</span> Cerrar
            </button>
            <button onclick="window.print()" class="btn btn-primary">
                <span class="material-symbols-outlined" style="font-size: 18px;">print</span> Imprimir Nota
            </button>
        </div>

        <div class="invoice-box">
            <div class="header">
                <div class="brand-section">
                    <h1>NOKIA 1100</h1>
                    <p>Sistema de Gestión Empresarial</p>
                </div>
                <div class="invoice-title-section">
                    <h2>NOTA DE CRÉDITO</h2>
                    <p>#NC-<?php echo str_pad($venta['id_venta'], 5, '0', STR_PAD_LEFT); ?></p>
                    <p style="font-size: 12px; margin-top: 5px; color: #666;">Comprobante de Devolución / Anulación</p>
                </div>
            </div>

            <div class="invoice-details">
                <div class="details-block">
                    <h3>Emitido A</h3>
                    <p><strong>Cliente:</strong> Consumidor Final</p>
                    <p><strong>Condición IVA:</strong> Consumidor Final</p>
                </div>
                <div class="details-block text-right">
                    <h3>Detalles de Emisión</h3>
                    <p><strong>Fecha Venta Orig.:</strong> <?php echo date('d/m/Y', strtotime($venta['fecha'])); ?></p>
                    <p><strong>Hora:</strong> <?php echo date('H:i', strtotime($venta['fecha'])); ?></p>
                    <p><strong>Método de Reintegro:</strong> <span style="text-transform: uppercase;"><?php echo htmlspecialchars($venta['metodo_de_pago']); ?></span></p>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Descripción del Producto Devuelto</th>
                        <th class="text-center">Cant.</th>
                        <th class="text-right">Precio Unitario</th>
                        <th class="text-right">Monto a Reintegrar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $item): ?>
                    <tr>
                        <td class="item-name"><span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: #dc2626;">undo</span> <?php echo htmlspecialchars($item['nombre_producto']); ?></td>
                        <td class="text-center">-<?php echo $item['cantidad']; ?> u.</td>
                        <td class="text-right">$<?php echo number_format($item['precio_unitario'], 2); ?></td>
                        <td class="text-right text-red">-$<?php echo number_format($item['precio_unitario'] * $item['cantidad'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="total-section">
                <div class="total-box">
                    <div class="total-row">
                        <span>Subtotal a devolver:</span>
                        <span class="text-red">-$<?php echo number_format($venta['total'], 2); ?></span>
                    </div>
                    <div class="total-row grand-total" style="color: #dc2626; border-top-color: #dc2626;">
                        <span>Total Reintegrado:</span>
                        <span>-$<?php echo number_format($venta['total'], 2); ?></span>
                    </div>
                </div>
            </div>

            <div class="description-section" style="margin-top: 20px; padding: 15px; background: #fff1f2; border-radius: 8px; border-left: 4px solid #dc2626;">
                <h4 style="margin: 0 0 5px 0; font-size: 12px; color: #991b1b; text-transform: uppercase;">Aclaración</h4>
                <p style="margin: 0; font-size: 13px; color: #7f1d1d; white-space: pre-wrap;">Este documento certifica la anulación de la Venta #TX-<?php echo str_pad($venta['id_venta'], 5, '0', STR_PAD_LEFT); ?>. El monto detallado ha sido reintegrado y el stock correspondiente devuelto al inventario general.</p>
            </div>

            <div class="footer" style="margin-top: 30px;">
                <p><strong>Operación Reversada en NOKIA 1100</strong></p>
                <p>Este documento es un comprobante de control interno de stock y caja.</p>
            </div>
        </div>
    </div>
</body>
</html>
