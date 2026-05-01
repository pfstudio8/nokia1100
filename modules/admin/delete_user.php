<?php
session_start();
require_once __DIR__ . "/../../config/db.php";

// Solo el admin puede borrar
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}


// Verificar que haya un id recibido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: users.php?error=No_id");
    exit();
}

$id = intval($_GET['id']);

// Proteger borrar solo si el usuario destino actualmente tiene rol admin
$sql_get = "SELECT id_persona, rol FROM usuario WHERE id_usuario = ?";
$stmt_get = $conn->prepare($sql_get);
$stmt_get->bind_param("i", $id);
$stmt_get->execute();
$result = $stmt_get->get_result();

$id_persona = null;
$rol_destino = null;
if ($row = $result->fetch_assoc()) {
    $id_persona = $row['id_persona'];
    $rol_destino = $row['rol'];
}
$stmt_get->close();

if ($rol_destino === 'admin') {
    header("Location: users.php?error=No se puede eliminar un usuario con rol admin");
    exit();
}

// Obtener el id_persona asociado
// (id_persona ya fue obtenido junto con el rol)

// Nullificar el id_usuario en las ventas para que no dé error de constraint (y mantener el historial de ventas)
$sql_ventas = "UPDATE venta SET id_usuario = NULL WHERE id_usuario = ?";
$stmt_ventas = $conn->prepare($sql_ventas);
$stmt_ventas->bind_param("i", $id);
$stmt_ventas->execute();
$stmt_ventas->close();

// Eliminar el usuario
$sql = "DELETE FROM usuario WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $stmt->close();
    // Eliminar también la persona asociada a ese usuario para no dejar registros huérfanos
    if ($id_persona) {
        $sql_per = "DELETE FROM persona WHERE id_persona = ?";
        $stmt_per = $conn->prepare($sql_per);
        $stmt_per->bind_param("i", $id_persona);
        $stmt_per->execute();
        $stmt_per->close();
    }
    header("Location: users.php?success=Usuario_eliminado");
} else {
    $stmt->close();
    header("Location: users.php?error=Error_al_eliminar");
}

$stmt->close();
$conn->close();
?>