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
});
