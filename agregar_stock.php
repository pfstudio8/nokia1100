<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'config/bd.php';

$message = '';
$messageType = '';

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
            // 1. Insertar en producto
            $stmt = $conn->prepare("INSERT INTO producto (nombre, precio) VALUES (?, ?)");
            $stmt->bind_param("sd", $nombre, $precio);
            if (!$stmt->execute()) throw new Exception("Error al insertar producto");
            $id_producto = $conn->insert_id;
            $stmt->close();

            // 2. Insertar en producto_detalle
            $stmt = $conn->prepare("INSERT INTO producto_detalle (id_producto, marca, modelo) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $id_producto, $marca, $modelo);
            if (!$stmt->execute()) throw new Exception("Error al insertar detalles");
            $stmt->close();

            // 3. Insertar en inventario
            $stmt = $conn->prepare("INSERT INTO inventario (id_producto, cantidad) VALUES (?, ?)");
            $stmt->bind_param("ii", $id_producto, $cantidad);
            if (!$stmt->execute()) throw new Exception("Error al insertar inventario");
            $stmt->close();

            $conn->commit();
            $message = "Producto agregado exitosamente";
            $messageType = "success";
            
            // Limpiar variables para el formulario
            $nombre = $marca = $modelo = $precio = $cantidad = '';
            
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
    <title>Agregar Stock - Nokia 1100</title>
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
                    <h1 style="margin-bottom: 0; font-size: 2.2rem; letter-spacing: -0.02em;">Nuevo Producto</h1>
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
                        <label for="cantidad">Cantidad Inicial</label>
                        <input type="number" id="cantidad" name="cantidad" min="1" required value="<?php echo isset($cantidad) ? htmlspecialchars($cantidad) : ''; ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; padding: 1rem; font-size: 1rem; letter-spacing: 0.05em;">GUARDAR EN INVENTARIO</button>
            </form>
        </div>
    </div>
</body>
</html>
