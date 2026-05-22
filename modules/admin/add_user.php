<?php
session_start();
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../config/audit.php";

// Solo el administrador puede crear usuarios directamente
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: users.php");
    exit();
}

$nombre           = trim($_POST['nombre'] ?? '');
$apellido         = trim($_POST['apellido'] ?? '');
$dni              = trim($_POST['dni'] ?? '');
$email            = trim($_POST['email'] ?? '');
$telefono         = trim($_POST['telefono'] ?? '');
$direccion        = trim($_POST['direccion'] ?? '');
$username         = trim($_POST['username'] ?? '');
$password         = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';
$rol              = trim($_POST['rol'] ?? 'empleado');

// Validar campos requeridos
if (empty($nombre) || empty($apellido) || empty($dni) || empty($email) || empty($username) || empty($password) || empty($password_confirm) || empty($rol)) {
    header("Location: users.php?error=" . urlencode("Complete todos los campos requeridos"));
    exit();
}

// Validar que las contraseñas coincidan
if ($password !== $password_confirm) {
    header("Location: users.php?error=" . urlencode("Las contraseñas no coinciden"));
    exit();
}

// Validar largo de contraseña
if (strlen($password) < 6) {
    header("Location: users.php?error=" . urlencode("La contraseña debe tener al menos 6 caracteres"));
    exit();
}

// Validar formato de email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: users.php?error=" . urlencode("El formato del email no es válido"));
    exit();
}

// Validar formato de DNI (entre 7 y 10 dígitos)
if (!preg_match('/^[0-9]{7,10}$/', $dni)) {
    header("Location: users.php?error=" . urlencode("El DNI debe tener entre 7 y 10 dígitos"));
    exit();
}

// Validar rol
if ($rol !== 'admin' && $rol !== 'empleado') {
    header("Location: users.php?error=" . urlencode("El rol seleccionado no es válido"));
    exit();
}

// Verificar si el usuario ya existe
$checkUser = $conn->prepare("SELECT id_usuario FROM usuario WHERE nombre_usuario = ?");
$checkUser->bind_param("s", $username);
$checkUser->execute();
$checkUser->store_result();
if ($checkUser->num_rows > 0) {
    $checkUser->close();
    header("Location: users.php?error=" . urlencode("El nombre de usuario ya está en uso"));
    exit();
}
$checkUser->close();

// Verificar si el email ya está registrado
$checkEmail = $conn->prepare("SELECT id_persona FROM persona WHERE email = ?");
$checkEmail->bind_param("s", $email);
$checkEmail->execute();
$checkEmail->store_result();
if ($checkEmail->num_rows > 0) {
    $checkEmail->close();
    header("Location: users.php?error=" . urlencode("El email ingresado ya está registrado"));
    exit();
}
$checkEmail->close();

// Verificar si el DNI ya está registrado
$checkDni = $conn->prepare("SELECT id_persona FROM persona WHERE dni = ?");
$checkDni->bind_param("s", $dni);
$checkDni->execute();
$checkDni->store_result();
if ($checkDni->num_rows > 0) {
    $checkDni->close();
    header("Location: users.php?error=" . urlencode("El DNI ingresado ya está registrado"));
    exit();
}
$checkDni->close();

$conn->begin_transaction();

try {
    // 1. Insertar en la tabla persona
    $stmtPersona = $conn->prepare("INSERT INTO persona (nombre, apellido, dni, telefono, email, direccion) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtPersona->bind_param("ssssss", $nombre, $apellido, $dni, $telefono, $email, $direccion);
    if (!$stmtPersona->execute()) {
        throw new Exception("Error al registrar los datos personales de la persona");
    }
    $id_persona = $conn->insert_id;
    $stmtPersona->close();

    // 2. Insertar en la tabla usuario (marcando verificado = 1, is_active = 1, sin token)
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmtUsuario = $conn->prepare(
        "INSERT INTO usuario (id_persona, nombre_usuario, contrasena, rol, email, verificado, is_active, token_verificacion, token_expira) 
         VALUES (?, ?, ?, ?, ?, 1, 1, NULL, NULL)"
    );
    $stmtUsuario->bind_param("issss", $id_persona, $username, $hashedPassword, $rol, $email);
    if (!$stmtUsuario->execute()) {
        throw new Exception("Error al registrar la cuenta de usuario");
    }
    $id_nuevo_usuario = $conn->insert_id;
    $stmtUsuario->close();

    $conn->commit();

    // 3. Registrar acción en auditoría
    $id_admin = (int)$_SESSION['user_id'];
    audit_log($conn, 'USER_CREATE', $id_admin, 'usuario', $id_nuevo_usuario, "Administrador creó usuario directamente: $username ($nombre $apellido) con rol $rol");

    header("Location: users.php?success=" . urlencode("Usuario '$username' agregado correctamente y listo para operar"));
    exit();

} catch (Exception $e) {
    $conn->rollback();
    header("Location: users.php?error=" . urlencode($e->getMessage()));
    exit();
}
?>
