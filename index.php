<?php
// index.php - Pantalla de Acceso Premium Nokia 1100 System
session_start();
require_once __DIR__ . '/config/db.php';

// Si ya tiene sesión activa, redirigir según su rol
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
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Nokia1100 </title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts & Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    
    <!-- Estilos Personalizados del Sistema -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">

    <script>
        tailwind.config = {
            darkMode: "class",
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
            background-color: #070708;
            color: #FAFAFA;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
            padding: 1.5rem;
        }

        /* Contenedor principal estilo Glassmorphism Double Slider */
        .double-slider-container {
            background: rgba(17, 17, 19, 0.65);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 28px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6), 0 0 40px rgba(33, 184, 189, 0.03);
            position: relative;
            overflow: hidden;
            width: 850px;
            max-width: 100%;
            min-height: 640px;
            transition: all 0.6s ease-in-out;
            z-index: 10;
        }

        .form-container {
            position: absolute;
            top: 0;
            height: 100%;
            transition: all 0.6s ease-in-out;
            background: transparent;
        }

        .sign-in-container {
            left: 0;
            width: 50%;
            z-index: 2;
            padding: 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .double-slider-container.right-panel-active .sign-in-container {
            transform: translateX(100%);
            opacity: 0;
            z-index: 1;
            pointer-events: none;
        }

        .sign-up-container {
            left: 0;
            width: 50%;
            opacity: 0;
            z-index: 1;
            padding: 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .double-slider-container.right-panel-active .sign-up-container {
            transform: translateX(100%);
            opacity: 1;
            z-index: 5;
            animation: show 0.6s;
        }

        @keyframes show {
            0%, 49.99% {
                opacity: 0;
                z-index: 1;
            }
            50%, 100% {
                opacity: 1;
                z-index: 5;
            }
        }

        .overlay-container {
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            overflow: hidden;
            transition: transform 0.6s ease-in-out;
            z-index: 100;
        }

        .double-slider-container.right-panel-active .overlay-container {
            transform: translateX(-100%);
        }

        .overlay {
            background: linear-gradient(160deg, #0d1517 0%, #0f1214 30%, #111113 50%, #130f16 70%, #0e0a10 100%);
            background-repeat: no-repeat;
            background-size: cover;
            color: #FFFFFF;
            position: relative;
            left: -100%;
            height: 100%;
            width: 200%;
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
            overflow: hidden;
        }

        /* Subtle animated mesh glows inside overlay */
        .overlay::before {
            content: '';
            position: absolute;
            top: -30%;
            left: -20%;
            width: 70%;
            height: 70%;
            background: radial-gradient(circle, rgba(33, 184, 189, 0.15) 0%, transparent 70%);
            filter: blur(60px);
            animation: overlayGlow1 12s infinite alternate ease-in-out;
            pointer-events: none;
        }

        .overlay::after {
            content: '';
            position: absolute;
            bottom: -25%;
            right: -15%;
            width: 65%;
            height: 65%;
            background: radial-gradient(circle, rgba(224, 79, 238, 0.12) 0%, transparent 70%);
            filter: blur(70px);
            animation: overlayGlow2 15s infinite alternate-reverse ease-in-out;
            pointer-events: none;
        }

        @keyframes overlayGlow1 {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(15%, 20%) scale(1.2); }
            100% { transform: translate(-5%, 10%) scale(0.95); }
        }

        @keyframes overlayGlow2 {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(-10%, -15%) scale(1.15); }
            100% { transform: translate(8%, -5%) scale(0.9); }
        }

        /* Subtle grid pattern on overlay */
        .overlay-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            opacity: 0.5;
        }

        /* Accent border glow on the overlay container edge */
        .overlay-container::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 2px;
            height: 100%;
            background: linear-gradient(to bottom, rgba(33, 184, 189, 0.4) 0%, rgba(224, 79, 238, 0.3) 50%, transparent 100%);
            z-index: 101;
            pointer-events: none;
            transition: opacity 0.6s ease-in-out;
        }

        .double-slider-container.right-panel-active .overlay-container::after {
            left: auto;
            right: 0;
        }

        .double-slider-container.right-panel-active .overlay {
            transform: translateX(50%);
        }

        .overlay-panel {
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 2.5rem;
            text-align: center;
            top: 0;
            height: 100%;
            width: 50%;
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
        }

        .overlay-left {
            transform: translateX(-20%);
        }

        .double-slider-container.right-panel-active .overlay-left {
            transform: translateX(0);
        }

        .overlay-right {
            right: 0;
            transform: translateX(0);
        }

        .double-slider-container.right-panel-active .overlay-right {
            transform: translateX(20%);
        }

        /* Estilo para los botones fantasmas */
        .auth-btn-ghost {
            background-color: transparent !important;
            border: 1.5px solid rgba(33, 184, 189, 0.5) !important;
            color: #FFFFFF !important;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            padding: 10px 36px;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
            margin-top: 1.25rem;
            position: relative;
            z-index: 10;
        }

        .auth-btn-ghost:hover {
            background-color: rgba(33, 184, 189, 0.15) !important;
            border-color: rgba(33, 184, 189, 0.8) !important;
            box-shadow: 0 0 20px rgba(33, 184, 189, 0.15);
            transform: translateY(-1px);
        }

        .auth-btn-ghost:active {
            transform: scale(0.95);
        }

        @media (max-width: 767px) {
            .double-slider-container {
                width: 100%;
                max-width: 480px;
                min-height: 580px;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            .overlay-container {
                display: none;
            }
            .form-container {
                position: relative;
                width: 100% !important;
                height: auto;
                transform: none !important;
                opacity: 1 !important;
                display: none;
            }
            .double-slider-container:not(.right-panel-active) .sign-in-container {
                display: flex;
            }
            .double-slider-container.right-panel-active .sign-up-container {
                display: flex;
            }
            .mobile-toggle {
                display: block !important;
            }
        }
        
        .mobile-toggle {
            display: none;
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .mobile-toggle button {
            background: transparent;
            border: none;
            color: var(--primary-color);
            font-weight: 600;
            cursor: pointer;
            text-decoration: underline;
        }

    </style>
</head>

<body class="font-sans antialiased text-text-main selection:bg-primary/20 selection:text-primary">

    <!-- Fondo de Mallas Animadas de Neón -->
    <div class="ambient-glow-container">
        <div class="ambient-glow-circle-1"></div>
        <div class="ambient-glow-circle-2"></div>
    </div>

    <?php
    $isRegister = isset($_GET['action']) && $_GET['action'] === 'register';
    $wrapperClass = $isRegister ? 'right-panel-active' : '';
    ?>
    <div class="double-slider-container <?php echo $wrapperClass; ?>" id="authContainer">
        
        <!-- PANEL 1: REGISTRO -->
        <div class="form-container sign-up-container">
            <div class="flex flex-col justify-center h-full">
                <div class="text-center mb-4 md:hidden">
                    <h1 class="text-2xl font-bold font-display tracking-tight text-text-main">NOKIA<span class="text-primary">1100</span></h1>
                    <p class="text-[10px] text-text-muted mt-1 tracking-widest uppercase font-extrabold">Registro de Operador</p>
                </div>

                <div class="mb-4">
                    <h2 class="text-xl font-display font-medium text-text-main">Registrarse</h2>
                </div>

                <form action="<?php echo BASE_URL; ?>/modules/auth/process_registration.php" method="POST" class="space-y-3">
                    
                    <!-- Datos Personales -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="premium-form-group">
                            <input type="text" id="nombre" name="nombre" required placeholder=" " class="premium-form-input peer !p-3.5 !pl-10">
                            <span class="material-symbols-outlined premium-form-icon text-[18px]">person</span>
                            <label for="nombre" class="premium-form-label !left-10">Nombre</label>
                        </div>
                        <div class="premium-form-group">
                            <input type="text" id="apellido" name="apellido" required placeholder=" " class="premium-form-input peer !p-3.5 !pl-10">
                            <span class="material-symbols-outlined premium-form-icon text-[18px]">person</span>
                            <label for="apellido" class="premium-form-label !left-10">Apellido</label>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="premium-form-group">
                            <input type="text" id="dni" name="dni" required placeholder=" " class="premium-form-input peer !p-3.5 !pl-10" pattern="[0-9]{7,10}" title="El DNI debe tener entre 7 y 10 dígitos">
                            <span class="material-symbols-outlined premium-form-icon text-[18px]">badge</span>
                            <label for="dni" class="premium-form-label !left-10">DNI</label>
                        </div>
                        <div class="premium-form-group">
                            <input type="email" id="email" name="email" required placeholder=" " class="premium-form-input peer !p-3.5 !pl-10">
                            <span class="material-symbols-outlined premium-form-icon text-[18px]">mail</span>
                            <label for="email" class="premium-form-label !left-10">Email</label>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="premium-form-group">
                            <input type="text" id="telefono" name="telefono" placeholder=" " class="premium-form-input peer !p-3.5 !pl-10">
                            <span class="material-symbols-outlined premium-form-icon text-[18px]">call</span>
                            <label for="telefono" class="premium-form-label !left-10">Teléfono</label>
                        </div>
                        <div class="premium-form-group">
                            <input type="text" id="direccion" name="direccion" placeholder=" " class="premium-form-input peer !p-3.5 !pl-10">
                            <span class="material-symbols-outlined premium-form-icon text-[18px]">home</span>
                            <label for="direccion" class="premium-form-label !left-10">Dirección</label>
                        </div>
                    </div>

                    <div class="h-[1px] bg-border/50 my-1"></div>

                    <!-- Credenciales -->
                    <div class="premium-form-group">
                        <input type="text" id="reg-username" name="username" required placeholder=" " class="premium-form-input peer">
                        <span class="material-symbols-outlined premium-form-icon">account_circle</span>
                        <label for="reg-username" class="premium-form-label">Nombre de usuario deseado</label>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="premium-form-group">
                            <input type="password" id="reg-password" name="password" required minlength="6" placeholder=" " class="premium-form-input peer !p-3.5 !pl-10">
                            <span class="material-symbols-outlined premium-form-icon text-[18px]">lock</span>
                            <label for="reg-password" class="premium-form-label !left-10">Contraseña</label>
                        </div>
                        <div class="premium-form-group">
                            <input type="password" id="reg-password-confirm" name="password_confirm" required minlength="6" placeholder=" " class="premium-form-input peer !p-3.5 !pl-10">
                            <span class="material-symbols-outlined premium-form-icon text-[18px]">key</span>
                            <label for="reg-password-confirm" class="premium-form-label !left-10">Confirmar</label>
                        </div>
                    </div>

                    <button type="submit" class="auth-btn mt-3 tracking-wide uppercase flex items-center justify-center gap-2" style="background: var(--secondary-color); box-shadow: 0 4px 6px -1px rgba(224, 79, 238, 0.3);">
                        <span>Registrar Cuenta</span>
                        <span class="material-symbols-outlined text-[18px]">how_to_reg</span>
                    </button>
                </form>

                <div class="mobile-toggle">
                    ¿Ya posees una cuenta de operador? 
                    <button type="button" onclick="toggleAuthMode('login')">Iniciar Sesión</button>
                </div>
            </div>
        </div>

        <!-- PANEL 2: INICIAR SESIÓN -->
        <div class="form-container sign-in-container">
            <div class="flex flex-col justify-center h-full">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-extrabold font-display tracking-tight text-text-main">
                        NOKIA<span class="text-primary" id="brandColor">1100</span>
                    </h1>
                    <p class="text-xs text-text-muted mt-1 uppercase tracking-widest font-semibold text-[10px]">Iniciar Sesión</p>
                </div>

                <div class="mb-6">
                    <h2 class="text-xl font-display font-medium text-text-main">Identificación de Usuario</h2>
                </div>

                <form action="<?php echo BASE_URL; ?>/modules/auth/auth.php" method="POST" class="space-y-4">
                    <div class="premium-form-group">
                        <input type="text" id="login-username" name="username" required placeholder=" " class="premium-form-input peer">
                        <span class="material-symbols-outlined premium-form-icon">account_circle</span>
                        <label for="login-username" class="premium-form-label">Nombre de usuario</label>
                    </div>

                    <div class="premium-form-group">
                        <div class="flex justify-between items-center mb-1 absolute right-2 -top-5 z-20">
                            <a href="forgot_password.php" class="text-[11px] font-semibold text-primary hover:underline transition-all">¿Olvidaste la clave?</a>
                        </div>
                        <input type="password" id="login-password" name="password" required placeholder=" " class="premium-form-input peer">
                        <span class="material-symbols-outlined premium-form-icon">lock</span>
                        <label for="login-password" class="premium-form-label">Contraseña</label>
                    </div>

                    <button type="submit" class="auth-btn mt-6 tracking-wide uppercase flex items-center justify-center gap-2">
                        <span>Ingresar al Sistema</span>
                        <span class="material-symbols-outlined text-[18px]">login</span>
                    </button>
                </form>

                <div class="mobile-toggle">
                    ¿Aún no tienes una cuenta corporativa? 
                    <button type="button" onclick="toggleAuthMode('register')">Registrarme</button>
                </div>
            </div>
        </div>

        <!-- CAPA DE COBERTURA SLIDER (OVERLAY) -->
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1 class="text-2xl font-bold font-display text-text-main">¡Bienvenido de Nuevo!</h1>
                    <p class="text-sm text-text-main/80 mt-3 max-w-xs leading-relaxed" style="font-weight: 300;">Para mantenerte conectado con nosotros por favor inicia sesión con tu cuenta corporativa</p>
                    <button class="auth-btn-ghost" id="signIn" onclick="toggleAuthMode('login')">Iniciar Sesión</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1 class="text-2xl font-bold font-display text-text-main">¿Eres nuevo?</h1>
                    <p class="text-sm text-text-main/80 mt-3 max-w-xs leading-relaxed" style="font-weight: 300;">Ingresa tus datos personales y comienza tu jornada con el sistema de control Nokia 1100</p>
                    <button class="auth-btn-ghost" id="signUp" onclick="toggleAuthMode('register')">Registrarme</button>
                </div>
            </div>
        </div>

    </div>

    <!-- Cargar React y Sileo Toast Bundle -->
    <script src="<?php echo BASE_URL; ?>/assets/js/sileo-toaster.bundle.js?v=<?php echo time(); ?>"></script>

    <!-- Cargar Scripts del Sistema para animaciones Toasts y decodificación de URL -->
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js?v=<?php echo time(); ?>"></script>

    <!-- Script de Gestión de Acceso -->
    <script>
        // Conmutador del modo Auth (Login / Registro)
        function toggleAuthMode(mode) {
            const container = document.getElementById('authContainer');
            const brandColor = document.getElementById('brandColor');
            
            if (mode === 'register') {
                container.classList.add('right-panel-active');
                if (brandColor) brandColor.style.color = '#E04FEE'; // Magenta en Registro
                
                setTimeout(() => {
                    const params = new URLSearchParams(window.location.search);
                    params.set('action', 'register');
                    window.history.pushState({}, '', '?' + params.toString());
                }, 300);
            } else {
                container.classList.remove('right-panel-active');
                if (brandColor) brandColor.style.color = '#21b8bd'; // Cian en Login
                
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