<?php
// modules/admin/save_user_modules.php
session_start();
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../config/audit.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . BASE_URL . "/modules/admin/users.php");
    exit();
}

$id_usuario = isset($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : 0;
$modulos = isset($_POST['modulos']) && is_array($_POST['modulos']) ? $_POST['modulos'] : [];

if ($id_usuario <= 0) {
    header("Location: " . BASE_URL . "/modules/admin/users.php?error=Usuario+no+válido");
    exit();
}

// Convert array of modules into a comma-separated string
// If empty, save it as an empty string to represent "no modules allowed"
$modulos_str = implode(',', $modulos);

// 1. Retrieve the username of the user being modified for the audit log
$stmt = $conn->prepare("SELECT nombre_usuario FROM usuario WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$res = $stmt->get_result();
$user_row = $res->fetch_assoc();
$target_username = $user_row ? $user_row['nombre_usuario'] : "ID $id_usuario";
$stmt->close();

// 2. Update the user's allowed modules in the database
$stmt = $conn->prepare("UPDATE usuario SET modulos_permitidos = ? WHERE id_usuario = ?");
$stmt->bind_param("si", $modulos_str, $id_usuario);

if ($stmt->execute()) {
    // Log in audit log
    $desc = "Permisos de módulos actualizados para $target_username a: [" . ($modulos_str ?: 'Ninguno') . "]";
    audit_log(
        $conn, 
        'ROLE_CHANGE', // Re-using standard ROLE_CHANGE or we can use any action code
        $_SESSION['user_id'], 
        'usuario', 
        $id_usuario, 
        $desc
    );
    $stmt->close();
    header("Location: " . BASE_URL . "/modules/admin/users.php?success=Permisos+actualizados+correctamente");
} else {
    $stmt->close();
    header("Location: " . BASE_URL . "/modules/admin/users.php?error=Error+al+guardar+los+permisos");
}
exit();
?>
