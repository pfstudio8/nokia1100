<?php
// modules/sales/Views/invoice.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura #TX-<?php echo $venta['id_venta']; ?> - NOKIA 1100</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/sales/sales_invoice.css?v=<?php echo time(); ?>">
</head>
<body onload="setTimeout(() => window.print(), 500)">
    <div class="invoice-box">
        <div class="no-print" style="margin-bottom: 20px;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #111827; color: white; border: none; border-radius: 6px; cursor: pointer;">Imprimir Factura</button>
            <button onclick="window.close()" style="padding: 10px 20px; background: #e5e7eb; color: #111827; border: none; border-radius: 6px; cursor: pointer; margin-left: 10px;">Cerrar</button>
        </div>

        <div class="header">
            <h1>NOKIA 1100 INC.</h1>
            <p>Comprobante de Venta</p>
        </div>

        <div class="invoice-details">
            <div>
                <strong>Facturar A:</strong><br>
                Consumidor Final
            </div>
            <div class="right">
                <strong>Factura #:</strong> TX-<?php echo $venta['id_venta']; ?><br>
                <strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($venta['fecha'])); ?><br>
                <strong>Método de Pago:</strong> <?php echo htmlspecialchars($venta['metodo_de_pago']); ?>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th class="text-center">Cant.</th>
                    <th class="text-right">Precio Unit.</th>
                    <th class="text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['nombre_producto']); ?></td>
                    <td class="text-center"><?php echo $item['cantidad']; ?></td>
                    <td class="text-right">$<?php echo number_format($item['precio_unitario'], 2); ?></td>
                    <td class="text-right">$<?php echo number_format($item['precio_unitario'] * $item['cantidad'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-section">
            <div>
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($venta['total'], 2); ?></span>
                </div>
                <div class="total-row grand-total">
                    <span>Total:</span>
                    <span>$<?php echo number_format($venta['total'], 2); ?></span>
                </div>
            </div>
        </div>

        <div class="footer">
            ¡Gracias por su compra!<br>
            Este documento no es válido como factura fiscal si no se encuentra homologado.
        </div>
    </div>
</body>
</html>
