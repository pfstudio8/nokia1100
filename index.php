<?php
session_start();
require_once __DIR__ . '/config/db.php';
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: " . BASE_URL . "/modules/admin/dashboard.php");
    } else {
        header("Location: " . BASE_URL . "/modules/employee/dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - Nokia 1100 System</title>
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
                        "surface-hover": "#18181B",
                        primary: "#21b8bd",
                        secondary: "#E04FEE",
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
            position: relative;
            padding: 2rem 1rem;
        }

        .auth-wrapper {
            position: relative;
            width: 100%;
            max-width: 440px;
            transition: max-width 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            z-index: 10;
            margin: auto;
        }

        .auth-wrapper.is-register {
            max-width: 680px;
        }

        .auth-card {
            background: rgba(17, 17, 19, 0.7);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid #27272A;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            overflow: hidden;
            position: relative;
            width: 100%;
        }

        .auth-slider {
            display: flex;
            width: 200%;
            transition: transform 0.7s cubic-bezier(0.645, 0.045, 0.355, 1);
        }

        .auth-panel {
            width: 50%;
            padding: 3rem;
            flex-shrink: 0;
            transition: opacity 0.5s ease;
        }

        .auth-wrapper.is-register .auth-slider {
            transform: translateX(-50%);
        }

        .auth-wrapper.is-register .login-panel {
            opacity: 0;
            pointer-events: none;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0s 0.3s;
        }

        .auth-wrapper:not(.is-register) .register-panel {
            opacity: 0;
            pointer-events: none;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0s 0.3s;
        }

        .auth-wrapper:not(.is-register) .login-panel,
        .auth-wrapper.is-register .register-panel {
            opacity: 1;
            visibility: visible;
            transition: opacity 0.5s ease 0.2s, visibility 0s 0s;
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

        .auth-input.no-icon {
            padding-left: 1rem;
        }

        .auth-input::placeholder {
            color: #64748B;
            font-weight: 400;
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

<body class="font-sans antialiased text-text-main selection:bg-secondary/30 selection:text-text-main">



    <!-- Determine initial state based on URL parameters -->
    <?php
    $isRegister = isset($_GET['action']) && $_GET['action'] === 'register';
    $wrapperClass = $isRegister ? 'is-register' : '';
    ?>

    <div class="auth-wrapper <?php echo $wrapperClass; ?>" id="authWrapper">
        <div class="auth-card">
            <div class="auth-slider">

                <!-- LOGIN PANEL -->
                <div class="auth-panel login-panel flex flex-col justify-center">
                    <div class="text-center mb-8">
                        <img src="assets/img/hero_phone.png" alt="Nokia Phone"
                            class="w-24 h-24 mx-auto mb-4 object-contain filter drop-shadow-[0_0_15px_rgba(33,184,189,0.3)] hover:scale-105 transition-transform duration-500">
                        <h1 class="text-3xl font-bold font-display tracking-tight text-text-main">NOKIA<span
                                class="text-primary" style="color: #21b8bd;">1100</span></h1>
                    </div>

                    <?php if (isset($_GET['error']) && !$isRegister): ?>
                        <div
                            class="bg-red-500/10 border border-red-500/20 text-red-500 text-sm p-4 rounded-xl mb-6 font-medium flex gap-3 items-center">
                            <span class="material-symbols-outlined text-lg">error</span>
                            <?php echo htmlspecialchars($_GET['error']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['success']) && !$isRegister): ?>
                        <div
                            class="bg-green-500/10 border border-green-500/20 text-green-500 text-sm p-4 rounded-xl mb-6 font-medium flex gap-3 items-center">
                            <span class="material-symbols-outlined text-lg">check_circle</span>
                            <?php echo htmlspecialchars($_GET['success']); ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo BASE_URL; ?>/modules/auth/auth.php" method="POST" class="space-y-5">
                        <div>
                            <label for="login-username"
                                class="block text-xs font-bold text-text-muted mb-2 uppercase tracking-wider">Identificación</label>
                            <div class="relative flex flex-col-reverse">
                                <input type="text" id="login-username" name="username" required
                                    placeholder="Ingresar usuario" class="auth-input peer">
                                <span
                                    class="material-symbols-outlined input-icon transition-colors peer-focus:text-text-main">account_circle</span>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label for="login-password"
                                    class="block text-xs font-bold text-text-muted uppercase tracking-wider">Credencial</label>
                                <a href="#"
                                    onclick="alert('Funcionalidad de recuperación de contraseña disponible próximamente. Por favor contacte al administrador.'); return false;"
                                    class="text-xs font-bold text-primary hover:text-primary-hover transition-colors">¿Olvidaste
                                    tu contraseña?</a>
                            </div>
                            <div class="relative flex flex-col-reverse">
                                <input type="password" id="login-password" name="password" required
                                    placeholder="••••••••" class="auth-input peer">
                                <span
                                    class="material-symbols-outlined input-icon transition-colors peer-focus:text-text-main">lock</span>
                            </div>
                        </div>

                        <button type="submit" class="auth-btn mt-6 tracking-wide">INICIAR SESIÓN</button>
                    </form>

                    <div class="text-center mt-8 text-sm text-text-muted font-medium">
                        ¿No tienes permisos?
                        <button type="button" onclick="toggleAuthMode('register')"
                            class="text-primary hover:text-primary-hover transition-colors hover:underline font-semibold bg-transparent border-none cursor-pointer">Solicitar
                            Acceso</button>
                    </div>
                </div>

                <!-- REGISTER PANEL -->
                <div class="auth-panel register-panel flex flex-col justify-center">
                    <div class="text-center mb-6">
                        <img src="assets/img/hero_phone.png" alt="Nokia Phone"
                            class="w-16 h-16 mx-auto mb-3 object-contain filter drop-shadow-[0_0_15px_rgba(224,79,238,0.3)] hover:scale-105 transition-transform duration-500">
                        <h1 class="text-3xl font-bold font-display tracking-tight text-text-main">NOKIA<span
                                class="text-primary" style="color: #21b8bd;">1100</span></h1>
                        <p class="text-[10px] text-text-muted mt-2 tracking-widest uppercase font-extrabold">Registro
                            Empresarial</p>
                    </div>

                    <?php if (isset($_GET['error']) && $isRegister): ?>
                        <div
                            class="bg-red-500/10 border border-red-500/20 text-red-500 text-sm p-4 rounded-xl mb-4 font-medium flex gap-3 items-center">
                            <span class="material-symbols-outlined text-lg">error</span>
                            <?php echo htmlspecialchars($_GET['error']); ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo BASE_URL; ?>/modules/auth/process_registration.php" method="POST"
                        class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="nombre"
                                    class="block text-[10px] font-bold text-text-muted mb-1 uppercase tracking-wide">Nombre</label>
                                <input type="text" id="nombre" name="nombre" required class="auth-input no-icon !p-3">
                            </div>
                            <div>
                                <label for="apellido"
                                    class="block text-[10px] font-bold text-text-muted mb-1 uppercase tracking-wide">Apellido</label>
                                <input type="text" id="apellido" name="apellido" required
                                    class="auth-input no-icon !p-3">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="dni"
                                    class="block text-[10px] font-bold text-text-muted mb-1 uppercase tracking-wide">DNI</label>
                                <input type="text" id="dni" name="dni" required class="auth-input no-icon !p-3">
                            </div>
                            <div>
                                <label for="email"
                                    class="block text-[10px] font-bold text-text-muted mb-1 uppercase tracking-wide">Email</label>
                                <input type="email" id="email" name="email" required class="auth-input no-icon !p-3">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="telefono"
                                    class="block text-[10px] font-bold text-text-muted mb-1 uppercase tracking-wide">Teléfono</label>
                                <input type="text" id="telefono" name="telefono" class="auth-input no-icon !p-3">
                            </div>
                            <div>
                                <label for="direccion"
                                    class="block text-[10px] font-bold text-text-muted mb-1 uppercase tracking-wide">Dirección</label>
                                <input type="text" id="direccion" name="direccion" class="auth-input no-icon !p-3">
                            </div>
                        </div>

                        <div class="h-px bg-slate-200 my-4"></div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <label for="reg-username"
                                    class="block text-[10px] font-extrabold text-text-muted mb-1 uppercase tracking-wide">Usuario
                                    Master</label>
                                <input type="text" id="reg-username" name="username" required
                                    class="auth-input no-icon !p-3 bg-secondary/10 hover:border-text-muted focus:border-text-main">
                            </div>

                            <div>
                                <label for="reg-password"
                                    class="block text-[10px] font-bold text-text-muted mb-1 uppercase tracking-wide">Contraseña</label>
                                <input type="password" id="reg-password" name="password" required
                                    class="auth-input no-icon !p-3">
                            </div>

                            <div>
                                <label for="rol"
                                    class="block text-[10px] font-bold text-text-muted mb-1 uppercase tracking-wide">Nivel
                                    de Acceso</label>
                                <select id="rol" name="rol" required class="auth-input no-icon !p-3">
                                    <option value="empleado">Operario (Empleado)</option>
                                    <option value="admin">Administrador Global</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="auth-btn mt-4 tracking-wide">CREAR NUEVA CUENTA</button>
                    </form>

                    <div class="text-center mt-6 text-sm text-text-muted font-medium">
                        ¿Ya tienes credenciales?
                        <button type="button" onclick="toggleAuthMode('login')"
                            class="text-primary hover:text-primary-hover transition-colors hover:underline font-semibold bg-transparent border-none cursor-pointer">Acceso
                            Autorizado</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function toggleAuthMode(mode) {
            const wrapper = document.getElementById('authWrapper');
            if (mode === 'register') {
                wrapper.classList.add('is-register');
                // Agregar timeout sutil para cambiar la URL y que no rompa el estilo visual inmediato
                setTimeout(() => {
                    const params = new URLSearchParams(window.location.search);
                    params.set('action', 'register');
                    window.history.pushState({}, '', '?' + params.toString());
                }, 300);
            } else {
                wrapper.classList.remove('is-register');
                setTimeout(() => {
                    const params = new URLSearchParams(window.location.search);
                    params.delete('action');
                    params.delete('error');
                    params.delete('success');
                    let newUrl = window.location.pathname;
                    if (params.toString()) newUrl += '?' + params.toString();
                    window.history.pushState({}, '', newUrl);
                }, 300);
            }
        }
    </script>
</body>

</html>