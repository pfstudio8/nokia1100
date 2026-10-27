// framer-toaster.js
// Uses Motion (Vanilla JS version of Framer Motion) for toast notifications

// Create the toast container
const toastContainer = document.createElement('div');
toastContainer.id = 'framer-toast-container';
Object.assign(toastContainer.style, {
    position: 'fixed',
    top: '3rem',
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
    const finalMessage = friendlyMessages[message] || String(message);

    // Normalizar para detección semántica (sin tildes)
    const rawType = String(type || '').toLowerCase().trim();
    const normalizedText = finalMessage.toLowerCase()
        .normalize("NFD").replace(/[\u0300-\u036f]/g, "");

    // Categorización semántica: 'baja' (rojo), 'modificacion' (amarillo), 'info' (cyan), 'alta' (verde)
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
            'error', 'fallo', 'fallida', 'imposible', 'no se puede', 'rechazad', 'insuficiente'
        ];
        const modKeywords = [
            'modificad', 'actualizad', 'editad', 'guardad', 'procesando', 'cambio',
            'iniciando', 'generando', 'cargando', 'atencion', 'cuidado'
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

    // Configuración visual: Colores 100% sólidos, vivos y limpios (sin efecto neón, sin tonos apagados/oscuros)
    let solidBg, borderColor, icon;

    switch (category) {
        case 'baja':
            // ROJO SÓLIDO VIVO (Baja / Error / Cancelado)
            solidBg = '#b91c1c';
            borderColor = '#ef4444';
            if (normalizedText.includes('cancelad')) {
                icon = 'cancel';
            } else if (normalizedText.includes('baja') || normalizedText.includes('eliminad') || normalizedText.includes('borrad')) {
                icon = 'do_not_disturb_on';
            } else {
                icon = 'error';
            }
            break;

        case 'modificacion':
            // ÁMBAR / AMARILLO SÓLIDO VIVO (Modificación / Proceso)
            solidBg = '#b45309';
            borderColor = '#f59e0b';
            if (normalizedText.includes('procesando') || normalizedText.includes('generando') || normalizedText.includes('cargando') || normalizedText.includes('iniciando')) {
                icon = 'sync';
            } else {
                icon = 'edit_note';
            }
            break;

        case 'info':
            // CIAN / AZUL SÓLIDO VIVO (Info / Exportación)
            solidBg = '#0284c7';
            borderColor = '#38bdf8';
            icon = 'info';
            break;

        case 'alta':
        default:
            // VERDE SÓLIDO VIVO (Alta exitosa / Guardado / Completado)
            solidBg = '#047857';
            borderColor = '#10b981';
            if (normalizedText.includes('completad') || normalizedText.includes('nuevo') || normalizedText.includes('agregad')) {
                icon = 'add_circle';
            } else {
                icon = 'check_circle';
            }
            break;
    }

    Object.assign(toast.style, {
        background: solidBg,
        backgroundColor: solidBg,
        border: `1.5px solid ${borderColor}`,
        color: '#ffffff',
        padding: '0.65rem 1.25rem',
        borderRadius: '12px',
        display: 'flex',
        alignItems: 'center',
        gap: '0.65rem',
        boxShadow: '0 10px 25px -4px rgba(0, 0, 0, 0.45), 0 4px 6px -2px rgba(0, 0, 0, 0.25)',
        fontWeight: '600',
        fontSize: '0.9rem',
        position: 'absolute',
        top: '0',
        left: '50%',
        margin: '0',
        width: 'max-content',
        minWidth: '220px',
        maxWidth: '460px',
        justifyContent: 'center',
        opacity: '0',
        transform: 'translate(-50%, -20px) scale(0.9)',
        transition: 'all 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275)',
        backdropFilter: 'none',
        webkitBackdropFilter: 'none'
    });

    // Construcción segura vía DOM: el mensaje NUNCA se interpreta como HTML (previene XSS)
    const iconEl = document.createElement('span');
    iconEl.className = 'material-symbols-outlined';
    Object.assign(iconEl.style, {
        fontSize: '1.25rem', color: '#ffffff', display: 'inline-flex',
        alignItems: 'center', justifyContent: 'center'
    });
    iconEl.textContent = icon;

    const textEl = document.createElement('span');
    Object.assign(textEl.style, { color: '#ffffff', lineHeight: '1.35', fontWeight: '600' });
    textEl.textContent = finalMessage;

    toast.appendChild(iconEl);
    toast.appendChild(textEl);

    toastContainer.appendChild(toast);
    activeToasts.push(toast);

    // Función para renderizar el "Stacking" (Agrupación Sonner-style)
    const renderToasts = () => {
        const total = activeToasts.length;
        activeToasts.forEach((t, i) => {
            const indexFromNewest = total - 1 - i; // 0 = newest
            
            // Limitamos a un máximo de 4 toasts visibles
            if (indexFromNewest > 3) {
                t.style.opacity = '0';
                t.style.pointerEvents = 'none';
                return;
            }

            const scale = Math.max(1 - (indexFromNewest * 0.05), 0.85);
            const yOffset = indexFromNewest * 12; // 12px hacia abajo por cada tostada antigua
            const opacity = indexFromNewest > 2 ? 0 : 1;
            const zIndex = 999 - indexFromNewest;

            t.style.transform = `translate(-50%, ${yOffset}px) scale(${scale})`;
            t.style.opacity = opacity;
            t.style.zIndex = zIndex;
            t.style.pointerEvents = indexFromNewest === 0 ? 'auto' : 'none';
        });
    };

    // Siguiente frame, animamos y actualizamos el stack
    setTimeout(() => {
        renderToasts();
    }, 10);

    // Remover automáticamente después de 3.5 segundos o manualmente vía toast.dismiss()
    let isDismissed = false;
    const dismiss = () => {
        if (isDismissed) return;
        isDismissed = true;
        clearTimeout(autoDismissTimer);
        activeToasts = activeToasts.filter(t => t !== toast);
        renderToasts();
        
        toast.style.opacity = '0';
        toast.style.transform = 'translate(-50%, -20px) scale(0.9)';
        toast.style.pointerEvents = 'none';
        
        setTimeout(() => toast.remove(), 400);
    };

    const autoDismissTimer = setTimeout(dismiss, 3500);

    toast.dismiss = dismiss;
    return toast;
};

// Polyfill window.showConfirmModal
window.showConfirmModal = function(title, message, confirmText = 'Confirmar', cancelText = 'Cancelar', isDestructive = false) {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        Object.assign(overlay.style, {
            position: 'fixed',
            inset: '0',
            backgroundColor: 'rgba(0,0,0,0.6)',
            backdropFilter: 'blur(4px)',
            zIndex: '100000',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            opacity: '0'
        });

        const modal = document.createElement('div');
        Object.assign(modal.style, {
            background: '#263347', // Card
            border: '1px solid #374151', // Border
            borderRadius: '16px',
            padding: '2rem',
            width: '90%',
            maxWidth: '400px',
            boxShadow: '0 25px 50px -12px rgba(0,0,0,0.5)',
            transform: 'scale(0.9)',
            opacity: '0'
        });

        const titleEl = document.createElement('h3');
        titleEl.textContent = title;
        Object.assign(titleEl.style, {
            margin: '0 0 1rem 0',
            color: '#FAFAFA',
            fontSize: '1.25rem',
            fontFamily: 'Outfit, sans-serif'
        });

        const messageEl = document.createElement('p');
        messageEl.textContent = message;
        Object.assign(messageEl.style, {
            margin: '0 0 2rem 0',
            color: '#9CA3AF',
            fontSize: '0.9rem',
            lineHeight: '1.5'
        });

        const btnContainer = document.createElement('div');
        Object.assign(btnContainer.style, {
            display: 'flex',
            justifyContent: 'flex-end',
            gap: '1rem'
        });

        const cancelBtn = document.createElement('button');
        cancelBtn.textContent = cancelText;
        Object.assign(cancelBtn.style, {
            padding: '0.5rem 1rem',
            background: 'transparent',
            border: '1px solid #374151',
            color: '#FAFAFA',
            borderRadius: '8px',
            cursor: 'pointer',
            fontSize: '0.9rem',
            transition: 'all 0.2s'
        });
        cancelBtn.onmouseover = () => cancelBtn.style.background = 'rgba(255,255,255,0.05)';
        cancelBtn.onmouseout = () => cancelBtn.style.background = 'transparent';

        const confirmBtn = document.createElement('button');
        confirmBtn.textContent = confirmText;
        const confirmBg = isDestructive ? '#F87171' : '#0EA5A0';
        Object.assign(confirmBtn.style, {
            padding: '0.5rem 1rem',
            background: confirmBg,
            border: 'none',
            color: isDestructive ? '#000' : '#FFF',
            borderRadius: '8px',
            cursor: 'pointer',
            fontSize: '0.9rem',
            fontWeight: '600',
            transition: 'all 0.2s'
        });
        confirmBtn.onmouseover = () => confirmBtn.style.opacity = '0.9';
        confirmBtn.onmouseout = () => confirmBtn.style.opacity = '1';

        btnContainer.appendChild(cancelBtn);
        btnContainer.appendChild(confirmBtn);

        modal.appendChild(titleEl);
        modal.appendChild(messageEl);
        modal.appendChild(btnContainer);
        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        const animateIn = () => {
            if (window.Motion && window.Motion.animate) {
                window.Motion.animate(overlay, { opacity: [0, 1] }, { duration: 0.3 });
                window.Motion.animate(modal, 
                    { opacity: [0, 1], scale: [0.9, 1] },
                    { duration: 0.4, easing: window.Motion.spring({ stiffness: 300, damping: 25 }) }
                );
            } else {
                overlay.style.transition = 'opacity 0.3s';
                modal.style.transition = 'all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                setTimeout(() => {
                    overlay.style.opacity = '1';
                    modal.style.opacity = '1';
                    modal.style.transform = 'scale(1)';
                }, 10);
            }
        };

        const close = (result) => {
            if (window.Motion && window.Motion.animate) {
                window.Motion.animate(overlay, { opacity: 0 }, { duration: 0.2 });
                window.Motion.animate(modal, { opacity: 0, scale: 0.9 }, { duration: 0.2 })
                    .finished.then(() => overlay.remove());
            } else {
                overlay.style.opacity = '0';
                modal.style.opacity = '0';
                modal.style.transform = 'scale(0.9)';
                setTimeout(() => overlay.remove(), 200);
            }
            resolve(result);
        };

        cancelBtn.onclick = () => close(false);
        confirmBtn.onclick = () => close(true);

        animateIn();
    });
};
