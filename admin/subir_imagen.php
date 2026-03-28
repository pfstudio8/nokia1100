<?php
require_once __DIR__ . '/../includes/auth.php';
// We don't use require_admin_auth() here to return JSON error instead of redirect
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Acceso denegado']));
}
require_once '../config/bd.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['imagen']) && isset($_POST['id_producto'])) {
    $id_producto = intval($_POST['id_producto']);
    $file = $_FILES['imagen'];
    
    // Validaciones
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        die(json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido (solo JPG, PNG, WEBP)']));
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB
        die(json_encode(['success' => false, 'message' => 'El archivo es demasiado grande (max 5MB)']));
    }
    
    // Crear directorio si no existe
    $upload_dir = "../uploads/productos/$id_producto/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generar nombre único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_') . '.' . $extension;
    $target_path = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        
        // Verificar si es la primera imagen
        $check = $conn->query("SELECT COUNT(*) as count FROM producto_imagen WHERE id_producto = $id_producto");
        $count = $check->fetch_assoc()['count'];
        $es_principal = ($count == 0) ? 1 : 0;
        
        $sql = "INSERT INTO producto_imagen (id_producto, nombre_archivo, es_principal, orden) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $orden = $count + 1;
        $stmt->bind_param("isii", $id_producto, $filename, $es_principal, $orden);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            unlink($target_path); // Borrar si falla BD
            echo json_encode(['success' => false, 'message' => 'Error en base de datos']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
}
?>
