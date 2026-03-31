<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID de venta no especificado.");
}

$id_venta = intval($_GET['id']);

// Obtener datos de la venta
$sql_venta = "SELECT id_venta, fecha, total, metodo_de_pago FROM venta WHERE id_venta = ?";
$stmt = $conn->prepare($sql_venta);
$stmt->bind_param("i", $id_venta);
$stmt->execute();
$res_venta = $stmt->get_result();

if ($res_venta->num_rows === 0) {
    die("Venta no encontrada.");
}
$venta = $res_venta->fetch_assoc();
$stmt->close();

// Obtener detalles de la venta
$sql_detalles = "SELECT nombre_producto, cantidad, precio_unitario 
                 FROM detalle_venta 
                 WHERE id_venta = ?";
$stmt_det = $conn->prepare($sql_detalles);
$stmt_det->bind_param("i", $id_venta);
$stmt_det->execute();
$res_detalles = $stmt_det->get_result();
$detalles = [];
while ($row = $res_detalles->fetch_assoc()) {
    $detalles[] = $row;
}
$stmt_det->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura #TX-<?php echo $venta['id_venta']; ?> - NOKIA 1100</title>
    <style>
        :root {
            --font-main: 'Inter', system-ui, -apple-system, sans-serif;
            --text-dark: #111827;
            --text-gray: #4b5563;
        }
        body {
            font-family: var(--font-main);
            color: var(--text-dark);
            margin: 0;
            padding: 20px;
            background: #f3f4f6;
            display: flex;
            justify-content: center;
        }
        .invoice-box {
            background: #fff;
            padding: 40px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .header p {
            margin: 5px 0 0;
            color: var(--text-gray);
            font-size: 14px;
        }
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .invoice-details div {
            flex: 1;
        }
        .invoice-details .right {
            text-align: right;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table th, table td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        table th {
            text-transform: uppercase;
            font-size: 12px;
            color: var(--text-gray);
        }
        table .text-right {
            text-align: right;
        }
        table .text-center {
            text-align: center;
        }
        .total-section {
            border-top: 2px solid #e5e7eb;
            padding-top: 20px;
            display: flex;
            justify-content: flex-end;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            width: 250px;
            font-size: 16px;
            margin-bottom: 10px;
        }
        .total-row.grand-total {
            font-size: 20px;
            font-weight: bold;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
        .footer {
            text-align: center;
            color: var(--text-gray);
            font-size: 12px;
            margin-top: 40px;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }
        
        /* Print Styles */
        @media print {
            body {
                background: none;
                padding: 0;
                display: block;
            }
            .invoice-box {
                box-shadow: none;
                border: none;
                border-radius: 0;
                max-width: 100%;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
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
