<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'config/bd.php';

// Procesar formulario (AJAX o POST normal con JSON)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input) {
        $items = $input['items'];
        $metodo_pago = $input['metodo_pago'];
        
        if (empty($items) || empty($metodo_pago)) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            exit;
        }

        $conn->begin_transaction();
        try {
            $total_venta = 0;
            $fecha = date('Y-m-d H:i:s');

            // 1. Calcular total y verificar stock primero
            foreach ($items as $item) {
                $stmt = $conn->prepare("SELECT precio, cantidad FROM inventario i JOIN producto p ON i.id_producto = p.id_producto WHERE p.id_producto = ? FOR UPDATE");
                $stmt->bind_param("i", $item['id']);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res->num_rows === 0) throw new Exception("Producto ID " . $item['id'] . " no encontrado");
                $prod = $res->fetch_assoc();
                $stmt->close();

                if ($prod['cantidad'] < $item['cantidad']) {
                    throw new Exception("Stock insuficiente para el producto ID " . $item['id']);
                }
                
                $total_venta += $prod['precio'] * $item['cantidad'];
            }

            // 2. Crear Venta
            $stmt = $conn->prepare("INSERT INTO venta (fecha, total, metodo_de_pago) VALUES (?, ?, ?)");
            $stmt->bind_param("sds", $fecha, $total_venta, $metodo_pago);
            if (!$stmt->execute()) throw new Exception("Error al crear venta");
            $id_venta = $conn->insert_id;
            $stmt->close();

            // 3. Insertar Detalles y Actualizar Stock
            foreach ($items as $item) {
                // Obtener precio actual de nuevo (o usar el guardado si no cambia)
                $stmt = $conn->prepare("SELECT p.precio, p.nombre, d.marca, d.modelo 
                                        FROM producto p 
                                        JOIN producto_detalle d ON p.id_producto = d.id_producto 
                                        WHERE p.id_producto = ?");
                $stmt->bind_param("i", $item['id']);
                $stmt->execute();
                $res = $stmt->get_result();
                $prod = $res->fetch_assoc();
                $precio_unitario = $prod['precio'];
                $stmt->close();

                // Insertar detalle con snapshot
                $nombre_producto = $prod['nombre'] . ' ' . $prod['marca'] . ' ' . $prod['modelo'];
                $stmt = $conn->prepare("INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario, nombre_producto, precio_copiado) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiidsd", $id_venta, $item['id'], $item['cantidad'], $precio_unitario, $nombre_producto, $precio_unitario);
                if (!$stmt->execute()) throw new Exception("Error al crear detalle");
                $stmt->close();

                // Actualizar inventario
                $stmt = $conn->prepare("UPDATE inventario SET cantidad = cantidad - ? WHERE id_producto = ?");
                $stmt->bind_param("ii", $item['cantidad'], $item['id']);
                if (!$stmt->execute()) throw new Exception("Error al actualizar stock");
                $stmt->close();
            }

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Venta registrada con éxito', 'id_venta' => $id_venta]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// Obtener productos para el select
$sql_prods = "SELECT p.id_producto, p.nombre, d.marca, d.modelo, p.precio, i.cantidad
              FROM producto p
              JOIN inventario i ON p.id_producto = i.id_producto
              JOIN producto_detalle d ON p.id_producto = d.id_producto
              WHERE i.cantidad > 0 AND p.is_active = 1
              ORDER BY p.nombre ASC";

$result_prods = $conn->query($sql_prods);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Venta - Nokia 1100</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            margin-bottom: 1rem;
        }
        .cart-table th, .cart-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        .cart-table th {
            background: rgba(255,255,255,0.05);
        }
        .btn-remove {
            color: #ef4444;
            background: transparent;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-remove:hover {
            text-decoration: underline;
        }
        .btn-add {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-add:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container dashboard-container" style="max-width: 800px;">
        <div class="glass-card">
            <div class="header-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2>Nueva Venta</h2>
                <a href="<?php echo $_SESSION['role'] === 'admin' ? 'panel_admin.php' : 'panel_empleado.php'; ?>" class="btn-back" style="text-decoration: none; color: var(--text-muted);">Volver</a>
            </div>

            <div id="alert-box" style="display: none;" class="alert"></div>

            <div style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 1rem; align-items: end; margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid var(--border-color);">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="id_producto">Producto</label>
                    <select id="id_producto">
                        <option value="">Seleccione un producto...</option>
                        <?php if ($result_prods): ?>
                            <?php while($p = $result_prods->fetch_assoc()): ?>
                                <option value="<?php echo $p['id_producto']; ?>" 
                                        data-nombre="<?php echo htmlspecialchars($p['nombre'] . " " . $p['marca'] . " " . $p['modelo']); ?>"
                                        data-precio="<?php echo $p['precio']; ?>"
                                        data-stock="<?php echo $p['cantidad']; ?>">
                                    <?php echo htmlspecialchars($p['nombre'] . " - " . $p['marca'] . " " . $p['modelo'] . " ($" . $p['precio'] . ") - Stock: " . $p['cantidad']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="cantidad">Cantidad</label>
                    <input type="number" id="cantidad" min="1" value="1">
                </div>
                <button type="button" class="btn-add" onclick="addToCart()">Agregar</button>
            </div>

            <h3>Carrito de Compra</h3>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Precio Unit.</th>
                        <th>Cant.</th>
                        <th>Subtotal</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody id="cart-body">
                    <!-- Items will be added here -->
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right; font-weight: bold;">Total:</td>
                        <td id="cart-total" style="font-weight: bold;">$0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>

            <div class="form-group" style="margin-top: 2rem;">
                <label for="metodo_pago">Método de Pago</label>
                <select id="metodo_pago" required>
                    <option value="Efectivo">Efectivo</option>
                    <option value="Tarjeta">Tarjeta</option>
                    <option value="Transferencia">Transferencia</option>
                </select>
            </div>

            <button type="button" onclick="submitSale()" style="margin-top: 1.5rem; width: 100%;">Registrar Venta</button>
        </div>
    </div>

    <script>
        let cart = [];

        function addToCart() {
            const select = document.getElementById('id_producto');
            const cantidadInput = document.getElementById('cantidad');
            const option = select.options[select.selectedIndex];

            if (!select.value) {
                showAlert('Por favor seleccione un producto', 'error');
                return;
            }

            const id = select.value;
            const nombre = option.dataset.nombre;
            const precio = parseFloat(option.dataset.precio);
            const stock = parseInt(option.dataset.stock);
            const cantidad = parseInt(cantidadInput.value);

            if (cantidad <= 0) {
                showAlert('La cantidad debe ser mayor a 0', 'error');
                return;
            }

            // Check if already in cart
            const existingItem = cart.find(item => item.id === id);
            const currentQtyInCart = existingItem ? existingItem.cantidad : 0;

            if (currentQtyInCart + cantidad > stock) {
                showAlert('No hay suficiente stock disponible', 'error');
                return;
            }

            if (existingItem) {
                existingItem.cantidad += cantidad;
            } else {
                cart.push({
                    id: id,
                    nombre: nombre,
                    precio: precio,
                    cantidad: cantidad
                });
            }

            updateCartTable();
            select.value = "";
            cantidadInput.value = 1;
            showAlert('Producto agregado', 'success');
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartTable();
        }

        function updateCartTable() {
            const tbody = document.getElementById('cart-body');
            const totalDisplay = document.getElementById('cart-total');
            tbody.innerHTML = '';
            let total = 0;

            cart.forEach((item, index) => {
                const subtotal = item.precio * item.cantidad;
                total += subtotal;

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.nombre}</td>
                    <td>$${item.precio.toFixed(2)}</td>
                    <td>${item.cantidad}</td>
                    <td>$${subtotal.toFixed(2)}</td>
                    <td><button class="btn-remove" onclick="removeFromCart(${index})">Eliminar</button></td>
                `;
                tbody.appendChild(row);
            });

            totalDisplay.textContent = '$' + total.toFixed(2);
        }

        function submitSale() {
            if (cart.length === 0) {
                showAlert('El carrito está vacío', 'error');
                return;
            }

            const metodoPago = document.getElementById('metodo_pago').value;

            fetch('nueva_venta.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    items: cart,
                    metodo_pago: metodoPago
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Venta registrada con éxito');
                    window.location.href = '<?php echo $_SESSION['role'] === 'admin' ? 'panel_admin.php' : 'panel_empleado.php'; ?>';
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error al procesar la venta', 'error');
            });
        }

        function showAlert(msg, type) {
            const alertBox = document.getElementById('alert-box');
            alertBox.textContent = msg;
            alertBox.className = 'alert alert-' + type;
            alertBox.style.display = 'block';
            setTimeout(() => {
                alertBox.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>
