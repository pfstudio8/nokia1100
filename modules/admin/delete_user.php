<?php
// modules/admin/delete_user.php  ←  BAJA LÓGICA (no borra físicamente)
session_start();
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../config/audit.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: users.php?error=ID no especificado");
    exit();
}

$id          = intval($_GET['id']);
$id_admin    = (int) $_SESSION['user_id'];

// No puede darse de baja a sí mismo
if ($id === $id_admin) {
    header("Location: users.php?error=No podés desactivar tu propia cuenta");
    exit();
}

// Obtener datos del usuario objetivo
$stmt = $conn->prepare("SELECT id_persona, rol, nombre_usuario, is_active FROM usuario WHERE id_usuario = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    header("Location: users.php?error=Usuario no encontrado");
    exit();
}

// No se puede desactivar a otro admin
if ($row['rol'] === 'admin') {
    header("Location: users.php?error=No se puede desactivar un usuario con rol admin");
    exit();
}

//RESTAURAR si ya está inactiv
if ((int)$row['is_active'] === 0) {
    $stmt = $conn->prepare(
        "UPDATE usuario SET is_active = 1, fecha_baja = NULL, motivo_baja = NULL WHERE id_usuario = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    audit_log($conn, 'USER_RESTORE', $id_admin, 'usuario', $id,
        "Usuario restaurado: {$row['nombre_usuario']}");

    header("Location: users.php?success=Usuario restaurado correctamente");
    exit();
}

//BAJA LÓGICA
$motivo     = 'Desactivado por administrador';
$fecha_baja = date('Y-m-d H:i:s');

$stmt = $conn->prepare(
    "UPDATE usuario SET is_active = 0, fecha_baja = ?, motivo_baja = ? WHERE id_usuario = ?"
);
$stmt->bind_param("ssi", $fecha_baja, $motivo, $id);

if ($stmt->execute()) {
    $stmt->close();
    audit_log($conn, 'USER_DELETE', $id_admin, 'usuario', $id,
        "Baja lógica aplicada a: {$row['nombre_usuario']}");
    header("Location: users.php?success=Usuario desactivado (baja lógica)");
} else {
    $stmt->close();
    header("Location: users.php?error=Error al desactivar usuario");
}
exit();
