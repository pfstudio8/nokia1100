// Nokia 1100 - Main JavaScript file

document.addEventListener('DOMContentLoaded', () => {
    // Arranco el buscador en tiempo real para filtrar las filas de las tablas mientras escribo
    initTableSearch();
    // Activo los modales bonitos de confirmación cuando toco botones de borrar o confirmar algo
    initConfirmModals();
    // Reemplaza los popups nativos del navegador por notificaciones Toast personalizadas en los formularios
    initCustomFormValidation();
    // Si la URL viene con algún parámetro de éxito o error, lanzo la notificación toast emergente
    handleUrlToasts();
    // Le meto el efecto de inclinación 3D interactivo a las tarjetas del panel cuando les paso el cursor
    initHover3D();
    // Hago que las páginas carguen con un efecto de transición suave y opacidad al navegar por los enlaces
    initPageTransitions();
    // Atajos de teclado rápidos para operaciones clave (F2, F3, F4, Ctrl+B)
    initGlobalKeyboardShortcuts();
    // Monitor en tiempo real de órdenes terminadas en Taller listas para cobrar
    initReadyRepairsWatcher();
});

// Reemplaza la validación nativa del navegador por notificaciones tipo Toast
function initCustomFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        // Agregamos novalidate para suprimir los tooltips nativos del navegador
        form.setAttribute('novalidate', 'true');

        form.addEventListener('submit', function (e) {
            if (!this.checkValidity()) {
                e.preventDefault(); // Detener el envío porque hay campos inválidos

                const firstInvalid = this.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus();

                    let fieldName = 'este campo';

                    // Intentamos obtener un nombre descriptivo (desde el placeholder, ID asociado al label, o name)
                    const labelDiv = firstInvalid.closest('div')?.querySelector('label');
                    if (firstInvalid.id) {
                        const label = document.querySelector(`label[for="${firstInvalid.id}"]`);
                        if (label) fieldName = label.textContent.trim().replace('*', '');
                    }
                    if (fieldName === 'este campo' && labelDiv) {
                        fieldName = labelDiv.textContent.trim().replace('*', '');
                    }
                    if (fieldName === 'este campo' && firstInvalid.placeholder) {
                        fieldName = firstInvalid.placeholder;
                    }
                    if (fieldName === 'este campo' && firstInvalid.name) {
                        fieldName = firstInvalid.name;
                    }

                    if (typeof showToast === 'function') {
                        // Formateamos el nombre a Capital Case para que se vea mejor y acortamos el mensaje
                        fieldName = fieldName.toLowerCase().replace(/(?:^|\s)\S/g, a => a.toUpperCase());
                        showToast(`Por favor completa el campo ${fieldName}`, 'warning');
                    } else {
                        alert(`Error:\nPor favor, completa correctamente el campo: ${fieldName}`);
                    }
                }
            }
        });
    });
}

// Calcula la distancia de Levenshtein entre dos cadenas
function levenshteinDistance(a, b) {
    if (a.length === 0) return b.length;
    if (b.length === 0) return a.length;
    const matrix = [];
    for (let i = 0; i <= b.length; i++) matrix[i] = [i];
    for (let j = 0; j <= a.length; j++) matrix[0][j] = j;
    for (let i = 1; i <= b.length; i++) {
        for (let j = 1; j <= a.length; j++) {
            if (b.charAt(i - 1) === a.charAt(j - 1)) {
                matrix[i][j] = matrix[i - 1][j - 1];
            } else {
                matrix[i][j] = Math.min(
                    matrix[i - 1][j - 1] + 1,
                    matrix[i][j - 1] + 1,
                    matrix[i - 1][j] + 1
                );
            }
        }
    }
    return matrix[b.length][a.length];
}

// Función para filtrar filas de una tabla en tiempo real con Fuzzy Search
function initTableSearch() {
    const searchInput = document.getElementById('search-input');
    const tableBody = document.querySelector('.table-container tbody');

    if (!searchInput || !tableBody) return;

    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase().trim();
        const rows = tableBody.querySelectorAll('tr');

        let hasVisibleRows = false;
        const queryWords = query.split(/\s+/).filter(w => w.length > 0);

        const noResultsRow = tableBody.querySelector('.js-no-results');
        if (noResultsRow) {
            noResultsRow.remove();
        }

        rows.forEach(row => {
            if (row.cells.length === 1 && row.textContent.trim().includes('No hay productos')) {
                row.style.display = 'none';
                return;
            }

            const text = row.textContent.toLowerCase();
            const textWords = text.split(/[\s\-_]+/);

            let isMatch = true;
            if (queryWords.length > 0) {
                isMatch = queryWords.every(qw => {
                    return textWords.some(tw => {
                        if (tw.includes(qw)) return true;
                        const maxDistance = qw.length > 4 ? 2 : (qw.length > 2 ? 1 : 0);
                        return levenshteinDistance(qw, tw) <= maxDistance ||
                            (tw.length >= qw.length && levenshteinDistance(qw, tw.substring(0, qw.length)) <= maxDistance);
                    });
                });
            }

            if (isMatch) {
                row.style.display = '';
                hasVisibleRows = true;
            } else {
                row.style.display = 'none';
            }
        });

        if (!hasVisibleRows && rows.length > 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.className = 'js-no-results';
            emptyRow.innerHTML = `<td colspan="${rows[0].cells.length}" style="text-align: center; padding: 3rem; color: var(--text-muted);">No se encontraron coincidencias locales.</td>`;
            tableBody.appendChild(emptyRow);
        }
    });
}

// Intercepta los clicks en enlaces o botones con atributo data-confirm
// Ejemplo: <a href="delete.php" data-confirm="¿Eliminar usuario?">Borrar</a>
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

// Revisa la URL por parámetros "success" o "error" y lanza un Toast automáticamente.
function handleUrlToasts() {
    const urlParams = new URLSearchParams(window.location.search);

    // Mapeo de códigos comunes a mensajes amigables
    const messages = {
        'deleted': 'Registro eliminado correctamente',
        'saved': 'Cambios guardados exitosamente',
        'created': 'Nuevo registro creado con éxito',
        'has_sales': 'No se puede borrar porque posee ventas',
        'has_orders': 'No se puede borrar porque posee órdenes',
        'has_purchases': 'No se puede borrar porque posee compras',
        'in_use': 'Elemento en uso, imposible modificar',
        'updated': 'Datos actualizados correctamente'
    };

    // URLSearchParams ya decodifica; un segundo decode puede lanzar URIError con '%'
    const safeDecode = (v) => { try { return decodeURIComponent(v); } catch (_) { return v; } };

    if (urlParams.has('success')) {
        const code = urlParams.get('success');
        const msg = messages[code] || safeDecode(code);
        showToast(msg, 'success');
        urlParams.delete('success');
        const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
        window.history.replaceState({}, document.title, newUrl);
    }
    else if (urlParams.has('error')) {
        const code = urlParams.get('error');
        const msg = messages[code] || safeDecode(code);
        showToast(msg, 'error');
        urlParams.delete('error');
        const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
        window.history.replaceState({}, document.title, newUrl);
    }
}

// Efecto de Hover interactivo 3D para tarjetas
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

// Envuelve los enlaces en animaciones de opacidad y deslizamiento al navegar
function initPageTransitions() {
    document.body.addEventListener('click', e => {
        // Respetar clic central / Ctrl / Cmd / Shift / Alt (abrir en nueva pestaña o ventana)
        if (e.defaultPrevented || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;

        const link = e.target.closest('a');
        if (!link || !link.href) return;

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
            }, 150); // Timeout más rápido para que la navegación sea casi inmediata
        }
    });
}

// Atajos de teclado globales (F2: Venta, F3: Inventario, F4: Taller, Ctrl+B: Clientes)
function initGlobalKeyboardShortcuts() {
    // Solo habilitar atajos si hay una sesión activa dentro del sistema (evitar en pantalla de login)
    if (!document.getElementById('mainSidebar') && !document.getElementById('mobileTopBar')) {
        return;
    }

    document.addEventListener('keydown', (e) => {
        // Ignorar si el usuario está interactuando con campos de texto o modales activos
        const tag = (document.activeElement && document.activeElement.tagName) || '';
        const isEditable = document.activeElement && document.activeElement.isContentEditable;
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(tag) || isEditable) {
            return;
        }

        const baseUrl = window.BASE_URL || (window.location.origin + '/nokia1100');

        if (e.key === 'F2') {
            e.preventDefault();
            if (typeof showToast === 'function') showToast('Abriendo Nueva Venta (F2)...', 'info');
            window.location.href = baseUrl + '/modules/sales/index.php?action=new_sale';
        } else if (e.key === 'F3') {
            e.preventDefault();
            if (typeof showToast === 'function') showToast('Saltando al Inventario (F3)...', 'info');
            window.location.href = baseUrl + '/modules/inventory/index.php';
        } else if (e.key === 'F4') {
            e.preventDefault();
            if (typeof showToast === 'function') showToast('Abriendo Taller y Reparaciones (F4)...', 'info');
            window.location.href = baseUrl + '/modules/workshop/index.php';
        } else if ((e.ctrlKey || e.metaKey) && (e.key === 'b' || e.key === 'B')) {
            e.preventDefault();
            if (typeof showToast === 'function') showToast('Abriendo Clientes (Ctrl + B)...', 'info');
            window.location.href = baseUrl + '/modules/clients/index.php';
        }
    });
}

// Monitor global en tiempo real de reparaciones listas para cobrar enviadas desde el taller
function initReadyRepairsWatcher() {
    // Si el usuario no ha iniciado sesión (ej: pantalla de login index.php o auth), no iniciar polling
    if (!document.getElementById('mainSidebar') && !document.getElementById('mobileTopBar')) {
        return;
    }

    // Si ya está en la bandeja especializada de cobrar reparaciones, esa pantalla maneja su propio ciclo de polling
    if (window.location.href.includes('action=repairs_to_charge')) {
        return;
    }

    const baseUrl = window.BASE_URL || (window.location.origin + '/nokia1100');
    let lastKnownCount = null;
    let knownIds = new Set();
    let isFirstCheck = true;

    function playGlobalChime() {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            if (ctx.state === 'suspended') ctx.resume();
            const notes = [
                { freq: 698.46, time: 0, dur: 0.15 },
                { freq: 880.00, time: 0.14, dur: 0.3 }
            ];
            notes.forEach(n => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(n.freq, ctx.currentTime + n.time);
                gain.gain.setValueAtTime(0.001, ctx.currentTime + n.time);
                gain.gain.exponentialRampToValueAtTime(0.2, ctx.currentTime + n.time + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + n.time + n.dur);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(ctx.currentTime + n.time);
                osc.stop(ctx.currentTime + n.time + n.dur);
            });
        } catch (e) {
            // Audio no soportado o bloqueado
        }
    }

    function checkReadyRepairs() {
        // No consultar mientras la pestaña está oculta (evita carga innecesaria en el servidor)
        if (document.hidden) return;
        fetch(baseUrl + '/modules/sales/index.php?action=check_ready_repairs', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => {
            if (res.status === 401) {
                if (typeof pollInterval !== 'undefined') clearInterval(pollInterval);
                return null;
            }
            if (!res.ok) return null;
            return res.json();
        })
        .then(data => {
            if (!data || !data.success) return;

            const count = parseInt(data.count) || 0;
            const incomingIds = (data.repairs || []).map(r => r.id);

            // Actualizar insignias de conteo en la barra lateral
            document.querySelectorAll('[data-badge="ready-repairs"]').forEach(badge => {
                badge.textContent = count;
                if (count > 0) {
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            });

            if (isFirstCheck) {
                lastKnownCount = count;
                knownIds = new Set(incomingIds);
                isFirstCheck = false;
                return;
            }

            // Si hay órdenes nuevas que antes no estaban
            const newOrders = (data.repairs || []).filter(r => !knownIds.has(r.id));
            if (newOrders.length > 0) {
                newOrders.forEach(o => knownIds.add(o.id));
                lastKnownCount = count;

                // Si ya está en la vista especializada de cobrar, esa pantalla maneja su propio reload
                const isOnRepairsToCharge = window.location.href.includes('action=repairs_to_charge');
                if (!isOnRepairsToCharge) {
                    playGlobalChime();
                    const latest = newOrders[newOrders.length - 1];
                    if (typeof showToast === 'function') {
                        showToast(`🔔 Taller: Orden #${latest.codigo} (${latest.cliente}) lista para cobrar (${latest.total_formatted})`, 'success');
                    }
                }
            } else {
                lastKnownCount = count;
                knownIds = new Set(incomingIds);
            }
        })
        .catch(() => {
            // Silencioso ante fallos temporales
        });
    }

    // Primer chequeo a los 2 segundos, luego cada 30 segundos y al volver a la pestaña
    setTimeout(checkReadyRepairs, 2000);
    setInterval(checkReadyRepairs, 30000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) checkReadyRepairs();
    });
}
