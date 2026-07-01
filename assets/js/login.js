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
