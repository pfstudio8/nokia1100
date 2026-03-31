<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

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
<?php
require_once __DIR__ . '/../../classes/Layout.php';
Layout::renderHead('Nueva Venta - NOKIA1100');
if ($_SESSION['role'] === 'admin') {
    Layout::renderAdminSidebar('ventas');
} else {
    Layout::renderEmployeeSidebar('ventas');
}
?>
<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8 border border-border/50">
        <div class="flex justify-between items-center mb-8 pb-4 border-b border-border/50">
            <div>
                <h2 class="text-2xl font-display font-medium text-text-main">Nueva Venta</h2>
                <p class="text-text-muted text-sm mt-1">Terminal de Punto de Venta (POS)</p>
            </div>
            <a href="<?php echo BASE_URL . ($_SESSION['role'] === 'admin' ? '/modules/admin/dashboard.php' : '/modules/employee/dashboard.php'); ?>" class="px-4 py-2 rounded-xl border border-border bg-surface hover:bg-surface-hover text-sm font-medium text-text-main transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span> Volver
            </a>
        </div>

        <div id="alert-box" style="display: none;"></div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-8 bg-surface/30 p-6 rounded-2xl border border-border/30">
            <div class="md:col-span-8">
                <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Producto</label>
                <select id="id_producto" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main appearance-none">
                    <option value="">Seleccione un producto...</option>
                    <?php if ($result_prods): ?>
                        <?php while($p = $result_prods->fetch_assoc()): ?>
                            <option value="<?php echo $p['id_producto']; ?>" 
                                    data-nombre="<?php echo htmlspecialchars($p['nombre'] . " " . $p['marca'] . " " . $p['modelo']); ?>"
                                    data-precio="<?php echo $p['precio']; ?>"
                                    data-stock="<?php echo $p['cantidad']; ?>">
                                <?php echo htmlspecialchars($p['nombre'] . " - " . $p['marca'] . " " . $p['modelo'] . " ($" . number_format($p['precio'], 2) . ") - Stock: " . $p['cantidad']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Cantidad</label>
                <input type="number" id="cantidad" min="1" value="1" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
            </div>
            <div class="md:col-span-2 flex items-end">
                <button type="button" class="w-full bg-primary/10 text-primary border border-primary/20 hover:bg-primary hover:text-background font-medium py-3 rounded-xl transition-all flex justify-center items-center gap-2" onclick="addToCart()">
                    <span class="material-symbols-outlined text-[18px]">add_shopping_cart</span> Agregar
                </button>
            </div>
        </div>

        <h3 class="text-xs uppercase font-semibold tracking-widest text-text-muted mb-4 px-2">Carrito de Compra</h3>
        <div class="overflow-x-auto bg-surface/20 rounded-2xl border border-border/50 mb-8">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface/50 border-b border-border/50 text-xs uppercase tracking-wider text-text-muted">
                        <th class="p-4 font-semibold">Producto</th>
                        <th class="p-4 font-semibold text-right">Precio Unit.</th>
                        <th class="p-4 font-semibold text-center">Cant.</th>
                        <th class="p-4 font-semibold text-right">Subtotal</th>
                        <th class="p-4 font-semibold text-center">Acción</th>
                    </tr>
                </thead>
                <tbody id="cart-body" class="divide-y divide-border/30">
                    <tr><td colspan="5" class="p-8 text-center text-text-muted text-sm border-none">El carrito está vacío</td></tr>
                </tbody>
                <tfoot>
                    <tr class="border-t border-border/50 bg-surface/30">
                        <td colspan="3" class="p-4 text-right font-medium text-text-muted">TOTAL A COBRAR:</td>
                        <td id="cart-total" class="p-4 text-right font-display text-xl font-semibold text-primary">$0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
            <div>
                <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Método de Pago</label>
                <select id="metodo_pago" required class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main appearance-none">
                    <option value="Efectivo">Efectivo</option>
                    <option value="Tarjeta">Tarjeta de Crédito/Débito</option>
                    <option value="Transferencia">Transferencia Bancaria</option>
                </select>
            </div>
            <div>
                <button type="button" onclick="submitSale()" class="w-full bg-text-main text-background hover:bg-text-muted font-medium py-3 px-6 rounded-xl transition-all flex justify-center items-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">point_of_sale</span> Confirmar y Registrar Venta
                </button>
            </div>
        </div>
    </div>
</main>
<?php Layout::renderFooter(); ?>

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

        const existingItem = cart.find(item => item.id === id);
        const currentQtyInCart = existingItem ? existingItem.cantidad : 0;

        if (currentQtyInCart + cantidad > stock) {
            showAlert('No hay suficiente stock disponible', 'error');
            return;
        }

        if (existingItem) {
            existingItem.cantidad += cantidad;
        } else {
            cart.push({ id, nombre, precio, cantidad });
        }

        // --- Flying Animation ---
        const btnRect = document.activeElement.getBoundingClientRect(); // Usually the add button
        const flyingEl = document.createElement('div');
        flyingEl.className = 'flying-to-cart';
        flyingEl.innerHTML = `<span class="material-symbols-outlined text-[16px]">inventory_2</span> ${nombre.split(' ')[0]}`; // just first word
        flyingEl.style.left = `${btnRect.left}px`;
        flyingEl.style.top = `${btnRect.top}px`;
        document.body.appendChild(flyingEl);

        setTimeout(() => {
            flyingEl.remove();
        }, 600);

        updateCartTable();
        select.value = "";
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
            tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-text-muted text-sm border-none">El carrito está vacío</td></tr>';
            totalDisplay.textContent = '$0.00';
            return;
        }

        cart.forEach((item, index) => {
            const subtotal = item.precio * item.cantidad;
            total += subtotal;
            const row = document.createElement('tr');
            row.className = 'hover:bg-surface/30 transition-colors';
            row.innerHTML = `
                <td class="p-4 text-sm font-medium text-text-main">${item.nombre}</td>
                <td class="p-4 text-sm text-right text-text-muted">$${item.precio.toFixed(2)}</td>
                <td class="p-4 text-sm text-center">
                    <span class="bg-surface border border-border px-3 py-1 rounded-full text-text-muted">${item.cantidad}</span>
                </td>
                <td class="p-4 text-sm text-right font-medium text-text-main">$${subtotal.toFixed(2)}</td>
                <td class="p-4 text-center">
                    <button class="text-red-400 hover:text-red-300 hover:bg-red-400/10 p-2 rounded-xl transition-colors inline-flex" onclick="removeFromCart(${index})" title="Eliminar">
                        <span class="material-symbols-outlined text-[18px]">delete</span>
                    </button>
                </td>
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

        fetch('new_sale.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items: cart, metodo_pago: metodoPago })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                cart = [];
                updateCartTable();
                showSuccessModal(data.id_venta);
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error al procesar la venta', 'error');
        });
    }

    function showSuccessModal(idVenta) {
        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 bg-background/80 backdrop-blur-sm z-50 flex items-center justify-center';
        overlay.innerHTML = `
            <div class="bg-surface border border-border rounded-2xl p-8 max-w-sm w-full text-center shadow-2xl transform transition-all scale-100">
                <div class="w-16 h-16 bg-primary/20 text-primary rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-3xl">check_circle</span>
                </div>
                <h3 class="text-xl font-display font-semibold text-text-main mb-2">¡Venta Exitosa!</h3>
                <p class="text-text-muted text-sm mb-6">La transacción ha sido registrada correctamente en el sistema.</p>
                
                <div class="flex flex-col gap-3">
                    <a href="invoice.php?id=${idVenta}" target="_blank" onclick="closeSuccessModal(this.parentElement.parentElement.parentElement)" class="w-full bg-primary text-background font-medium py-3 px-4 rounded-xl transition-all flex justify-center items-center gap-2 hover:bg-primary/90">
                        <span class="material-symbols-outlined">print</span> Imprimir Factura
                    </a>
                    <button onclick="closeSuccessModal(this.parentElement.parentElement.parentElement)" class="w-full bg-surface border border-border text-text-main font-medium py-3 px-4 rounded-xl transition-all hover:bg-border">
                        Nueva Venta
                    </button>
                    <a href="<?php echo BASE_URL . ($_SESSION['role'] === 'admin' ? '/modules/admin/dashboard.php' : '/modules/employee/dashboard.php'); ?>" class="w-full text-text-muted hover:text-text-main text-sm font-medium py-2 mt-2 transition-colors">
                        Volver al Panel
                    </a>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
    }

    function closeSuccessModal(overlay) {
        document.body.removeChild(overlay);
    }

    function showAlert(msg, type) {
        const errorDisplay = document.getElementById('alert-box');
        errorDisplay.innerHTML = `<span class="material-symbols-outlined">${type === 'success' ? 'check_circle' : 'error'}</span> ` + msg;
        errorDisplay.className = `p-4 rounded-xl mb-6 text-sm font-medium flex gap-3 items-center ${type === 'success' ? 'bg-primary/10 border border-primary/20 text-primary' : 'bg-red-500/10 border border-red-500/20 text-red-500'}`;
        errorDisplay.style.display = 'flex';
        setTimeout(() => { errorDisplay.style.display = 'none'; }, 4000);
    }
</script>
