<?php
// modules/auth/Views/reset.php

/**
 * Variables inyectadas por AuthController::reset_password_view()
 * @var bool   $isValidToken
 * @var string $token
 * @var int    $id_usuario
 */
$isValidToken = $isValidToken ?? false;
$token = $token ?? '';

$cssVer = file_exists(__DIR__ . '/../../../assets/css/forgot_password.css') ? filemtime(__DIR__ . '/../../../assets/css/forgot_password.css') : time();
$toasterVer = file_exists(__DIR__ . '/../../../assets/js/framer-toaster.js') ? filemtime(__DIR__ . '/../../../assets/js/framer-toaster.js') : time();
$mainVer = file_exists(__DIR__ . '/../../../assets/js/main.js') ? filemtime(__DIR__ . '/../../../assets/js/main.js') : time();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña - Sistema Nokia 1100</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/forgot_password.css?v=<?php echo $cssVer; ?>">
</head>

<body>
    <main class="recovery-card">
        <header class="recovery-topbar">
            <a href="<?php echo BASE_URL; ?>/index.php" class="brand-wrap">
                <span class="brand-text">SISTEMA NOKIA</span>
                <span class="brand-chip">1100</span>
            </a>
            <span class="status-dot" title="Sistema activo"></span>
        </header>

        <div class="recovery-body">
            <span class="form-header-badge">SEGURIDAD DE CUENTA</span>
            <h1 class="form-title">Nueva contraseña</h1>
            <p class="form-subtitle">
                <?php echo $isValidToken
                    ? 'Definí una nueva clave segura para restablecer el acceso a tu cuenta.'
                    : 'No pudimos validar el enlace de restablecimiento solicitado.'; ?>
            </p>

            <?php if (!$isValidToken): ?>
                <div class="recovery-alert-error" role="alert">
                    <span class="material-symbols-outlined">link_off</span>
                    <span>El enlace es inválido o ya expiró. Solicitá uno nuevo para continuar.</span>
                </div>

                <a href="<?php echo BASE_URL; ?>/auth/forgot_password.php" class="btn-primary-auth">
                    <span>Solicitar nuevo enlace</span>
                    <span class="material-symbols-outlined" style="font-size: 18px;">arrow_forward</span>
                </a>
            <?php else: ?>
                <form action="<?php echo BASE_URL; ?>/modules/auth/index.php?action=reset_password" method="POST" novalidate>
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="field-group">
                        <label for="password" class="field-label">NUEVA CONTRASEÑA</label>
                        <div class="field-input-box">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                minlength="8"
                                autocomplete="new-password"
                                placeholder="Mínimo 8 caracteres">
                            <button type="button" class="field-action-btn" onclick="togglePass('password', this)" aria-label="Mostrar u ocultar contraseña">
                                <span class="material-symbols-outlined" style="font-size: 18px;">visibility</span>
                            </button>
                        </div>
                    </div>

                    <div class="strength-meter" aria-hidden="true">
                        <div class="strength-segment" id="seg-1"></div>
                        <div class="strength-segment" id="seg-2"></div>
                        <div class="strength-segment" id="seg-3"></div>
                    </div>
                    <p class="strength-hint" id="strength-text">Usá al menos 8 caracteres, mayúscula, minúscula y número.</p>

                    <div class="field-group">
                        <label for="password_confirm" class="field-label">CONFIRMAR CONTRASEÑA</label>
                        <div class="field-input-box">
                            <input
                                type="password"
                                id="password_confirm"
                                name="password_confirm"
                                required
                                minlength="8"
                                autocomplete="new-password"
                                placeholder="Repetí la nueva contraseña">
                            <button type="button" class="field-action-btn" onclick="togglePass('password_confirm', this)" aria-label="Mostrar u ocultar confirmación">
                                <span class="material-symbols-outlined" style="font-size: 18px;">visibility</span>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary-auth">
                        <span>Actualizar contraseña</span>
                        <span class="material-symbols-outlined" style="font-size: 18px;">lock_reset</span>
                    </button>
                </form>
            <?php endif; ?>

            <footer class="recovery-footer">
                <a href="<?php echo BASE_URL; ?>/index.php" class="back-link">
                    <span class="material-symbols-outlined" style="font-size: 16px;">arrow_back</span>
                    <span class="back-link-label">Volver al inicio de sesión</span>
                </a>
            </footer>
        </div>
    </main>

    <script src="<?php echo BASE_URL; ?>/assets/js/framer-toaster.js?v=<?php echo $toasterVer; ?>"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js?v=<?php echo $mainVer; ?>"></script>
    <script>
        function togglePass(id, btn) {
            const input = document.getElementById(id);
            const icon = btn.querySelector('.material-symbols-outlined');
            if (!input || !icon) return;
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility';
            }
        }

        const pwdInput = document.getElementById('password');
        if (pwdInput) {
            pwdInput.addEventListener('input', function() {
                const val = this.value;
                const s1 = document.getElementById('seg-1');
                const s2 = document.getElementById('seg-2');
                const s3 = document.getElementById('seg-3');
                const hint = document.getElementById('strength-text');

                let score = 0;
                if (val.length >= 8) score++;
                if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
                if (/[0-9]/.test(val)) score++;

                const colors = ['#dcd7c9', '#a82a1d', '#d98a1c', '#1d6a38'];
                const activeColor = colors[score];

                s1.style.backgroundColor = score >= 1 ? activeColor : '#dcd7c9';
                s2.style.backgroundColor = score >= 2 ? activeColor : '#dcd7c9';
                s3.style.backgroundColor = score >= 3 ? activeColor : '#dcd7c9';

                if (!val.length) {
                    hint.textContent = 'Usá al menos 8 caracteres, mayúscula, minúscula y número.';
                } else if (score === 1) {
                    hint.textContent = 'Seguridad baja: agregá mayúsculas y números.';
                } else if (score === 2) {
                    hint.textContent = 'Seguridad media: completá todos los requisitos.';
                } else {
                    hint.textContent = 'Contraseña segura y lista para guardar.';
                }
            });
        }
    </script>
</body>

</html>
