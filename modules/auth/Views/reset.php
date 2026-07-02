<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Establecer Nueva Contraseña - Nokia 1100 System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script src="<?php echo BASE_URL; ?>/assets/js/tailwind_config.js?v=<?php echo time(); ?>"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/auth/reset_password.css?v=<?php echo time(); ?>">
</head>

<body class="font-sans antialiased">
    <div class="auth-card">
        <div class="text-center mb-8">
            <img src="<?php echo BASE_URL; ?>/assets/img/hero_phone.png" alt="Phone" class="w-16 h-16 mx-auto mb-4 object-contain filter drop-shadow-[0_0_15px_rgba(33,184,189,0.3)]">
            <h1 class="text-2xl font-bold font-display tracking-tight text-text-main mb-2">Crear Nueva Contraseña</h1>
            <p class="text-sm text-text-muted">Introduce tu nueva contraseña segura abajo.</p>
        </div>

        <?php if (!$isValidToken): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-500 text-sm p-4 rounded-xl mb-6 font-medium flex gap-3 items-center">
                <span class="material-symbols-outlined text-lg">error</span>
                Enlace inválido o expirado. Por favor, solicita uno nuevo.
            </div>
            
            <div class="text-center mt-6">
                <a href="<?php echo BASE_URL; ?>/forgot_password.php" class="auth-btn inline-block text-center no-underline">SOLICITAR NUEVO ENLACE</a>
            </div>
        <?php else: ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-500 text-sm p-4 rounded-xl mb-6 font-medium flex gap-3 items-center">
                    <span class="material-symbols-outlined text-lg">error</span>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo BASE_URL; ?>/modules/auth/process_reset.php" method="POST" class="space-y-5" novalidate>
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div>
                    <label for="password" class="block text-xs font-bold text-text-muted mb-2 uppercase tracking-wider">Nueva Contraseña</label>
                    <div class="relative flex flex-col-reverse">
                        <input type="password" id="password" name="password" required placeholder="••••••••" class="auth-input peer">
                        <span class="material-symbols-outlined input-icon transition-colors peer-focus:text-text-main">lock</span>
                    </div>
                </div>

                <div>
                    <label for="password_confirm" class="block text-xs font-bold text-text-muted mb-2 uppercase tracking-wider">Confirmar Contraseña</label>
                    <div class="relative flex flex-col-reverse">
                        <input type="password" id="password_confirm" name="password_confirm" required placeholder="••••••••" class="auth-input peer">
                        <span class="material-symbols-outlined input-icon transition-colors peer-focus:text-text-main">lock_clock</span>
                    </div>
                </div>

                <button type="submit" class="auth-btn mt-6 tracking-wide">ACTUALIZAR CONTRASEÑA</button>
            </form>
        <?php endif; ?>

        <div class="text-center mt-8 text-sm text-text-muted font-medium">
            <a href="<?php echo BASE_URL; ?>/index.php" class="text-primary hover:text-primary-hover transition-colors hover:underline font-semibold bg-transparent border-none cursor-pointer">Volver al inicio de sesión</a>
        </div>
    </div>
</body>

</html>
