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
    <title>Acceso Corporativo - Nokia 1100 System</title>
    
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

        .main-auth-container {
            width: 100%;
            max-width: 480px;
            z-index: 10;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @media (min-width: 768px) {
            .main-auth-container {
                max-width: 480px;
            }
            .main-auth-container.is-register {
                max-width: 580px;
            }
        }

        .auth-glass-card {
            background: rgba(17, 17, 19, 0.65);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6), 0 0 40px rgba(33, 184, 189, 0.03);
            border-radius: 28px;
            overflow: hidden;
            position: relative;
            width: 100%;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .auth-slider {
            display: flex;
            width: 200%;
            transition: transform 0.6s cubic-bezier(0.76, 0, 0.24, 1);
        }

        .auth-panel {
            width: 50%;
            padding: 2.5rem;
            flex-shrink: 0;
            transition: opacity 0.4s ease;
        }

        .main-auth-container.is-register .auth-slider {
            transform: translateX(-50%);
        }

        .main-auth-container.is-register .login-panel {
            opacity: 0;
            pointer-events: none;
            visibility: hidden;
            transition: opacity 0.2s ease, visibility 0s 0.2s;
        }

        .main-auth-container:not(.is-register) .register-panel {
            opacity: 0;
            pointer-events: none;
            visibility: hidden;
            transition: opacity 0.2s ease, visibility 0s 0.2s;
        }

        .main-auth-container:not(.is-register) .login-panel,
        .main-auth-container.is-register .register-panel {
            opacity: 1;
            visibility: visible;
            transition: opacity 0.4s ease 0.15s, visibility 0s 0s;
        }

        .brand-logo-glow {
            filter: drop-shadow(0 0 20px rgba(33,184,189,0.25));
            transition: all 0.5s ease;
        }

        .main-auth-container.is-register .brand-logo-glow {
            filter: drop-shadow(0 0 20px rgba(224,79,238,0.20));
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
    $wrapperClass = $isRegister ? 'is-register' : '';
    ?>
    <div class="main-auth-container <?php echo $wrapperClass; ?>" id="authWrapper">
        
        <!-- Tarjeta de Acceso Glassmorphism Centrada -->
        <div class="auth-glass-card">
            <div class="auth-slider">

                <!-- PANEL 1: INICIAR SESIÓN -->
                <div class="auth-panel login-panel flex flex-col justify-center">
                    <div class="text-center mb-8">
                        <h1 class="text-3xl font-extrabold font-display tracking-tight text-text-main">
                            NOKIA<span class="text-primary" id="brandColor">1100</span>
                        </h1>
                        <p class="text-xs text-text-muted mt-1 uppercase tracking-widest font-semibold text-[10px]">Iniciar Sesion</p>
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

                    <div class="text-center mt-8 text-xs text-text-muted font-medium border-t border-border/40 pt-6">
                        ¿Aún no tienes una cuenta corporativa? 
                        <button type="button" onclick="toggleAuthMode('register')" class="text-primary hover:underline font-semibold bg-transparent border-none cursor-pointer">Registrarme</button>
                    </div>
                </div>

                <!-- PANEL 2: REGISTRO EMPRESARIAL -->
                <div class="auth-panel register-panel flex flex-col justify-center">
                    <div class="text-center mb-6 md:hidden">
                        <h1 class="text-2xl font-bold font-display tracking-tight text-text-main">NOKIA<span class="text-primary">1100</span></h1>
                        <p class="text-[10px] text-text-muted mt-1 tracking-widest uppercase font-extrabold">Registro de Operador</p>
                    </div>

                    <div class="mb-5">
                        <h2 class="text-xl font-display font-medium text-text-main">Registrarse</h2>
                    </div>

                    <form action="<?php echo BASE_URL; ?>/modules/auth/process_registration.php" method="POST" class="space-y-3.5">
                        
                        <!-- Datos Personales -->
                        <div class="grid grid-cols-2 gap-3.5">
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

                        <div class="grid grid-cols-2 gap-3.5">
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

                        <div class="grid grid-cols-2 gap-3.5">
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

                        <div class="grid grid-cols-2 gap-3.5">
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

                    <div class="text-center mt-6 text-xs text-text-muted font-medium border-t border-border/40 pt-5">
                        ¿Ya posees una cuenta de operador? 
                        <button type="button" onclick="toggleAuthMode('login')" class="text-primary hover:underline font-semibold bg-transparent border-none cursor-pointer">Iniciar Sesión</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Contenedor Unificado para Notificaciones Toasts Flotantes -->
    <div id="toast-container" class="fixed top-6 right-6 z-[9999] flex flex-col items-end pointer-events-none gap-2"></div>

    <!-- Cargar Scripts del Sistema para animaciones Toasts y decodificación de URL -->
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js?v=<?php echo time(); ?>"></script>

    <!-- Script de Gestión de Acceso -->
    <script>
        // Conmutador del modo Auth (Login / Registro)
        function toggleAuthMode(mode) {
            const wrapper = document.getElementById('authWrapper');
            const brandColor = document.getElementById('brandColor');
            
            if (mode === 'register') {
                wrapper.classList.add('is-register');
                
                if (brandColor) brandColor.style.color = '#E04FEE'; // Magenta en Registro
                
                setTimeout(() => {
                    const params = new URLSearchParams(window.location.search);
                    params.set('action', 'register');
                    window.history.pushState({}, '', '?' + params.toString());
                }, 300);
            } else {
                wrapper.classList.remove('is-register');
                
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