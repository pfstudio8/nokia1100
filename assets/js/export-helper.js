/**
 * export-helper.js - Nokia 1100 System
 * Helper para exportar cualquier tabla HTML a Excel (.xlsx) y PDF (.pdf) del lado del cliente.
 */

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

async function exportTableToExcel(tableId, filename, btnElement) {
    const btn = btnElement || (window.event ? window.event.currentTarget : null);
    
    // Si ya está exportando, actuar como botón de cancelar
    if (btn && btn.dataset.isExporting === 'true') {
        if (typeof btn.cancelExport === 'function') {
            btn.cancelExport();
        }
        return;
    }

    let originalHtml = '';
    
    try {
        if (btn) {
            originalHtml = btn.innerHTML;
            btn.dataset.originalHtml = originalHtml;
            btn.dataset.isExporting = 'true';
            btn.innerHTML = '<span class="material-symbols-outlined text-[16px] spin" style="animation: spin 1s linear infinite;">autorenew</span> Procesando...';
        }
        
        if (typeof showToast === 'function') {
            showToast('Generando archivo Excel...', 'info');
        }
        
        // Simular retraso de 2 segundos de forma cancelable
        const isCompleted = await new Promise(resolve => {
            let timeoutId;
            if (btn) {
                btn.cancelExport = () => {
                    clearTimeout(timeoutId);
                    btn.dataset.isExporting = 'false';
                    btn.innerHTML = '<span class="material-symbols-outlined text-[16px] text-red-500">cancel</span> Cancelado';
                    if (typeof showToast === 'function') {
                        showToast('Exportación cancelada', 'warning');
                    }
                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                    }, 1500);
                    resolve(false);
                };
            }
            timeoutId = setTimeout(() => {
                if (btn) btn.dataset.isExporting = 'false';
                resolve(true);
            }, 2000);
        });

        if (!isCompleted) return; // Se canceló
        
        // Cargar SheetJS
        await loadScript('https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js');
        
        const table = document.getElementById(tableId);
        if (!table) {
            if (typeof showToast === 'function') showToast('Tabla no encontrada', 'error');
            if (btn) { btn.innerHTML = originalHtml; btn.style.pointerEvents = 'auto'; }
            return;
        }
        
        // Clonar la tabla para manipularla sin alterar la original en pantalla
        const clone = table.cloneNode(true);
        
        // Remover columnas de acciones y filas de historial
        clone.querySelectorAll('.history-row, .no-export').forEach(el => el.remove());
        
        // Buscar el header de acciones y removerlo junto con su columna en las filas
        const headers = clone.querySelectorAll('thead th');
        let actionsColIndex = -1;
        headers.forEach((th, idx) => {
            const txt = th.innerText.toLowerCase().trim();
            if (txt === 'acciones' || txt === 'acción' || txt === 'accion') {
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

        // Prepend a nice title row for Excel
        const titleRow = document.createElement('tr');
        const titleTd = document.createElement('th');
        const colCount = clone.querySelector('thead tr').children.length;
        titleTd.colSpan = colCount > 0 ? colCount : 5;
        titleTd.innerText = `SISTEMA NOKIA 1100 - REPORTE DE ${filename.toUpperCase()} - ${new Date().toLocaleDateString()}`;
        titleRow.appendChild(titleTd);
        clone.querySelector('thead').prepend(titleRow);
        
        const wb = XLSX.utils.table_to_book(clone, { sheet: "Datos" });
        XLSX.writeFile(wb, filename + '_' + new Date().toISOString().slice(0, 10) + '.xlsx');
        
        if (typeof showToast === 'function') {
            showToast('Excel descargado con éxito', 'success');
        }
        
        if (btn) {
            // Efecto verde de éxito temporal si el usuario lo deseó (como sugerencia del plan)
            btn.innerHTML = '<span class="material-symbols-outlined text-[16px] text-green-500">check_circle</span> ¡Listo!';
            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.style.pointerEvents = 'auto';
            }, 2000);
        }
    } catch (e) {
        console.error(e);
        if (typeof showToast === 'function') showToast('Error al exportar a Excel', 'error');
        if (btn) { btn.innerHTML = originalHtml; btn.style.pointerEvents = 'auto'; }
    }
}

async function exportTableToPDF(tableId, title, filename, btnElement) {
    const btn = btnElement || (window.event ? window.event.currentTarget : null);
    
    // Si ya está exportando, actuar como botón de cancelar
    if (btn && btn.dataset.isExporting === 'true') {
        if (typeof btn.cancelExport === 'function') {
            btn.cancelExport();
        }
        return;
    }

    let originalHtml = '';
    
    try {
        if (btn) {
            originalHtml = btn.innerHTML;
            btn.dataset.originalHtml = originalHtml;
            btn.dataset.isExporting = 'true';
            btn.innerHTML = '<span class="material-symbols-outlined text-[16px] spin" style="animation: spin 1s linear infinite;">autorenew</span> Procesando...';
        }

        if (typeof showToast === 'function') {
            showToast('Generando reporte PDF...', 'info');
        }
        
        // Simular retraso de 2 segundos de forma cancelable
        const isCompleted = await new Promise(resolve => {
            let timeoutId;
            if (btn) {
                btn.cancelExport = () => {
                    clearTimeout(timeoutId);
                    btn.dataset.isExporting = 'false';
                    btn.innerHTML = '<span class="material-symbols-outlined text-[16px] text-red-500">cancel</span> Cancelado';
                    if (typeof showToast === 'function') {
                        showToast('Exportación cancelada', 'warning');
                    }
                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                    }, 1500);
                    resolve(false);
                };
            }
            timeoutId = setTimeout(() => {
                if (btn) btn.dataset.isExporting = 'false';
                resolve(true);
            }, 2000);
        });

        if (!isCompleted) return; // Se canceló
        
        // Cargar jsPDF y su plugin AutoTable
        await loadScript('https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js');
        await loadScript('https://cdn.jsdelivr.net/npm/jspdf-autotable@3.5.29/dist/jspdf.plugin.autotable.min.js');
        
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'mm', 'a4');
        
        // Cabecera estilizada estilo Light Mode / Clean Premium
        doc.setFillColor(255, 255, 255); 
        doc.rect(0, 0, 210, 30, 'F');
        
        doc.setTextColor(17, 24, 39); // Dark Gray
        doc.setFontSize(22);
        doc.setFont("helvetica", "bold");
        doc.text("NOKIA 1100", 15, 20);
        
        doc.setTextColor(107, 114, 128); // Gray
        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        doc.text("SISTEMA DE GESTIÓN", 15, 26);
        
        doc.setTextColor(55, 65, 81);
        doc.setFontSize(14);
        doc.setFont("helvetica", "bold");
        doc.text(title.toUpperCase(), 195, 20, { align: 'right' });
        
        doc.setFontSize(8);
        doc.setTextColor(156, 163, 175);
        doc.text("Generado: " + new Date().toLocaleString(), 195, 26, { align: 'right' });
        
        const table = document.getElementById(tableId);
        if (!table) {
            if (typeof showToast === 'function') showToast('Tabla no encontrada', 'error');
            if (btn) { btn.innerHTML = originalHtml; btn.style.pointerEvents = 'auto'; }
            return;
        }
        
        // Extraer encabezados y cuerpo de datos
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
            if (tds.length === 0) return; // evitar filas vacías de carga
            
            // Ignorar filas de "no hay datos"
            if (tds.length === 1 && tr.innerText.toLowerCase().includes('no hay')) return;
            
            tds.forEach((td, idx) => {
                if (idx !== actionsColIndex) {
                    // Limpiar texto para remover íconos y espacios dobles
                    let text = td.innerText.trim().replace(/\s+/g, ' ');
                    
                    // Remover flechas indicadoras de expandibles
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
                lineColor: [229, 231, 235], // Light gray borders
                fontSize: 9,
                font: "helvetica",
                cellPadding: 4
            },
            headStyles: {
                fillColor: [243, 244, 246], // Very light gray bg
                textColor: [17, 24, 39], // Almost black text
                fontStyle: 'bold',
                lineColor: [209, 213, 219],
                lineWidth: 0.1
            },
            alternateRowStyles: {
                fillColor: [250, 250, 250] // extremely subtle gray
            },
            margin: { left: 15, right: 15 }
        });
        
        doc.save(filename + '_' + new Date().toISOString().slice(0, 10) + '.pdf');
        
        if (typeof showToast === 'function') {
            showToast('PDF descargado con éxito', 'success');
        }
        
        if (btn) {
            btn.innerHTML = '<span class="material-symbols-outlined text-[16px] text-green-500">check_circle</span> ¡Listo!';
            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.style.pointerEvents = 'auto';
            }, 2000);
        }
    } catch (e) {
        console.error(e);
        if (typeof showToast === 'function') showToast('Error al exportar a PDF', 'error');
        if (btn) { btn.innerHTML = originalHtml; btn.style.pointerEvents = 'auto'; }
    }
}
