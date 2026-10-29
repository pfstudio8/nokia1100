// framer-toaster.js
// Sistema unificado de notificaciones Toast y modales de confirmación (Nokia 1100 Design System)

const toastContainer = document.createElement('div');
toastContainer.id = 'framer-toast-container';
Object.assign(toastContainer.style, {
    position: 'fixed',
    top: '1.5rem',
    left: '0',
    right: '0',
    width: '100%',
    zIndex: '99999',
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    pointerEvents: 'none'
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        document.body.appendChild(toastContainer);
    });
} else {
    document.body.appendChild(toastContainer);
}

let activeToasts = [];

window.showToast = function(message, type = 'success') {
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

    // Configuración visual moderna sin bordes 3D
    const solidBg = '#16191c';
    let accentColor = '#c6e24a';
    let accentBg = 'rgba(198, 226, 74, 0.14)';
    let accentBorder = 'rgba(198, 226, 74, 0.28)';
    let icon = 'check_circle';

    switch (category) {
        case 'baja':
            accentColor = '#ff8a7a';
            accentBg = 'rgba(255, 138, 122, 0.14)';
            accentBorder = 'rgba(255, 138, 122, 0.28)';
            if (normalizedText.includes('cancelad')) {
                icon = 'cancel';
            } else if (normalizedText.includes('baja') || normalizedText.includes('eliminad') || normalizedText.includes('borrad')) {
                icon = 'delete_sweep';
            } else {
                icon = 'error';
            }
            break;

        case 'modificacion':
            accentColor = '#f2b84b';
            accentBg = 'rgba(242, 184, 75, 0.14)';
            accentBorder = 'rgba(242, 184, 75, 0.28)';
            if (normalizedText.includes('procesando') || normalizedText.includes('generando') || normalizedText.includes('cargando') || normalizedText.includes('iniciando')) {
                icon = 'sync';
            } else if (normalizedText.includes('completa') || normalizedText.includes('ingresa') || normalizedText.includes('por favor')) {
                icon = 'warning';
            } else {
                icon = 'edit_note';
            }
            break;

        case 'info':
            accentColor = '#5cc8ff';
            accentBg = 'rgba(92, 200, 255, 0.14)';
            accentBorder = 'rgba(92, 200, 255, 0.28)';
            icon = 'info';
            break;

        case 'alta':
        default:
            accentColor = '#c6e24a';
            accentBg = 'rgba(198, 226, 74, 0.14)';
            accentBorder = 'rgba(198, 226, 74, 0.28)';
            if (normalizedText.includes('completad') || normalizedText.includes('nuevo') || normalizedText.includes('agregad') || normalizedText.includes('anadid')) {
                icon = 'task_alt';
            } else {
                icon = 'check_circle';
            }
            break;
    }

    Object.assign(toast.style, {
        background: solidBg,
        backgroundColor: solidBg,
        border: '1px solid rgba(255, 255, 255, 0.09)',
        color: '#f3f1ea',
        fontFamily: "'IBM Plex Sans', system-ui, -apple-system, sans-serif",
        padding: '0.55rem 0.7rem 0.55rem 0.6rem',
        borderRadius: '14px',
        display: 'flex',
        alignItems: 'center',
        gap: '0.7rem',
        boxShadow: '0 14px 34px -6px rgba(15, 17, 20, 0.28), 0 4px 12px -2px rgba(15, 17, 20, 0.14)',
        fontWeight: '500',
        fontSize: '0.86rem',
        position: 'absolute',
        top: '0',
        left: '50%',
        margin: '0',
        width: 'max-content',
        minWidth: '240px',
        maxWidth: 'min(460px, calc(100vw - 2rem))',
        justifyContent: 'space-between',
        opacity: '0',
        transform: 'translate(-50%, -16px) scale(0.95)',
        transition: 'transform 0.34s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.26s ease',
        boxSizing: 'border-box'
    });

    // Contenedor de ícono con badge redondeado suave
    const iconBadge = document.createElement('span');
    Object.assign(iconBadge.style, {
        width: '30px',
        height: '30px',
        borderRadius: '9px',
        background: accentBg,
        border: `1px solid ${accentBorder}`,
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        flexShrink: '0'
    });

    const iconEl = document.createElement('span');
    iconEl.className = 'material-symbols-outlined';
    Object.assign(iconEl.style, {
        fontSize: '1.1rem',
        color: accentColor,
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        lineHeight: '1'
    });
    iconEl.textContent = icon;
    iconBadge.appendChild(iconEl);

    // Texto seguro vía DOM (previene XSS)
    const textEl = document.createElement('span');
    Object.assign(textEl.style, {
        color: '#f3f1ea',
        lineHeight: '1.38',
        fontWeight: '500',
        flex: '1',
        letterSpacing: '-0.005em'
    });
    textEl.textContent = finalMessage;

    // Botón sutil de cierre manual
    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.setAttribute('aria-label', 'Cerrar notificación');
    Object.assign(closeBtn.style, {
        width: '24px',
        height: '24px',
        minHeight: '24px',
        padding: '0',
        marginLeft: '0.15rem',
        borderRadius: '7px',
        background: 'transparent',
        border: 'none',
        color: '#858c93',
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        cursor: 'pointer',
        flexShrink: '0',
        transition: 'background 0.15s ease, color 0.15s ease'
    });
    const closeIcon = document.createElement('span');
    closeIcon.className = 'material-symbols-outlined';
    Object.assign(closeIcon.style, { fontSize: '15px', lineHeight: '1' });
    closeIcon.textContent = 'close';
    closeBtn.appendChild(closeIcon);

    closeBtn.onmouseenter = () => {
        closeBtn.style.background = 'rgba(255, 255, 255, 0.08)';
        closeBtn.style.color = '#f3f1ea';
    };
    closeBtn.onmouseleave = () => {
        closeBtn.style.background = 'transparent';
        closeBtn.style.color = '#858c93';
    };

    toast.appendChild(iconBadge);
    toast.appendChild(textEl);
    toast.appendChild(closeBtn);

    toastContainer.appendChild(toast);
    activeToasts.push(toast);

    // Agrupación apilada limpia (Sonner-style)
    const renderToasts = () => {
        const total = activeToasts.length;
        activeToasts.forEach((t, i) => {
            const indexFromNewest = total - 1 - i; // 0 = más reciente

            if (indexFromNewest > 3) {
                t.style.opacity = '0';
                t.style.pointerEvents = 'none';
                return;
            }

            const scale = Math.max(1 - (indexFromNewest * 0.045), 0.86);
            const yOffset = indexFromNewest * 11;
            const opacity = indexFromNewest > 2 ? 0 : (1 - indexFromNewest * 0.12);
            const zIndex = 999 - indexFromNewest;

            t.style.transform = `translate(-50%, ${yOffset}px) scale(${scale})`;
            t.style.opacity = String(opacity);
            t.style.zIndex = String(zIndex);
            t.style.pointerEvents = indexFromNewest === 0 ? 'auto' : 'none';
        });
    };

    requestAnimationFrame(() => {
        requestAnimationFrame(renderToasts);
    });

    let isDismissed = false;
    let autoDismissTimer = null;

    const dismiss = (fast = false) => {
        if (isDismissed) return;
        isDismissed = true;
        if (autoDismissTimer) clearTimeout(autoDismissTimer);
        activeToasts = activeToasts.filter(t => t !== toast);
        renderToasts();

        if (fast === true) {
            toast.style.transition = 'opacity 0.11s ease, transform 0.11s ease';
        }
        toast.style.opacity = '0';
        toast.style.transform = 'translate(-50%, -12px) scale(0.96)';
        toast.style.pointerEvents = 'none';

        setTimeout(() => toast.remove(), fast === true ? 120 : 360);
    };

    const startTimer = (ms = 3600) => {
        if (autoDismissTimer) clearTimeout(autoDismissTimer);
        autoDismissTimer = setTimeout(dismiss, ms);
    };

    startTimer(3600);

    toast.addEventListener('mouseenter', () => {
        if (autoDismissTimer) clearTimeout(autoDismissTimer);
    });
    toast.addEventListener('mouseleave', () => {
        if (!isDismissed) startTimer(1800);
    });
    closeBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        dismiss();
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
        Object.assign(modal.style, {
            background: '#ffffff',
            border: '1px solid rgba(21, 23, 26, 0.08)',
            borderRadius: '16px',
            padding: '1.65rem',
            width: '100%',
            maxWidth: '420px',
            boxShadow: '0 24px 54px -12px rgba(15, 17, 20, 0.28)',
            transform: 'scale(0.95) translateY(8px)',
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
        cancelBtn.textContent = cancelText;
        Object.assign(cancelBtn.style, {
            padding: '0.55rem 1.05rem',
            background: '#f8f6f0',
            border: '1px solid #d3cebf',
            color: '#15171a',
            borderRadius: '8px',
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
        confirmBtn.textContent = confirmText;
        const confirmBg = isDestructive ? '#a82a1d' : '#15171a';
        const confirmColor = '#ffffff';
        Object.assign(confirmBtn.style, {
            padding: '0.55rem 1.15rem',
            background: confirmBg,
            border: `1px solid ${confirmBg}`,
            color: confirmColor,
            borderRadius: '8px',
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
