<?php
// config/audit.php
// Helper para registrar acciones en el log de auditoría.
// Requiere que $conn esté disponible (ya incluido por db.php).

/**
 * Registra una acción en la tabla audit_log.
 *
 * @param mysqli $conn          Conexión activa a la base de datos
 * @param string $accion        Código de acción: LOGIN_OK, LOGIN_FAIL, LOGOUT, etc.
 * @param int|null $id_usuario  ID del usuario que ejecuta la acción (null si no aplica)
 * @param string|null $tabla    Tabla afectada (ej: 'usuario', 'producto')
 * @param int|null $id_registro ID del registro afectado
 * @param string|null $desc     Descripción libre adicional
 * @param string|null $username_intent  Usuario que intentó login (útil en LOGIN_FAIL)
 */
function audit_log(
    mysqli $conn,
    string $accion,
    ?int   $id_usuario    = null,
    ?string $tabla        = null,
    ?int   $id_registro   = null,
    ?string $desc         = null,
    ?string $username_intent = null
): void {
    $ip         = $_SERVER['REMOTE_ADDR']     ?? 'unknown';
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

    $stmt = $conn->prepare(
        "INSERT INTO audit_log
            (id_usuario, accion, tabla_afectada, id_registro, descripcion, ip, user_agent, username_intent)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) return; // tabla no existe aún (antes de migración)

    $stmt->bind_param(
        'ississss',
        $id_usuario,
        $accion,
        $tabla,
        $id_registro,
        $desc,
        $ip,
        $user_agent,
        $username_intent
    );
    $stmt->execute();
    $stmt->close();
}
