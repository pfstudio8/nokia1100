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

// showToast is now defined globally by the Sileo React bundle (sileo-toaster.bundle.js)
// showConfirmModal is now defined globally by the Sileo React bundle

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
