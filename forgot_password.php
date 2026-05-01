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
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        background: "#0A0A0B",
                        surface: "#111113",
                        primary: "#21b8bd",
                        border: "#27272A",
                        "text-main": "#FAFAFA",
                        "text-muted": "#A1A1AA",
                    },
                    fontFamily: {
                        sans: ["Inter", "sans-serif"],
                        display: ["Outfit", "sans-serif"],
                    },
                },
            },
        }
    </script>
    <style>
        body {
            background-color: #0A0A0B;
            color: #FAFAFA;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow-x: hidden;
            padding: 2rem 1rem;
        }

        .auth-card {
            background: rgba(17, 17, 19, 0.7);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid #27272A;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 3rem;
            width: 100%;
            max-width: 440px;
        }

        .auth-input {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 2.8rem;
            background: #0A0A0B;
            border: 1px solid #27272A;
            border-radius: 12px;
            color: #FAFAFA;
            font-size: 0.95rem;
            transition: all 0.2s;
            font-weight: 500;
        }

        .auth-input:focus {
            outline: none;
            border-color: #21b8bd;
            background: #111113;
            box-shadow: 0 0 0 3px rgba(33, 184, 189, 0.15);
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #A1A1AA;
            font-size: 1.2rem;
            pointer-events: none;
            transition: color 0.2s;
        }

        .auth-input:focus~.input-icon {
            color: #21b8bd;
        }

        .auth-btn {
            width: 100%;
            padding: 0.85rem;
            background: #21b8bd;
            color: #0A0A0B;
            font-weight: 600;
            font-family: 'Outfit', sans-serif;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            font-size: 1rem;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(33, 184, 189, 0.3);
        }

        .auth-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -10px rgba(33, 184, 189, 0.5);
            background: #1a9498;
        }
    </style>
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

        <form action="<?php echo BASE_URL; ?>/modules/auth/process_forgot.php" method="POST" class="space-y-5">
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