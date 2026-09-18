<?php
session_start();
require_once __DIR__ . '/../config/db.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Nokia 1100 System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="<?php echo BASE_URL; ?>/assets/js/tailwind_config.js?v=<?php echo time(); ?>"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/forgot_password.css?v=<?php echo time(); ?>">
</head>

<body class="font-sans antialiased">
    <div class="auth-card">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold font-display tracking-tight text-text-main mb-2">Recuperar Contraseña</h1>
            <p class="text-sm text-text-muted">Introduce tu email para recibir un enlace de recuperación.</p>
        </div>



        <form action="<?php echo BASE_URL; ?>/modules/auth/index.php?action=forgot_password" method="POST" class="space-y-5" novalidate>
            <div>
                <label for="email" class="block text-xs font-bold text-text-muted mb-2 uppercase tracking-wider">Correo
                    Electrónico</label>
                <div class="relative flex flex-col-reverse">
                    <input type="email" id="email" name="email" required placeholder="tu@email.com"
                        class="auth-input peer">
                    <span
                        class="material-symbols-outlined input-icon transition-colors peer-focus:text-text-main">mail</span>
                </div>
            </div>

            <button type="submit" class="auth-btn mt-6 tracking-wide">ENVIAR ENLACE</button>
        </form>

        <div class="text-center mt-8 text-sm text-text-muted font-medium">
            <a href="<?php echo BASE_URL; ?>/index.php"
                class="text-primary hover:text-primary-hover transition-colors hover:underline font-semibold bg-transparent border-none cursor-pointer">Volver
                al inicio de sesión</a>
        </div>
    </div>

    <!-- Contenedor Unificado para Notificaciones Toasts Flotantes -->
    <div id="toast-container" class="fixed top-6 right-6 z-[9999] flex flex-col items-end pointer-events-none gap-2"></div>

    <script src="<?php echo BASE_URL; ?>/assets/js/framer-toaster.js?v=<?php echo time(); ?>"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            let msgShowed = false;
            
            if (urlParams.has("success")) {
                if (typeof showToast === "function") showToast(urlParams.get("success"), "success");
                urlParams.delete("success");
                msgShowed = true;
            }
            if (urlParams.has("error")) {
                if (typeof showToast === "function") showToast(urlParams.get("error"), "error");
                urlParams.delete("error");
                msgShowed = true;
            }
            
            <?php if (isset($_SESSION['mail_debug_notice'])): ?>
                if (typeof showToast === "function") showToast("<?php echo addslashes($_SESSION['mail_debug_notice']); ?>", "warning");
                msgShowed = true;
                <?php unset($_SESSION['mail_debug_notice']); ?>
            <?php endif; ?>
            
            if (msgShowed) {
                window.history.replaceState({}, document.title, window.location.pathname + (urlParams.toString() ? "?" + urlParams.toString() : ""));
            }
        });
    </script>
</body>

</html>