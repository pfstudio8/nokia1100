// assets/js/pages/login.js

// Conmutador del modo Auth (Login / Registro)
function toggleAuthMode(mode) {
    const container = document.getElementById('authContainer');
    
    if (mode === 'register') {
        container.classList.add('right-panel-active');
        
        setTimeout(() => {
            const params = new URLSearchParams(window.location.search);
            params.set('action', 'register');
            window.history.pushState({}, '', '?' + params.toString());
        }, 300);
    } else {
        container.classList.remove('right-panel-active');
        
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

// Conmutador entre el formulario de Registro y el formulario de Invitado (ahora en Registro)
function showGuestForm(show) {
    const registerWrapper = document.getElementById('register-form-wrapper');
    const guestWrapper = document.getElementById('guest-form-wrapper');
    
    if (show) {
        if (registerWrapper) registerWrapper.classList.add('hidden');
        if (guestWrapper) guestWrapper.classList.remove('hidden');
    } else {
        if (registerWrapper) registerWrapper.classList.remove('hidden');
        if (guestWrapper) guestWrapper.classList.add('hidden');
    }
}

// Conmutador de visibilidad de contraseña
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const iconSpan = btn.querySelector('span');
    
    if (input.type === 'password') {
        input.type = 'text';
        iconSpan.textContent = 'visibility_off';
    } else {
        input.type = 'password';
        iconSpan.textContent = 'visibility';
    }
}

// Medidor de fuerza de contraseña
function checkPasswordStrength(password) {
    let strength = 0;
    if (password.length > 7) strength += 25;
    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 25;
    if (password.match(/\d/)) strength += 25;
    if (password.match(/[^a-zA-Z\d]/)) strength += 25;
    return strength;
}

document.addEventListener('DOMContentLoaded', () => {
    const pwdInput = document.getElementById('register-password') || document.getElementById('password');
    const container = document.getElementById('password-strength-container');
    const bar = document.getElementById('password-strength-bar');

    if (pwdInput && container && bar) {
        pwdInput.addEventListener('input', (e) => {
            const val = e.target.value;
            if (val.length === 0) {
                container.style.display = 'none';
                return;
            }
            container.style.display = 'block';
            const strength = checkPasswordStrength(val);
            
            bar.style.width = strength + '%';
            
            if (strength <= 25) {
                bar.style.backgroundColor = '#ef4444'; // Rojo
            } else if (strength <= 50) {
                bar.style.backgroundColor = '#f59e0b'; // Naranja
            } else if (strength <= 75) {
                bar.style.backgroundColor = '#eab308'; // Amarillo
            } else {
                bar.style.backgroundColor = '#22c55e'; // Verde
            }
        });
    }

    // ========================================================
    // Integración OneTimeCode (Bencho) + Modal de Verificación OTP
    // ========================================================
    let otpInstance = null;
    let currentVerifyEmail = '';
    let resendCooldown = 0;
    let resendTimer = null;

    const otpModal = document.getElementById('otpModal');
    const otpModalCard = document.getElementById('otpModalCard');
    const otpMount = document.getElementById('otpMount');
    const otpTargetEmail = document.getElementById('otpTargetEmail');
    const otpStatusMsg = document.getElementById('otpStatusMsg');
    const closeOtpModalBtn = document.getElementById('closeOtpModalBtn');
    const cancelOtpBtn = document.getElementById('cancelOtpBtn');
    const resendOtpBtn = document.getElementById('resendOtpBtn');
    const resendOtpLabel = document.getElementById('resendOtpLabel');

    function getCsrfToken() {
        const input = document.querySelector('input[name="csrf_token"]');
        return input ? input.value : '';
    }

    function openOtpModal(email) {
        currentVerifyEmail = email || '';
        if (otpTargetEmail) {
            otpTargetEmail.textContent = currentVerifyEmail || 'tu correo';
        }
        if (otpStatusMsg) {
            otpStatusMsg.innerHTML = 'Validez: 15 minutos';
            otpStatusMsg.className = 'min-h-[22px] text-xs font-mono font-medium transition-all text-text-muted my-2';
        }

        if (otpModal) {
            otpModal.classList.remove('hidden');
            setTimeout(() => {
                otpModal.classList.remove('opacity-0');
                if (otpModalCard) {
                    otpModalCard.classList.remove('scale-95');
                    otpModalCard.classList.add('scale-100');
                }
            }, 10);
        }

        // Inicializar componente OneTimeCode si no existe
        if (!otpInstance && typeof window.OneTimeCode === 'function' && otpMount) {
            otpInstance = window.OneTimeCode(otpMount, {
                length: 6,
                corner: 10,
                onComplete: async (code, api) => {
                    if (otpStatusMsg) {
                        otpStatusMsg.innerHTML = '<span class="inline-flex items-center gap-1 text-text-muted"><span class="material-symbols-outlined text-[14px] animate-spin">sync</span> Verificando código...</span>';
                    }

                    try {
                        const formData = new FormData();
                        formData.append('csrf_token', getCsrfToken());
                        formData.append('email', currentVerifyEmail);
                        formData.append('code', code);

                        const response = await fetch('modules/auth/index.php?action=verify_otp', {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            api.accept();
                            if (otpStatusMsg) {
                                otpStatusMsg.innerHTML = '<span style="color: #22c55e;" class="font-bold">¡Cuenta verificada exitosamente!</span>';
                            }
                            if (typeof showToast === 'function') {
                                showToast(data.message || '¡Cuenta verificada!', 'success');
                            }

                            setTimeout(() => {
                                closeOtpModal();
                                toggleAuthMode('login');
                                const loginUser = document.querySelector('input[name="username"]');
                                if (loginUser && data.username) {
                                    loginUser.value = data.username;
                                }
                            }, 1600);
                        } else {
                            api.reject();
                            if (otpStatusMsg) {
                                otpStatusMsg.innerHTML = `<span style="color: #ef4444;">${data.message || 'Código incorrecto'}</span>`;
                            }
                            if (typeof showToast === 'function') {
                                showToast(data.message || 'Código incorrecto o expirado', 'error');
                            }
                        }
                    } catch (err) {
                        console.error('Error al verificar OTP:', err);
                        api.reject();
                        if (otpStatusMsg) {
                            otpStatusMsg.innerHTML = '<span style="color: #ef4444;">Error de conexión con el servidor</span>';
                        }
                    }
                }
            });
        } else if (otpInstance) {
            otpInstance.reset();
        }

        setTimeout(() => {
            if (otpInstance) {
                otpInstance.focus();
            }
        }, 150);
    }

    function closeOtpModal() {
        if (!otpModal) return;
        otpModal.classList.add('opacity-0');
        if (otpModalCard) {
            otpModalCard.classList.remove('scale-100');
            otpModalCard.classList.add('scale-95');
        }
        setTimeout(() => {
            otpModal.classList.add('hidden');
            if (otpInstance) {
                otpInstance.reset();
            }
        }, 300);
    }

    if (closeOtpModalBtn) closeOtpModalBtn.addEventListener('click', closeOtpModal);
    if (cancelOtpBtn) cancelOtpBtn.addEventListener('click', closeOtpModal);

    // Reenviar Código OTP
    if (resendOtpBtn) {
        resendOtpBtn.addEventListener('click', async () => {
            if (resendCooldown > 0) return;
            if (!currentVerifyEmail) {
                if (typeof showToast === 'function') showToast('No se especificó un correo para reenviar.', 'error');
                return;
            }

            resendOtpBtn.disabled = true;
            if (resendOtpLabel) resendOtpLabel.textContent = 'Enviando...';

            try {
                const formData = new FormData();
                formData.append('csrf_token', getCsrfToken());
                formData.append('email', currentVerifyEmail);

                const res = await fetch('modules/auth/index.php?action=resend_otp', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    if (typeof showToast === 'function') showToast(data.message, 'success');
                    if (otpStatusMsg) {
                        otpStatusMsg.innerHTML = '<span style="color: #22c55e;">Nuevo código de 6 dígitos enviado.</span>';
                    }
                    if (otpInstance) otpInstance.reset();

                    // Cooldown de 30 segundos
                    resendCooldown = 30;
                    if (resendTimer) clearInterval(resendTimer);
                    resendTimer = setInterval(() => {
                        resendCooldown--;
                        if (resendCooldown <= 0) {
                            clearInterval(resendTimer);
                            resendOtpBtn.disabled = false;
                            if (resendOtpLabel) resendOtpLabel.textContent = 'Reenviar código';
                        } else {
                            if (resendOtpLabel) resendOtpLabel.textContent = `Reenviar (${resendCooldown}s)`;
                        }
                    }, 1000);
                } else {
                    resendOtpBtn.disabled = false;
                    if (resendOtpLabel) resendOtpLabel.textContent = 'Reenviar código';
                    if (typeof showToast === 'function') showToast(data.message || 'Error al reenviar código', 'error');
                }
            } catch (err) {
                resendOtpBtn.disabled = false;
                if (resendOtpLabel) resendOtpLabel.textContent = 'Reenviar código';
                if (typeof showToast === 'function') showToast('Error al comunicar con el servidor', 'error');
            }
        });
    }

    // Interceptar Formulario de Registro con AJAX
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitBtn = registerForm.querySelector('button[type="submit"]');
            const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span>Registrando...</span><span class="material-symbols-outlined text-[18px] animate-spin">sync</span>';
            }

            try {
                const formData = new FormData(registerForm);
                const response = await fetch(registerForm.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    if (data.require_otp) {
                        const email = data.email || formData.get('email');
                        openOtpModal(email);
                        if (typeof showToast === 'function') {
                            showToast(data.message || 'Registro exitoso. Te enviamos el código por correo.', 'success');
                        }
                        registerForm.reset();
                    } else if (data.is_admin) {
                        if (typeof showToast === 'function') {
                            showToast(data.message, 'success');
                        }
                        toggleAuthMode('login');
                    } else {
                        if (typeof showToast === 'function') {
                            showToast(data.message || 'Registro completado.', 'success');
                        }
                    }
                } else {
                    if (typeof showToast === 'function') {
                        showToast(data.message || 'Ocurrió un error en el registro.', 'error');
                    }
                }
            } catch (err) {
                console.error('Error en registro AJAX:', err);
                // Fallback: enviar de forma tradicional si falla la petición JSON
                registerForm.submit();
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
            }
        });
    }

    // Interceptar Formulario de Login con AJAX para detectar cuentas sin verificar
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitBtn = loginForm.querySelector('button[type="submit"]');
            const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span>Ingresando...</span><span class="material-symbols-outlined text-[18px] animate-spin">sync</span>';
            }

            try {
                const formData = new FormData(loginForm);
                const response = await fetch(loginForm.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                } else {
                    if (data.unverified) {
                        openOtpModal(data.email);
                        if (typeof showToast === 'function') {
                            showToast(data.message || 'Tu cuenta requiere verificación.', 'warn');
                        }
                    } else {
                        if (typeof showToast === 'function') {
                            showToast(data.message || 'Usuario o contraseña incorrectos', 'error');
                        }
                    }
                }
            } catch (err) {
                console.error('Error en login AJAX:', err);
                // Fallback tradicional
                loginForm.submit();
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
            }
        });
    }

    // Comprobar parámetros de URL al cargar para abrir el modal automáticamente
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'verify_otp_modal' || urlParams.has('verify_email')) {
        const emailToVerify = urlParams.get('email') || urlParams.get('verify_email') || '';
        setTimeout(() => {
            openOtpModal(emailToVerify);
        }, 300);
    }
});
