// session-cache.js - Nokia 1100 System
// Maneja el caché de la sesión usando localStorage para almacenar, leer y mostrar
// el nombre del usuario en la barra superior derecha y limpiarlo al cerrar sesión.

document.addEventListener('DOMContentLoaded', () => {
    const STORAGE_KEY = 'nokia1100_user';
    const phpUserMeta = document.querySelector('meta[name="current-user"]');
    const serverUser = phpUserMeta ? phpUserMeta.content.trim() : '';

    const trUser = document.getElementById('top-right-user');
    const trUsername = document.getElementById('tr-username');
    const trInitial = document.getElementById('tr-initial');

    // 1. Sincronizar localStorage con el estado de sesión reportado por el servidor
    if (serverUser !== '') {
        if (localStorage.getItem(STORAGE_KEY) !== serverUser) {
            localStorage.setItem(STORAGE_KEY, serverUser);
        }
    } else if (phpUserMeta !== null) {
        // Si la página declaró meta[name="current-user"] vacío (login/logout/sesión expirada), limpiar caché
        localStorage.removeItem(STORAGE_KEY);
    }

    // 2. Leer el usuario desde localStorage para hidratar el indicador superior derecho
    const cachedUser = localStorage.getItem(STORAGE_KEY);
    if (cachedUser && trUser && trUsername && trInitial) {
        trUsername.textContent = cachedUser;
        trInitial.textContent = cachedUser.charAt(0).toUpperCase();
        requestAnimationFrame(() => {
            trUser.classList.remove('opacity-0');
            trUser.classList.add('opacity-100');
        });
    } else if (trUser) {
        trUser.classList.add('hidden');
    }

    // 3. Limpiar inmediatamente localStorage al hacer clic en Cerrar Sesión
    document.querySelectorAll('a[href*="action=logout"], [data-nk-loader="logout"]').forEach((link) => {
        link.addEventListener('click', () => {
            localStorage.removeItem(STORAGE_KEY);
        });
    });
});
