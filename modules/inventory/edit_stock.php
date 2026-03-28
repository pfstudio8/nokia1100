<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/modules/inventory/inventory.php");
    exit();
}

$message = '';
$messageType = '';
$id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_producto === 0) {
    header("Location: " . BASE_URL . "/modules/inventory/inventory.php");
    exit();
}

// Fetch existing data
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $stmt = $conn->prepare("
        SELECT p.nombre, p.precio, d.marca, d.modelo, i.cantidad 
        FROM producto p
        JOIN producto_detalle d ON p.id_producto = d.id_producto
        JOIN inventario i ON p.id_producto = i.id_producto
        WHERE p.id_producto = ?
    ");
    $stmt->bind_param("i", $id_producto);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $nombre = $row['nombre'];
        $marca = $row['marca'];
        $modelo = $row['modelo'];
        $precio = $row['precio'];
        $cantidad = $row['cantidad'];
    } else {
        header("Location: " . BASE_URL . "/modules/inventory/inventory.php");
        exit();
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $marca = $_POST['marca'];
    $modelo = $_POST['modelo'];
    $precio = $_POST['precio'];
    $cantidad = $_POST['cantidad'];

    if (empty($nombre) || empty($marca) || empty($modelo) || empty($precio) || empty($cantidad)) {
        $message = "Todos los campos son obligatorios";
        $messageType = "error";
    } else {
        $conn->begin_transaction();
        try {
            // 1. Update producto
            $stmt = $conn->prepare("UPDATE producto SET nombre = ?, precio = ? WHERE id_producto = ?");
            $stmt->bind_param("sdi", $nombre, $precio, $id_producto);
            if (!$stmt->execute()) throw new Exception("Error al actualizar producto");
            $stmt->close();

            // 2. Update producto_detalle
            $stmt = $conn->prepare("UPDATE producto_detalle SET marca = ?, modelo = ? WHERE id_producto = ?");
            $stmt->bind_param("ssi", $marca, $modelo, $id_producto);
            if (!$stmt->execute()) throw new Exception("Error al actualizar detalles");
            $stmt->close();

            // 3. Update inventario
            $stmt = $conn->prepare("UPDATE inventario SET cantidad = ? WHERE id_producto = ?");
            $stmt->bind_param("ii", $cantidad, $id_producto);
            if (!$stmt->execute()) throw new Exception("Error al actualizar inventario");
            $stmt->close();

            $conn->commit();
            $message = "Producto actualizado exitosamente";
            $messageType = "success";
            
            // Redirect after short delay or show success
            // header("refresh:2;url=inventory.php"); 
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
            $messageType = "error";
        }
    }
}
?>
<?php
require_once __DIR__ . '/../../classes/Layout.php';
Layout::renderHead('Editar Stock - NOKIA1100');
Layout::renderAdminSidebar('inventario');
?>
<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8 border border-border/50 max-w-3xl mx-auto">
        <div class="flex justify-between items-center mb-8 pb-4 border-b border-border/50">
            <div>
                <h2 class="text-2xl font-display font-medium text-text-main">Editar Producto</h2>
                <p class="text-text-muted text-sm mt-1">Modificar detalles y existencias del inventario</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/modules/inventory/inventory.php" class="px-4 py-2 rounded-xl border border-border bg-surface hover:bg-surface-hover text-sm font-medium transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span> Volver
            </a>
        </div>

        <?php if ($message): ?>
            <div class="p-4 rounded-xl mb-6 text-sm font-medium flex gap-3 items-center <?php echo $messageType === 'success' ? 'bg-primary/10 border border-primary/20 text-primary' : 'bg-red-500/10 border border-red-500/20 text-red-500'; ?>">
                <span class="material-symbols-outlined"><?php echo $messageType === 'success' ? 'check_circle' : 'error'; ?></span>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <div>
                <label for="nombre" class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Nombre del Producto</label>
                <input type="text" id="nombre" name="nombre" required value="<?php echo isset($nombre) ? htmlspecialchars($nombre) : ''; ?>" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="marca" class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Marca</label>
                    <input type="text" id="marca" name="marca" required value="<?php echo isset($marca) ? htmlspecialchars($marca) : ''; ?>" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
                </div>
                <div>
                    <label for="modelo" class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Modelo</label>
                    <input type="text" id="modelo" name="modelo" required value="<?php echo isset($modelo) ? htmlspecialchars($modelo) : ''; ?>" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pb-6 border-b border-border/30">
                <div>
                    <label for="precio" class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Precio Unitario ($)</label>
                    <input type="number" id="precio" name="precio" step="0.01" min="0" required value="<?php echo isset($precio) ? htmlspecialchars($precio) : ''; ?>" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
                </div>
                <div>
                    <label for="cantidad" class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Cantidad Total (Stock Físico)</label>
                    <input type="number" id="cantidad" name="cantidad" min="0" required value="<?php echo isset($cantidad) ? htmlspecialchars($cantidad) : ''; ?>" class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
                </div>
            </div>

            <button type="submit" class="w-full bg-primary/10 text-primary border border-primary/20 hover:bg-primary hover:text-background font-medium py-3 rounded-xl transition-all flex justify-center items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">save</span> Actualizar Producto
            </button>
        </form>
    </div>
</main>
<?php Layout::renderFooter(); ?>
