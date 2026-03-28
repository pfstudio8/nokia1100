<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin_auth();
require_once '../../config/bd.php';

// Handle Purchase Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input) {
        $id_proveedor = $input['id_proveedor'];
        $items = $input['items'];
        $descripcion = $input['descripcion'];
        $tiempo_entrega = $input['tiempo_entrega'];
        $iva = $input['iva'];
        $autorizado_por = $input['autorizado_por'];
        $fecha = date('Y-m-d H:i:s');
        $total_compra = 0;

        if (empty($id_proveedor) || empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            exit;
        }

        $conn->begin_transaction();
        try {
            // Calculate Total
            foreach ($items as $item) {
                $total_compra += $item['costo'] * $item['cantidad'];
            }

            // 1. Create Purchase Header
            $stmt = $conn->prepare("INSERT INTO compra (id_proveedor, fecha, total, descripcion, tiempo_entrega, iva, autorizado_por) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isdssds", $id_proveedor, $fecha, $total_compra, $descripcion, $tiempo_entrega, $iva, $autorizado_por);
            if (!$stmt->execute()) throw new Exception("Error al crear compra");
            $id_compra = $conn->insert_id;
            $stmt->close();

            // 2. Process Each Item: Create Product (if needed) and Update Inventory
            foreach ($items as $item) {
                $id_producto = null;
                
                // Check if product already exists by name, marca, modelo
                $stmt = $conn->prepare("SELECT p.id_producto FROM producto p 
                                       JOIN producto_detalle d ON p.id_producto = d.id_producto 
                                       WHERE p.nombre = ? AND d.marca = ? AND d.modelo = ?");
                $stmt->bind_param("sss", $item['nombre'], $item['marca'], $item['modelo']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    // Product exists, use existing id
                    $id_producto = $row['id_producto'];
                } else {
                    // Product doesn't exist, create it
                    // a) Create producto
                    $stmt = $conn->prepare("INSERT INTO producto (nombre, precio, is_active) VALUES (?, ?, 1)");
                    $stmt->bind_param("sd", $item['nombre'], $item['costo']);
                    if (!$stmt->execute()) throw new Exception("Error al crear producto");
                    $id_producto = $conn->insert_id;
                    $stmt->close();
                    
                    // b) Create producto_detalle
                    $stmt = $conn->prepare("INSERT INTO producto_detalle (id_producto, marca, modelo, categoria) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("isss", $id_producto, $item['marca'], $item['modelo'], $item['categoria']);
                    if (!$stmt->execute()) throw new Exception("Error al crear detalle de producto");
                    $stmt->close();
                }
                
                // c) Update or Create Inventory Entry
                $stmt = $conn->prepare("INSERT INTO inventario (id_producto, cantidad) VALUES (?, ?) 
                                       ON DUPLICATE KEY UPDATE cantidad = cantidad + ?");
                $stmt->bind_param("iii", $id_producto, $item['cantidad'], $item['cantidad']);
                if (!$stmt->execute()) throw new Exception("Error al actualizar inventario");
                $stmt->close();
                
                // d) Insert Purchase Detail with real id_producto
                $stmt = $conn->prepare("INSERT INTO detalle_compra (id_compra, id_producto, cantidad, precio_compra) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiid", $id_compra, $id_producto, $item['cantidad'], $item['costo']);
                if (!$stmt->execute()) throw new Exception("Error al crear detalle de compra");
                $id_detalle_compra = $conn->insert_id;
                $stmt->close();

                // e) Insert History for tracking
                $stmt = $conn->prepare("INSERT INTO producto_compra_historial (id_detalle_compra, nombre_producto, marca, modelo) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $id_detalle_compra, $item['nombre'], $item['marca'], $item['modelo']);
                if (!$stmt->execute()) throw new Exception("Error al crear historial de compra");
                $stmt->close();
            }

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Compra registrada con éxito']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// Fetch Suppliers
$suppliers = $conn->query("SELECT * FROM proveedor ORDER BY nombre ASC");

// Fetch Products
$products = $conn->query("SELECT p.id_producto, p.nombre, d.marca, d.modelo FROM producto p JOIN producto_detalle d ON p.id_producto = d.id_producto WHERE p.is_active = 1 ORDER BY p.nombre ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Compra - Nokia 1100</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container" style="padding-top: 3rem;">
        
        <div style="margin-bottom: 3rem; width: 100%; max-width: 1000px; margin-left: auto; margin-right: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <p style="text-transform: uppercase; letter-spacing: 0.2em; font-size: 0.7rem; color: var(--primary); font-weight: 800; margin-bottom: 0.25rem; opacity: 0.8;">Admin & Logística</p>
                    <h1 style="margin-bottom: 0; font-size: 2.2rem; letter-spacing: -0.02em;">Registrar Nueva Compra</h1>
                </div>
                <div style="display: flex; gap: 0.75rem;">
                    <a href="../panel_admin.php" class="btn btn-outline" style="padding: 0.6rem 1.2rem; font-size: 0.85rem;">Cerrar</a>
                </div>
            </div>
            <div style="height: 3px; width: 30px; background: var(--primary); margin-top: 1.5rem; border-radius: 2px;"></div>
        </div>

        <div class="container" style="max-width: 1000px; margin: 0 auto; padding: 0;">
            <div class="glass-card" style="padding: 2.5rem;">


            <div class="form-group">
                <label>Proveedor</label>
                <select id="id_proveedor">
                    <option value="">Seleccione un proveedor...</option>
                    <?php while($s = $suppliers->fetch_assoc()): ?>
                        <option value="<?php echo $s['id_proveedor']; ?>"><?php echo htmlspecialchars($s['nombre']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label>Descripción de la Compra</label>
                    <input type="text" id="descripcion" placeholder=>
                </div>
                <div class="form-group">
                    <label>Tiempo de Entrega</label>
                    <input type="text" id="tiempo_entrega" placeholder="Ej: 30 días">
                </div>
                <div class="form-group">
                    <label>IVA (%)</label>
                    <input type="number" id="iva" min="0" step="0.01" value="0" placeholder="Ej: 9">
                </div>
                <div class="form-group">
                    <label>Autorizado Por</label>
                    <input type="text" id="autorizado_por" placeholder="Ej: Pedro Páramo">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr auto; gap: 1rem; align-items: end; margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid var(--border-color);">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Nombre del Producto</label>
                    <input type="text" id="product_nombre" placeholder=>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Marca</label>
                    <input type="text" id="product_marca" placeholder=>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Modelo</label>
                    <input type="text" id="product_modelo" placeholder=>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Categoría</label>
                    <select id="product_categoria">
                        <option value="Celular">Celular</option>
                        <option value="Accesorios">Accesorios</option>
                        <option value="Repuestos">Repuestos</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Costo Unit.</label>
                    <input type="number" id="costo" min="0" step="0.01">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Cantidad</label>
                    <input type="number" id="cantidad" min="1" value="1">
                </div>
                <button type="button" class="btn btn-primary" style="padding: 0.6rem 1.5rem;" onclick="addToCart()">AÑADIR</button>
            </div>

            <h3>Items de Compra</h3>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Costo Unit.</th>
                        <th>Cant.</th>
                        <th>Subtotal</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody id="cart-body"></tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right; font-weight: bold;">Total Compra:</td>
                        <td id="cart-total" style="font-weight: bold;">$0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>

            <button type="button" class="btn btn-primary" onclick="submitPurchase()" style="margin-top: 2rem; width: 100%; padding: 1rem; font-size: 1.1rem; letter-spacing: 0.05em;">REGISTRAR COMPRA Y STOCK</button>
        </div>
    </div>

    <!-- Modal for New Product -->
    <div id="newProductModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center;">
        <div class="glass-card" style="max-width: 500px; margin: 2rem;">
            <h3 style="margin-bottom: 1.5rem;">Agregar Nuevo Producto</h3>
            <div class="form-group">
                <label>Nombre del Producto</label>
                <input type="text" id="new_nombre" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Marca</label>
                    <input type="text" id="new_marca">
                </div>
                <div class="form-group">
                    <label>Modelo</label>
                    <input type="text" id="new_modelo">
                </div>
            </div>
            <div class="form-group">
                <label>Precio de Venta (Sugerido)</label>
                <input type="number" id="new_precio" min="0" step="0.01" required>
            </div>
            <div class="form-group">
                <label>Categoría</label>
                <select id="new_categoria">
                    <option value="Celular">Celular</option>
                    <option value="Accesorios">Accesorios</option>
                    <option value="Repuestos">Repuestos</option>
                </select>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="button" class="btn btn-primary" onclick="createNewProduct()" style="flex: 1;">CREAR PRODUCTO</button>
                <button type="button" class="btn btn-outline" onclick="closeNewProductModal()" style="flex: 1;">CANCELAR</button>
            </div>
        </div>
    </div>

    <script>
        let cart = [];

        function addToCart() {
            const nombreInput = document.getElementById('product_nombre');
            const marcaInput = document.getElementById('product_marca');
            const modeloInput = document.getElementById('product_modelo');
            const costoInput = document.getElementById('costo');
            const cantidadInput = document.getElementById('cantidad');
            
            if (!nombreInput.value || !costoInput.value) {
                alert('Nombre del producto y costo son obligatorios');
                return;
            }

            const nombre = nombreInput.value.trim();
            const marca = marcaInput.value.trim();
            const modelo = modeloInput.value.trim();
            const categoria = document.getElementById('product_categoria').value;
            const costo = parseFloat(costoInput.value);
            const cantidad = parseInt(cantidadInput.value);

            // Create display name
            const displayName = `${nombre}${marca ? ' - ' + marca : ''}${modelo ? ' ' + modelo : ''} (${categoria})`;

            cart.push({ nombre, marca, modelo, categoria, displayName, costo, cantidad });
            updateCartTable();
            
            nombreInput.value = '';
            marcaInput.value = '';
            modeloInput.value = '';
            costoInput.value = '';
            cantidadInput.value = 1;
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
                const subtotal = item.costo * item.cantidad;
                total += subtotal;
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.displayName}</td>
                    <td>$${item.costo.toFixed(2)}</td>
                    <td>${item.cantidad}</td>
                    <td>$${subtotal.toFixed(2)}</td>
                    <td><button class="btn-remove" data-index="${index}">X</button></td>
                `;
                tbody.appendChild(row);
            });
            totalDisplay.textContent = '$' + total.toFixed(2);
            
            // Add event listeners to remove buttons
            tbody.querySelectorAll('.btn-remove').forEach(btn => {
                btn.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    removeFromCart(index);
                });
            });
        }

        function submitPurchase() {
            const idProveedor = document.getElementById('id_proveedor').value;
            const descripcion = document.getElementById('descripcion').value;
            const tiempoEntrega = document.getElementById('tiempo_entrega').value;
            const iva = parseFloat(document.getElementById('iva').value) || 0;
            const autorizadoPor = document.getElementById('autorizado_por').value;

            if (!idProveedor) { alert('Seleccione un proveedor'); return; }
            if (cart.length === 0) { alert('Agregue productos'); return; }

            fetch('nueva_compra.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    id_proveedor: idProveedor, 
                    items: cart,
                    descripcion: descripcion,
                    tiempo_entrega: tiempoEntrega,
                    iva: iva,
                    autorizado_por: autorizadoPor
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Compra registrada');
                    window.location.href = '../panel_admin.php';
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        function showNewProductModal() {
            document.getElementById('newProductModal').style.display = 'flex';
        }

        function closeNewProductModal() {
            document.getElementById('newProductModal').style.display = 'none';
            document.getElementById('new_nombre').value = '';
            document.getElementById('new_marca').value = '';
            document.getElementById('new_modelo').value = '';
            document.getElementById('new_precio').value = '';
        }

        function createNewProduct() {
            const nombre = document.getElementById('new_nombre').value;
            const marca = document.getElementById('new_marca').value;
            const modelo = document.getElementById('new_modelo').value;
            const precio = document.getElementById('new_precio').value;
            const categoria = document.getElementById('new_categoria').value;

            if (!nombre || !precio) {
                alert('Nombre y Precio son obligatorios');
                return;
            }

            fetch('../../api/crear_producto_ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ nombre, marca, modelo, precio, categoria })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Add to datalist
                    const datalist = document.getElementById('products_list');
                    const option = document.createElement('option');
                    option.dataset.id = data.id_producto;
                    option.value = `${nombre} - ${marca} ${modelo}`;
                    datalist.appendChild(option);

                    // Set as selected
                    document.getElementById('product_input').value = option.value;
                    
                    closeNewProductModal();
                    alert('Producto creado exitosamente');
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
    </script>
</body>
</html>
