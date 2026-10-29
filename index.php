<?php
// index.php - Pantalla de Acceso Premium Nokia 1100 System
session_start();
require_once __DIR__ . '/config/db.php';

if (empty($_SESSION['session_token'])) {
    $_SESSION['session_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['session_token'];

// Si ya tiene sesión activa, redirigir según su rol
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: " . BASE_URL . "/modules/admin/index.php");
    } else {
        header("Location: " . BASE_URL . "/modules/employee/index.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema - Sistema Nokia 1100</title>
    
    <?php // Tailwind CSS ?>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <?php // Google Fonts ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&family=IBM+Plex+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    
    <?php // Material Symbols ?>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    <?php // Estilos Personalizados del Sistema ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/login.css?v=<?php echo time(); ?>">

    <script src="<?php echo BASE_URL; ?>/assets/js/tailwind_config.js?v=<?php echo time(); ?>"></script>
</head>

<body class="font-sans antialiased text-[#15171a] bg-[#eeebe3]">

    <?php
    $isRegister = isset($_GET['action']) && $_GET['action'] === 'register';
    $wrapperClass = $isRegister ? 'right-panel-active' : '';
    ?>
    <div class="double-slider-container <?php echo $wrapperClass; ?>" id="authContainer">
        
        <?php // PANEL 1: REGISTRO (SIGN UP) ?>
        <div class="form-container sign-up-container">
            <div id="register-form-wrapper" class="w-full flex flex-col justify-center">
                <span class="form-header-badge">CUENTA NUEVA</span>
                <h2 class="form-title">Crear cuenta</h2>
                <p class="form-subtitle">Ingresá tus datos para unirte al equipo.</p>

                <form action="<?php echo BASE_URL; ?>/modules/auth/index.php?action=register" method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div class="field-group">
                            <label class="field-label">NOMBRE</label>
                            <div class="field-input-box">
                                <input type="text" name="nombre" placeholder=" " required>
                                <span class="material-symbols-outlined field-icon">person</span>
                            </div>
                        </div>
                        <div class="field-group">
                            <label class="field-label">APELLIDO</label>
                            <div class="field-input-box">
                                <input type="text" name="apellido" placeholder=" " required>
                                <span class="material-symbols-outlined field-icon">person</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="field-group">
                            <label class="field-label">DNI</label>
                            <div class="field-input-box">
                                <input type="text" name="dni" placeholder=" " required pattern="[0-9]{7,10}">
                                <span class="material-symbols-outlined field-icon">badge</span>
                            </div>
                        </div>
                        <div class="field-group">
                            <label class="field-label">EMAIL</label>
                            <div class="field-input-box">
                                <input type="email" name="email" placeholder=" " required>
                                <span class="material-symbols-outlined field-icon">mail</span>
                            </div>
                        </div>
                    </div>

                    <div class="field-group">
                        <label class="field-label">USUARIO</label>
                        <div class="field-input-box">
                            <input type="text" name="username" placeholder=" " required>
                            <span class="material-symbols-outlined field-icon">person_add</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="field-group mb-1">
                            <label class="field-label">CONTRASEÑA</label>
                            <div class="field-input-box">
                                <input type="password" id="register-password" name="password" placeholder=" " required minlength="8">
                                <button type="button" class="field-action-btn" onclick="togglePasswordVisibility('register-password', this)">
                                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                                </button>
                            </div>
                        </div>
                        <div class="field-group mb-1">
                            <label class="field-label">REPETIR CONTRASEÑA</label>
                            <div class="field-input-box">
                                <input type="password" id="register-confirm" name="password_confirm" placeholder=" " required minlength="8">
                                <button type="button" class="field-action-btn" onclick="togglePasswordVisibility('register-confirm', this)">
                                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <?php // Password Strength Meter ?>
                    <div id="password-strength-container" class="w-full h-1 bg-[#d3cebf] mb-1 rounded-full overflow-hidden" style="display: none;">
                        <div id="password-strength-bar" class="h-full w-0 transition-all duration-300"></div>
                    </div>
                    <span class="font-mono text-[9px] text-[#5c6165] block mb-2">Mínimo 8 caracteres (mayúscula, minúscula y símbolo).</span>

                    <button type="submit" class="btn-primary-auth">
                        <span>Crear cuenta</span>
                        <span class="material-symbols-outlined text-[18px]">person_add</span>
                    </button>
                </form>

                <div class="text-center mt-3 text-xs text-[#5c6165]">
                    ¿Ya tenés cuenta? <a href="javascript:void(0)" onclick="toggleAuthMode('login')" class="text-[#15171a] font-bold underline underline-offset-2 hover:text-black">Iniciá sesión</a>
                </div>

                <?php // Acceso Rápido como Invitado ?>
                <div class="guest-access-divider">
                    <span>o acceso como invitado</span>
                </div>

                <form action="<?php echo BASE_URL; ?>/modules/auth/index.php?action=guestLogin" method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="field-group mb-2">
                        <label class="field-label">NOMBRE DE INVITADO</label>
                        <div class="field-input-box">
                            <input type="text" name="guest_name" placeholder="Ej: Invitado Técnico" required minlength="2" maxlength="30">
                            <span class="material-symbols-outlined field-icon">badge</span>
                        </div>
                    </div>

                    <button type="submit" class="btn-guest-auth">
                        <span class="material-symbols-outlined text-[16px] text-[#464b4f]">meeting_room</span>
                        <span>Ingresar como Invitado</span>
                    </button>
                </form>
            </div>
        </div>

        <?php // PANEL 2: INICIAR SESIÓN (SIGN IN) ?>
        <div class="form-container sign-in-container">
            <div id="login-form-wrapper" class="w-full flex flex-col justify-center">
                <span class="form-header-badge">ACCESO AL SISTEMA</span>
                <h2 class="form-title">Iniciar sesión</h2>
                <p class="form-subtitle">Ingresá con tu usuario para continuar.</p>

                <form action="<?php echo BASE_URL; ?>/modules/auth/index.php?action=login" method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    
                    <div class="field-group">
                        <label class="field-label">USUARIO</label>
                        <div class="field-input-box">
                            <input type="text" name="username" placeholder=" " required>
                            <span class="material-symbols-outlined field-icon">person</span>
                        </div>
                    </div>

                    <div class="field-group mb-2">
                        <label class="field-label">CONTRASEÑA</label>
                        <div class="field-input-box">
                            <input type="password" name="password" id="login-password" placeholder=" " required>
                            <button type="button" class="field-action-btn" onclick="togglePasswordVisibility('login-password', this)">
                                <span class="material-symbols-outlined text-[18px]">visibility</span>
                            </button>
                        </div>
                    </div>

                    <div class="text-left mb-3">
                        <a href="auth/forgot_password.php" class="text-xs text-[#5c6165] hover:text-[#15171a] font-medium underline underline-offset-2 transition-colors">¿Olvidaste la clave?</a>
                    </div>

                    <button type="submit" class="btn-primary-auth">
                        <span>Ingresar</span>
                        <span class="font-mono text-base leading-none">→]</span>
                    </button>
                </form>

                <div class="text-center mt-3 text-xs text-[#5c6165]">
                    ¿No tenés una cuenta? <a href="javascript:void(0)" onclick="toggleAuthMode('register')" class="text-[#15171a] font-bold underline underline-offset-2 hover:text-black">Registrate</a>
                </div>
            </div>
        </div>

        <?php // CAPA DE COBERTURA SLIDER (OVERLAY DARK SIDE) ?>
        <div class="overlay-container">
            <div class="overlay">
                
                <?php // PANEL IZQUIERDO DEL OVERLAY (Visible cuando está en Registro) ?>
                <div class="overlay-panel overlay-left">
                    <div class="overlay-brand">
                        <span class="brand-text">SISTEMA NOKIA</span>
                        <span class="brand-chip">1100</span>
                    </div>

                    <div class="overlay-content">
                        <h1 class="overlay-title">¿Ya sos parte del equipo?</h1>
                        <p class="overlay-desc">Ingresá con tu usuario para seguir donde lo dejaste y operar en la plataforma.</p>

                        <div class="trust-features">
                            <div class="trust-item">
                                <span class="material-symbols-outlined trust-icon">shield_person</span>
                                <div>
                                    <h4 class="trust-title">Seguridad y roles definidos</h4>
                                    <p class="trust-text">Acceso protegido con permisos para administradores, empleados y técnicos.</p>
                                </div>
                            </div>
                            <div class="trust-item">
                                <span class="material-symbols-outlined trust-icon">analytics</span>
                                <div>
                                    <h4 class="trust-title">Métricas claras al instante</h4>
                                    <p class="trust-text">Dashboard gerencial con KPIs de ventas, facturación y rotación diaria.</p>
                                </div>
                            </div>
                            <div class="trust-item">
                                <span class="material-symbols-outlined trust-icon">history_edu</span>
                                <div>
                                    <h4 class="trust-title">Auditoría automática de acciones</h4>
                                    <p class="trust-text">Historial protegido para que tu negocio funcione con total transparencia.</p>
                                </div>
                            </div>
                        </div>

                        <button class="ghost-btn" id="signIn" onclick="toggleAuthMode('login')">Iniciar sesión</button>
                    </div>

                    <div class="overlay-footer">
                        <span>STOCK</span>
                        <span class="dot">▪</span>
                        <span>VENTAS</span>
                        <span class="dot">▪</span>
                        <span>TALLER</span>
                        <span class="dot">▪</span>
                        <span>PROVEEDORES</span>
                    </div>
                </div>

                <?php // PANEL DERECHO DEL OVERLAY (Visible cuando está en Login) ?>
                <div class="overlay-panel overlay-right">
                    <div class="overlay-brand">
                        <span class="brand-text">SISTEMA NOKIA</span>
                        <span class="brand-chip">1100</span>
                    </div>

                    <div class="overlay-content">
                        <h1 class="overlay-title">Todo tu negocio, en un solo lugar.</h1>
                        <p class="overlay-desc">Stock, ventas, taller y proveedores sincronizados para que te ocupes de lo importante.</p>

                        <div class="trust-features">
                            <div class="trust-item">
                                <span class="material-symbols-outlined trust-icon">bolt</span>
                                <div>
                                    <h4 class="trust-title">Operaciones sin esperas</h4>
                                    <p class="trust-text">Ventas y emisión de comprobantes en segundos para un mostrador ágil.</p>
                                </div>
                            </div>
                            <div class="trust-item">
                                <span class="material-symbols-outlined trust-icon">inventory_2</span>
                                <div>
                                    <h4 class="trust-title">Control de stock 100% trazable</h4>
                                    <p class="trust-text">Alertas de reposición y auditoría de cada movimiento en tiempo real.</p>
                                </div>
                            </div>
                            <div class="trust-item">
                                <span class="material-symbols-outlined trust-icon">build_circle</span>
                                <div>
                                    <h4 class="trust-title">Módulo de taller integrado</h4>
                                    <p class="trust-text">Seguimiento de órdenes de reparación, repuestos y estados de entrega.</p>
                                </div>
                            </div>
                        </div>

                        <button class="ghost-btn" id="signUp" onclick="toggleAuthMode('register')">Crear nueva cuenta</button>
                    </div>

                    <div class="overlay-footer">
                        <span>STOCK</span>
                        <span class="dot">▪</span>
                        <span>VENTAS</span>
                        <span class="dot">▪</span>
                        <span>TALLER</span>
                        <span class="dot">▪</span>
                        <span>PROVEEDORES</span>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <?php // Contenedor Unificado para Notificaciones Toasts Flotantes ?>
    <div id="toast-container" class="fixed top-6 right-6 z-[9999] flex flex-col items-end pointer-events-none gap-2"></div>

    <?php // Cargar Scripts del Sistema ?>
    <script src="<?php echo BASE_URL; ?>/assets/js/framer-toaster.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js?v=<?php echo time(); ?>"></script>
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