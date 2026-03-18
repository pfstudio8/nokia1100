<?php
require_once __DIR__ . '/../includes/auth.php';
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Acceso denegado']));
}
require_once '../config/bd.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_imagen'])) {
    $id_imagen = intval($_POST['id_imagen']);
    
    // Obtener info de la imagen
    $stmt = $conn->prepare("SELECT id_producto, nombre_archivo, es_principal FROM producto_imagen WHERE id_imagen = ?");
    $stmt->bind_param("i", $id_imagen);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $img = $res->fetch_assoc();
        $filepath = "../uploads/productos/" . $img['id_producto'] . "/" . $img['nombre_archivo'];
        
        // Borrar archivo físico
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        
        // Borrar de BD
        $del = $conn->prepare("DELETE FROM producto_imagen WHERE id_imagen = ?");
        $del->bind_param("i", $id_imagen);
        $del->execute();
        
        // Si era principal, asignar otra
        if ($img['es_principal'] == 1) {
            $next = $conn->query("SELECT id_imagen FROM producto_imagen WHERE id_producto = " . $img['id_producto'] . " ORDER BY id_imagen ASC LIMIT 1");
            if ($next->num_rows > 0) {
                $new_main = $next->fetch_assoc();
                $conn->query("UPDATE producto_imagen SET es_principal = 1 WHERE id_imagen = " . $new_main['id_imagen']);
            }
        }
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Imagen no encontrada']);
    }
}
?>
