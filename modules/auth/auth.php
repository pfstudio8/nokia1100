<?php
// modules/auth/auth.php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/audit.php'; // ← helper de auditoría

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    header("Location: " . BASE_URL . "/index.php?error=Por favor complete todos los campos");
    exit();
}

$stmt = $conn->prepare(
    "SELECT id_usuario, nombre_usuario, contrasena, rol, verificado, is_active
     FROM usuario
     WHERE nombre_usuario = ?"
);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

// ── Usuario no encontrado ────────────────────────────────────────────────────
if ($result->num_rows !== 1) {
    $stmt->close();
    audit_log($conn, 'LOGIN_FAIL', null, 'usuario', null,
        "Usuario no encontrado: $username", $username);
    header("Location: " . BASE_URL . "/index.php?error=Usuario o contraseña incorrectos");
    exit();
}

$row = $result->fetch_assoc();
$stmt->close();

// ── Contraseña incorrecta ────────────────────────────────────────────────────
if (!password_verify($password, $row['contrasena'])) {
    audit_log($conn, 'LOGIN_FAIL', $row['id_usuario'], 'usuario', $row['id_usuario'],
        "Contraseña incorrecta para: $username", $username);
    header("Location: " . BASE_URL . "/index.php?error=Usuario o contraseña incorrectos");
    exit();
}

// ── Cuenta desactivada (baja lógica) ─────────────────────────────────────────
if ((int)$row['is_active'] === 0) {
    audit_log($conn, 'LOGIN_FAIL', $row['id_usuario'], 'usuario', $row['id_usuario'],
        "Intento de login en cuenta desactivada: $username", $username);
    header("Location: " . BASE_URL . "/index.php?error=Esta cuenta fue desactivada. Contacte al administrador.");
    exit();
}

// ── Email no verificado ───────────────────────────────────────────────────────
if ((int)$row['verificado'] === 0) {
    audit_log($conn, 'LOGIN_FAIL', $row['id_usuario'], 'usuario', $row['id_usuario'],
        "Login bloqueado: email sin verificar para $username", $username);
    header("Location: " . BASE_URL . "/index.php?error=Tu cuenta aún no fue verificada. Revisá tu email.");
    exit();
}

// ── Login exitoso ─────────────────────────────────────────────────────────────
$_SESSION['user_id']  = $row['id_usuario'];
$_SESSION['username'] = $row['nombre_usuario'];
$_SESSION['role']     = $row['rol'];

audit_log($conn, 'LOGIN_OK', $row['id_usuario'], 'usuario', $row['id_usuario'],
    "Sesión iniciada correctamente");

$welcome_msg = urlencode("Bienvenido, " . $row['nombre_usuario']);
if ($row['rol'] === 'admin') {
    header("Location: " . BASE_URL . "/modules/admin/dashboard.php?success=" . $welcome_msg);
} else {
    header("Location: " . BASE_URL . "/modules/employee/dashboard.php?success=" . $welcome_msg);
}
exit();
