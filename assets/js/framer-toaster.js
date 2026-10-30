// framer-toaster.js
// Sistema unificado de notificaciones Toast y modales de confirmación (Nokia 1100 Design System)

const toastContainer = document.createElement('div');
toastContainer.id = 'framer-toast-container';
Object.assign(toastContainer.style, {
    position: 'fixed',
    top: '1.5rem',
    right: '1.5rem',
    left: 'auto',
    width: 'auto',
    maxWidth: 'calc(100vw - 3rem)',
    zIndex: '99999',
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'flex-end',
    pointerEvents: 'none',
    gap: '0.65rem'
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        document.body.appendChild(toastContainer);
    });
} else {
    document.body.appendChild(toastContainer);
}

let activeToasts = [];

window.showToast = function(message, type = 'success', duration = 1000) {
    const toast = document.createElement('div');
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');

    // Mapeo de códigos comunes a textos legibles
    const friendlyMessages = {
        'deleted': 'Registro eliminado correctamente',
        'saved': 'Cambios guardados exitosamente',
        'created': 'Nuevo registro creado con éxito',
        'has_sales': 'No se puede borrar porque posee ventas asociadas',
        'has_orders': 'No se puede borrar porque posee órdenes asociadas',
        'has_purchases': 'No se puede borrar porque posee compras asociadas',
        'in_use': 'Elemento en uso, imposible modificar',
        'updated': 'Datos actualizados correctamente'
    };
    const finalMessage = (friendlyMessages[message] || String(message)).trim();

    // Normalizar para detección semántica (sin tildes)
    const rawType = String(type || '').toLowerCase().trim();
    const normalizedText = finalMessage.toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '');

    // Categorización semántica: 'baja' (rojo), 'modificacion' (ámbar), 'info' (celeste), 'alta' (verde LCD)
    let category = 'alta';

    if (['baja', 'delete', 'deleted', 'cancel', 'cancelado', 'error', 'danger', 'rojo', 'red'].includes(rawType)) {
        category = 'baja';
    } else if (['warning', 'modificacion', 'cambio', 'proceso', 'yellow', 'amarillo', 'orange'].includes(rawType)) {
        category = 'modificacion';
    } else if (['info', 'azul', 'cyan'].includes(rawType)) {
        category = 'info';
    } else if (['alta', 'success', 'verde', 'green', 'create', 'created'].includes(rawType)) {
        category = 'alta';
    } else {
        const bajaKeywords = [
            'cancelad', 'eliminad', 'borrad', 'anulad', 'desactivad', 'baja',
            'error', 'fallo', 'fallida', 'imposible', 'no se puede', 'rechazad', 'insuficiente', 'invalido', 'incorrect'
        ];
        const modKeywords = [
            'modificad', 'actualizad', 'editad', 'guardad', 'procesando', 'cambio',
            'iniciando', 'generando', 'cargando', 'atencion', 'cuidado', 'completa'
        ];
        const infoKeywords = ['abriendo', 'saltando', 'reporte', 'informe', 'consulta'];

        if (bajaKeywords.some(kw => normalizedText.includes(kw))) {
            category = 'baja';
        } else if (modKeywords.some(kw => normalizedText.includes(kw))) {
            category = 'modificacion';
        } else if (infoKeywords.some(kw => normalizedText.includes(kw))) {
            category = 'info';
        } else {
            category = 'alta';
        }
    }

    // Configuración visual Nokia 1100 UI Kit v1.0 (Graphite + LCD)
    const solidBg = '#15181a';
    let accentColor = '#c6e24a'; // Verde LCD Nokia por defecto

    switch (category) {
        case 'baja':
            accentColor = '#a82a1d'; // --nk-bad
            break;

        case 'modificacion':
            accentColor = '#8a5200'; // --nk-warn
            break;

        case 'info':
            accentColor = '#1f4e8c'; // --nk-focus
            break;

        case 'alta':
        default:
            accentColor = '#c6e24a'; // --nk-lcd
            break;
    }

    toast.className = `nk-toast nk-toast--${category}`;
    Object.assign(toast.style, {
        background: solidBg,
        backgroundColor: solidBg,
        border: '1px solid #262a2d',
        borderLeft: `4px solid ${accentColor}`,
        color: '#f8f6f0',
        fontFamily: "'IBM Plex Sans', system-ui, -apple-system, sans-serif",
        padding: '0.65rem 1.15rem 0.65rem 0.95rem',
        borderRadius: '2px',
        display: 'flex',
        alignItems: 'center',
        gap: '0.75rem',
        boxShadow: '0 4px 16px rgba(0, 0, 0, 0.45), 0 1px 3px rgba(0, 0, 0, 0.25)',
        fontWeight: '600',
        fontSize: '0.92rem',
        position: 'relative',
        margin: '0',
        width: 'max-content',
        minWidth: '260px',
        maxWidth: 'min(520px, calc(100vw - 3rem))',
        justifyContent: 'space-between',
        opacity: '0',
        transform: 'translateX(115%)',
        transition: 'transform 0.32s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.24s ease',
        boxSizing: 'border-box',
        pointerEvents: 'auto'
    });

    // Ícono limpio nítido sin recuadros pesados
    const iconContainer = document.createElement('span');
    Object.assign(iconContainer.style, {
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        flexShrink: '0',
        lineHeight: '1'
    });

    if (category === 'alta') {
        iconContainer.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="${accentColor}" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
    } else if (category === 'baja') {
        iconContainer.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="${accentColor}" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>`;
    } else if (category === 'modificacion') {
        iconContainer.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="${accentColor}" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`;
    } else {
        iconContainer.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="${accentColor}" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>`;
    }

    // Texto seguro vía DOM (previene XSS)
    const textEl = document.createElement('span');
    Object.assign(textEl.style, {
        color: '#f8f6f0',
        lineHeight: '1.35',
        fontWeight: '600',
        fontSize: '0.92rem',
        flex: '1',
        letterSpacing: '-0.01em'
    });
    textEl.textContent = finalMessage;

    // Envoltorio de contenido
    const contentWrapper = document.createElement('div');
    Object.assign(contentWrapper.style, {
        display: 'flex',
        alignItems: 'center',
        gap: '0.65rem',
        flex: '1'
    });
    contentWrapper.appendChild(iconContainer);
    contentWrapper.appendChild(textEl);

    // Botón sutil de cierre manual
    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.setAttribute('aria-label', 'Cerrar notificación');
    Object.assign(closeBtn.style, {
        width: '20px',
        height: '20px',
        minHeight: '20px',
        padding: '0',
        marginLeft: '0.5rem',
        borderRadius: '2px',
        background: 'transparent',
        border: 'none',
        color: '#71787e',
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        cursor: 'pointer',
        flexShrink: '0',
        fontSize: '13px',
        lineHeight: '1',
        transition: 'color 0.15s ease'
    });
    closeBtn.textContent = '✕';

    closeBtn.onmouseenter = () => {
        closeBtn.style.color = '#f8f6f0';
    };
    closeBtn.onmouseleave = () => {
        closeBtn.style.color = '#71787e';
    };

    toast.appendChild(contentWrapper);
    toast.appendChild(closeBtn);

    toastContainer.appendChild(toast);
    activeToasts.push(toast);

    // Animación de entrada deslizándose desde la derecha
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            toast.style.transform = 'translateX(0)';
            toast.style.opacity = '1';
        });
    });

    let isDismissed = false;
    let autoDismissTimer = null;

    // Se vuelve a esconder deslizándose hacia la derecha
    const dismiss = (fast = false) => {
        if (isDismissed) return;
        isDismissed = true;
        if (autoDismissTimer) clearTimeout(autoDismissTimer);
        activeToasts = activeToasts.filter(t => t !== toast);

        toast.style.transition = fast === true
            ? 'transform 0.16s ease, opacity 0.14s ease'
            : 'transform 0.28s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.22s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(115%)';
        toast.style.pointerEvents = 'none';

        setTimeout(() => toast.remove(), fast === true ? 180 : 320);
    };

    // Auto-cierre con delay de 1s por defecto o duración personalizada
    const autoDelay = (typeof duration === 'number' && duration > 0) ? duration : 1000;
    const startTimer = (ms = autoDelay) => {
        if (autoDismissTimer) clearTimeout(autoDismissTimer);
        autoDismissTimer = setTimeout(dismiss, ms);
    };

    startTimer(autoDelay);

    toast.addEventListener('mouseenter', () => {
        if (autoDismissTimer) clearTimeout(autoDismissTimer);
    });
    toast.addEventListener('mouseleave', () => {
        if (!isDismissed) startTimer(1000);
    });
    closeBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        dismiss(true);
    });

    toast.dismiss = dismiss;
    return toast;
};

// Modal de confirmación limpio sin sombras 3D
window.showConfirmModal = function(title, message, confirmText = 'Confirmar', cancelText = 'Cancelar', isDestructive = false) {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        Object.assign(overlay.style, {
            position: 'fixed',
            inset: '0',
            backgroundColor: 'rgba(15, 17, 20, 0.58)',
            backdropFilter: 'blur(5px)',
            webkitBackdropFilter: 'blur(5px)',
            zIndex: '100000',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            padding: '1rem',
            opacity: '0'
        });

        const modal = document.createElement('div');
        modal.className = 'nk-dialog nk-dialog--sm';
        Object.assign(modal.style, {
            background: '#f8f6f0',
            border: '1px solid #b5af9c',
            borderTop: isDestructive ? '3px solid #a82a1d' : '3px solid #15171a',
            borderRadius: '2px',
            padding: '1.65rem',
            width: '100%',
            maxWidth: '420px',
            boxShadow: '0 8px 24px rgba(21, 23, 26, 0.28)',
            transform: 'scale(0.96) translateY(6px)',
            opacity: '0',
            fontFamily: "'IBM Plex Sans', system-ui, sans-serif"
        });

        const titleEl = document.createElement('h3');
        titleEl.textContent = title;
        Object.assign(titleEl.style, {
            margin: '0 0 0.55rem 0',
            color: '#15171a',
            fontSize: '1.2rem',
            fontWeight: '700',
            letterSpacing: '-0.015em',
            fontFamily: "'Bricolage Grotesque', system-ui, sans-serif"
        });

        const messageEl = document.createElement('p');
        messageEl.textContent = message;
        Object.assign(messageEl.style, {
            margin: '0 0 1.4rem 0',
            color: '#5c6165',
            fontSize: '0.88rem',
            lineHeight: '1.5'
        });

        const btnContainer = document.createElement('div');
        Object.assign(btnContainer.style, {
            display: 'flex',
            justifyContent: 'flex-end',
            gap: '0.65rem'
        });

        const cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.className = 'nk-btn nk-btn--sm';
        cancelBtn.textContent = cancelText;
        Object.assign(cancelBtn.style, {
            padding: '0.55rem 1.05rem',
            background: '#f8f6f0',
            border: '1px solid #d3cebf',
            color: '#15171a',
            borderRadius: '2px',
            cursor: 'pointer',
            fontSize: '0.84rem',
            fontWeight: '600',
            transition: 'background 0.15s, border-color 0.15s'
        });
        cancelBtn.onmouseover = () => {
            cancelBtn.style.background = '#e5e1d6';
            cancelBtn.style.borderColor = '#b5af9c';
        };
        cancelBtn.onmouseout = () => {
            cancelBtn.style.background = '#f8f6f0';
            cancelBtn.style.borderColor = '#d3cebf';
        };

        const confirmBtn = document.createElement('button');
        confirmBtn.type = 'button';
        confirmBtn.className = `nk-btn nk-btn--sm ${isDestructive ? 'nk-btn--danger-solid' : 'nk-btn--primary'}`;
        confirmBtn.textContent = confirmText;
        const confirmBg = isDestructive ? '#a82a1d' : '#15171a';
        const confirmColor = '#ffffff';
        Object.assign(confirmBtn.style, {
            padding: '0.55rem 1.15rem',
            background: confirmBg,
            border: `1px solid ${confirmBg}`,
            color: confirmColor,
            borderRadius: '2px',
            cursor: 'pointer',
            fontSize: '0.84rem',
            fontWeight: '600',
            transition: 'opacity 0.15s'
        });
        confirmBtn.onmouseover = () => { confirmBtn.style.opacity = '0.9'; };
        confirmBtn.onmouseout = () => { confirmBtn.style.opacity = '1'; };

        btnContainer.appendChild(cancelBtn);
        btnContainer.appendChild(confirmBtn);

        modal.appendChild(titleEl);
        modal.appendChild(messageEl);
        modal.appendChild(btnContainer);
        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        overlay.style.transition = 'opacity 0.22s ease';
        modal.style.transition = 'transform 0.28s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.22s ease';

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                overlay.style.opacity = '1';
                modal.style.opacity = '1';
                modal.style.transform = 'scale(1) translateY(0)';
            });
        });

        const close = (result) => {
            overlay.style.opacity = '0';
            modal.style.opacity = '0';
            modal.style.transform = 'scale(0.95) translateY(8px)';
            setTimeout(() => overlay.remove(), 220);
            resolve(result);
        };

        cancelBtn.onclick = () => close(false);
        confirmBtn.onclick = () => close(true);
        overlay.onclick = (e) => {
            if (e.target === overlay) close(false);
        };
    });
};

// Integración helpers UI Kit Nokia 1100 (window.NK)
window.NK = {
    toast: (msg, type = 'ok', duration = 1000) => window.showToast(msg, type, duration),
    confirm: ({ title = '¿Confirmar acción?', body = '', label = 'Confirmar', danger = false }) => 
        window.showConfirmModal(title, body, label, 'Cancelar', danger),
    modal: {
        open: function (selector) {
            const m = document.querySelector(selector);
            if (!m) return;
            m.classList.add('is-open');
            m._prevFocus = document.activeElement;
            const focusable = m.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            if (focusable) focusable.focus();
        },
        close: function (selector) {
            const m = document.querySelector(selector);
            if (!m) return;
            m.classList.remove('is-open');
            if (m._prevFocus && typeof m._prevFocus.focus === 'function') m._prevFocus.focus();
        }
    },
    busy: async function (btn, asyncFn) {
        if (!btn) return asyncFn();
        const prev = btn.innerHTML;
        btn.setAttribute('aria-busy', 'true');
        btn.disabled = true;
        try {
            return await asyncFn();
        } finally {
            btn.removeAttribute('aria-busy');
            btn.disabled = false;
            btn.innerHTML = prev;
        }
    },
    esc: function (str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
    },
    money: function (num) {
        const val = parseFloat(num) || 0;
        return '$ ' + val.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
};

document.addEventListener('click', (e) => {
    const openBtn = e.target.closest('[data-nk-open]');
    if (openBtn) {
        const target = openBtn.getAttribute('data-nk-open');
        if (target) window.NK.modal.open(target);
    }
    const closeBtn = e.target.closest('[data-nk-close]');
    if (closeBtn) {
        const modal = closeBtn.closest('.nk-modal');
        if (modal) modal.classList.remove('is-open');
    }
});
