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
    <title>Login - Sistema Nokia1100</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Boxicons CDN -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    <!-- Estilos Personalizados del Sistema -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/login.css?v=<?php echo time(); ?>">

    <script src="<?php echo BASE_URL; ?>/assets/js/tailwind_config.js?v=<?php echo time(); ?>"></script>
</head>

<body class="font-sans antialiased">

    <?php
    $isRegister = isset($_GET['action']) && $_GET['action'] === 'register';
    $wrapperClass = $isRegister ? 'right-panel-active' : '';
    ?>
    <div class="double-slider-container <?php echo $wrapperClass; ?>" id="authContainer">
        
        <!-- PANEL 1: REGISTRO -->
        <div class="form-container sign-up-container">
            <!-- Formulario de Registro Tradicional -->
            <div id="register-form-wrapper" class="w-full flex flex-col justify-center">
                <h2>Crear Cuenta</h2>
                


                <form action="<?php echo BASE_URL; ?>/modules/auth/index.php?action=register" method="POST" novalidate>
                    <div class="grid grid-cols-2 gap-x-4">
                        <div class="input-box">
                            <input type="text" name="nombre" placeholder=" " required>
                            <label>Nombre</label>
                            <i class='bx bxs-user'></i>
                        </div>
                        <div class="input-box">
                            <input type="text" name="apellido" placeholder=" " required>
                            <label>Apellido</label>
                            <i class='bx bxs-user'></i>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-x-4">
                        <div class="input-box">
                            <input type="text" name="dni" placeholder=" " required pattern="[0-9]{7,10}">
                            <label>DNI</label>
                            <i class='bx bxs-id-card'></i>
                        </div>
                        <div class="input-box">
                            <input type="email" name="email" placeholder=" " required>
                            <label>Email</label>
                            <i class='bx bxs-envelope'></i>
                        </div>
                    </div>

                    <div class="input-box">
                        <input type="text" name="username" placeholder=" " required>
                        <label>Usuario</label>
                        <i class='bx bxs-user-circle'></i>
                    </div>

                    <div class="grid grid-cols-2 gap-x-4" style="margin-bottom: 0.25rem;">
                        <div class="input-box" style="margin-bottom: 0;">
                            <input type="password" id="register-password" name="password" placeholder=" " required minlength="8">
                            <label>Contraseña</label>
                            <i class='bx bxs-lock-alt'></i>
                        </div>
                        <div class="input-box" style="margin-bottom: 0;">
                            <input type="password" name="password_confirm" placeholder=" " required minlength="8">
                            <label>Confirmar Clave</label>
                            <i class='bx bxs-lock-alt'></i>
                        </div>
                    </div>
                    <!-- Password Strength Meter -->
                    <div id="password-strength-container" class="w-full h-1 bg-border/30 mb-5 rounded-full overflow-hidden" style="display: none;">
                        <div id="password-strength-bar" class="h-full w-0 transition-all duration-300"></div>
                    </div>

                    <button type="submit" class="auth-btn-pill">
                        <span>Registrarse</span>
                        <i class='bx bxs-user-plus'></i>
                    </button>
                </form>

                <div class="mt-6 flex flex-col items-center gap-2 text-center text-xs">
                    <p class="text-text-muted">
                        ¿Ya tenés una cuenta? 
                        <a href="javascript:void(0)" onclick="toggleAuthMode('login')" class="text-primary hover:underline font-semibold ml-1">Ingresar</a>
                    </p>
                    <div class="w-full flex items-center justify-center gap-2 text-text-muted/30 my-1">
                        <span class="h-[1px] w-6 bg-border/40"></span>
                        <span>o bien</span>
                        <span class="h-[1px] w-6 bg-border/40"></span>
                    </div>
                    <a href="javascript:void(0)" onclick="showGuestForm(true)" class="w-full flex items-center justify-center gap-2 py-2.5 px-4 mt-1 rounded-xl border border-[rgba(255,255,255,0.08)] hover:border-[#0EA5A0]/50 text-text-muted hover:text-[#0EA5A0] transition-all duration-300 font-medium text-[13px] bg-[#1F2937]/50 hover:bg-[#0EA5A0]/5">
                        <span class="material-symbols-outlined text-[18px]">person</span>
                        Ingresar como Invitado
                    </a>
                </div>
            </div>

            <!-- Formulario de Ingreso de Invitado (ahora en el panel de Registro) -->
            <div id="guest-form-wrapper" class="w-full hidden flex flex-col justify-center">
                <h2>Invitado</h2>
                <p class="text-text-muted text-xs mb-6 leading-relaxed">Ingresá tu nombre para acceder de forma limitada y consultar el stock del sistema.</p>
                
                <form action="<?php echo BASE_URL; ?>/modules/auth/index.php?action=guestLogin" method="POST" novalidate>
                    <div class="input-box">
                        <input type="text" name="guest_name" placeholder=" " required minlength="2" maxlength="30">
                        <label>Nombre de Invitado</label>
                        <i class='bx bxs-user-badge'></i>
                    </div>

                    <button type="submit" class="auth-btn-pill w-full mt-2">
                        <span>Ingresar</span>
                        <i class='bx bx-right-arrow-alt'></i>
                    </button>
                </form>

                <div class="toggle-link mt-6 text-center">
                    <a href="javascript:void(0)" onclick="showGuestForm(false)" class="text-text-muted hover:text-text-main text-sm flex items-center justify-center gap-1.5 mx-auto transition-colors">
                        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                        Volver al Registro
                    </a>
                </div>
            </div>
        </div>

        <!-- PANEL 2: INICIAR SESIÓN -->
        <div class="form-container sign-in-container">
            <!-- Formulario de Login Tradicional -->
            <div id="login-form-wrapper" class="w-full flex flex-col justify-center">
                <h2>Iniciar Sesión</h2>
                


                <form action="<?php echo BASE_URL; ?>/modules/auth/index.php?action=login" method="POST" novalidate>
                    <div class="input-box">
                        <input type="text" name="username" placeholder=" " required>
                        <label>Usuario</label>
                        <i class='bx bxs-user'></i>
                    </div>

                    <div class="input-box">
                        <input type="password" name="password" id="login-password" placeholder=" " required>
                        <label>Contraseña</label>
                        <a href="javascript:void(0)" onclick="togglePasswordVisibility('login-password', this)" style="position: absolute !important; right: 5px !important; top: 50% !important; transform: translateY(-50%) !important; color: #A1A1AA; cursor: pointer;" class="hover:text-text-main transition-colors flex items-center justify-center p-0">
                            <span class="material-symbols-outlined text-[18px] select-none">visibility</span>
                        </a>
                    </div>

                    <div class="flex items-center justify-between mt-2">
                        <a href="auth/forgot_password.php">¿Olvidaste la clave?</a>
                    </div>

                    <button type="submit" class="auth-btn-pill">
                        <span>Ingresar</span>
                        <i class='bx bx-log-in'></i>
                    </button>
                </form>

                <div class="toggle-link text-center mt-6">
                    ¿No tenés una cuenta? <a href="javascript:void(0)" onclick="toggleAuthMode('register')" class="text-primary hover:underline font-semibold ml-1">Registrate</a>
                </div>
            </div>
        </div>

        <!-- CAPA DE COBERTURA SLIDER (OVERLAY) -->
        <div class="overlay-container">
            <div class="overlay">
                <!-- Mostrado cuando la cobertura está a la izquierda (Registrándose) -->
                <div class="overlay-panel overlay-left">
                    <h1>¡BIENVENIDO!</h1>
                    <p>Inicia sesión con tu cuenta personal para acceder a todas las funciones de administración y taller.</p>
                </div>
                <!-- Mostrado cuando la cobertura está a la derecha (Iniciando Sesión) -->
                <div class="overlay-panel overlay-right">
                    <h1>REGÍSTRATE</h1>
                    <p>Ingresa tus datos personales para unirte al equipo y comenzar a usar el sistema Nokia 1100.</p>
                </div>
            </div>
        </div>

    </div>

    <!-- Contenedor Unificado para Notificaciones Toasts Flotantes -->
    <div id="toast-container" class="fixed top-6 right-6 z-[9999] flex flex-col items-end pointer-events-none gap-2"></div>

    <!-- Cargar Scripts del Sistema para animaciones Toasts y decodificación de URL -->

    <script src="<?php echo BASE_URL; ?>/assets/js/framer-toaster.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js?v=<?php echo time(); ?>"></script>

    <!-- Script de Gestión de Acceso -->
    <script src="<?php echo BASE_URL; ?>/assets/js/login.js?v=<?php echo time(); ?>"></script>
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
            
            if (msgShowed) {
                // Si la URL contenía 'action=register', mantenerlo
                if (urlParams.has("action")) {
                    const action = urlParams.get("action");
                    window.history.replaceState({}, document.title, window.location.pathname + "?action=" + action);
                } else {
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }
        });
    </script>
</body>

</html>