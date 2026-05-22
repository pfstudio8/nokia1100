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
    // 4. Iniciar Hover 3D
    initHover3D();
    // 5. Iniciar transiciones de página suaves
    initPageTransitions();
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
 * Función para mostrar notificaciones "Toast" flotantes premium
 * @param {string} message - El mensaje a mostrar
 * @param {string} type - 'success', 'error', 'info', o 'warning'
 */
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');

    // Configurar iconos y colores según el tipo
    let icon = '';
    let themeClass = '';
    let accentColor = '';

    switch (type) {
        case 'success':
            icon = 'check_circle';
            themeClass = 'premium-toast-success';
            accentColor = '#22C55E';
            break;
        case 'error':
            icon = 'error';
            themeClass = 'premium-toast-error';
            accentColor = '#ff716c';
            break;
        case 'warning':
            icon = 'warning';
            themeClass = 'premium-toast-warning';
            accentColor = '#eab308';
            break;
        default:
            icon = 'info';
            themeClass = 'premium-toast-info';
            accentColor = '#21b8bd';
    }

    toast.className = `premium-toast ${themeClass} pointer-events-auto`;
    toast.innerHTML = `
        <div class="premium-toast-content">
            <span class="material-symbols-outlined premium-toast-icon">${icon}</span>
            <p class="premium-toast-message">${message}</p>
            <button class="premium-toast-close" onclick="closeToast(this.closest('.premium-toast'))">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="premium-toast-progress" style="background-color: ${accentColor};"></div>
    `;

    container.appendChild(toast);

    // Activar animación de entrada
    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0) scale(1)';
    }, 10);

    // Animación de barra de progreso
    const progressBar = toast.querySelector('.premium-toast-progress');
    progressBar.animate([
        { width: '100%' },
        { width: '0%' }
    ], {
        duration: 4000,
        easing: 'linear'
    });

    // Eliminar automáticamente después de 4 segundos
    setTimeout(() => {
        closeToast(toast);
    }, 4000);
}

function closeToast(toast) {
    if (!toast) return;
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100px) scale(0.9)';
    setTimeout(() => toast.remove(), 300);
}

/**
 * Muestra un modal de confirmación personalizado de calidad Premium.
 * Retorna una promesa que se resuelve en `true` si el usuario confirma
 * o `false` si el usuario cancela.
 */
function showConfirmModal(title, message, confirmText = 'Confirmar', cancelText = 'Cancelar', isDestructive = false) {
    return new Promise((resolve) => {
        // Crear overlay oscuro con blur
        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 bg-background/80 backdrop-blur-[6px] z-50 flex items-center justify-center opacity-0 transition-opacity duration-300';

        // Estilos e iconos para modal premium
        const iconName = isDestructive ? 'warning' : 'help';
        const iconBg = isDestructive ? 'bg-red-500/15 text-red-400' : 'bg-primary/15 text-primary';
        const confirmBtnClass = isDestructive
            ? 'bg-red-500 text-white hover:bg-red-600 shadow-lg shadow-red-500/20'
            : 'bg-primary text-background hover:bg-primary-hover font-semibold';

        // Estructura del modal (Glassmorphism + Diseño Premium)
        const modal = document.createElement('div');
        modal.className = 'bg-surface border border-border/80 rounded-2xl shadow-2xl p-6 w-full max-w-sm transform scale-95 transition-all duration-300 relative overflow-hidden';
        modal.style.boxShadow = isDestructive 
            ? '0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 35px rgba(239, 68, 68, 0.1)' 
            : '0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 35px rgba(33, 184, 189, 0.1)';

        modal.innerHTML = `
            <div class="flex flex-col items-center text-center mb-6">
                <div class="w-12 h-12 rounded-full ${iconBg} flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-[28px] font-bold">${iconName}</span>
                </div>
                <h3 class="text-xl font-bold font-display text-text-main mb-2">${title}</h3>
                <p class="text-sm text-text-muted leading-relaxed">${message}</p>
            </div>
            <div class="flex gap-3 w-full">
                <button id="modal-cancel" type="button" class="flex-1 py-2.5 text-sm font-medium text-text-muted hover:text-text-main hover:bg-surface-hover rounded-xl transition-all border border-border focus:outline-none">
                    ${cancelText}
                </button>
                <button id="modal-confirm" type="button" class="flex-1 py-2.5 text-sm font-medium rounded-xl transition-all focus:outline-none ${confirmBtnClass}">
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
            modal.classList.add('scale-100');
        });

        // Funciones para cerrar
        const closeModal = (result) => {
            overlay.classList.add('opacity-0');
            modal.classList.remove('scale-100');
            modal.classList.add('scale-95');
            setTimeout(() => {
                overlay.remove();
                resolve(result);
            }, 300);
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
        const msg = messages[code] || decodeURIComponent(code);
        showToast(msg, 'success');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    else if (urlParams.has('error')) {
        const code = urlParams.get('error');
        const msg = messages[code] || decodeURIComponent(code);
        showToast(msg, 'error');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
}

/**
 * Efecto de Hover interactivo 3D para tarjetas
 */
function initHover3D() {
    document.querySelectorAll('.hover-3d-target').forEach(card => {
        card.addEventListener('mousemove', e => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            const centerX = rect.width / 2;
            const centerY = rect.height / 2;

            const rotateX = ((y - centerY) / centerY) * -5;
            const rotateY = ((x - centerX) / centerX) * 5;

            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.02, 1.02, 1.02)`;
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = `perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)`;
        });
    });
}

/**
 * Envuelve los enlaces en animaciones de opacidad y deslizamiento al navegar
 */
function initPageTransitions() {
    document.body.addEventListener('click', e => {
        const link = e.target.closest('a');
        if (!link) return;

        // Evitar interceptar links que abren en otra pestaña, descargas o javascript
        if (link.target === '_blank' ||
            link.href.startsWith('javascript:') ||
            link.getAttribute('href')?.startsWith('#') ||
            link.hasAttribute('download') ||
            link.classList.contains('no-transition') ||
            link.hasAttribute('data-confirm')) {
            return;
        }

        // Si es un enlace interno valido
        if (link.href && link.hostname === window.location.hostname) {
            e.preventDefault();
            document.documentElement.classList.add('is-animating');
            setTimeout(() => {
                window.location.href = link.href;
            }, 250); // Mismo que la transición CSS
        }
    });
}
