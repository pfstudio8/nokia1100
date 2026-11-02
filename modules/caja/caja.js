/**
 * Nokia 1100 · Módulo Caja (caja.js)
 * Interactividad para apertura, movimientos, arqueo y cierre de caja
 */
(function() {
    'use strict';

    const CONCEPTOS = {
        in: ['Venta en efectivo', 'Cobro de reparación', 'Seña', 'Otro ingreso'],
        out: ['Pago a proveedor', 'Retiro de efectivo', 'Gasto menor', 'Otro egreso']
    };

    let currentTipo = 'in';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Utilidades de formato
    const money = n => '$' + Number(n || 0).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const smoney = n => (n > 0 ? '+' : n < 0 ? '−' : '') + money(Math.abs(n));
    const near0 = n => Math.abs(n) < 0.005;

    // Toast notifications
    function showToast(msg, type = 'ok') {
        if (typeof window.showToast === 'function') {
            window.showToast(msg, type === 'err' ? 'error' : 'success');
            return;
        }
        let box = document.getElementById('nk-toasts');
        if (!box) {
            box = document.createElement('div');
            box.id = 'nk-toasts';
            box.className = 'nk-toasts';
            box.setAttribute('role', 'status');
            box.setAttribute('aria-live', 'polite');
            document.body.appendChild(box);
        }
        const el = document.createElement('div');
        el.className = 'nk-toast' + (type === 'err' ? ' nk-toast--err' : '');
        el.innerHTML = `<span>${msg}</span>`;
        box.appendChild(el);
        setTimeout(() => el.remove(), 4200);
    }

    // Modal Helpers
    function openModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('is-open');
            document.body.classList.add('is-locked');
            const autoFocus = modal.querySelector('[data-autofocus]') || modal.querySelector('input:not([type="hidden"]), button.nk-btn--primary');
            if (autoFocus) autoFocus.focus();
        }
    }

    function closeModal(id) {
        const modal = typeof id === 'string' ? document.getElementById(id) : id;
        if (modal) {
            modal.classList.remove('is-open');
            if (!document.querySelector('.caja-modal-backdrop.is-open')) {
                document.body.classList.remove('is-locked');
            }
        }
    }

    // Actualiza dropdown de conceptos según el tipo (in/out)
    function fillConceptos() {
        const select = document.getElementById('mvConcepto');
        if (!select) return;
        select.innerHTML = CONCEPTOS[currentTipo].map(c => `<option value="${c}">${c}</option>`).join('');
    }

    // Cálculo y aviso de diferencia al cerrar
    function updateDiff() {
        const clAmount = document.getElementById('clAmount');
        const diffMsg = document.getElementById('clDiffMsg');
        const expEl = document.getElementById('closeExpectedAmount');
        if (!clAmount || !diffMsg || !expEl) return;

        const val = parseFloat(clAmount.value);
        const expected = parseFloat(expEl.value || 0);

        if (isNaN(val)) {
            diffMsg.textContent = '';
            diffMsg.className = 'nk-hint';
            return;
        }

        const diff = val - expected;
        if (near0(diff)) {
            diffMsg.textContent = 'La caja cuadra con exactitud.';
            diffMsg.className = 'nk-hint diff--pos';
        } else if (diff > 0) {
            diffMsg.textContent = `Sobran ${money(diff)} respecto de lo esperado. Dejá una observación obligatoria.`;
            diffMsg.className = 'nk-hint diff--pos';
        } else {
            diffMsg.textContent = `Faltan ${money(Math.abs(diff))} respecto de lo esperado. Dejá una observación obligatoria.`;
            diffMsg.className = 'nk-hint diff--neg';
        }
    }

    // Filtrar tabla historial por estado y mes
    function filterHistory() {
        const statusActive = document.querySelector('#stSeg button[aria-pressed="true"]')?.dataset.v || 'all';
        const monthActive = document.getElementById('monthSel')?.value || 'all';
        const rows = document.querySelectorAll('#cajaTbody tr');
        let visibleCount = 0;
        let sumDiff = 0;
        let closedCount = 0;

        rows.forEach(tr => {
            const st = tr.dataset.status; // 'open' | 'closed'
            const m = tr.dataset.month;   // 'YYYY-MM'
            const diff = parseFloat(tr.dataset.diff || '0');

            const matchSt = (statusActive === 'all' || statusActive === st);
            const matchM = (monthActive === 'all' || monthActive === m);

            if (matchSt && matchM) {
                tr.style.display = '';
                visibleCount++;
                if (st === 'closed') {
                    sumDiff += diff;
                    closedCount++;
                }
            } else {
                tr.style.display = 'none';
            }
        });

        const empty = document.getElementById('emptyHist');
        if (empty) empty.hidden = (visibleCount > 0);

        const footCount = document.getElementById('footCount');
        if (footCount) footCount.textContent = `${visibleCount} ${visibleCount === 1 ? 'caja' : 'cajas'}`;

        const footDiff = document.getElementById('footDiff');
        if (footDiff) {
            footDiff.textContent = closedCount ? `Diferencia acumulada: ${near0(sumDiff) ? '$0' : smoney(sumDiff)}` : '';
        }
    }

    // Eventos iniciales
    document.addEventListener('DOMContentLoaded', () => {
        fillConceptos();
        filterHistory();

        // Control segmentado para Tipo de Movimiento (Ingreso / Egreso)
        const mvTipo = document.getElementById('mvTipo');
        if (mvTipo) {
            mvTipo.addEventListener('click', e => {
                const btn = e.target.closest('button');
                if (!btn) return;
                mvTipo.querySelectorAll('button').forEach(b => b.setAttribute('aria-pressed', 'false'));
                btn.setAttribute('aria-pressed', 'true');
                currentTipo = btn.dataset.v;
                fillConceptos();
            });
        }

        // Control segmentado para Filtro de Historial
        const stSeg = document.getElementById('stSeg');
        if (stSeg) {
            stSeg.addEventListener('click', e => {
                const btn = e.target.closest('button');
                if (!btn) return;
                stSeg.querySelectorAll('button').forEach(b => b.setAttribute('aria-pressed', 'false'));
                btn.setAttribute('aria-pressed', 'true');
                filterHistory();
            });
        }

        // Selector de mes
        const monthSel = document.getElementById('monthSel');
        if (monthSel) {
            monthSel.addEventListener('change', filterHistory);
        }

        // Abrir Caja Form
        const openForm = document.getElementById('openForm');
        if (openForm) {
            openForm.addEventListener('submit', async e => {
                e.preventDefault();
                const amountInput = document.getElementById('opAmount');
                const val = parseFloat(amountInput.value);
                const err = document.getElementById('opAmountErr');

                if (isNaN(val) || val < 0) {
                    if (err) { err.textContent = 'Ingrese un monto de apertura válido.'; err.hidden = false; }
                    amountInput.focus();
                    return;
                }
                if (err) err.hidden = true;

                const btn = document.getElementById('btnOpen');
                btn.disabled = true;
                btn.textContent = 'Abriendo...';

                try {
                    const fd = new FormData();
                    fd.append('monto_apertura', val);
                    fd.append('csrf_token', csrfToken);
                    fd.append('ajax', '1');

                    const res = await fetch('index.php?action=open', {
                        method: 'POST',
                        body: fd,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const json = await res.json();
                    if (json.success) {
                        showToast(json.message);
                        setTimeout(() => window.location.reload(), 400);
                    } else {
                        showToast(json.error || 'No se pudo abrir la caja.', 'err');
                        btn.disabled = false;
                        btn.innerHTML = 'Abrir caja';
                    }
                } catch (ex) {
                    showToast('Error de conexión al abrir la caja.', 'err');
                    btn.disabled = false;
                    btn.innerHTML = 'Abrir caja';
                }
            });
        }

        // Agregar Movimiento Form
        const mvForm = document.getElementById('mvForm');
        if (mvForm) {
            mvForm.addEventListener('submit', async e => {
                e.preventDefault();
                const montoInput = document.getElementById('mvMonto');
                const conceptoInput = document.getElementById('mvConcepto');
                const detalleInput = document.getElementById('mvDetalle');
                const err = document.getElementById('mvMontoErr');

                const val = parseFloat(montoInput.value);
                if (isNaN(val) || val <= 0) {
                    if (err) { err.textContent = 'El monto debe ser mayor a cero.'; err.hidden = false; }
                    montoInput.focus();
                    return;
                }
                if (err) err.hidden = true;

                try {
                    const fd = new FormData();
                    fd.append('tipo', currentTipo);
                    fd.append('concepto', conceptoInput.value);
                    fd.append('detalle', detalleInput.value);
                    fd.append('monto', val);
                    fd.append('csrf_token', csrfToken);
                    fd.append('ajax', '1');

                    const res = await fetch('index.php?action=add_movement', {
                        method: 'POST',
                        body: fd,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const json = await res.json();
                    if (json.success) {
                        showToast(json.message);
                        setTimeout(() => window.location.reload(), 300);
                    } else {
                        showToast(json.error || 'Error al agregar movimiento.', 'err');
                    }
                } catch (ex) {
                    showToast('Error de conexión al registrar movimiento.', 'err');
                }
            });
        }

        // Diferencia al escribir en monto de cierre
        const clAmount = document.getElementById('clAmount');
        if (clAmount) {
            clAmount.addEventListener('input', updateDiff);
        }

        // Formulario Cerrar Caja
        const closeForm = document.getElementById('closeForm');
        if (closeForm) {
            closeForm.addEventListener('submit', async e => {
                e.preventDefault();
                const clAmountInput = document.getElementById('clAmount');
                const clObsInput = document.getElementById('clObs');
                const errAmt = document.getElementById('clAmountErr');
                const errObs = document.getElementById('clObsErr');
                const expEl = document.getElementById('closeExpectedAmount');

                const val = parseFloat(clAmountInput.value);
                const expected = parseFloat(expEl?.value || 0);

                if (isNaN(val) || val < 0) {
                    if (errAmt) { errAmt.textContent = 'Ingrese el monto de cierre en efectivo.'; errAmt.hidden = false; }
                    clAmountInput.focus();
                    return;
                }
                if (errAmt) errAmt.hidden = true;

                const diff = val - expected;
                if (!near0(diff) && !clObsInput.value.trim()) {
                    if (errObs) { errObs.textContent = 'Explique la diferencia para poder cerrar la caja.'; errObs.hidden = false; }
                    clObsInput.focus();
                    return;
                }
                if (errObs) errObs.hidden = true;

                const btn = document.getElementById('btnConfirmClose');
                btn.disabled = true;
                btn.textContent = 'Cerrando...';

                try {
                    const fd = new FormData();
                    fd.append('monto_cierre', val);
                    fd.append('observaciones', clObsInput.value.trim());
                    fd.append('csrf_token', csrfToken);
                    fd.append('ajax', '1');

                    const res = await fetch('index.php?action=close', {
                        method: 'POST',
                        body: fd,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const json = await res.json();
                    if (json.success) {
                        showToast(json.message);
                        setTimeout(() => window.location.reload(), 400);
                    } else {
                        showToast(json.error || 'No se pudo cerrar la caja.', 'err');
                        btn.disabled = false;
                        btn.textContent = 'Confirmar cierre';
                    }
                } catch (ex) {
                    showToast('Error de conexión al cerrar la caja.', 'err');
                    btn.disabled = false;
                    btn.textContent = 'Confirmar cierre';
                }
            });
        }

        // Clicks globales delegados
        document.addEventListener('click', async e => {
            // Cerrar modales con botones de cerrar o backdrop
            if (e.target.closest('[data-caja-close]')) {
                const modal = e.target.closest('.caja-modal-backdrop');
                if (modal) closeModal(modal);
                return;
            }
            if (e.target.classList.contains('caja-modal-backdrop')) {
                closeModal(e.target);
                return;
            }

            // Usar último cierre como apertura sugerida
            const useLastBtn = e.target.closest('[data-action="use-last"]');
            if (useLastBtn) {
                const lastVal = useLastBtn.dataset.amount;
                const opAmount = document.getElementById('opAmount');
                if (opAmount && lastVal) {
                    opAmount.value = lastVal;
                    opAmount.focus();
                }
                return;
            }

            // Foco al formulario de movimientos
            if (e.target.closest('[data-action="focus-mov"]')) {
                const movP = document.getElementById('movPanel');
                if (movP) {
                    movP.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    setTimeout(() => document.getElementById('mvMonto')?.focus(), 250);
                }
                return;
            }

            // Abrir modal de cierre
            if (e.target.closest('[data-action="close-caja"]')) {
                openModal('closeModal');
                updateDiff();
                return;
            }

            // Quitar movimiento
            const rmBtn = e.target.closest('[data-action="mv-remove"]');
            if (rmBtn) {
                const idMov = rmBtn.dataset.id;
                const concepto = rmBtn.dataset.concepto;
                const monto = rmBtn.dataset.monto;
                const confirmed = (typeof window.showConfirmModal === 'function')
                    ? await window.showConfirmModal(
                        'Quitar Movimiento',
                        `¿Desea quitar el movimiento "${concepto}" por ${money(monto)}?\n\nEl efectivo esperado se recalculará automáticamente.`,
                        'Sí, quitar',
                        'Cancelar',
                        true
                    )
                    : confirm(`¿Desea quitar el movimiento "${concepto}" por ${money(monto)}? El efectivo esperado se recalculará automáticamente.`);

                if (!confirmed) {
                    return;
                }

                try {
                    const fd = new FormData();
                    fd.append('id_movimiento', idMov);
                    fd.append('csrf_token', csrfToken);
                    fd.append('ajax', '1');

                    const res = await fetch('index.php?action=remove_movement', {
                        method: 'POST',
                        body: fd,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const json = await res.json();
                    if (json.success) {
                        showToast(json.message);
                        setTimeout(() => window.location.reload(), 300);
                    } else {
                        showToast(json.error || 'Error al eliminar movimiento.', 'err');
                    }
                } catch (ex) {
                    showToast('Error de conexión al eliminar movimiento.', 'err');
                }
                return;
            }

            // Ver detalle de una caja
            const dtBtn = e.target.closest('[data-action="detail"]');
            if (dtBtn) {
                const idCaja = dtBtn.dataset.id;
                try {
                    const res = await fetch(`index.php?action=detail&id=${idCaja}&ajax=1`);
                    const json = await res.json();
                    if (json.success && json.caja) {
                        const c = json.caja;
                        document.getElementById('dtTitle').textContent = `Caja del ${fdate(c.fecha)}`;
                        document.getElementById('dtSub').textContent = c.estado === 'abierta'
                            ? `Abierta ${c.hora_apertura} hs · En curso`
                            : `${c.hora_apertura} → ${c.hora_cierre || ''} hs`;

                        const d = c.diferencia !== null ? parseFloat(c.diferencia) : null;
                        const diffText = (d === null) ? '—' : near0(d) ? 'Cuadra' : smoney(d);
                        const diffClass = (d === null) ? '' : near0(d) ? 'diff--pos' : d > 0 ? 'diff--pos' : 'diff--neg';

                        document.getElementById('dtSum').innerHTML = `
                            <dt>Monto de apertura</dt><dd>${money(c.monto_apertura)}</dd>
                            <dt>Ingresos</dt><dd class="mv-in">${c.ingresos ? '+' : ''}${money(c.ingresos)}</dd>
                            <dt>Egresos</dt><dd class="mv-out">${c.egresos ? '−' : ''}${money(c.egresos)}</dd>
                            <span class="rule"></span>
                            <dt class="is-big">Efectivo esperado</dt><dd class="is-big">${money(c.monto_esperado)}</dd>
                            ${c.estado === 'cerrada' && c.monto_cierre !== null ? `
                            <dt class="is-big">Monto de cierre</dt><dd class="is-big">${money(c.monto_cierre)}</dd>
                            <dt>Diferencia</dt><dd class="${diffClass}">${diffText}</dd>
                            ` : ''}
                        `;

                        document.getElementById('dtObs').textContent = c.observaciones ? `Observación: ${c.observaciones}` : '';

                        const tbody = document.getElementById('dtTbody');
                        if (c.movs && c.movs.length > 0) {
                            tbody.innerHTML = c.movs.map(m => `
                                <tr>
                                    <td><span class="nk-mono">${m.hora}</span></td>
                                    <td>
                                        <span class="nk-cell-main">${escapeHtml(m.concepto)}</span>
                                        ${m.detalle ? `<span class="nk-cell-meta">${escapeHtml(m.detalle)}</span>` : ''}
                                    </td>
                                    <td class="nk-num mv-in">${m.tipo === 'in' ? money(m.monto) : '—'}</td>
                                    <td class="nk-num mv-out">${m.tipo === 'out' ? money(m.monto) : '—'}</td>
                                </tr>
                            `).join('');
                        } else {
                            tbody.innerHTML = `<tr><td colspan="4" class="is-c" style="padding:22px;color:var(--nk-ink-2);">Esta caja no tuvo movimientos registrados.</td></tr>`;
                        }

                        openModal('detailModal');
                    } else {
                        showToast('No se pudo cargar el detalle de la caja.', 'err');
                    }
                } catch (ex) {
                    showToast('Error de conexión al consultar detalle.', 'err');
                }
                return;
            }

            // Exportar CSV
            if (e.target.closest('[data-action="export"]')) {
                const statusActive = document.querySelector('#stSeg button[aria-pressed="true"]')?.dataset.v || 'all';
                const monthActive = document.getElementById('monthSel')?.value || 'all';
                window.location.href = `index.php?action=export_csv&status=${statusActive}&month=${monthActive}`;
                return;
            }

            // Imprimir página general
            if (e.target.closest('[data-action="print"]')) {
                window.print();
                return;
            }

            // Imprimir detalle específico
            if (e.target.closest('[data-action="print-detail"]')) {
                document.body.classList.add('print-detail');
                window.print();
                return;
            }

            // Restablecer datos de demo
            if (e.target.closest('[data-action="reset-demo"]')) {
                const confirmed = (typeof window.showConfirmModal === 'function')
                    ? await window.showConfirmModal(
                        'Restablecer Datos de Demo',
                        '¿Desea restablecer los datos de demo?\n\nSe borrarán las cajas y movimientos actuales y se restaurarán los datos de ejemplo.',
                        'Sí, restablecer',
                        'Cancelar',
                        true
                    )
                    : confirm('¿Desea restablecer los datos de demo? Se borrarán las cajas y movimientos actuales y se restaurarán los datos de ejemplo.');

                if (!confirmed) {
                    return;
                }
                try {
                    const fd = new FormData();
                    fd.append('csrf_token', csrfToken);
                    fd.append('ajax', '1');
                    const res = await fetch('index.php?action=reset_demo', {
                        method: 'POST',
                        body: fd,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const json = await res.json();
                    if (json.success) {
                        showToast(json.message);
                        setTimeout(() => window.location.reload(), 400);
                    } else {
                        showToast(json.error || 'Error al restablecer demo.', 'err');
                    }
                } catch (ex) {
                    showToast('Error de conexión al restablecer demo.', 'err');
                }
                return;
            }
        });

        // Limpiar clase de impresión tras imprimir
        window.addEventListener('afterprint', () => {
            document.body.classList.remove('print-detail');
        });

        // Cerrar con Escape
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                const openModals = document.querySelectorAll('.caja-modal-backdrop.is-open');
                openModals.forEach(m => closeModal(m));
            }
        });
    });

    function fdate(str) {
        if (!str) return '';
        const parts = str.split('-');
        if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
        return str;
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/[&<>"']/g, m => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[m]));
    }
})();
