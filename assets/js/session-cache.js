/**
 * session-cache.js - Nokia 1100 System
 * Maneja el caché de la sesión usando localStorage para mostrar
 * el nombre del usuario en la parte superior derecha.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Si la sesión de PHP dice que estamos logueados, guardamos en localStorage
    const phpUserMeta = document.querySelector('meta[name="current-user"]');
    if (phpUserMeta && phpUserMeta.content.trim() !== '') {
        localStorage.setItem('nokia1100_user', phpUserMeta.content.trim());
    }

    const savedUser = localStorage.getItem('nokia1100_user');
    const trUser = document.getElementById('top-right-user');
    const trUsername = document.getElementById('tr-username');
    const trInitial = document.getElementById('tr-initial');

    // Si estamos en la página de login (index.php) o de reset de contraseña, podemos limpiar el storage
    // si PHP no nos envió un user y la url incluye logout o es el index principal
    const isLoginScreen = window.location.pathname.endsWith('index.php') && window.location.pathname.split('/').length <= 3;
    
    if (savedUser && trUser && !isLoginScreen) {
        trUsername.textContent = savedUser;
        trInitial.textContent = savedUser.charAt(0).toUpperCase();
        
        // Mostrar la cajita con animación
        requestAnimationFrame(() => {
            trUser.classList.remove('opacity-0');
            trUser.classList.add('opacity-100');
        });
    } else if (isLoginScreen) {
        // Limpiamos el storage si estamos en la pantalla de login principal
        // a menos que estemos en un proceso de auth interno
        if (!phpUserMeta || phpUserMeta.content.trim() === '') {
            localStorage.removeItem('nokia1100_user');
        }
    }
});
