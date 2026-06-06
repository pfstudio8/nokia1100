<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

$token = $_GET['token'] ?? '';
$isValidToken = false;
$id_usuario = 0;

if (!empty($token)) {
    // Validar token en la base de datos
    $stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE token_verificacion = ? AND token_expira > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $isValidToken = true;
        $row = $result->fetch_assoc();
        $id_usuario = $row['id_usuario'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Establecer Nueva Contraseña - Nokia 1100 System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
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

            <form action="<?php echo BASE_URL; ?>/modules/auth/process_reset.php" method="POST" class="space-y-5">
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
