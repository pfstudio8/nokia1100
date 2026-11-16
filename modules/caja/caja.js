/**
 * Nokia 1100 · Módulo Caja (caja.js)
 * Libro de movimientos inmutable y arqueo de cierre ciego
 */
(function() {
    'use strict';

    const CONCEPTOS = {
        in: ['Venta en efectivo', 'Cobro de reparación', 'Seña', 'Aporte de cambio', 'Otro ingreso'],
        out: ['Pago a proveedor', 'Retiro de efectivo', 'Gasto menor', 'Otro egreso']
    };

    let currentTipo = 'in';
    let closeCompleted = false;
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
            const autoFocus = modal.querySelector('[data-autofocus]') || modal.querySelector('input:not([type="hidden"]):not([readonly]), textarea, button.nk-btn--primary');
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
            if (closeCompleted && modal.id === 'closeModal') {
                window.location.reload();
            }
        }
    }

    // Actualiza dropdown de conceptos según el tipo (in/out)
    function fillConceptos() {
        const select = document.getElementById('mvConcepto');
        if (!select) return;
        select.innerHTML = CONCEPTOS[currentTipo].map(c => `<option value="${c}">${c}</option>`).join('');
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

            const matchSt = (statusActive === 'all' || statusActive === st || (statusActive === 'diff' && Math.abs(diff) >= 0.01));
            const matchM = (monthActive === 'all' || monthActive === m);

            if (matchSt && matchM) {
                tr.style.display = '';
                tr.classList.remove('sys-search-hidden');
                visibleCount++;
                if (st === 'closed') {
                    sumDiff += diff;
                    closedCount++;
                }
            } else {
                tr.style.display = 'none';
                tr.classList.add('sys-search-hidden');
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

    // Pantallita LCD Nokia Loader (N-O-K-I-A en Cascada Elástica)
    function showNokiaLcdLoader(statusText = 'ESPERE...') {
        let loader = document.getElementById('nkNokiaLcdModal');
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'nkNokiaLcdModal';
            loader.className = 'nk-nokia-lcd-backdrop';
            loader.setAttribute('role', 'status');
            loader.setAttribute('aria-live', 'polite');
            loader.innerHTML = `
                <div class="nk-nokia-lcd-screen">
                    <div class="nk-nokia-letters" aria-label="NOKIA">
                        <span>N</span>
                        <span>O</span>
                        <span>K</span>
                        <span>I</span>
                        <span>A</span>
                    </div>
                    <div class="nk-nokia-lcd-status">
                        <span class="nk-lcd-dot">■</span>
                        <span class="nk-lcd-status-text">${statusText}</span>
                    </div>
                </div>
            `;
            document.body.appendChild(loader);
        } else {
            const statusEl = loader.querySelector('.nk-lcd-status-text');
            if (statusEl) statusEl.textContent = statusText;
        }

        requestAnimationFrame(() => {
            loader.classList.add('is-active');
        });
    }

    function hideNokiaLcdLoader() {
        const loader = document.getElementById('nkNokiaLcdModal');
        if (loader) {
            loader.classList.remove('is-active');
            setTimeout(() => {
                if (loader.parentNode) loader.parentNode.removeChild(loader);
            }, 250);
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

                const startTime = Date.now();
                showNokiaLcdLoader('ABRIENDO CAJA...');

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

                    const elapsed = Date.now() - startTime;
                    const minWait = Math.max(0, 1000 - elapsed);

                    setTimeout(() => {
                        if (json.success) {
                            window.location.reload();
                        } else {
                            hideNokiaLcdLoader();
                            showToast(json.error || 'No se pudo abrir la caja.', 'err');
                            btn.disabled = false;
                            btn.innerHTML = '<span class="material-symbols-outlined text-[19px]">lock_open</span> Abrir caja';
                        }
                    }, minWait);
                } catch (ex) {
                    hideNokiaLcdLoader();
                    showToast('Error de conexión al abrir la caja.', 'err');
                    btn.disabled = false;
                    btn.innerHTML = '<span class="material-symbols-outlined text-[19px]">lock_open</span> Abrir caja';
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
                const medioInput = document.getElementById('mvMedio');
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
                    fd.append('medio_pago', medioInput ? medioInput.value : 'Efectivo');
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

        // Formulario de Contra-asiento (Reversión inmutable con motivo obligatorio)
        const reverseForm = document.getElementById('reverseForm');
        if (reverseForm) {
            reverseForm.addEventListener('submit', async e => {
                e.preventDefault();
                const idMov = document.getElementById('rvMovId')?.value;
                const motivoInput = document.getElementById('rvMotivo');
                const err = document.getElementById('rvMotivoErr');
                const motivo = (motivoInput?.value || '').trim();

                if (!motivo) {
                    if (err) {
                        err.textContent = 'Debe ingresar el motivo obligatorio del contra-asiento.';
                        err.hidden = false;
                    }
                    motivoInput?.focus();
                    return;
                }
                if (err) err.hidden = true;

                const btn = document.getElementById('btnConfirmReverse');
                if (btn) {
                    btn.disabled = true;
                    btn.textContent = 'Registrando...';
                }

                try {
                    const fd = new FormData();
                    fd.append('id_movimiento', idMov);
                    fd.append('motivo', motivo);
                    fd.append('csrf_token', csrfToken);
                    fd.append('ajax', '1');

                    const res = await fetch('index.php?action=reverse_movement', {
                        method: 'POST',
                        body: fd,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const json = await res.json();
                    if (json.success) {
                        closeModal('reverseModal');
                        showToast(json.message || 'Contra-asiento registrado.');
                        setTimeout(() => window.location.reload(), 300);
                    } else {
                        showToast(json.error || 'No se pudo registrar el contra-asiento.', 'err');
                        if (btn) {
                            btn.disabled = false;
                            btn.textContent = 'Confirmar contra-asiento';
                        }
                    }
                } catch (ex) {
                    showToast('Error de conexión al registrar contra-asiento.', 'err');
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = 'Confirmar contra-asiento';
                    }
                }
            });
        }

        // Botón finalizar tras ver resultado de cierre ciego
        const btnFinishClose = document.getElementById('btnFinishClose');
        if (btnFinishClose) {
            btnFinishClose.addEventListener('click', () => {
                window.location.reload();
            });
        }

        // Formulario Cerrar Caja (Cierre Ciego: el esperado y la diferencia se revelan recién al confirmar)
        const closeForm = document.getElementById('closeForm');
        if (closeForm) {
            closeForm.addEventListener('submit', async e => {
                e.preventDefault();
                const clAmountInput = document.getElementById('clAmount');
                const clObsInput = document.getElementById('clObs');
                const errAmt = document.getElementById('clAmountErr');
                const errObs = document.getElementById('clObsErr');

                const val = parseFloat(clAmountInput.value);

                if (isNaN(val) || val < 0) {
                    if (errAmt) { errAmt.textContent = 'Ingrese el monto de cierre en efectivo.'; errAmt.hidden = false; }
                    clAmountInput.focus();
                    return;
                }
                if (errAmt) errAmt.hidden = true;
                if (errObs) errObs.hidden = true;

                const btn = document.getElementById('btnConfirmClose');
                btn.disabled = true;
                btn.textContent = 'Verificando arqueo...';

                const startTime = Date.now();
                showNokiaLcdLoader('CERRANDO CAJA...');
                closeModal('closeModal');

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

                    const elapsed = Date.now() - startTime;
                    const minWait = Math.max(0, 900 - elapsed);

                    setTimeout(() => {
                        hideNokiaLcdLoader();

                        if (json.success) {
                            closeCompleted = true;
                            const stepForm = document.getElementById('clStepForm');
                            const footForm = document.getElementById('clFootForm');
                            const stepResult = document.getElementById('clStepResult');
                            const footResult = document.getElementById('clFootResult');
                            const resultSum = document.getElementById('clResultSum');
                            const resultBanner = document.getElementById('clResultBanner');

                            if (stepForm) stepForm.hidden = true;
                            if (footForm) footForm.hidden = true;
                            if (stepResult) stepResult.hidden = false;
                            if (footResult) footResult.hidden = false;

                            const d = parseFloat(json.diferencia || 0);
                            const cuadra = near0(d);
                            const diffClass = cuadra ? 'diff--pos' : (d > 0 ? 'diff--pos' : 'diff--neg');
                            const diffLabel = cuadra
                                ? 'Cuadra con exactitud ($0,00)'
                                : (d > 0 ? `Sobrante ${smoney(d)}` : `Faltante ${smoney(d)}`);

                            if (resultBanner) {
                                resultBanner.className = cuadra ? 'nk-callout--ok' : 'nk-callout--bad';
                                resultBanner.innerHTML = cuadra
                                    ? '<strong>Caja cerrada correctamente · El arqueo cuadra.</strong> Resultado del cierre ciego:'
                                    : '<strong>Caja cerrada con diferencia asentada.</strong> Resultado del cierre ciego:';
                            }

                            if (resultSum) {
                                resultSum.innerHTML = `
                                    <dt>Monto de apertura (efectivo)</dt><dd>${money(json.monto_apertura)}</dd>
                                    <dt>Ingresos en efectivo</dt><dd class="mv-in">+${money(json.ingresos)}</dd>
                                    <dt>Egresos en efectivo</dt><dd class="mv-out">−${money(json.egresos)}</dd>
                                    <span class="rule"></span>
                                    <dt class="is-big">Efectivo esperado</dt><dd class="is-big">${money(json.monto_esperado)}</dd>
                                    <dt class="is-big">Efectivo físico contado</dt><dd class="is-big">${money(json.monto_cierre)}</dd>
                                    <dt class="is-big">Diferencia</dt><dd class="is-big ${diffClass}">${diffLabel}</dd>
                                `;
                            }

                            openModal('closeModal');
                            return;
                        }

                        // Si el servidor detectó diferencia en el conteo ciego y exige observación obligatoria:
                        // Bloqueamos el campo de monto contado y revelamos la diferencia para que justifique sin poder alterar el conteo.
                        if (json.require_obs) {
                            clAmountInput.value = Number(json.monto_cierre).toFixed(2);
                            clAmountInput.readOnly = true;
                            clAmountInput.style.opacity = '0.75';
                            clAmountInput.style.cursor = 'not-allowed';

                            const diffBox = document.getElementById('clBlindDiffBox');
                            const revSum = document.getElementById('clRevealedSum');
                            const diffMsg = document.getElementById('clDiffMsg');
                            const d = parseFloat(json.diferencia || 0);

                            if (revSum) {
                                revSum.innerHTML = `
                                    <dt>Efectivo esperado (sistema)</dt><dd>${money(json.monto_esperado)}</dd>
                                    <dt>Efectivo contado (fijado)</dt><dd>${money(json.monto_cierre)}</dd>
                                    <dt class="is-big">Diferencia detectada</dt><dd class="is-big ${d > 0 ? 'diff--pos' : 'diff--neg'}">${smoney(d)}</dd>
                                `;
                            }
                            if (diffMsg) {
                                diffMsg.textContent = d > 0
                                    ? `Sobran ${money(d)} respecto al efectivo esperado. El monto contado quedó fijado; ingresá una observación obligatoria para cerrar.`
                                    : `Faltan ${money(Math.abs(d))} respecto al efectivo esperado. El monto contado quedó fijado; ingresá una observación obligatoria para cerrar.`;
                            }
                            if (diffBox) diffBox.hidden = false;
                            if (errObs) {
                                errObs.textContent = 'Explique el motivo de la diferencia para confirmar el cierre.';
                                errObs.hidden = false;
                            }

                            openModal('closeModal');
                            btn.disabled = false;
                            btn.textContent = 'Confirmar cierre con diferencia';
                            clObsInput.focus();
                            return;
                        }

                        openModal('closeModal');
                        showToast(json.error || 'No se pudo cerrar la caja.', 'err');
                        btn.disabled = false;
                        btn.textContent = 'Confirmar cierre';
                    }, minWait);
                } catch (ex) {
                    hideNokiaLcdLoader();
                    openModal('closeModal');
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

            // Abrir modal de cierre ciego
            if (e.target.closest('[data-action="close-caja"]')) {
                openModal('closeModal');
                return;
            }

            // Abrir modal de contra-asiento (reversión inmutable con motivo)
            const revBtn = e.target.closest('[data-action="mv-reverse"]');
            if (revBtn) {
                const idMov = revBtn.dataset.id;
                const concepto = revBtn.dataset.concepto;
                const medio = revBtn.dataset.medio || 'Efectivo';
                const monto = revBtn.dataset.monto;

                const rvMovId = document.getElementById('rvMovId');
                const rvMovDesc = document.getElementById('rvMovDesc');
                const rvMotivo = document.getElementById('rvMotivo');
                const rvMotivoErr = document.getElementById('rvMotivoErr');

                if (rvMovId) rvMovId.value = idMov;
                if (rvMovDesc) rvMovDesc.textContent = `${concepto} (${medio}) por ${money(monto)}`;
                if (rvMotivo) rvMotivo.value = '';
                if (rvMotivoErr) rvMotivoErr.hidden = true;

                openModal('reverseModal');
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
                            ? `Abierta ${c.hora_apertura} hs · En curso (Cierre ciego)`
                            : `${c.hora_apertura} → ${c.hora_cierre || ''} hs`;

                        const d = c.diferencia !== null ? parseFloat(c.diferencia) : null;
                        const diffText = (d === null) ? '—' : near0(d) ? 'Cuadra' : smoney(d);
                        const diffClass = (d === null) ? '' : near0(d) ? 'diff--pos' : d > 0 ? 'diff--pos' : 'diff--neg';

                        if (c.estado === 'abierta') {
                            document.getElementById('dtSum').innerHTML = `
                                <dt>Monto de apertura (efectivo)</dt><dd>${money(c.monto_apertura)}</dd>
                                <dt>Movimientos registrados</dt><dd>${(c.movs || []).length}</dd>
                                <span class="rule"></span>
                                <dt class="is-big">Efectivo esperado</dt><dd class="is-big">Oculto (Cierre ciego)</dd>
                            `;
                        } else {
                            document.getElementById('dtSum').innerHTML = `
                                <dt>Monto de apertura (efectivo)</dt><dd>${money(c.monto_apertura)}</dd>
                                <dt>Ingresos en efectivo</dt><dd class="mv-in">${c.ingresos ? '+' : ''}${money(c.ingresos)}</dd>
                                <dt>Egresos en efectivo</dt><dd class="mv-out">${c.egresos ? '−' : ''}${money(c.egresos)}</dd>
                                <span class="rule"></span>
                                <dt class="is-big">Efectivo esperado</dt><dd class="is-big">${money(c.monto_esperado)}</dd>
                                ${c.monto_cierre !== null ? `
                                <dt class="is-big">Monto de cierre</dt><dd class="is-big">${money(c.monto_cierre)}</dd>
                                <dt>Diferencia</dt><dd class="${diffClass}">${diffText}</dd>
                                ` : ''}
                            `;
                        }

                        document.getElementById('dtObs').textContent = c.observaciones ? `Observación: ${c.observaciones}` : '';

                        const tbody = document.getElementById('dtTbody');
                        if (c.movs && c.movs.length > 0) {
                            tbody.innerHTML = c.movs.map(m => {
                                const medio = m.medio_pago || 'Efectivo';
                                const esEfectivo = medio.toLowerCase() === 'efectivo';
                                return `
                                    <tr style="${m.ya_revertido ? 'opacity:0.6;' : ''}">
                                        <td><span class="nk-mono">${escapeHtml(m.hora)}</span></td>
                                        <td><span class="nk-tag nk-tag--${esEfectivo ? 'lcd' : 'neutral'}">${escapeHtml(medio)}</span></td>
                                        <td>
                                            <span class="nk-cell-main">${escapeHtml(m.concepto)}</span>
                                            ${m.detalle ? `<span class="nk-cell-meta">${escapeHtml(m.detalle)}</span>` : ''}
                                            ${m.motivo ? `<span class="nk-motivo-meta">Motivo: ${escapeHtml(m.motivo)}</span>` : ''}
                                        </td>
                                        <td class="nk-num mv-in">${m.tipo === 'in' ? money(m.monto) : '—'}</td>
                                        <td class="nk-num mv-out">${m.tipo === 'out' ? money(m.monto) : '—'}</td>
                                    </tr>
                                `;
                            }).join('');
                        } else {
                            tbody.innerHTML = `<tr><td colspan="5" class="is-c" style="padding:22px;color:var(--nk-ink-2);">Esta caja no tuvo movimientos registrados.</td></tr>`;
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

            // Imprimir detalle específico
            if (e.target.closest('[data-action="print-detail"]')) {
                document.body.classList.add('print-detail');
                window.print();
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
