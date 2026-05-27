<?php
// modules/auth/process_registration.php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/audit.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . BASE_URL . "/index.php?action=register");
    exit();
}

$nombre    = trim($_POST['nombre']    ?? '');
$apellido  = trim($_POST['apellido']  ?? '');
$dni       = trim($_POST['dni']       ?? '');
$email     = trim($_POST['email']     ?? '');
$telefono  = trim($_POST['telefono']  ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$username  = trim($_POST['username']  ?? '');
$password  = $_POST['password']       ?? '';

// ── Validaciones ─────────────────────────────────────────────────────────────
if (empty($nombre) || empty($apellido) || empty($dni) || empty($username) || empty($password)) {
    header("Location: " . BASE_URL . "/index.php?action=register&error=Completá todos los campos requeridos");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: " . BASE_URL . "/index.php?action=register&error=El formato del email no es válido");
    exit();
}

if (strlen($password) < 6) {
    header("Location: " . BASE_URL . "/index.php?action=register&error=La contraseña debe tener al menos 6 caracteres");
    exit();
}

// ── Unicidad: usuario ─────────────────────────────────────────────────────────
$check = $conn->prepare("SELECT id_usuario FROM usuario WHERE nombre_usuario = ?");
$check->bind_param("s", $username);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    $check->close();
    header("Location: " . BASE_URL . "/index.php?action=register&error=El nombre de usuario ya está en uso");
    exit();
}
$check->close();

// ── Unicidad: DNI ─────────────────────────────────────────────────────────────
$check2 = $conn->prepare("SELECT id_persona FROM persona WHERE dni = ?");
$check2->bind_param("s", $dni);
$check2->execute();
$check2->store_result();
if ($check2->num_rows > 0) {
    $check2->close();
    header("Location: " . BASE_URL . "/index.php?action=register&error=El DNI ingresado ya está registrado");
    exit();
}
$check2->close();

// ── Transacción ───────────────────────────────────────────────────────────────
$conn->begin_transaction();

try {
    // Inserto los datos personales (nombre, apellido, DNI, etc.) de la persona que se está registrando
    $stmt = $conn->prepare("INSERT INTO persona (nombre, apellido, dni, telefono, email, direccion) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $nombre, $apellido, $dni, $telefono, $email, $direccion);
    if (!$stmt->execute()) throw new Exception("Error al registrar persona");
    $id_persona = $conn->insert_id;
    $stmt->close();

    // Le creo la cuenta. Si es el primerísimo usuario del sistema lo hago admin; si no, empleado
    $hashed   = password_hash($password, PASSWORD_DEFAULT);
    
    // Si es el primer usuario del sistema, se le asigna el rol de administrador automáticamente
    $total_usuarios = 0;
    $count_query = $conn->query("SELECT COUNT(*) as total FROM usuario");
    if ($count_query) {
        $row_count = $count_query->fetch_assoc();
        $total_usuarios = (int) $row_count['total'];
    }
    
    $rol = ($total_usuarios === 0) ? 'admin' : 'empleado';
    $verificado = 1;

    $stmt = $conn->prepare(
        "INSERT INTO usuario (id_persona, nombre_usuario, contrasena, rol, email, verificado, is_active)
         VALUES (?, ?, ?, ?, ?, ?, 1)"
    );
    $stmt->bind_param("issssi", $id_persona, $username, $hashed, $rol, $email, $verificado);
    if (!$stmt->execute()) throw new Exception("Error al registrar usuario");
    $id_nuevo_usuario = $conn->insert_id;
    $stmt->close();

    $conn->commit();

    // Auditoría
    $id_ejecutor = $_SESSION['user_id'] ?? null;
    audit_log($conn, 'USER_CREATE', $id_ejecutor, 'usuario', $id_nuevo_usuario,
        "Nuevo usuario creado: $username ($nombre $apellido)");

    header("Location: " . BASE_URL . "/index.php?success=" . urlencode("Registro exitoso. Podés iniciar sesión."));
    exit();

} catch (Exception $e) {
    $conn->rollback();
    header("Location: " . BASE_URL . "/index.php?action=register&error=" . urlencode($e->getMessage()));
    exit();
}
