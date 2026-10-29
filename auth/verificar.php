<?php
// verificar.php - Nokia 1100 Design System
session_start();
require_once __DIR__ . '/../config/db.php';
/** @var mysqli $conn */
global $conn;

function renderVerificationView(bool $isSuccess, string $title, string $message): void
{
    $cssVer = file_exists(__DIR__ . '/../assets/css/forgot_password.css') ? filemtime(__DIR__ . '/../assets/css/forgot_password.css') : time();
    $badgeText = $isSuccess ? 'VERIFICACIÓN COMPLETADA' : 'ESTADO DE VERIFICACIÓN';
    $iconName = $isSuccess ? 'verified_user' : 'error';
    $iconBoxStyle = $isSuccess
        ? 'background:#dfeee3; border:1px solid #a8d5b5; color:#1d6a38;'
        : 'background:#f6dcd7; border:1px solid #dfa39a; color:#a82a1d;';
    $btnLabel = $isSuccess ? 'Ir a iniciar sesión' : 'Volver al inicio';
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - Sistema Nokia 1100</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/forgot_password.css?v=<?php echo $cssVer; ?>">
</head>
<body>
    <main class="recovery-card">
        <header class="recovery-topbar">
            <a href="<?php echo BASE_URL; ?>/index.php" class="brand-wrap">
                <span class="brand-text">SISTEMA NOKIA</span>
                <span class="brand-chip">1100</span>
            </a>
            <span class="status-dot" title="Sistema activo"></span>
        </header>

        <div class="recovery-body" style="text-align: center;">
            <div style="width: 56px; height: 56px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1.1rem; <?php echo $iconBoxStyle; ?>">
                <span class="material-symbols-outlined" style="font-size: 28px;"><?php echo $iconName; ?></span>
            </div>

            <span class="form-header-badge"><?php echo $badgeText; ?></span>
            <h1 class="form-title" style="text-align: center;"><?php echo htmlspecialchars($title); ?></h1>
            <p class="form-subtitle" style="margin-bottom: 1.5rem;"><?php echo htmlspecialchars($message); ?></p>

            <a href="<?php echo BASE_URL; ?>/index.php" class="btn-primary-auth">
                <span><?php echo htmlspecialchars($btnLabel); ?></span>
                <span class="material-symbols-outlined" style="font-size: 18px;">arrow_forward</span>
            </a>

            <footer class="recovery-footer">
                <button type="button" onclick="window.close()" class="back-link" style="background: none; border: none; cursor: pointer;">
                    <span class="material-symbols-outlined" style="font-size: 15px;">close</span>
                    <span class="back-link-label">Cerrar esta pestaña</span>
                </button>
            </footer>
        </div>
    </main>
</body>
</html>
<?php
    exit();
}

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $now = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("SELECT id_usuario, nombre_usuario, rol FROM usuario WHERE token_verificacion = ? AND token_expira > ? AND verificado = 0");
    $stmt->bind_param("ss", $token, $now);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $id_usuario = $user['id_usuario'];
        $username = $user['nombre_usuario'];
        $stmt->close();

        // Activar cuenta
        $update_stmt = $conn->prepare("UPDATE usuario SET verificado = 1, token_verificacion = NULL, token_expira = NULL WHERE id_usuario = ?");
        $update_stmt->bind_param("i", $id_usuario);

        $executed = $update_stmt->execute();
        $update_stmt->close();

        if ($executed) {
            require_once __DIR__ . '/../config/audit.php';
            audit_log($conn, 'LOGIN_OK', $id_usuario, 'usuario', $id_usuario, "Cuenta verificada desde correo electrónico: $username");
            renderVerificationView(
                true,
                '¡Cuenta verificada!',
                'Tu correo electrónico fue confirmado con éxito. Ya podés iniciar sesión en el sistema.'
            );
        } else {
            renderVerificationView(
                false,
                'Error de verificación',
                'Ocurrió un inconveniente al activar la cuenta en la base de datos.'
            );
        }
    } else {
        $stmt->close();
        renderVerificationView(
            false,
            'Enlace no disponible',
            'El enlace de verificación es inválido, expiró o tu cuenta ya fue activada previamente.'
        );
    }
} else {
    renderVerificationView(
        false,
        'Enlace incompleto',
        'No se recibió un token de verificación válido.'
    );
}
