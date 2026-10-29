// export-helper.js - Nokia 1100 System
// Helper unificado para exportaciones Excel (.xlsx) y PDF (.pdf) del lado del cliente.
// Incluye animación de descarga en el mismo lugar (0 desplazamiento), delay >= 1s y cancelación en rojo.

const EXPORT_DOWNLOADING_ICON =
    '<svg viewBox="0 0 24 24" aria-hidden="true">' +
        '<g class="exp-arrow"><path d="M12 3v12M7 10l5 5 5-5"/></g>' +
        '<path d="M4 21h16"/>' +
    '</svg>';

const EXPORT_CANCEL_ICON =
    '<svg viewBox="0 0 24 24" aria-hidden="true">' +
        '<path class="exp-x-1" d="M17 7 7 17"/>' +
        '<path class="exp-x-2" d="M7 7l10 10"/>' +
    '</svg>';

const EXPORT_SPIN_ICON =
    '<svg class="is-spin" viewBox="0 0 24 24" aria-hidden="true">' +
        '<path d="M21 12a9 9 0 1 1-3-6.7"/>' +
        '<path d="M21 3v6h-6"/>' +
    '</svg>';

const EXPORT_CHECK_ICON =
    '<svg viewBox="0 0 24 24" aria-hidden="true">' +
        '<path class="exp-check-path" d="M5 12.5l4.5 4.5L19 7.5"/>' +
    '</svg>';

function escapeExportLabel(str) {
    return String(str || '').replace(/[&<>"']/g, ch => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    }[ch]));
}

function buildExportInner(iconSvg, labelText, withProgress = false) {
    const progressLayers = withProgress
        ? '<span class="exp-fill" aria-hidden="true"></span><span class="exp-bar" aria-hidden="true"></span>'
        : '';
    return progressLayers + iconSvg + '<span class="exp-label">' + escapeExportLabel(labelText) + '</span>';
}

function loadScript(url) {
    return new Promise((resolve, reject) => {
        if (document.querySelector(`script[src="${url}"]`)) {
            resolve();
            return;
        }
        const script = document.createElement('script');
        script.src = url;
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });
}
window.loadScript = loadScript;

function resolveExportButton(btnElement) {
    let btn = btnElement;
    if (!btn && typeof window !== 'undefined' && window.event) {
        btn = window.event.currentTarget || (window.event.target && typeof window.event.target.closest === 'function' ? window.event.target.closest('button') : null);
    }
    if (btn && typeof btn.closest === 'function') {
        btn = btn.closest('button') || btn;
    }
    return btn || null;
}

function restoreExportButton(btn) {
    if (!btn) return;
    if (btn._restoreTimerId) {
        clearTimeout(btn._restoreTimerId);
        btn._restoreTimerId = null;
    }
    if (typeof btn._originalHtml === 'string') {
        btn.innerHTML = btn._originalHtml;
    }
    if (btn._hadOriginalTitle) {
        btn.setAttribute('title', btn._originalTitle || '');
    } else if (btn._originalTitle !== undefined) {
        btn.removeAttribute('title');
    }
    btn.classList.remove('is-downloading', 'is-canceled', 'is-processing', 'is-completed');
    btn.style.removeProperty('--export-w');
    btn.style.removeProperty('--exp-ms');
    btn.style.removeProperty('--exp-elapsed');
    btn.style.pointerEvents = '';
    delete btn._originalHtml;
    delete btn._originalLabel;
    delete btn._originalTitle;
    delete btn._hadOriginalTitle;
    delete btn._exportStartTime;
    delete btn._exportDelayMs;
    delete btn._exportTimerId;
    delete btn._cancelResolve;
    delete btn._isProcessingFile;
    delete btn._initToast;
}

/**
 * Ejecuta una acción de exportación tras un delay mínimo (>= 1s, por defecto 1400ms)
 * manteniendo el ancho exacto del botón para no desplazar ningún elemento vecino.
 * Un segundo clic durante la descarga cancela la acción con animación y tono rojo.
 */
window.runCancelableExport = async function runCancelableExport(btnElement, performExportFn, options = {}) {
    const btn = resolveExportButton(btnElement);
    const delayMs = Math.max(1000, Number(options.delayMs) || 1400);

    // 1. Segundo clic durante la espera: cancelar inmediatamente sin desplazar botones
    if (btn && btn._exportTimerId) {
        clearTimeout(btn._exportTimerId);
        btn._exportTimerId = null;

        if (typeof btn._cancelResolve === 'function') {
            btn._cancelResolve(false);
            btn._cancelResolve = null;
        }

        if (btn._initToast && typeof btn._initToast.dismiss === 'function') {
            btn._initToast.dismiss(true);
            btn._initToast = null;
        }

        const elapsed = performance.now() - (btn._exportStartTime || performance.now());
        const ratio = Math.min(1, Math.max(0.22, elapsed / (btn._exportDelayMs || delayMs)));
        const label = btn._originalLabel || 'Exportar';

        btn.style.setProperty('--exp-elapsed', ratio.toFixed(3));
        btn.classList.remove('is-downloading', 'is-processing', 'is-completed');
        btn.classList.add('is-canceled');
        btn.innerHTML = buildExportInner(EXPORT_CANCEL_ICON, label, true);
        btn.style.pointerEvents = 'none';

        if (typeof showToast === 'function') {
            showToast('Exportación cancelada', 'baja');
        }

        btn._restoreTimerId = setTimeout(() => {
            restoreExportButton(btn);
        }, 1050);

        return false;
    }

    // Ignorar clics mientras se genera el archivo o termina la animación final
    if (btn && (btn.disabled || btn._isProcessingFile || btn.classList.contains('is-canceled') || btn.classList.contains('is-completed'))) {
        return false;
    }

    // 2. Primer clic: fijar ancho exacto del botón e iniciar animación de descarga (>= 1s)
    if (btn) {
        const rect = btn.getBoundingClientRect();
        if (rect.width > 0) {
            btn.style.setProperty('--export-w', `${rect.width.toFixed(2)}px`);
        }
        btn.style.setProperty('--exp-ms', `${delayMs}ms`);

        if (typeof btn._originalHtml !== 'string') {
            btn._originalHtml = btn.innerHTML;
            btn._originalLabel = (btn.textContent || '').replace(/\s+/g, ' ').trim() || 'Exportar';
            btn._hadOriginalTitle = btn.hasAttribute('title');
            btn._originalTitle = btn.getAttribute('title');
        }

        btn._exportStartTime = performance.now();
        btn._exportDelayMs = delayMs;
        btn.classList.remove('is-canceled', 'is-processing', 'is-completed');
        btn.classList.add('is-downloading');
        btn.setAttribute('title', 'Clic nuevamente para cancelar la descarga');
        btn.innerHTML = buildExportInner(EXPORT_DOWNLOADING_ICON, btn._originalLabel, true);
    }

    if (typeof showToast === 'function') {
        const toast = showToast('Preparando descarga... Clic de nuevo para cancelar', 'info');
        if (btn) {
            btn._initToast = toast;
        }
    }

    const shouldProceed = await new Promise((resolve) => {
        if (btn) {
            btn._cancelResolve = resolve;
            btn._exportTimerId = setTimeout(() => {
                btn._exportTimerId = null;
                btn._cancelResolve = null;
                resolve(true);
            }, delayMs);
        } else {
            setTimeout(() => resolve(true), delayMs);
        }
    });

    if (!shouldProceed) {
        return false;
    }

    // 3. Ejecutar generación y descarga tras cumplirse el delay sin cancelación
    const label = (btn && btn._originalLabel) ? btn._originalLabel : 'Exportar';
    if (btn) {
        btn._isProcessingFile = true;
        btn.classList.remove('is-downloading');
        btn.classList.add('is-processing');
        btn.style.pointerEvents = 'none';
        btn.innerHTML = buildExportInner(EXPORT_SPIN_ICON, label, true);
    }

    const dismissInitToast = () => {
        if (btn && btn._initToast && typeof btn._initToast.dismiss === 'function') {
            btn._initToast.dismiss(true);
            btn._initToast = null;
        }
    };

    try {
        await performExportFn({ dismissInitToast });
        dismissInitToast();

        if (btn) {
            btn.classList.remove('is-processing', 'is-downloading');
            btn.classList.add('is-completed');
            btn.innerHTML = buildExportInner(EXPORT_CHECK_ICON, label, false);
            await new Promise(r => setTimeout(r, 650));
        }
        return true;
    } finally {
        restoreExportButton(btn);
    }
};

// Helper interno para clonar y limpiar tablas antes de exportar
function getCleanTableClone(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return null;

    const clone = table.cloneNode(true);
    // Remover filas de sub-historial, detalles expandibles, vacíos y elementos ocultos por búsqueda
    clone.querySelectorAll('.history-row, .detail-row, .js-no-results, .no-export, .sys-search-hidden').forEach(el => el.remove());
    // Incluir todas las páginas de la tabla en la exportación
    clone.querySelectorAll('.sys-page-hidden').forEach(el => el.classList.remove('sys-page-hidden'));

    // Detectar y remover columna de acciones
    const headers = clone.querySelectorAll('thead th');
    let actionsColIndex = -1;
    headers.forEach((th, idx) => {
        const txt = th.innerText.toLowerCase().trim();
        if (txt === 'acciones' || txt === 'acción' || txt === 'accion' || th.classList.contains('no-export')) {
            actionsColIndex = idx;
            th.remove();
        }
    });

    const rows = clone.querySelectorAll('tbody tr');
    rows.forEach(tr => {
        const cells = tr.querySelectorAll('td');
        if (actionsColIndex !== -1 && cells[actionsColIndex]) {
            cells[actionsColIndex].remove();
        }
    });

    return clone;
}

// Exportar a Excel (.xlsx) con >= 1s de delay cancelable
async function exportTableToExcel(tableId, filename, btnElement) {
    const btn = resolveExportButton(btnElement);
    return window.runCancelableExport(btn, async ({ dismissInitToast }) => {
        try {
            if (typeof XLSX === 'undefined') {
                await loadScript('https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js');
            }

            const clone = getCleanTableClone(tableId);
            if (!clone) {
                throw new Error('Tabla no encontrada');
            }

            // Fila de título para el archivo Excel
            const titleRow = document.createElement('tr');
            const titleTd = document.createElement('th');
            const colCount = clone.querySelector('thead tr') ? clone.querySelector('thead tr').children.length : 5;
            titleTd.colSpan = colCount > 0 ? colCount : 5;
            titleTd.innerText = `SISTEMA NOKIA 1100 - REPORTE DE ${(filename || 'DATOS').toUpperCase()} - ${new Date().toLocaleDateString()}`;
            titleRow.appendChild(titleTd);
            if (clone.querySelector('thead')) {
                clone.querySelector('thead').prepend(titleRow);
            }

            const wb = XLSX.utils.table_to_book(clone, { sheet: 'Datos' });
            const dateStr = new Date().toISOString().slice(0, 10);
            XLSX.writeFile(wb, `${filename || 'exportacion'}_${dateStr}.xlsx`);

            dismissInitToast();
            if (typeof showToast === 'function') {
                showToast('Excel descargado con éxito', 'success');
            }
        } catch (e) {
            console.error('Error al exportar Excel:', e);
            dismissInitToast();
            if (typeof showToast === 'function') {
                showToast('Error al exportar a Excel', 'error');
            }
        }
    });
}

// Exportar a Informe PDF (.pdf) con >= 1s de delay cancelable
async function exportTableToPDF(tableId, title, filename, btnElement) {
    const btn = resolveExportButton(btnElement);
    return window.runCancelableExport(btn, async ({ dismissInitToast }) => {
        try {
            if (!window.jspdf) {
                await loadScript('https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js');
            }
            if (!window.jspdf?.jsPDF?.prototype?.autoTable && !window.autoTable) {
                await loadScript('https://cdn.jsdelivr.net/npm/jspdf-autotable@3.5.29/dist/jspdf.plugin.autotable.min.js');
            }

            const table = document.getElementById(tableId);
            if (!table) {
                throw new Error('Tabla no encontrada');
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'mm', 'a4');

            // Cabecera estilizada
            doc.setFillColor(255, 255, 255);
            doc.rect(0, 0, 210, 30, 'F');

            doc.setTextColor(17, 24, 39);
            doc.setFontSize(22);
            doc.setFont('helvetica', 'bold');
            doc.text('NOKIA 1100', 15, 20);

            doc.setTextColor(107, 114, 128);
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.text('SISTEMA DE GESTIÓN', 15, 26);

            doc.setTextColor(55, 65, 81);
            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.text((title || 'REPORTE').toUpperCase(), 195, 20, { align: 'right' });

            doc.setFontSize(8);
            doc.setTextColor(156, 163, 175);
            doc.text('Generado: ' + new Date().toLocaleString(), 195, 26, { align: 'right' });

            const headers = [];
            const body = [];

            const ths = table.querySelectorAll('thead th');
            let actionsColIndex = -1;
            ths.forEach((th, idx) => {
                const txt = th.innerText.toLowerCase().trim();
                if (txt === 'acciones' || txt === 'acción' || txt === 'accion' || th.classList.contains('no-export')) {
                    actionsColIndex = idx;
                } else {
                    headers.push(th.innerText.trim());
                }
            });

            const trs = table.querySelectorAll('tbody tr:not(.history-row):not(.detail-row):not(.js-no-results):not(.sys-search-hidden)');
            trs.forEach(tr => {
                const rowData = [];
                const tds = tr.querySelectorAll('td');
                if (tds.length === 0) return;

                if (tds.length === 1 && (tr.textContent || '').toLowerCase().includes('no hay')) return;

                tds.forEach((td, idx) => {
                    if (idx !== actionsColIndex) {
                        let text = (td.innerText || td.textContent || '').trim().replace(/\s+/g, ' ');
                        if (text.includes('▼') || text.includes('▲')) {
                            text = text.replace(/[▼▲]/g, '').trim();
                        }
                        rowData.push(text);
                    }
                });
                if (rowData.length > 0) {
                    body.push(rowData);
                }
            });

            doc.autoTable({
                head: [headers],
                body: body,
                startY: 38,
                theme: 'grid',
                styles: {
                    fillColor: [255, 255, 255],
                    textColor: [55, 65, 81],
                    lineColor: [229, 231, 235],
                    fontSize: 9,
                    font: 'helvetica',
                    cellPadding: 4
                },
                headStyles: {
                    fillColor: [243, 244, 246],
                    textColor: [17, 24, 39],
                    fontStyle: 'bold',
                    lineColor: [209, 213, 219],
                    lineWidth: 0.1
                },
                alternateRowStyles: {
                    fillColor: [250, 250, 250]
                },
                margin: { left: 15, right: 15 }
            });

            const dateStr = new Date().toISOString().slice(0, 10);
            doc.save(`${filename || 'reporte'}_${dateStr}.pdf`);

            dismissInitToast();
            if (typeof showToast === 'function') {
                showToast('PDF descargado con éxito', 'success');
            }
        } catch (e) {
            console.error('Error al exportar PDF:', e);
            dismissInitToast();
            if (typeof showToast === 'function') {
                showToast('Error al exportar a PDF', 'error');
            }
        }
    });
}
