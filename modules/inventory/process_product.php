<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
// Solo admins pueden agregar productos
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/modules/inventory/inventory.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'] ?? '';
    $marca = $_POST['marca'] ?? '';
    $modelo = $_POST['modelo'] ?? '';
    $precio = $_POST['precio'] ?? 0;
    $cantidad = $_POST['cantidad'] ?? 0;
    
    // Nuevos campos
    $categoria = $_POST['categoria'] ?? '';
    $codigo = $_POST['codigo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $stock_minimo = $_POST['stock_minimo'] ?? 0;

    if (empty($nombre) || empty($marca) || empty($modelo) || empty($precio) || $cantidad === '' || empty($categoria)) {
        header("Location: add_product.php?error=Los campos principales son obligatorios");
        exit();
    }

    $precio = floatval($precio);
    $cantidad = intval($cantidad);
    $stock_minimo = intval($stock_minimo);

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // 1. Insertar producto
        $stmt_prod = $conn->prepare("INSERT INTO producto (nombre, precio, is_active) VALUES (?, ?, 1)");
        $stmt_prod->bind_param("sd", $nombre, $precio);
        
        if (!$stmt_prod->execute()) {
            throw new Exception("Error al registrar producto base");
        }
        
        $id_producto = $conn->insert_id;
        $stmt_prod->close();

        // 2. Insertar detalles del producto
        $stmt_det = $conn->prepare("INSERT INTO producto_detalle (id_producto, marca, modelo, categoria, codigo, descripcion, stock_minimo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_det->bind_param("isssssi", $id_producto, $marca, $modelo, $categoria, $codigo, $descripcion, $stock_minimo);
        
        if (!$stmt_det->execute()) {
            throw new Exception("Error al registrar detalles del producto");
        }
        $stmt_det->close();

        // 3. Insertar inventario inicial
        $stmt_inv = $conn->prepare("INSERT INTO inventario (id_producto, cantidad) VALUES (?, ?)");
        $stmt_inv->bind_param("ii", $id_producto, $cantidad);
        
        if (!$stmt_inv->execute()) {
            throw new Exception("Error al registrar el stock inicial");
        }
        $stmt_inv->close();

        // Confirmar transacción
        $conn->commit();
        
        header("Location: inventory.php?success=created");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: add_product.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: inventory.php");
    exit();
}
