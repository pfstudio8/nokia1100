<?php
session_start();
require_once __DIR__ . '/config/db.php';
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
    <script src="<?php echo BASE_URL; ?>/assets/js/tailwind_config.js"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/forgot_password.css?v=<?php echo time(); ?>">
</head>

<body class="font-sans antialiased">
    <div class="auth-card">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold font-display tracking-tight text-text-main mb-2">Recuperar Contraseña</h1>
            <p class="text-sm text-text-muted">Introduce tu email para recibir un enlace de recuperación.</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div
                class="bg-red-500/10 border border-red-500/20 text-red-500 text-sm p-4 rounded-xl mb-6 font-medium flex gap-3 items-center">
                <span class="material-symbols-outlined text-lg">error</span>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div
                class="bg-green-500/10 border border-green-500/20 text-green-500 text-sm p-4 rounded-xl mb-6 font-medium flex gap-3 items-center">
                <span class="material-symbols-outlined text-lg">check_circle</span>
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>

            <?php if (isset($_SESSION['mail_debug_notice'])): ?>
                <div
                    class="bg-yellow-500/10 border border-yellow-500/20 text-yellow-300 text-xs p-4 rounded-xl mb-6 font-medium flex gap-3 items-center">
                    <span class="material-symbols-outlined text-lg">warning</span>
                    <?php echo htmlspecialchars($_SESSION['mail_debug_notice']); ?>
                </div>
                <?php unset($_SESSION['mail_debug_notice']); ?>
            <?php endif; ?>

        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>/modules/auth/process_forgot.php" method="POST" class="space-y-5" novalidate>
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
            <a href="index.php"
                class="text-primary hover:text-primary-hover transition-colors hover:underline font-semibold bg-transparent border-none cursor-pointer">Volver
                al inicio de sesión</a>
        </div>
    </div>
</body>

</html>