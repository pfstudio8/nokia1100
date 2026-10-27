// export-helper.js - Nokia 1100 System
// Helper para exportar cualquier tabla HTML a Excel (.xlsx) y PDF (.pdf) del lado del cliente.
// Incluye delay cancelable de 1s y notificación de cancelación.

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

// Helper interno para clonar y limpiar tablas antes de exportar
function getCleanTableClone(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return null;

    const clone = table.cloneNode(true);
    // Remover filas de sub-historial y elementos con clase no-export
    clone.querySelectorAll('.history-row, .no-export').forEach(el => el.remove());

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

// Exportar a Excel (.xlsx) con 1s de delay cancelable
async function exportTableToExcel(tableId, filename, btnElement) {
    let btn = btnElement;
    if (!btn && typeof window !== 'undefined' && window.event) {
        btn = window.event.currentTarget || (window.event.target ? window.event.target.closest('button') : null);
    }
    if (btn && typeof btn.closest === 'function') {
        btn = btn.closest('button') || btn;
    }

    // 1. Si ya tiene un temporizador activo (se presionó por segunda vez para cancelar):
    if (btn && btn._exportTimerId) {
        clearTimeout(btn._exportTimerId);
        btn._exportTimerId = null;

        if (typeof btn._cancelResolve === 'function') {
            btn._cancelResolve(false);
            btn._cancelResolve = null;
        }

        if (btn._initToast && typeof btn._initToast.dismiss === 'function') {
            btn._initToast.dismiss();
            btn._initToast = null;
        }

        btn.innerHTML = '<span class="material-symbols-outlined text-[16px]">cancel</span> Cancelado';
        btn.style.pointerEvents = 'none';

        if (typeof showToast === 'function') {
            showToast('Exportación cancelada', 'baja');
        }

        setTimeout(() => {
            if (btn && btn._originalHtml) {
                btn.innerHTML = btn._originalHtml;
            }
            if (btn) {
                btn.style.pointerEvents = 'auto';
                delete btn._originalHtml;
                delete btn._exportTimerId;
                delete btn._cancelResolve;
                delete btn._isProcessingFile;
                delete btn._initToast;
            }
        }, 1000);

        return;
    }

    // Si ya está procesando la descarga final en el navegador, ignorar
    if (btn && (btn.disabled || btn._isProcessingFile)) {
        return;
    }

    // 2. Primer clic: guardar el estado original del botón
    if (btn) {
        if (!btn._originalHtml) {
            btn._originalHtml = btn.innerHTML;
        }
        btn.innerHTML = '<span class="material-symbols-outlined text-[16px] spin" style="animation: spin 1s linear infinite;">autorenew</span> Cancelar...';
    }

    // Notificación inmediata al presionar el botón de exportación
    if (typeof showToast === 'function') {
        const toast = showToast('Iniciando exportación...', 'info');
        if (btn) {
            btn._initToast = toast;
        }
    }

    // 3. Esperar 1 segundo (1000 ms) permitiendo que un nuevo clic cancele
    const shouldProceed = await new Promise((resolve) => {
        if (btn) {
            btn._cancelResolve = resolve;
            btn._exportTimerId = setTimeout(() => {
                btn._exportTimerId = null;
                btn._cancelResolve = null;
                resolve(true);
            }, 1000);
        } else {
            resolve(true);
        }
    });

    // Si fue cancelado por el usuario en esos 1.5s, no continuar
    if (!shouldProceed) {
        return;
    }

    // 4. Iniciar la generación y descarga
    if (btn) {
        btn._isProcessingFile = true;
        btn.style.pointerEvents = 'none';
        btn.innerHTML = '<span class="material-symbols-outlined text-[16px] spin" style="animation: spin 1s linear infinite;">autorenew</span> Generando...';
    }

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

        const wb = XLSX.utils.table_to_book(clone, { sheet: "Datos" });
        const dateStr = new Date().toISOString().slice(0, 10);
        XLSX.writeFile(wb, `${filename || 'exportacion'}_${dateStr}.xlsx`);

        if (btn && btn._initToast && typeof btn._initToast.dismiss === 'function') {
            btn._initToast.dismiss();
            btn._initToast = null;
        }

        if (typeof showToast === 'function') {
            showToast('Excel descargado con éxito', 'success');
        }
    } catch (e) {
        console.error('Error al exportar Excel:', e);
        if (btn && btn._initToast && typeof btn._initToast.dismiss === 'function') {
            btn._initToast.dismiss();
            btn._initToast = null;
        }
        if (typeof showToast === 'function') {
            showToast('Error al exportar a Excel', 'error');
        }
    } finally {
        if (btn) {
            if (btn._originalHtml) {
                btn.innerHTML = btn._originalHtml;
            }
            btn.style.pointerEvents = 'auto';
            delete btn._originalHtml;
            delete btn._exportTimerId;
            delete btn._cancelResolve;
            delete btn._isProcessingFile;
            delete btn._initToast;
        }
    }
}

// Exportar a Informe PDF (.pdf) con 1s de delay cancelable
async function exportTableToPDF(tableId, title, filename, btnElement) {
    let btn = btnElement;
    if (!btn && typeof window !== 'undefined' && window.event) {
        btn = window.event.currentTarget || (window.event.target ? window.event.target.closest('button') : null);
    }
    if (btn && typeof btn.closest === 'function') {
        btn = btn.closest('button') || btn;
    }

    // 1. Si ya tiene un temporizador activo (se presionó por segunda vez para cancelar):
    if (btn && btn._exportTimerId) {
        clearTimeout(btn._exportTimerId);
        btn._exportTimerId = null;

        if (typeof btn._cancelResolve === 'function') {
            btn._cancelResolve(false);
            btn._cancelResolve = null;
        }

        if (btn._initToast && typeof btn._initToast.dismiss === 'function') {
            btn._initToast.dismiss();
            btn._initToast = null;
        }

        btn.innerHTML = '<span class="material-symbols-outlined text-[16px]">cancel</span> Cancelado';
        btn.style.pointerEvents = 'none';

        if (typeof showToast === 'function') {
            showToast('Exportación cancelada', 'baja');
        }

        setTimeout(() => {
            if (btn && btn._originalHtml) {
                btn.innerHTML = btn._originalHtml;
            }
            if (btn) {
                btn.style.pointerEvents = 'auto';
                delete btn._originalHtml;
                delete btn._exportTimerId;
                delete btn._cancelResolve;
                delete btn._isProcessingFile;
                delete btn._initToast;
            }
        }, 1000);

        return;
    }

    // Si ya está procesando la descarga final en el navegador, ignorar
    if (btn && (btn.disabled || btn._isProcessingFile)) {
        return;
    }

    // 2. Primer clic: guardar el estado original del botón
    if (btn) {
        if (!btn._originalHtml) {
            btn._originalHtml = btn.innerHTML;
        }
        btn.innerHTML = '<span class="material-symbols-outlined text-[16px] spin" style="animation: spin 1s linear infinite;">autorenew</span> Cancelar...';
    }

    // Notificación inmediata al presionar el botón de exportación
    if (typeof showToast === 'function') {
        const toast = showToast('Iniciando exportación...', 'info');
        if (btn) {
            btn._initToast = toast;
        }
    }

    // 3. Esperar 1 segundo (1000 ms) permitiendo que un nuevo clic cancele
    const shouldProceed = await new Promise((resolve) => {
        if (btn) {
            btn._cancelResolve = resolve;
            btn._exportTimerId = setTimeout(() => {
                btn._exportTimerId = null;
                btn._cancelResolve = null;
                resolve(true);
            }, 1000);
        } else {
            resolve(true);
        }
    });

    // Si fue cancelado por el usuario en esos 1.5s, no continuar
    if (!shouldProceed) {
        return;
    }

    // 4. Iniciar la generación y descarga
    if (btn) {
        btn._isProcessingFile = true;
        btn.style.pointerEvents = 'none';
        btn.innerHTML = '<span class="material-symbols-outlined text-[16px] spin" style="animation: spin 1s linear infinite;">autorenew</span> Generando...';
    }

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
        doc.setFont("helvetica", "bold");
        doc.text("NOKIA 1100", 15, 20);

        doc.setTextColor(107, 114, 128);
        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        doc.text("SISTEMA DE GESTIÓN", 15, 26);

        doc.setTextColor(55, 65, 81);
        doc.setFontSize(14);
        doc.setFont("helvetica", "bold");
        doc.text((title || 'REPORTE').toUpperCase(), 195, 20, { align: 'right' });

        doc.setFontSize(8);
        doc.setTextColor(156, 163, 175);
        doc.text("Generado: " + new Date().toLocaleString(), 195, 26, { align: 'right' });

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

        const trs = table.querySelectorAll('tbody tr:not(.history-row)');
        trs.forEach(tr => {
            const rowData = [];
            const tds = tr.querySelectorAll('td');
            if (tds.length === 0) return;

            if (tds.length === 1 && tr.innerText.toLowerCase().includes('no hay')) return;

            tds.forEach((td, idx) => {
                if (idx !== actionsColIndex) {
                    let text = td.innerText.trim().replace(/\s+/g, ' ');
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
                font: "helvetica",
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

        if (btn && btn._initToast && typeof btn._initToast.dismiss === 'function') {
            btn._initToast.dismiss();
            btn._initToast = null;
        }

        if (typeof showToast === 'function') {
            showToast('PDF descargado con éxito', 'success');
        }
    } catch (e) {
        console.error('Error al exportar PDF:', e);
        if (btn && btn._initToast && typeof btn._initToast.dismiss === 'function') {
            btn._initToast.dismiss();
            btn._initToast = null;
        }
        if (typeof showToast === 'function') {
            showToast('Error al exportar a PDF', 'error');
        }
    } finally {
        if (btn) {
            if (btn._originalHtml) {
                btn.innerHTML = btn._originalHtml;
            }
            btn.style.pointerEvents = 'auto';
            delete btn._originalHtml;
            delete btn._exportTimerId;
            delete btn._cancelResolve;
            delete btn._isProcessingFile;
            delete btn._initToast;
        }
    }
}
