<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: inventario.php");
    exit();
}
require_once 'config/bd.php';

$message = '';
$messageType = '';
$id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_producto === 0) {
    header("Location: inventario.php");
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
        header("Location: inventario.php");
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
            // header("refresh:2;url=inventario.php"); 
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Stock - Nokia 1100</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
</head>
<body>
    <div class="dashboard-container" style="padding-top: 3rem;">
        
        <div style="margin-bottom: 3rem; width: 100%; max-width: 600px; margin-left: auto; margin-right: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <p style="text-transform: uppercase; letter-spacing: 0.2em; font-size: 0.7rem; color: var(--primary); font-weight: 800; margin-bottom: 0.25rem; opacity: 0.8;">Gestión de Catálogo</p>
                    <h1 style="margin-bottom: 0; font-size: 2.2rem; letter-spacing: -0.02em;">Editar Producto</h1>
                </div>
                <div style="display: flex; gap: 0.75rem;">
                    <a href="inventario.php" class="btn btn-outline" style="padding: 0.6rem 1.2rem; font-size: 0.85rem;">Cerrar</a>
                </div>
            </div>
            <div style="height: 3px; width: 30px; background: var(--primary); margin-top: 1.5rem; border-radius: 2px;"></div>
        </div>

        <div class="container" style="max-width: 600px; margin: 0 auto; padding: 0;">
            <div class="glass-card" style="padding: 2.5rem;">


            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>" style="display: block; margin-bottom: 1.5rem;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="nombre">Nombre del Producto</label>
                    <input type="text" id="nombre" name="nombre" required value="<?php echo isset($nombre) ? htmlspecialchars($nombre) : ''; ?>" placeholder="Ej: Celular, Cargador...">
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label for="marca">Marca</label>
                        <input type="text" id="marca" name="marca" required value="<?php echo isset($marca) ? htmlspecialchars($marca) : ''; ?>" placeholder="Ej: Nokia">
                    </div>
                    <div class="form-group">
                        <label for="modelo">Modelo</label>
                        <input type="text" id="modelo" name="modelo" required value="<?php echo isset($modelo) ? htmlspecialchars($modelo) : ''; ?>" placeholder="Ej: 1100">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label for="precio">Precio ($)</label>
                        <input type="number" id="precio" name="precio" step="0.01" min="0" required value="<?php echo isset($precio) ? htmlspecialchars($precio) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="cantidad">Cantidad Total</label>
                        <input type="number" id="cantidad" name="cantidad" min="0" required value="<?php echo isset($cantidad) ? htmlspecialchars($cantidad) : ''; ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; padding: 1rem; font-size: 1rem; letter-spacing: 0.05em;">ACTUALIZAR PRODUCTO</button>
            </form>
        </div>
    </div>
</body>
</html>
