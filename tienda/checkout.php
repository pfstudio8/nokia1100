<?php
require_once __DIR__ . '/../includes/auth.php';
require_cliente_auth();
require_once __DIR__ . '/../config/bd.php';

if (empty($_SESSION['carrito'])) {
    header("Location: carrito.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $direccion = trim($_POST['direccion']);
    $ciudad = trim($_POST['ciudad']);
    $cp = trim($_POST['cp']);
    $metodo_pago = $_POST['metodo_pago'];
    
    $direccion_completa = "$direccion, $ciudad (CP: $cp)";

    if (empty($direccion) || empty($ciudad)) {
        $error = "Por favor complete la dirección de envío.";
    } else {
        $conn->begin_transaction();
        try {
            $total = 0;
            $items = [];
            
            // 1. Validate Stock and Calculate Total
            foreach ($_SESSION['carrito'] as $item) {
                $stmt = $conn->prepare("SELECT cantidad FROM inventario WHERE id_producto = ? FOR UPDATE");
                $stmt->bind_param("i", $item['id']);
                $stmt->execute();
                $res = $stmt->get_result();
                $stock = $res->fetch_assoc();
                
                if ($stock['cantidad'] < $item['cantidad']) {
                    throw new Exception("Stock insuficiente para " . $item['nombre']);
                }
                $total += $item['precio'] * $item['cantidad'];
                $items[] = array_merge($item, ['stock_actual' => $stock['cantidad']]);
                $stmt->close();
            }

            // 2. Insert Sale with new fields
            $usuario_id = $_SESSION['cliente_id'];
            $stmt = $conn->prepare("INSERT INTO venta (fecha, total, metodo_de_pago, tipo_venta, id_usuario, direccion_envio, estado) VALUES (NOW(), ?, ?, 'online', ?, ?, 'pendiente')");
            $stmt->bind_param("dsis", $total, $metodo_pago, $usuario_id, $direccion_completa);
            $stmt->execute();
            $id_venta = $conn->insert_id;
            $stmt->close();

            // 3. Process Items
            foreach ($items as $item) {
                // Insert Detail
                $stmt = $conn->prepare("INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario, nombre_producto, precio_copiado) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiisdd", $id_venta, $item['id'], $item['cantidad'], $item['precio'], $item['nombre'], $item['precio']);
                $stmt->execute();
                $stmt->close();

                // Update Stock
                $nuevo_stock = $item['stock_actual'] - $item['cantidad'];
                $stmt = $conn->prepare("UPDATE inventario SET cantidad = ? WHERE id_producto = ?");
                $stmt->bind_param("ii", $nuevo_stock, $item['id']);
                $stmt->execute();
                $stmt->close();
            }

            $conn->commit();
            unset($_SESSION['carrito']);
            header("Location: mis_pedidos.php?success=1");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Finalizar Compra - Nokia Store</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <nav class="navbar">
        <div class="container nav-inner">
            <a href="index.php" class="logo">NOKIA<span>STORE</span></a>
            <div class="nav-links">
                <a href="carrito.php" class="nav-link">Volver al Carrito</a>
            </div>
        </div>
    </nav>

    <div class="container" style="max-width: 800px; margin-top: 3rem;">
        <h1 style="margin-bottom: 2rem;">Finalizar Compra</h1>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card" style="padding: 2rem;">
            <form method="POST">
                <h3 style="margin-bottom: 1.5rem; color: var(--primary);">Datos de Envío</h3>
                
                <div class="form-group">
                    <label>Dirección (Calle y Número)</label>
                    <input type="text" name="direccion" class="form-control" required placeholder="Ej: Av. Corrientes 1234">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 150px; gap: 1rem;">
                    <div class="form-group">
                        <label>Ciudad / Localidad</label>
                        <input type="text" name="ciudad" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Código Postal</label>
                        <input type="text" name="cp" class="form-control" required>
                    </div>
                </div>

                <h3 style="margin: 1.5rem 0; color: var(--primary);">Método de Pago</h3>
                <div class="form-group">
                    <select name="metodo_pago" class="form-control">
                        <option value="tarjeta">Tarjeta de Crédito / Débito</option>
                        <option value="transferencia">Transferencia Bancaria</option>
                        <option value="efectivo">Efectivo en local</option>
                    </select>
                </div>
                
                <div class="alert alert-success" style="margin-top: 2rem;">
                    <strong>Total a Pagar:</strong> 
                    $<?php 
                        $total = 0; 
                        foreach($_SESSION['carrito'] as $c) $total += $c['precio']*$c['cantidad']; 
                        echo number_format($total, 0, ',', '.');
                    ?>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.2rem; padding: 1rem;">Confirmar Pedido</button>
            </form>
        </div>
    </div>

</body>
</html>
