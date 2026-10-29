<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['session_token'])) {
    $_SESSION['session_token'] = bin2hex(random_bytes(32));
}
$cssVer = file_exists(__DIR__ . '/../assets/css/forgot_password.css') ? filemtime(__DIR__ . '/../assets/css/forgot_password.css') : time();
$toasterVer = file_exists(__DIR__ . '/../assets/js/framer-toaster.js') ? filemtime(__DIR__ . '/../assets/js/framer-toaster.js') : time();
$mainVer = file_exists(__DIR__ . '/../assets/js/main.js') ? filemtime(__DIR__ . '/../assets/js/main.js') : time();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Sistema Nokia 1100</title>
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

        <div class="recovery-body">
            <span class="form-header-badge">RECUPERACIÓN DE ACCESO</span>
            <h1 class="form-title">Recuperar contraseña</h1>
            <p class="form-subtitle">
                Ingresá el correo electrónico asociado a tu cuenta y te enviaremos un enlace seguro para restablecer tu clave.
            </p>

            <form action="<?php echo BASE_URL; ?>/modules/auth/index.php?action=forgot_password" method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['session_token'] ?? ''); ?>">

                <div class="field-group">
                    <label for="email" class="field-label">CORREO ELECTRÓNICO</label>
                    <div class="field-input-box">
                        <input
                            type="email"
                            id="email"
                            name="email"
                            required
                            autocomplete="email"
                            placeholder="nombre@correo.com">
                        <span class="material-symbols-outlined field-icon">mail</span>
                    </div>
                </div>

                <button type="submit" class="btn-primary-auth">
                    <span>Enviar enlace de recuperación</span>
                    <span class="material-symbols-outlined" style="font-size: 18px;">arrow_forward</span>
                </button>
            </form>

            <div class="recovery-note">
                <span class="material-symbols-outlined">schedule</span>
                <span>Por seguridad, el enlace de recuperación tiene una validez de 60 minutos desde su solicitud.</span>
            </div>

            <footer class="recovery-footer">
                <a href="<?php echo BASE_URL; ?>/index.php" class="back-link">
                    <span class="material-symbols-outlined" style="font-size: 16px;">arrow_back</span>
                    <span class="back-link-label">Volver al inicio de sesión</span>
                </a>
            </footer>
        </div>
    </main>

    <script src="<?php echo BASE_URL; ?>/assets/js/framer-toaster.js?v=<?php echo $toasterVer; ?>"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js?v=<?php echo $mainVer; ?>"></script>
    <?php if (isset($_SESSION['mail_debug_notice'])): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof showToast === "function") {
                showToast(<?php echo json_encode((string)$_SESSION['mail_debug_notice'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>, "warning");
            }
        });
    </script>
    <?php unset($_SESSION['mail_debug_notice']); endif; ?>
</body>

</html>
