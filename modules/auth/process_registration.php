<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Datos Persona
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $dni = $_POST['dni'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];

    // Datos Usuario
    $username = $_POST['username'];
    $password = $_POST['password'];
    $rol = 'empleado';

    // Validar campos básicos
    if (empty($nombre) || empty($apellido) || empty($dni) || empty($username) || empty($password)) {
        header("Location: " . BASE_URL . "/index.php?action=register&error=Por favor complete todos los campos requeridos");
        exit();
    }

    // Verificar si el usuario ya existe
    $check_stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE nombre_usuario = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        header("Location: " . BASE_URL . "/index.php?action=register&error=El nombre de usuario ya existe");
        exit();
    }
    $check_stmt->close();

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // 1. Insertar Persona
        $stmt_persona = $conn->prepare("INSERT INTO persona (nombre, apellido, dni, telefono, email, direccion) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_persona->bind_param("ssssss", $nombre, $apellido, $dni, $telefono, $email, $direccion);
        
        if (!$stmt_persona->execute()) {
            throw new Exception("Error al registrar persona");
        }
        
        $id_persona = $conn->insert_id;
        $stmt_persona->close();

        // 2. Insertar Usuario
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt_usuario = $conn->prepare("INSERT INTO usuario (id_persona, nombre_usuario, contrasena, rol) VALUES (?, ?, ?, ?)");
        $stmt_usuario->bind_param("isss", $id_persona, $username, $hashed_password, $rol);
        
        if (!$stmt_usuario->execute()) {
            throw new Exception("Error al registrar usuario");
        }
        $stmt_usuario->close();

        // Confirmar transacción
        $conn->commit();
        
        header("Location: " . BASE_URL . "/index.php?success=Registro exitoso. Por favor inicie sesión.");
        exit();

    } catch (Exception $e) {
        // Revertir cambios si hay error
        $conn->rollback();
        header("Location: " . BASE_URL . "/index.php?action=register&error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: " . BASE_URL . "/index.php?action=register");
    exit();
}
?>
