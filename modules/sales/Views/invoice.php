<?php
// modules/sales/Views/invoice.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura #TX-<?php echo $venta['id_venta']; ?> - NOKIA 1100</title>
    <!-- Incluir iconos Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/sales/sales_invoice.css?v=<?php echo time(); ?>">
</head>
<body onload="setTimeout(() => window.print(), 500)">
    <div class="invoice-wrapper">
        <div class="no-print">
            <button onclick="window.close()" class="btn btn-secondary">
                <span class="material-symbols-outlined" style="font-size: 18px;">close</span> Cerrar
            </button>
            <button onclick="window.print()" class="btn btn-primary">
                <span class="material-symbols-outlined" style="font-size: 18px;">print</span> Imprimir Factura
            </button>
        </div>

        <div class="invoice-box">
            <div class="header">
                <div class="brand-section">
                    <h1>NOKIA 1100</h1>
                    <p>Sistema de Gestión Empresarial</p>
                </div>
                <div class="invoice-title-section">
                    <h2>COMPROBANTE</h2>
                    <p>#TX-<?php echo str_pad($venta['id_venta'], 5, '0', STR_PAD_LEFT); ?></p>
                </div>
            </div>

            <div class="invoice-details">
                <div class="details-block">
                    <h3>Facturar A</h3>
                    <p><strong>Cliente:</strong> Consumidor Final</p>
                    <p><strong>Condición IVA:</strong> Consumidor Final</p>
                </div>
                <div class="details-block text-right">
                    <h3>Detalles de Emisión</h3>
                    <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($venta['fecha'])); ?></p>
                    <p><strong>Hora:</strong> <?php echo date('H:i', strtotime($venta['fecha'])); ?></p>
                    <p><strong>Método de Pago:</strong> <span style="text-transform: uppercase;"><?php echo htmlspecialchars($venta['metodo_de_pago']); ?></span></p>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Descripción del Producto</th>
                        <th class="text-center">Cant.</th>
                        <th class="text-right">Precio Unitario</th>
                        <th class="text-right">Monto Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $item): ?>
                    <tr>
                        <td class="item-name"><?php echo htmlspecialchars($item['nombre_producto']); ?></td>
                        <td class="text-center"><?php echo $item['cantidad']; ?> u.</td>
                        <td class="text-right">$<?php echo number_format($item['precio_unitario'], 2); ?></td>
                        <td class="text-right">$<?php echo number_format($item['precio_unitario'] * $item['cantidad'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="total-section">
                <div class="total-box">
                    <div class="total-row">
                        <span>Subtotal neto:</span>
                        <span>$<?php echo number_format($venta['total'], 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Descuentos:</span>
                        <span>$0.00</span>
                    </div>
                    <div class="total-row grand-total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($venta['total'], 2); ?></span>
                    </div>
                </div>
            </div>

            <div class="footer">
                <p><strong>¡Gracias por su compra en NOKIA 1100!</strong></p>
                <p>Este documento es un comprobante de control interno y no es válido como factura fiscal si no se encuentra homologado.</p>
            </div>
        </div>
    </div>
</body>
</html>
