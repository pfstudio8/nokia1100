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

async function exportTableToExcel(tableId, filename) {
    try {
        if (typeof showToast === 'function') {
            showToast('Generando archivo Excel...', 'info');
        }
        
        // Cargar SheetJS
        await loadScript('https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js');
        
        const table = document.getElementById(tableId);
        if (!table) {
            if (typeof showToast === 'function') showToast('Tabla no encontrada', 'error');
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
        
        const wb = XLSX.utils.table_to_book(clone, { sheet: "Datos" });
        XLSX.writeFile(wb, filename + '_' + new Date().toISOString().slice(0, 10) + '.xlsx');
        
        if (typeof showToast === 'function') {
            showToast('Excel descargado con éxito', 'success');
        }
    } catch (e) {
        console.error(e);
        if (typeof showToast === 'function') showToast('Error al exportar a Excel', 'error');
    }
}

async function exportTableToPDF(tableId, title, filename) {
    try {
        if (typeof showToast === 'function') {
            showToast('Generando reporte PDF...', 'info');
        }
        
        // Cargar jsPDF y su plugin AutoTable
        await loadScript('https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js');
        await loadScript('https://cdn.jsdelivr.net/npm/jspdf-autotable@3.5.29/dist/jspdf.plugin.autotable.min.js');
        
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'mm', 'a4');
        
        // Cabecera estilizada estilo Nokia 1100 Premium (Dark mode header)
        doc.setFillColor(17, 17, 19); // #111113
        doc.rect(0, 0, 210, 30, 'F');
        
        doc.setTextColor(33, 184, 189); // #21b8bd (Cian)
        doc.setFontSize(18);
        doc.setFont("helvetica", "bold");
        doc.text("SISTEMA NOKIA 1100", 15, 14);
        
        doc.setTextColor(250, 250, 250); // #FAFAFA
        doc.setFontSize(12);
        doc.setFont("helvetica", "normal");
        doc.text(title.toUpperCase(), 15, 22);
        
        doc.setFontSize(8);
        doc.setTextColor(161, 161, 170); // #A1A1AA (Muted)
        doc.text("Generado el: " + new Date().toLocaleString(), 145, 14);
        doc.text("Reporte Operativo Oficial", 145, 22);
        
        const table = document.getElementById(tableId);
        if (!table) {
            if (typeof showToast === 'function') showToast('Tabla no encontrada', 'error');
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
                fillColor: [17, 17, 19], // #111113
                textColor: [240, 240, 240],
                lineColor: [39, 39, 42], // #27272A
                fontSize: 9,
                font: "helvetica"
            },
            headStyles: {
                fillColor: [33, 184, 189], // #21b8bd (Cian)
                textColor: [10, 10, 11], // #0A0A0B (Dark)
                fontStyle: 'bold'
            },
            alternateRowStyles: {
                fillColor: [24, 24, 27] // #18181B
            },
            margin: { left: 15, right: 15 }
        });
        
        doc.save(filename + '_' + new Date().toISOString().slice(0, 10) + '.pdf');
        
        if (typeof showToast === 'function') {
            showToast('PDF descargado con éxito', 'success');
        }
    } catch (e) {
        console.error(e);
        if (typeof showToast === 'function') showToast('Error al exportar a PDF', 'error');
    }
}
