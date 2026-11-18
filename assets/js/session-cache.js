// session-cache.js - Nokia 1100 System
// Maneja el caché de la sesión usando localStorage para mostrar
// el nombre del usuario en la parte superior derecha y limpiar al salir.

document.addEventListener('DOMContentLoaded', () => {
    const phpUserMeta = document.querySelector('meta[name="current-user"]');
    const currentUser = phpUserMeta ? phpUserMeta.content.trim() : '';

    const trUser = document.getElementById('top-right-user');
    const trUsername = document.getElementById('tr-username');
    const trInitial = document.getElementById('tr-initial');

    if (currentUser !== '') {
        localStorage.setItem('nokia1100_user', currentUser);
        if (trUser && trUsername && trInitial) {
            trUsername.textContent = currentUser;
            trInitial.textContent = currentUser.charAt(0).toUpperCase();
            requestAnimationFrame(() => {
                trUser.classList.remove('opacity-0');
                trUser.classList.add('opacity-100');
            });
        }
    } else {
        // Sin sesión activa en PHP (login, logout, reset o sesión expirada): limpiar caché
        localStorage.removeItem('nokia1100_user');
        if (trUser) {
            trUser.classList.add('hidden');
        }
    }

    // Limpiar también inmediatamente al hacer clic en Cerrar Sesión
    document.querySelectorAll('a[href*="action=logout"], [data-nk-loader="logout"]').forEach((link) => {
        link.addEventListener('click', () => {
            localStorage.removeItem('nokia1100_user');
        });
    });
});
