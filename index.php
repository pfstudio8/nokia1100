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

    <script src="<?php echo BASE_URL; ?>/assets/js/tailwind_config.js"></script>
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
                
                <?php if (isset($_GET['error']) && $isRegister): ?>
                    <div class="bg-red-500/10 border border-red-500/20 text-red-500 text-xs p-3 rounded-xl mb-3 font-medium flex gap-2 items-center">
                        <span class="material-symbols-outlined text-base">error</span>
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <form action="<?php echo BASE_URL; ?>/modules/auth/process_registration.php" method="POST" novalidate>
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

                    <div class="grid grid-cols-2 gap-x-4">
                        <div class="input-box">
                            <input type="password" name="password" placeholder=" " required minlength="8">
                            <label>Contraseña</label>
                            <i class='bx bxs-lock-alt'></i>
                        </div>
                        <div class="input-box">
                            <input type="password" name="password_confirm" placeholder=" " required minlength="8">
                            <label>Confirmar Clave</label>
                            <i class='bx bxs-lock-alt'></i>
                        </div>
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
                    <a href="javascript:void(0)" onclick="showGuestForm(true)" class="text-text-muted hover:text-primary hover:underline font-medium flex items-center gap-1.5 transition-colors text-xs">
                        <span class="material-symbols-outlined text-[16px]">person</span>
                        Ingresar como Invitado
                    </a>
                </div>
            </div>

            <!-- Formulario de Ingreso de Invitado (ahora en el panel de Registro) -->
            <div id="guest-form-wrapper" class="w-full hidden flex flex-col justify-center">
                <h2>Invitado</h2>
                <p class="text-text-muted text-xs mb-6 leading-relaxed">Ingresá tu nombre para acceder de forma limitada y consultar el stock del sistema.</p>
                
                <form action="<?php echo BASE_URL; ?>/modules/auth/guest_login.php" method="POST" novalidate>
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
                
                <?php if (isset($_GET['error']) && !$isRegister): ?>
                    <div class="bg-red-500/10 border border-red-500/20 text-red-500 text-xs p-3 rounded-xl mb-3 font-medium flex gap-2 items-center">
                        <span class="material-symbols-outlined text-base">error</span>
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="bg-green-500/10 border border-green-500/20 text-green-600 text-xs p-3 rounded-xl mb-3 font-medium flex gap-2 items-center">
                        <span class="material-symbols-outlined text-base">check_circle</span>
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <form action="<?php echo BASE_URL; ?>/modules/auth/auth.php" method="POST" novalidate>
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

                    <div class="forgot-link">
                        <a href="forgot_password.php">¿Olvidaste la clave?</a>
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
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js?v=<?php echo time(); ?>"></script>

    <!-- Script de Gestión de Acceso -->
    <script src="<?php echo BASE_URL; ?>/assets/js/login.js?v=<?php echo time(); ?>"></script>
</body>

</html>