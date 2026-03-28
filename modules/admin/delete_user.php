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

// Proteger al admin principal (si querés evitar borrar al id 1)
if ($id == 1) {
    header("Location: users.php?error=No_puedes_eliminar_admin");
    exit();
}

// Eliminar el usuario
$sql = "DELETE FROM usuario WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: users.php?success=Usuario_eliminado");
} else {
    header("Location: users.php?error=Error_al_eliminar");
}

$stmt->close();
$conn->close();
?>
