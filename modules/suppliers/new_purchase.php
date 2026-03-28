<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}


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
                    $stmt = $conn->prepare("INSERT INTO producto_detalle (id_producto, marca, modelo) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $id_producto, $item['marca'], $item['modelo']);
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
<?php
require_once __DIR__ . '/../../classes/Layout.php';
Layout::renderHead('Registrar Compra - NOKIA1100');
Layout::renderAdminSidebar('proveedores');
?>
<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8 border border-border/50">
        <div class="flex justify-between items-center mb-8 pb-4 border-b border-border/50">
            <div>
                <h2 class="text-2xl font-display font-medium text-text-main">Registrar Compra</h2>
                <p class="text-text-muted text-sm mt-1">Gestión de abastecimiento e ingreso de mercadería</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/modules/admin/dashboard.php" class="px-4 py-2 rounded-xl border border-border bg-surface hover:bg-surface-hover text-sm font-medium transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span> Volver
            </a>
        </div>

        <div class="mb-6">
            <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Proveedor</label>
            <select id="id_proveedor" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main appearance-none">
                <option value="">Seleccione un proveedor...</option>
                <?php while($s = $suppliers->fetch_assoc()): ?>
                    <option value="<?php echo $s['id_proveedor']; ?>"><?php echo htmlspecialchars($s['nombre']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div>
                <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Descripción</label>
                <input type="text" id="descripcion" placeholder="Ej: Lote mensual" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
            </div>
            <div>
                <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Tiempo Entrega</label>
                <input type="text" id="tiempo_entrega" placeholder="Ej: 30 días" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
            </div>
            <div>
                <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">IVA (%)</label>
                <input type="number" id="iva" min="0" step="0.01" value="0" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
            </div>
            <div>
                <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Autorizado Por</label>
                <input type="text" id="autorizado_por" placeholder="Ej: Pedro P." class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end mb-8 bg-surface/30 p-6 rounded-2xl border border-border/30">
            <div class="md:col-span-3">
                <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Producto / Nro Parte</label>
                <input type="text" id="product_nombre" placeholder="Nombre" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Marca</label>
                <input type="text" id="product_marca" placeholder="Opcional" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Modelo</label>
                <input type="text" id="product_modelo" placeholder="Opcional" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Costo Unit.</label>
                <input type="number" id="costo" min="0" step="0.01" value="0" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
            </div>
            <div class="md:col-span-1">
                <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Cant.</label>
                <input type="number" id="cantidad" min="1" value="1" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
            </div>
            <div class="md:col-span-2 flex">
                <button type="button" class="w-full bg-primary/10 text-primary border border-primary/20 hover:bg-primary hover:text-background font-medium py-3 rounded-xl transition-all flex justify-center items-center gap-2" onclick="addToCart()">
                    <span class="material-symbols-outlined text-[18px]">add_box</span> Añadir
                </button>
            </div>
        </div>

        <h3 class="text-xs uppercase font-semibold tracking-widest text-text-muted mb-4 px-2">Items a Ingresar</h3>
        <div class="overflow-x-auto bg-surface/20 rounded-2xl border border-border/50 mb-8">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface/50 border-b border-border/50 text-xs uppercase tracking-wider text-text-muted">
                        <th class="p-4 font-semibold">Producto</th>
                        <th class="p-4 font-semibold text-right">Costo Unit.</th>
                        <th class="p-4 font-semibold text-center">Cant.</th>
                        <th class="p-4 font-semibold text-right">Subtotal</th>
                        <th class="p-4 font-semibold text-center">Acción</th>
                    </tr>
                </thead>
                <tbody id="cart-body" class="divide-y divide-border/30">
                    <tr><td colspan="5" class="p-8 text-center text-text-muted text-sm border-none">Aún no hay productos en la orden de compra</td></tr>
                </tbody>
                <tfoot>
                    <tr class="border-t border-border/50 bg-surface/30">
                        <td colspan="3" class="p-4 text-right font-medium text-text-muted">TOTAL COMPRA:</td>
                        <td id="cart-total" class="p-4 text-right font-display text-xl font-semibold text-primary">$0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <button type="button" onclick="submitPurchase()" class="w-full bg-text-main text-background hover:bg-text-muted font-medium py-4 px-6 rounded-xl transition-all flex justify-center items-center gap-2">
            <span class="material-symbols-outlined text-[20px]">save</span> Efectuar e Ingresar al Inventario
        </button>
    </div>
</main>
<?php Layout::renderFooter(); ?>

<script>
    let cart = [];

    function addToCart() {
        const nombreInput = document.getElementById('product_nombre');
        const marcaInput = document.getElementById('product_marca');
        const modeloInput = document.getElementById('product_modelo');
        const costoInput = document.getElementById('costo');
        const cantidadInput = document.getElementById('cantidad');
        
        if (!nombreInput.value || !costoInput.value || parseFloat(costoInput.value) <= 0) {
            alert('Nombre del producto y un costo unitario válido son obligatorios');
            return;
        }

        const nombre = nombreInput.value.trim();
        const marca = marcaInput.value.trim();
        const modelo = modeloInput.value.trim();
        const costo = parseFloat(costoInput.value);
        const cantidad = parseInt(cantidadInput.value);

        const displayName = `${nombre}${marca ? ' - ' + marca : ''}${modelo ? ' ' + modelo : ''}`;

        cart.push({ nombre, marca, modelo, displayName, costo, cantidad });
        updateCartTable();
        
        nombreInput.value = '';
        marcaInput.value = '';
        modeloInput.value = '';
        costoInput.value = '0';
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

        if (cart.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-text-muted text-sm border-none">Aún no hay productos en la orden de compra</td></tr>';
            totalDisplay.textContent = '$0.00';
            return;
        }

        cart.forEach((item, index) => {
            const subtotal = item.costo * item.cantidad;
            total += subtotal;
            const row = document.createElement('tr');
            row.className = 'hover:bg-surface/30 transition-colors';
            row.innerHTML = `
                <td class="p-4 text-sm font-medium text-text-main">${item.displayName}</td>
                <td class="p-4 text-sm text-right text-text-muted">$${item.costo.toFixed(2)}</td>
                <td class="p-4 text-sm text-center"><span class="bg-surface border border-border px-3 py-1 rounded-full text-text-muted">${item.cantidad}</span></td>
                <td class="p-4 text-sm text-right font-medium text-text-main">$${subtotal.toFixed(2)}</td>
                <td class="p-4 text-center">
                    <button class="text-red-400 hover:text-red-300 hover:bg-red-400/10 p-2 rounded-xl transition-colors inline-flex" onclick="removeFromCart(${index})">
                        <span class="material-symbols-outlined text-[18px]">delete</span>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
        totalDisplay.textContent = '$' + total.toFixed(2);
    }

    function submitPurchase() {
        const idProveedor = document.getElementById('id_proveedor').value;
        const descripcion = document.getElementById('descripcion').value;
        const tiempoEntrega = document.getElementById('tiempo_entrega').value;
        const iva = parseFloat(document.getElementById('iva').value) || 0;
        const autorizadoPor = document.getElementById('autorizado_por').value;

        if (!idProveedor) { alert('Seleccione un proveedor principal'); return; }
        if (cart.length === 0) { alert('Agregue productos a la orden de compra'); return; }

        fetch('new_purchase.php', {
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
                alert('Compra registrada y stock actualizado con éxito');
                window.location.href = '<?php echo BASE_URL; ?>/modules/suppliers/suppliers.php';
            } else {
                alert('Error al registrar compra: ' + data.message);
            }
        });
    }
</script>
