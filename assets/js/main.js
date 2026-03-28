/**
 * Nokia 1100 - Main JavaScript file
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Iniciar Búsqueda en Tiempo Real en Tablas
    initTableSearch();
    // 2. Iniciar interceptores de modales de confirmación
    initConfirmModals();
    // 3. Revisar la URL en busca de respuestas PHP para mostrar Toasts
    handleUrlToasts();
});

/**
 * Función para filtrar filas de una tabla en tiempo real
 */
function initTableSearch() {
    const searchInput = document.getElementById('search-input');
    const tableBody = document.querySelector('.table-container tbody');

    if (!searchInput || !tableBody) return;

    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        const rows = tableBody.querySelectorAll('tr');

        let hasVisibleRows = false;
        
        // Eliminar fila de "sin resultados" dinámica si existe
        const noResultsRow = tableBody.querySelector('.js-no-results');
        if (noResultsRow) {
            noResultsRow.remove();
        }

        rows.forEach(row => {
            // Ignorar la fila nativa de tabla vacía si existe
            if (row.cells.length === 1 && row.textContent.trim().includes('No hay productos')) {
                row.style.display = 'none';
                return;
            }

            const text = row.textContent.toLowerCase();
            if (text.includes(query)) {
                row.style.display = '';
                hasVisibleRows = true;
            } else {
                row.style.display = 'none';
            }
        });

        // Mostrar fila de "sin coincidencias" si corresponde
        if (!hasVisibleRows && rows.length > 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.className = 'js-no-results';
            emptyRow.innerHTML = `<td colspan="${rows[0].cells.length}" style="text-align: center; padding: 3rem; color: var(--text-muted);">No se encontraron coincidencias locales.</td>`;
            tableBody.appendChild(emptyRow);
        }
    });
}

/**
 * Función para mostrar notificaciones "Toast" flotantes
 * @param {string} message - El mensaje a mostrar
 * @param {string} type - 'success', 'error', 'info', o 'warning'
 */
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    
    // Configurar iconos y colores según el tipo
    let icon = '';
    let colors = '';
    
    switch(type) {
        case 'success':
            icon = 'check_circle';
            colors = 'bg-surface border-green-500/30 text-green-400';
            break;
        case 'error':
            icon = 'error';
            colors = 'bg-surface border-red-500/30 text-red-400';
            break;
        case 'warning':
            icon = 'warning';
            colors = 'bg-surface border-yellow-500/30 text-yellow-400';
            break;
        default:
            icon = 'info';
            colors = 'bg-surface border-primary/30 text-primary';
    }
    
    // Clases base (Tailwind + CSS Custom)
    toast.className = `toast flex items-center gap-3 p-4 mb-3 rounded-lg border shadow-lg transform transition-all duration-300 translate-x-full opacity-0 pointer-events-auto ${colors}`;

    toast.innerHTML = `
        <span class="material-symbols-outlined shrink-0">${icon}</span>
        <p class="text-sm font-medium text-text-main m-0 p-0 leading-tight">${message}</p>
        <button class="ml-auto text-text-muted hover:text-text-main transition-colors focus:outline-none" onclick="this.parentElement.remove()">
            <span class="material-symbols-outlined text-sm">close</span>
        </button>
    `;

    container.appendChild(toast);

    // Activar animación de entrada
    setTimeout(() => {
        toast.classList.remove('translate-x-full', 'opacity-0');
    }, 10);

    // Eliminar automáticamente después de 4 segundos
    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-x-full');
        setTimeout(() => toast.remove(), 300); // Dar tiempo a la transición CSS
    }, 4000);
}

/**
 * Muestra un modal de confirmación personalizado.
 * Retorna una promesa que se resuelve en `true` si el usuario confirma
 * o `false` si el usuario cancela.
 */
function showConfirmModal(title, message, confirmText = 'Confirmar', cancelText = 'Cancelar', isDestructive = false) {
    return new Promise((resolve) => {
        // Crear overlay oscuro
        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 bg-background/80 backdrop-blur-sm z-50 flex items-center justify-center opacity-0 transition-opacity duration-300';
        
        // Colores del botón de confirmación
        const confirmBtnClass = isDestructive 
            ? 'bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white border border-red-500/20' 
            : 'bg-primary/10 text-primary hover:bg-primary hover:text-background border border-primary/20';

        // Estructura del modal (Glassmorphism)
        const modal = document.createElement('div');
        modal.className = 'bg-surface border border-border rounded-2xl shadow-2xl p-6 w-full max-w-sm transform scale-95 transition-transform duration-300';
        modal.innerHTML = `
            <div class="mb-6">
                <h3 class="text-lg font-bold font-display text-text-main mb-2">${title}</h3>
                <p class="text-sm text-text-muted leading-relaxed">${message}</p>
            </div>
            <div class="flex gap-3 justify-end">
                <button id="modal-cancel" type="button" class="px-4 py-2 text-sm font-medium text-text-muted hover:text-text-main hover:bg-surface-hover rounded-lg transition-colors focus:outline-none">
                    ${cancelText}
                </button>
                <button id="modal-confirm" type="button" class="px-4 py-2 text-sm font-medium rounded-lg transition-all focus:outline-none ${confirmBtnClass}">
                    ${confirmText}
                </button>
            </div>
        `;

        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        // Activar animación
        requestAnimationFrame(() => {
            overlay.classList.remove('opacity-0');
            modal.classList.remove('scale-95');
        });

        // Funciones para cerrar
        const closeModal = (result) => {
            overlay.classList.add('opacity-0');
            modal.classList.add('scale-95');
            setTimeout(() => {
                overlay.remove();
                resolve(result);
            }, 300); // Esperar que termine la animación
        };

        // Listeners
        modal.querySelector('#modal-cancel').addEventListener('click', () => closeModal(false));
        modal.querySelector('#modal-confirm').addEventListener('click', () => closeModal(true));
        
        // Cerrar al hacer clic afuera
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal(false);
        });
    });
}

/**
 * Intercepta los clicks en enlaces o botones con atributo data-confirm
 * Ejemplo: <a href="delete.php" data-confirm="¿Eliminar usuario?">Borrar</a>
 */
function initConfirmModals() {
    document.body.addEventListener('click', async (e) => {
        // Buscar el elemento más cercano con data-confirm
        const target = e.target.closest('[data-confirm]');
        if (!target) return;

        e.preventDefault(); // Detener navegación inmediata
        const message = target.getAttribute('data-confirm');
        const title = target.getAttribute('data-confirm-title') || 'Confirmación requerida';
        const isDestructive = target.classList.contains('text-red-500') || target.classList.contains('btn-delete') || target.getAttribute('data-destructive') === 'true';
        
        const confirmed = await showConfirmModal(title, message, 'Sí, continuar', 'Cancelar', isDestructive);
        
        if (confirmed) {
            // Si es un enlace, navegar a su URL
            if (target.tagName.toLowerCase() === 'a' && target.href) {
                window.location.href = target.href;
            } 
            // Si es un submit, enviar el formulario
            else if (target.tagName.toLowerCase() === 'button' && target.type === 'submit') {
                target.form.submit();
            }
        }
    });
}

/**
 * Revisa la URL por parámetros "success" o "error" y lanza un Toast automáticamente.
 * Útil para feedback de PHP después de redigir, ej: ?success=deleted
 */
function handleUrlToasts() {
    const urlParams = new URLSearchParams(window.location.search);
    
    // Mapeo de códigos comunes a mensajes amigables
    const messages = {
        'deleted': 'Registro eliminado correctamente',
        'saved': 'Cambios guardados exitosamente',
        'created': 'Nuevo registro creado con éxito',
        'has_sales': 'No se puede eliminar porque tiene ventas asociadas',
        'in_use': 'El elemento está en uso y no puede modificarse',
        'updated': 'Datos actualizados correctamente'
    };

    if (urlParams.has('success')) {
        const code = urlParams.get('success');
        const msg = messages[code] || 'Operación completada con éxito';
        showToast(msg, 'success');
        window.history.replaceState({}, document.title, window.location.pathname);
    } 
    else if (urlParams.has('error')) {
        const code = urlParams.get('error');
        const msg = messages[code] || 'Ocurrió un error en la operación';
        showToast(msg, 'error');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
}
