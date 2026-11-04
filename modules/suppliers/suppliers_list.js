/**
 * Nokia 1100 · Abastecimiento y Proveedores (suppliers_list.js)
 * Sistema integral: Directorio, Punto de Reposición, Orden Manual,
 * Recepción Verificada en Mostrador y Monitoreo de Stock en Vivo.
 */
(function () {
  'use strict';

  const BOOT = window.NK_SUPPLIERS_DATA || {};
  let suppliers = Array.isArray(BOOT.suppliers) ? BOOT.suppliers : [];
  let catalog = Array.isArray(BOOT.catalog) ? BOOT.catalog : [];
  let orders = Array.isArray(BOOT.orders) ? BOOT.orders : [];
  let currentView = BOOT.initialView || 'sec1';
  let currentRole = BOOT.userRole || 'admin';
  const csrfToken = BOOT.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';

  // Estado interactivo de la vista
  let smartState = new Map(); // id_producto -> { checked, qty, costo }
  let manualCart = [];        // [{ id_producto, sku, nombre, marca, modelo, cantidad, costo }]
  let statusFilter = 'ALL';
  let supplierOrderFilter = null; // id_proveedor o null
  let flashOrderId = null;
  let activeReceiveOrder = null;  // Copia editable para #receiveModal
  let currentWaPayload = { phone: '', text: '', supplierName: '' };

  // Copys de cabecera por sección
  const VIEW_META = {
    sec1: {
      title: 'Proveedores',
      sub: 'Directorio de distribuidores, contacto directo por WhatsApp y acceso rápido a pedidos.'
    },
    sec2: {
      title: 'Asistente de reposición',
      sub: 'Artículos con stock igual o menor al mínimo. Ajustá cantidades, armá el WhatsApp o registrá el pedido.'
    },
    sec3: {
      title: 'Compra manual',
      sub: 'Armá una orden a medida con productos del catálogo o creá artículos nuevos al vuelo.'
    },
    sec4: {
      title: 'Pedidos y recepción',
      sub: 'Seguimiento de órdenes en tránsito y control físico de mercadería antes de impactar el inventario.'
    },
    secStock: {
      title: 'Stock en vivo',
      sub: 'Disponibilidad física comparada contra el doble del stock mínimo configurado.'
    }
  };

  // =====================================================================
  // UTILIDADES
  // =====================================================================
  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

  function esc(val) {
    return String(val ?? '').replace(/[&<>"']/g, ch => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[ch]));
  }

  function money(n) {
    const num = Number(n || 0);
    return '$' + num.toLocaleString('es-AR', {
      minimumFractionDigits: num % 1 === 0 ? 0 : 2,
      maximumFractionDigits: 2
    });
  }

  function initials(name) {
    const parts = String(name || 'PR').trim().split(/\s+/).filter(Boolean);
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
    return String(parts[0] || 'PR').slice(0, 2).toUpperCase();
  }

  function cleanPhone(raw) {
    return String(raw || '').replace(/\D+/g, '');
  }

  function toast(msg, type = 'ok') {
    if (typeof window.showToast === 'function') {
      window.showToast(msg, type === 'err' ? 'error' : 'success');
      return;
    }
    const box = $('#nkToasts');
    if (!box) return;
    const el = document.createElement('div');
    el.className = 'nk-toast' + (type === 'err' ? ' nk-toast--err' : '');
    const icon = type === 'err' ? '#nk-i-x' : '#nk-i-check';
    el.innerHTML = `<svg class="nk-icon" aria-hidden="true"><use href="${icon}"/></svg><span>${esc(msg)}</span>`;
    box.appendChild(el);
    setTimeout(() => {
      if (el.parentNode) el.parentNode.removeChild(el);
    }, 4000);
  }

  function openModal(id) {
    const m = typeof id === 'string' ? document.getElementById(id) : id;
    if (!m) return;
    m.hidden = false;
    m.classList.add('is-open');
    document.body.classList.add('is-locked');
    setTimeout(() => {
      const focusEl = m.querySelector('[data-autofocus]') || m.querySelector('input:not([type="hidden"]), textarea, select, button.nk-btn--primary');
      if (focusEl) focusEl.focus();
    }, 40);
  }

  function closeModal(modalEl) {
    const m = typeof modalEl === 'string' ? document.getElementById(modalEl) : modalEl;
    if (!m) return;
    m.classList.remove('is-open');
    m.hidden = true;
    const anyOpen = $('.nk-modal.is-open:not([hidden])').length > 0;
    if (!anyOpen) {
      document.body.classList.remove('is-locked');
    }
  }

  // Genera las 8 barras de señal estilo Nokia 1100 según ratio de stock
  function renderSignalBars(stock, minimo) {
    const target = Math.max(2, minimo * 2);
    const ratio = stock <= 0 ? 0 : Math.min(1, stock / target);
    const activeBars = stock <= 0 ? 0 : Math.max(1, Math.round(ratio * 8));
    const stateCls = stock <= 0 ? 'nk-bars--out' : (stock <= minimo ? 'nk-bars--low' : 'nk-bars--ok');

    let barsHtml = '';
    for (let i = 1; i <= 8; i++) {
      const h = 4 + (i * 2);
      const onCls = i <= activeBars ? ' class="on"' : '';
      barsHtml += `<i style="height:${h}px"${onCls}></i>`;
    }
    return `<span class="nk-bars ${stateCls}" aria-hidden="true">${barsHtml}</span>`;
  }

  // =====================================================================
  // SINCRONIZACIÓN DE ESTADO Y KPIs
  // =====================================================================
  function syncSmartState() {
    const alertItems = catalog.filter(p => p.stock <= p.minimo);
    const currentIds = new Set(alertItems.map(p => p.id));

    // Limpiar los que ya no están en alerta
    for (const id of smartState.keys()) {
      if (!currentIds.has(id)) smartState.delete(id);
    }

    // Inicializar nuevos artículos en punto de reposición
    alertItems.forEach(p => {
      const suggested = Math.max(1, (p.minimo * 2) - p.stock);
      if (!smartState.has(p.id)) {
        smartState.set(p.id, {
          checked: true,
          qty: suggested,
          costo: p.costo > 0 ? p.costo : 1000
        });
      }
    });
  }

  function updateKPIs() {
    const supCount = suppliers.length;
    const alertCount = catalog.filter(p => p.stock <= p.minimo).length;
    const pendingCount = orders.filter(o => o.estado === 'Pendiente').length;
    const catalogCount = catalog.length;

    const setTxt = (id, val) => {
      const el = document.getElementById(id);
      if (el) {
        el.textContent = String(val);
        el.setAttribute('data-n', String(val));
      }
    };

    setTxt('kpiSuppliers', supCount);
    setTxt('cntSuppliers', supCount);
    setTxt('kpiLow', alertCount);
    setTxt('cntAlert', alertCount);
    setTxt('kpiPending', pendingCount);
    setTxt('cntPending', pendingCount);
    setTxt('kpiCatalog', catalogCount);
  }

  function populateSupplierSelects(preferredId = 0) {
    const smartSel = $('#smartSupplier');
    const manualSel = $('#manualSupplier');

    const currentSmart = preferredId || Number(smartSel?.value || 0) || (suppliers[0]?.id || 0);
    const currentManual = preferredId || Number(manualSel?.value || 0) || (suppliers[0]?.id || 0);

    const optionsHtml = suppliers.length
      ? suppliers.map(s => `<option value="${s.id}">${esc(s.nombre)}${s.atencion ? ' · ' + esc(s.atencion) : ''}</option>`).join('')
      : `<option value="">Sin proveedores registrados</option>`;

    if (smartSel) {
      smartSel.innerHTML = optionsHtml;
      if (currentSmart && suppliers.some(s => s.id === currentSmart)) {
        smartSel.value = String(currentSmart);
      }
    }
    if (manualSel) {
      manualSel.innerHTML = optionsHtml;
      if (currentManual && suppliers.some(s => s.id === currentManual)) {
        manualSel.value = String(currentManual);
      }
    }
    renderSmartSupplierMeta();
  }

  function populateCatalogSelect() {
    const sel = $('#manualProduct');
    if (!sel) return;
    if (!catalog.length) {
      sel.innerHTML = `<option value="">Catálogo vacío — creá un producto nuevo</option>`;
      return;
    }
    sel.innerHTML = catalog.map(p => {
      const full = [p.nombre, p.marca, p.modelo].filter(Boolean).join(' · ');
      return `<option value="${p.id}">[${esc(p.sku)}] ${esc(full)} — Stock: ${p.stock} u. (${money(p.costo)})</option>`;
    }).join('');
  }

  // =====================================================================
  // NAVEGACIÓN ENTRE SECCIONES Y PERFIL
  // =====================================================================
  function switchView(viewId) {
    if (!VIEW_META[viewId]) viewId = 'sec1';
    currentView = viewId;

    $$('section[data-view]').forEach(sec => {
      sec.hidden = (sec.id !== viewId);
    });

    $$('.nk-subnav .nk-nav__item').forEach(btn => {
      if (btn.dataset.view === viewId) {
        btn.setAttribute('aria-current', 'page');
      } else {
        btn.removeAttribute('aria-current');
      }
    });

    $$('.nk-kpis .nk-kpi').forEach(kpi => {
      kpi.setAttribute('aria-pressed', kpi.dataset.view === viewId ? 'true' : 'false');
    });

    const meta = VIEW_META[viewId];
    const titleEl = $('#pageTitle');
    const subEl = $('#pageSub');
    if (titleEl) titleEl.textContent = meta.title;
    if (subEl) subEl.textContent = meta.sub;

    const VIEW_TABLE_MAP = {
      sec1: { id: 'suppliers-table', title: 'Directorio de Proveedores', file: 'proveedores' },
      sec2: { id: 'smart-table', title: 'Artículos en Reposición', file: 'reposicion_stock' },
      sec3: { id: 'manual-table', title: 'Orden Manual de Compra', file: 'orden_compra' },
      sec4: { id: 'purchases-table', title: 'Historial de Pedidos a Proveedores', file: 'pedidos_proveedores' },
      secStock: { id: 'stock-table', title: 'Inventario Físico en Vivo', file: 'stock_en_vivo' }
    };
    const wrap = document.getElementById('headerExportWrap');
    if (wrap) {
      const info = VIEW_TABLE_MAP[viewId] || VIEW_TABLE_MAP.sec1;
      const btnExcel = wrap.querySelector('button[title*="Excel"]');
      const btnPdf = wrap.querySelector('button[title*="PDF"]');
      if (btnExcel) {
        btnExcel.setAttribute('onclick', "exportTableToExcel('" + info.id + "', '" + info.file + "', this)");
      }
      if (btnPdf) {
        btnPdf.setAttribute('onclick', "exportTableToPDF('" + info.id + "', '" + info.title + "', '" + info.file + "', this)");
      }
    }

    try {
      const url = new URL(window.location.href);
      url.searchParams.set('view', viewId);
      window.history.replaceState({}, '', url.toString());
    } catch (e) {}
  }

  function applyRoleMode(role) {
    currentRole = role === 'empleado' ? 'empleado' : 'admin';
    $$('#roleSeg button').forEach(btn => {
      btn.setAttribute('aria-pressed', btn.dataset.v === currentRole ? 'true' : 'false');
    });
    renderSuppliers();
    renderHistory();
  }

  // =====================================================================
  // 01 · DIRECTORIO DE PROVEEDORES
  // =====================================================================
  function renderSuppliers() {
    const tbody = $('#suppliersTbody');
    const empty = $('#suppliersEmpty');
    if (!tbody) return;

    const q = ($('#supSearch')?.value || '').trim().toLowerCase();
    const filtered = suppliers.filter(s => {
      if (!q) return true;
      return [s.nombre, s.atencion, s.telefono, s.email, s.domicilio]
        .some(field => String(field || '').toLowerCase().includes(q));
    });

    tbody.innerHTML = '';
    if (empty) empty.hidden = filtered.length > 0;

    const isMostrador = currentRole === 'empleado';

    filtered.forEach(s => {
      const orderCount = orders.filter(o => o.id_proveedor === s.id).length;
      const waClean = cleanPhone(s.telefono);
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td data-label="Proveedor">
          <div class="nk-who">
            <span class="nk-avatar" aria-hidden="true">${esc(initials(s.nombre))}</span>
            <div>
              <span class="nk-cell-main">${esc(s.nombre)}</span>
              <span class="nk-cell-meta">${orderCount} ${orderCount === 1 ? 'orden registrada' : 'órdenes registradas'}</span>
            </div>
          </div>
        </td>
        <td data-label="Contacto">${s.atencion ? esc(s.atencion) : '<span class="nk-cell-meta">Sin especificar</span>'}</td>
        <td data-label="WhatsApp">
          ${waClean
            ? `<button type="button" class="nk-wa-pill" data-action="wa-supplier" data-id="${s.id}" title="Armar mensaje de WhatsApp para ${esc(s.nombre)}">
                 <svg class="nk-icon" aria-hidden="true" style="stroke:#1d6a38"><use href="#nk-i-chat"/></svg>
                 <span>+${esc(waClean)}</span>
               </button>`
            : '<span class="nk-cell-meta">No cargado</span>'}
        </td>
        <td data-label="Email de órdenes">
          ${s.email
            ? `<a class="link" href="mailto:${esc(s.email)}">${esc(s.email)}</a>`
            : '<span class="nk-cell-meta">No cargado</span>'}
        </td>
        <td data-label="Retiro / Depósito">${s.domicilio ? esc(s.domicilio) : '<span class="nk-cell-meta">Envío a coordinar</span>'}</td>
        <td data-label="Acciones" class="is-r">
          <div class="nk-cell-actions">
            <button type="button" class="nk-btn nk-btn--sm nk-btn--lcd" data-action="order-supplier" data-id="${s.id}" title="Abrir asistente de reposición con este proveedor">
              Pedir
            </button>
            <button type="button" class="nk-btn nk-btn--sm" data-action="wa-supplier" data-id="${s.id}" title="Armar mensaje de WhatsApp para ${esc(s.nombre)}">
              <svg class="nk-icon" aria-hidden="true" style="stroke:#1d6a38"><use href="#nk-i-chat"/></svg>WhatsApp
            </button>
            <button type="button" class="nk-btn nk-btn--sm" data-action="filter-supplier-orders" data-id="${s.id}" title="Ver órdenes de este proveedor">
              Pedidos (${orderCount})
            </button>
            ${!isMostrador ? `
              <button type="button" class="nk-btn nk-btn--sm" data-action="edit-supplier" data-id="${s.id}" title="Editar datos del proveedor">
                <svg class="nk-icon" aria-hidden="true"><use href="#nk-i-edit"/></svg>Editar
              </button>
              <button type="button" class="nk-btn nk-btn--sm nk-btn--danger" data-action="delete-supplier" data-id="${s.id}" title="Eliminar proveedor">
                <svg class="nk-icon" aria-hidden="true"><use href="#nk-i-trash"/></svg>
              </button>
            ` : ''}
          </div>
        </td>
      `;
      tbody.appendChild(tr);
    });
  }

  // =====================================================================
  // 02 · ASISTENTE DE REPOSICIÓN INTELIGENTE
  // =====================================================================
  function renderSmartSupplierMeta() {
    const metaEl = $('#smartMeta');
    const sel = $('#smartSupplier');
    if (!metaEl || !sel) return;

    const supId = Number(sel.value || 0);
    const sup = suppliers.find(s => s.id === supId);
    if (!sup) {
      metaEl.innerHTML = `<div><dd>Registrá o elegí un distribuidor para direccionar la orden.</dd></div>`;
      return;
    }

    const waClean = cleanPhone(sup.telefono);
    metaEl.innerHTML = `
      <div>
        <dt><svg class="nk-icon" aria-hidden="true"><use href="#nk-i-user"/></svg></dt>
        <dd>${esc(sup.atencion || 'Atención comercial')}</dd>
      </div>
      <div>
        <dt><svg class="nk-icon" aria-hidden="true"><use href="#nk-i-phone"/></svg></dt>
        <dd class="nk-mono">${waClean ? '+' + esc(waClean) : 'Sin WhatsApp'}</dd>
      </div>
      <div>
        <dt><svg class="nk-icon" aria-hidden="true"><use href="#nk-i-mail"/></svg></dt>
        <dd>${esc(sup.email || 'Sin email')}</dd>
      </div>
      <div>
        <dt><svg class="nk-icon" aria-hidden="true"><use href="#nk-i-pin"/></svg></dt>
        <dd>${esc(sup.domicilio || 'Entrega a coordinar')}</dd>
      </div>
    `;
  }

  function renderSmartReplenishment() {
    syncSmartState();
    const tbody = $('#smartTbody');
    const empty = $('#smartEmpty');
    const dock = $('#smartDock');
    const checkAll = $('#smartAll');
    if (!tbody) return;

    const alertItems = catalog.filter(p => p.stock <= p.minimo);
    tbody.innerHTML = '';

    if (alertItems.length === 0) {
      if (empty) empty.hidden = false;
      if (dock) dock.hidden = true;
      if (checkAll) checkAll.checked = false;
      return;
    }

    if (empty) empty.hidden = true;
    if (dock) dock.hidden = false;

    alertItems.forEach(p => {
      const st = smartState.get(p.id) || { checked: true, qty: Math.max(1, p.minimo * 2 - p.stock), costo: p.costo };
      const subtotal = st.checked ? (st.qty * st.costo) : 0;
      const isOut = p.stock <= 0;
      const tagHtml = isOut
        ? `<span class="nk-tag nk-tag--bad">Sin stock</span>`
        : `<span class="nk-tag nk-tag--warn">Bajo</span>`;
      const metaText = [p.marca, p.modelo, p.categoria].filter(Boolean).join(' · ');

      const tr = document.createElement('tr');
      if (!st.checked) tr.classList.add('is-off');
      tr.dataset.id = String(p.id);

      tr.innerHTML = `
        <td data-label="Incluir" class="nk-keep">
          <input type="checkbox" class="nk-check" data-smart-check="${p.id}" ${st.checked ? 'checked' : ''} aria-label="Incluir ${esc(p.nombre)}">
        </td>
        <td data-label="SKU"><span class="nk-mono sku">${esc(p.sku)}</span></td>
        <td data-label="Artículo">
          <span class="nk-cell-main">${esc(p.nombre)}</span>
          <span class="nk-cell-meta">${esc(metaText)}</span>
        </td>
        <td data-label="Stock">
          <div class="stock-cell">
            ${renderSignalBars(p.stock, p.minimo)}
            <div>
              ${tagHtml}
              <small>Hay ${p.stock} · mín ${p.minimo}</small>
            </div>
          </div>
        </td>
        <td data-label="Cantidad a pedir">
          <div class="nk-stepper">
            <button type="button" data-smart-step="-1" data-id="${p.id}" aria-label="Restar 1">−</button>
            <input type="number" min="1" value="${st.qty}" data-smart-qty="${p.id}" aria-label="Cantidad para ${esc(p.nombre)}">
            <button type="button" data-smart-step="1" data-id="${p.id}" aria-label="Sumar 1">+</button>
          </div>
        </td>
        <td data-label="Costo unit.">
          <div class="nk-money">
            <span>$</span>
            <input type="number" min="0" step="0.01" class="nk-field nk-field--num" value="${st.costo}" data-smart-cost="${p.id}" aria-label="Costo unitario de ${esc(p.nombre)}">
          </div>
        </td>
        <td data-label="Subtotal" class="nk-num" data-smart-sub="${p.id}">${money(subtotal)}</td>
      `;
      tbody.appendChild(tr);
    });

    updateSmartDock();
  }

  function updateSmartDock() {
    const alertItems = catalog.filter(p => p.stock <= p.minimo);
    let selectedRows = 0;
    let totalUnits = 0;
    let totalMoney = 0;

    alertItems.forEach(p => {
      const st = smartState.get(p.id);
      if (st && st.checked && st.qty > 0) {
        selectedRows++;
        totalUnits += st.qty;
        totalMoney += (st.qty * st.costo);
      }
    });

    const dockCount = $('#dockCount');
    const dockTotal = $('#dockTotal');
    const checkAll = $('#smartAll');

    if (dockCount) dockCount.textContent = `${selectedRows} (${totalUnits} u.)`;
    if (dockTotal) dockTotal.textContent = money(totalMoney);
    if (checkAll && alertItems.length > 0) {
      checkAll.checked = selectedRows === alertItems.length;
    }

    const disabled = selectedRows === 0 || suppliers.length === 0;
    ['btnWa', 'btnPrint', 'btnRegister'].forEach(id => {
      const btn = document.getElementById(id);
      if (btn) btn.disabled = disabled;
    });
  }

  function getSelectedSmartItems() {
    const alertItems = catalog.filter(p => p.stock <= p.minimo);
    const list = [];
    alertItems.forEach(p => {
      const st = smartState.get(p.id);
      if (st && st.checked && st.qty > 0) {
        list.push({
          id_producto: p.id,
          sku: p.sku,
          nombre: p.nombre,
          marca: p.marca,
          modelo: p.modelo,
          cantidad: st.qty,
          costo: st.costo
        });
      }
    });
    return list;
  }

  // =====================================================================
  // 03 · COMPRA MANUAL
  // =====================================================================
  function renderManualOrder() {
    const tbody = $('#manualTbody');
    const empty = $('#manualEmpty');
    const totalEl = $('#manualTotal');
    const btnSave = $('#btnSaveManual');
    const btnWa = $('#btnWaManual');
    if (!tbody) return;

    tbody.innerHTML = '';
    if (manualCart.length === 0) {
      if (empty) empty.hidden = false;
      if (totalEl) totalEl.textContent = '$0';
      if (btnSave) btnSave.disabled = true;
      if (btnWa) btnWa.disabled = true;
      return;
    }

    if (empty) empty.hidden = true;
    if (btnSave) btnSave.disabled = false;
    if (btnWa) btnWa.disabled = false;

    let sum = 0;
    manualCart.forEach((item, idx) => {
      const sub = item.cantidad * item.costo;
      sum += sub;
      const meta = [item.marca, item.modelo].filter(Boolean).join(' · ');
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td data-label="SKU"><span class="nk-mono sku">${esc(item.sku)}</span></td>
        <td data-label="Artículo">
          <span class="nk-cell-main">${esc(item.nombre)}</span>
          ${meta ? `<span class="nk-cell-meta">${esc(meta)}</span>` : ''}
        </td>
        <td data-label="Cantidad">
          <div class="nk-stepper">
            <button type="button" data-manual-step="-1" data-idx="${idx}" aria-label="Restar 1">−</button>
            <input type="number" min="1" value="${item.cantidad}" data-manual-qty="${idx}" aria-label="Cantidad">
            <button type="button" data-manual-step="1" data-idx="${idx}" aria-label="Sumar 1">+</button>
          </div>
        </td>
        <td data-label="Costo unit.">
          <div class="nk-money">
            <span>$</span>
            <input type="number" min="0" step="0.01" class="nk-field nk-field--num" value="${item.costo}" data-manual-cost="${idx}" aria-label="Costo unitario">
          </div>
        </td>
        <td data-label="Subtotal" class="nk-num" data-manual-sub="${idx}">${money(sub)}</td>
        <td data-label="Quitar" class="is-r">
          <button type="button" class="nk-btn nk-btn--sm nk-btn--danger" data-action="manual-remove" data-idx="${idx}" aria-label="Quitar fila">
            <svg class="nk-icon" aria-hidden="true"><use href="#nk-i-x"/></svg>
          </button>
        </td>
      `;
      tbody.appendChild(tr);
    });

    if (totalEl) totalEl.textContent = money(sum);
  }

  function addProductToManualCart(prod, qty = 1, customCost = null) {
    if (!prod) return;
    const existing = manualCart.find(i => i.id_producto === prod.id);
    if (existing) {
      existing.cantidad += qty;
      if (customCost !== null) existing.costo = customCost;
    } else {
      manualCart.push({
        id_producto: prod.id,
        sku: prod.sku,
        nombre: prod.nombre,
        marca: prod.marca || '',
        modelo: prod.modelo || '',
        cantidad: qty,
        costo: customCost !== null ? customCost : (prod.costo > 0 ? prod.costo : 1000)
      });
    }
    renderManualOrder();
  }

  // =====================================================================
  // 04 · HISTORIAL DE PEDIDOS Y RECEPCIÓN VERIFICADA
  // =====================================================================
  function renderHistory() {
    const tbody = $('#historyTbody');
    const empty = $('#historyEmpty');
    const chip = $('#supFilterChip');
    if (!tbody) return;

    // Chip de filtro por proveedor
    if (chip) {
      if (supplierOrderFilter) {
        const sup = suppliers.find(s => s.id === supplierOrderFilter);
        chip.hidden = false;
        chip.innerHTML = `Proveedor: ${esc(sup ? sup.nombre : '#' + supplierOrderFilter)} <button type="button" data-action="clear-sup-filter" aria-label="Quitar filtro"><svg class="nk-icon" style="width:12px;height:12px"><use href="#nk-i-x"/></svg></button>`;
      } else {
        chip.hidden = true;
        chip.innerHTML = '';
      }
    }

    // Actualizar contadores en botones segmentados de estado
    const baseForCounts = supplierOrderFilter
      ? orders.filter(o => o.id_proveedor === supplierOrderFilter)
      : orders;

    const counts = {
      ALL: baseForCounts.length,
      Pendiente: baseForCounts.filter(o => o.estado === 'Pendiente').length,
      Recibido: baseForCounts.filter(o => o.estado === 'Recibido').length,
      Cancelado: baseForCounts.filter(o => o.estado === 'Cancelado').length
    };

    $$('#statusSeg button').forEach(btn => {
      const v = btn.dataset.v;
      const labels = { ALL: 'Todas', Pendiente: 'Pendientes', Recibido: 'Recibidas', Cancelado: 'Canceladas' };
      btn.setAttribute('aria-pressed', v === statusFilter ? 'true' : 'false');
      btn.innerHTML = `${labels[v] || v}<span class="nk-seg__n">${counts[v] ?? 0}</span>`;
    });

    const filtered = baseForCounts.filter(o => statusFilter === 'ALL' || o.estado === statusFilter);
    tbody.innerHTML = '';
    if (empty) empty.hidden = filtered.length > 0;

    filtered.forEach(o => {
      const code = '#OC-' + String(o.id).padStart(4, '0');
      const details = Array.isArray(o.details) ? o.details : [];
      const summaryParts = details.slice(0, 2).map(d => `${d.nombre} ×${d.cantidad}`);
      if (details.length > 2) summaryParts.push(`+${details.length - 2} más`);
      const summaryText = summaryParts.join(', ') || (o.descripcion || 'Orden de abastecimiento');

      let tagCls = 'nk-tag--warn';
      if (o.estado === 'Recibido') tagCls = 'nk-tag--ok';
      else if (o.estado === 'Cancelado') tagCls = 'nk-tag--bad';

      const tr = document.createElement('tr');
      if (flashOrderId === o.id) {
        tr.classList.add('is-flash');
      }

      tr.innerHTML = `
        <td data-label="Orden"><span class="nk-mono">${esc(code)}</span></td>
        <td data-label="Emisión">
          <span class="nk-mono">${esc(o.fecha_fmt || o.fecha)}</span>
          ${o.tiempo_entrega ? `<span class="nk-cell-meta">Entrega: ${esc(o.tiempo_entrega)}</span>` : ''}
        </td>
        <td data-label="Proveedor">
          <span class="nk-cell-main">${esc(o.proveedor_nombre)}</span>
          ${o.autorizado_por ? `<span class="nk-cell-meta">Aut: ${esc(o.autorizado_por)}</span>` : ''}
        </td>
        <td data-label="Detalle">
          <span class="nk-cell-main">${esc(summaryText)}</span>
          <span class="nk-cell-meta">${details.length} ${details.length === 1 ? 'renglón' : 'renglones'}${o.descripcion ? ' · ' + esc(o.descripcion) : ''}</span>
        </td>
        <td data-label="Importe" class="nk-num">${money(o.total)}</td>
        <td data-label="Estado"><span class="nk-tag ${tagCls}">${esc(o.estado)}</span></td>
        <td data-label="Acciones" class="is-r">
          <div class="nk-cell-actions">
            ${o.estado === 'Pendiente' ? `
              <button type="button" class="nk-btn nk-btn--sm nk-btn--lcd" data-action="open-receive" data-id="${o.id}">
                <svg class="nk-icon" aria-hidden="true"><use href="#nk-i-in"/></svg>Recibir caja
              </button>
              <button type="button" class="nk-btn nk-btn--sm" data-action="wa-order" data-id="${o.id}" title="Reenviar pedido por WhatsApp">
                <svg class="nk-icon" aria-hidden="true"><use href="#nk-i-chat"/></svg>
              </button>
              <button type="button" class="nk-btn nk-btn--sm nk-btn--danger" data-action="cancel-order" data-id="${o.id}" title="Cancelar orden">
                Cancelar
              </button>
            ` : `
              <button type="button" class="nk-btn nk-btn--sm" data-action="view-order" data-id="${o.id}">
                <svg class="nk-icon" aria-hidden="true"><use href="#nk-i-eye"/></svg>Detalle
              </button>
              <button type="button" class="nk-btn nk-btn--sm" data-action="wa-order" data-id="${o.id}" title="Ver texto de WhatsApp">
                <svg class="nk-icon" aria-hidden="true"><use href="#nk-i-chat"/></svg>
              </button>
            `}
          </div>
        </td>
      `;
      tbody.appendChild(tr);
    });
  }

  // =====================================================================
  // 05 · STOCK EN VIVO
  // =====================================================================
  function renderLiveStock() {
    const tbody = $('#stockTbody');
    if (!tbody) return;

    const q = ($('#stockSearch')?.value || '').trim().toLowerCase();
    const filtered = catalog.filter(p => {
      if (!q) return true;
      return [p.sku, p.nombre, p.marca, p.modelo, p.categoria]
        .some(f => String(f || '').toLowerCase().includes(q));
    });

    tbody.innerHTML = '';
    filtered.forEach(p => {
      const target = Math.max(2, p.minimo * 2);
      const pct = p.stock <= 0 ? 0 : Math.min(100, Math.round((p.stock / target) * 100));
      const meterCls = p.stock <= 0 ? 'nk-meter--out' : (p.stock <= p.minimo ? 'nk-meter--low' : '');
      const tagHtml = p.stock <= 0
        ? `<span class="nk-tag nk-tag--bad">Sin stock</span>`
        : (p.stock <= p.minimo ? `<span class="nk-tag nk-tag--warn">Alerta</span>` : `<span class="nk-tag nk-tag--ok">Óptimo</span>`);
      const meta = [p.marca, p.modelo, p.categoria].filter(Boolean).join(' · ');

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td data-label="SKU"><span class="nk-mono sku">${esc(p.sku)}</span></td>
        <td data-label="Producto">
          <span class="nk-cell-main">${esc(p.nombre)}</span>
          <span class="nk-cell-meta">${esc(meta)}</span>
        </td>
        <td data-label="Disponibilidad">
          <div class="stock-cell">
            ${renderSignalBars(p.stock, p.minimo)}
            <span class="nk-meter ${meterCls}" style="--w:${pct}%"><i></i></span>
            <span class="nk-mono">${p.stock} u.</span>
            ${tagHtml}
          </div>
        </td>
        <td data-label="Mínimo" class="nk-num">${p.minimo} u.</td>
        <td data-label="Último costo" class="nk-num">${money(p.costo)}</td>
        <td data-label="Acción" class="is-r">
          <button type="button" class="nk-btn nk-btn--sm" data-action="stock-to-manual" data-id="${p.id}">
            <svg class="nk-icon" aria-hidden="true"><use href="#nk-i-plus"/></svg>Orden manual
          </button>
        </td>
      `;
      tbody.appendChild(tr);
    });
  }

  // =====================================================================
  // MODAL: CONTROL DE RECEPCIÓN (#receiveModal)
  // =====================================================================
  function openReceiveModal(orderId) {
    const order = orders.find(o => o.id === orderId);
    if (!order || order.estado !== 'Pendiente') return;

    activeReceiveOrder = {
      id: order.id,
      proveedor_nombre: order.proveedor_nombre,
      items: (order.details || []).map(d => ({
        id_detalle_compra: d.id_detalle_compra,
        id_producto: d.id_producto,
        sku: d.sku,
        nombre: d.nombre,
        marca: d.marca,
        modelo: d.modelo,
        pedido: d.cantidad,
        recibido: d.cantidad,
        costo: d.costo
      }))
    };

    const titleEl = $('#recTitle');
    const subEl = $('#recSub');
    if (titleEl) titleEl.textContent = `Control de recepción · #OC-${String(order.id).padStart(4, '0')}`;
    if (subEl) subEl.textContent = `Proveedor: ${order.proveedor_nombre}. Ajustá las unidades que realmente vinieron en la caja.`;

    renderReceiveModalRows();
    openModal('receiveModal');
  }

  function renderReceiveModalRows() {
    if (!activeReceiveOrder) return;
    const tbody = $('#recBody');
    const totalEl = $('#recTotal');
    if (!tbody) return;

    tbody.innerHTML = '';
    let total = 0;

    activeReceiveOrder.items.forEach((it, idx) => {
      total += it.recibido * it.costo;
      let statusTag = `<span class="nk-tag nk-tag--ok">Completo</span>`;
      if (it.recibido === 0) {
        statusTag = `<span class="nk-tag nk-tag--bad">Sin ingreso</span>`;
      } else if (it.recibido < it.pedido) {
        statusTag = `<span class="nk-tag nk-tag--warn">Parcial (${it.recibido}/${it.pedido})</span>`;
      } else if (it.recibido > it.pedido) {
        statusTag = `<span class="nk-tag nk-tag--lcd">Excedente (+${it.recibido - it.pedido})</span>`;
      }

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td data-label="SKU"><span class="nk-mono sku">${esc(it.sku)}</span></td>
        <td data-label="Artículo">
          <span class="nk-cell-main">${esc(it.nombre)}</span>
          <span class="nk-cell-meta">${esc([it.marca, it.modelo].filter(Boolean).join(' · '))}</span>
        </td>
        <td data-label="Pedido"><span class="nk-mono">${it.pedido} u.</span></td>
        <td data-label="Recibido real">
          <div class="nk-stepper">
            <button type="button" data-rec-step="-1" data-idx="${idx}" aria-label="Restar 1">−</button>
            <input type="number" min="0" value="${it.recibido}" data-rec-qty="${idx}" aria-label="Recibido real">
            <button type="button" data-rec-step="1" data-idx="${idx}" aria-label="Sumar 1">+</button>
          </div>
        </td>
        <td data-label="Estado" data-rec-status="${idx}">${statusTag}</td>
        <td data-label="Costo unit.">
          <div class="nk-money">
            <span>$</span>
            <input type="number" min="0" step="0.01" class="nk-field nk-field--num" value="${it.costo}" data-rec-cost="${idx}" aria-label="Costo unitario">
          </div>
        </td>
      `;
      tbody.appendChild(tr);
    });

    if (totalEl) totalEl.textContent = money(total);
  }

  async function confirmReceivePurchase() {
    if (!activeReceiveOrder) return;
    const btn = $('#btnConfirmReceive');
    if (btn) btn.setAttribute('aria-busy', 'true');

    try {
      const res = await fetch((BOOT.baseUrl || '') + '/modules/suppliers/index.php?action=receive_purchase', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({
          id_compra: activeReceiveOrder.id,
          items: activeReceiveOrder.items,
          csrf_token: csrfToken
        })
      });
      const data = await res.json();
      if (!data.success) {
        toast(data.message || 'Error al acreditar la recepción.', 'err');
        return;
      }

      // Actualizar estado de la orden e impactar stock en vivo localmente
      const ord = orders.find(o => o.id === activeReceiveOrder.id);
      let newTotal = 0;
      activeReceiveOrder.items.forEach(it => {
        newTotal += it.recibido * it.costo;
        const prod = catalog.find(p => p.id === it.id_producto);
        if (prod && it.recibido > 0) {
          prod.stock += it.recibido;
          prod.costo = it.costo;
        }
        if (ord && Array.isArray(ord.details)) {
          const det = ord.details.find(d => d.id_detalle_compra === it.id_detalle_compra || d.id_producto === it.id_producto);
          if (det) {
            det.cantidad = it.recibido;
            det.costo = it.costo;
          }
        }
      });

      if (ord) {
        ord.estado = 'Recibido';
        ord.total = newTotal;
      }

      flashOrderId = activeReceiveOrder.id;
      closeModal('receiveModal');
      activeReceiveOrder = null;

      updateKPIs();
      renderSmartReplenishment();
      renderHistory();
      renderLiveStock();
      toast(data.message || 'Mercadería acreditada al inventario físico.');
    } catch (err) {
      toast('Error de red al confirmar la recepción.', 'err');
    } finally {
      if (btn) btn.removeAttribute('aria-busy');
    }
  }

  // =====================================================================
  // MODAL: DETALLE DE ORDEN (#orderDetailModal)
  // =====================================================================
  function openOrderDetailModal(orderId) {
    const ord = orders.find(o => o.id === orderId);
    if (!ord) return;

    const titleEl = $('#odTitle');
    const metaEl = $('#odMeta');
    const bodyEl = $('#odBody');
    const totalEl = $('#odTotal');

    if (titleEl) titleEl.textContent = `Orden #OC-${String(ord.id).padStart(4, '0')} · ${ord.estado}`;
    if (metaEl) metaEl.textContent = `${ord.proveedor_nombre} · Emitida: ${ord.fecha_fmt || ord.fecha}${ord.autorizado_por ? ' · Aut: ' + ord.autorizado_por : ''}`;

    if (bodyEl) {
      bodyEl.innerHTML = (ord.details || []).map(d => {
        const sub = d.cantidad * d.costo;
        return `
          <tr>
            <td data-label="SKU"><span class="nk-mono sku">${esc(d.sku)}</span></td>
            <td data-label="Artículo">
              <span class="nk-cell-main">${esc(d.nombre)}</span>
              <span class="nk-cell-meta">${esc([d.marca, d.modelo].filter(Boolean).join(' · '))}</span>
            </td>
            <td data-label="Cantidad" class="is-c"><span class="nk-mono">${d.cantidad} u.</span></td>
            <td data-label="Costo unit." class="nk-num">${money(d.costo)}</td>
            <td data-label="Subtotal" class="nk-num">${money(sub)}</td>
          </tr>
        `;
      }).join('');
    }
    if (totalEl) totalEl.textContent = money(ord.total);
    openModal('orderDetailModal');
  }

  // =====================================================================
  // MODAL: WHATSAPP (#waModal)
  // =====================================================================
  function openSupplierChatModal(supplierId) {
    const sup = suppliers.find(s => s.id === Number(supplierId));
    if (!sup) {
      toast('Proveedor no encontrado.', 'err');
      return;
    }
    const phone = cleanPhone(sup.telefono);
    const contactGreeting = sup.atencion ? `Hola ${sup.atencion} (${sup.nombre})` : `Hola ${sup.nombre}`;
    
    // Revisar si hay artículos con alerta para reponer
    const alertItems = catalog.filter(p => p.stock <= p.minimo);
    let text = '';
    if (alertItems.length > 0) {
      const lines = alertItems.slice(0, 6).map(i => {
        const qty = Math.max(1, (i.minimo * 2) - i.stock);
        const label = [i.nombre, i.marca, i.modelo].filter(Boolean).join(' ');
        return `  • *${qty} u.* · [${i.sku}] ${label}`;
      }).join('\n');
      text = `${contactGreeting}, te contacto de *Nokia 1100*.\n\nQuería consultar cotización y stock para reposición de los siguientes artículos:\n\n${lines}\n\n¿Tenés disponibilidad para retiro o envío? ¡Muchas gracias!`;
    } else {
      text = `${contactGreeting}, te contacto desde *Nokia 1100* para consultar sobre disponibilidad de stock y lista de precios actualizada de repuestos y accesorios.\n\n¡Muchas gracias!`;
    }

    currentWaPayload = { phone, text, supplierName: sup.nombre };
    const toEl = $('#waTo');
    const txtEl = $('#waText');
    if (toEl) {
      toEl.textContent = `Destinatario: ${sup.nombre}${phone ? ' (+' + phone + ')' : ' (sin teléfono registrado)'}`;
    }
    if (txtEl) {
      if (txtEl.tagName === 'TEXTAREA') {
        txtEl.value = text;
      } else {
        txtEl.textContent = text;
      }
    }
    openModal('waModal');
  }

  function openWhatsAppModal(supplierId, items, refCode = '') {
    const sup = suppliers.find(s => s.id === Number(supplierId));
    if (!sup) {
      toast('Seleccioná primero un proveedor destinatario.', 'err');
      return;
    }
    if (!items || items.length === 0) {
      toast('No hay artículos seleccionados para armar el pedido.', 'err');
      return;
    }

    const total = items.reduce((acc, i) => acc + (i.cantidad * i.costo), 0);
    const contactGreeting = sup.atencion ? `Hola ${sup.atencion} (${sup.nombre})` : `Hola ${sup.nombre}`;
    const headerLine = refCode
      ? `${contactGreeting}, te comparto la orden de compra *${refCode}* de *Nokia 1100*:`
      : `${contactGreeting}, te paso consulta / pedido de reposición para *Nokia 1100*:`;

    const itemLines = items.map(i => {
      const label = [i.nombre, i.marca, i.modelo].filter(Boolean).join(' ');
      return `  • *${i.cantidad} u.* · [${i.sku}] ${label} - ${money(i.costo)} c/u`;
    }).join('\n');

    const text = `${headerLine}\n\n${itemLines}\n\n*Total estimado:* ${money(total)}\n\nPor favor confirmanos disponibilidad y demora de retiro/envío. ¡Muchas gracias!`;
    const phone = cleanPhone(sup.telefono);

    currentWaPayload = { phone, text, supplierName: sup.nombre };
    const toEl = $('#waTo');
    const txtEl = $('#waText');
    if (toEl) {
      toEl.textContent = `Destinatario: ${sup.nombre}${phone ? ' (+' + phone + ')' : ' (sin teléfono registrado)'}`;
    }
    if (txtEl) {
      if (txtEl.tagName === 'TEXTAREA') {
        txtEl.value = text;
      } else {
        txtEl.textContent = text;
      }
    }
    openModal('waModal');
  }

  // =====================================================================
  // GUARDAR ÓRDENES (REPOSICIÓN Y COMPRA MANUAL)
  // =====================================================================
  async function submitPurchaseOrder({ supplierId, items, descripcion, tiempoEntrega, btnEl }) {
    if (!supplierId) {
      toast('Seleccioná un proveedor destinatario.', 'err');
      return;
    }
    if (!items || items.length === 0) {
      toast('Agregá al menos un artículo a la orden.', 'err');
      return;
    }

    if (btnEl) btnEl.setAttribute('aria-busy', 'true');
    try {
      const res = await fetch((BOOT.baseUrl || '') + '/modules/suppliers/index.php?action=new_purchase', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({
          id_proveedor: supplierId,
          items: items,
          descripcion: descripcion,
          tiempo_entrega: tiempoEntrega,
          iva: 0,
          autorizado_por: BOOT.currentUser || 'Admin',
          csrf_token: csrfToken
        })
      });
      const data = await res.json();
      if (!data.success) {
        toast(data.message || 'Error al registrar la orden.', 'err');
        return false;
      }

      const p = data.purchase;
      const total = items.reduce((acc, i) => acc + (i.cantidad * i.costo), 0);
      const newOrder = {
        id: Number(data.id_compra),
        id_proveedor: Number(supplierId),
        proveedor_nombre: p?.proveedor_nombre || (suppliers.find(s => s.id === Number(supplierId))?.nombre || 'Proveedor'),
        fecha: p?.fecha || new Date().toISOString(),
        fecha_fmt: 'Recién emitida',
        descripcion: descripcion,
        tiempo_entrega: tiempoEntrega,
        autorizado_por: BOOT.currentUser || 'Admin',
        total: total,
        estado: 'Pendiente',
        details: items.map((it, idx) => ({
          id_detalle_compra: p?.details?.[idx]?.id_detalle_compra || 0,
          id_producto: it.id_producto,
          sku: it.sku,
          nombre: it.nombre,
          marca: it.marca || '',
          modelo: it.modelo || '',
          cantidad: it.cantidad,
          costo: it.costo
        }))
      };

      orders.unshift(newOrder);
      flashOrderId = newOrder.id;
      statusFilter = 'ALL';
      supplierOrderFilter = null;

      updateKPIs();
      renderSuppliers();
      renderHistory();
      switchView('sec4');
      toast(data.message || 'Orden registrada como Pendiente.');
      return true;
    } catch (err) {
      toast('Error de conexión al registrar la orden.', 'err');
      return false;
    } finally {
      if (btnEl) btnEl.removeAttribute('aria-busy');
    }
  }

  // =====================================================================
  // MODAL: PROVEEDOR (ALTA / EDICIÓN) Y PRODUCTO RÁPIDO
  // =====================================================================
  function openSupplierModal(supplierId = 0) {
    const form = $('#supplierForm');
    if (form) form.reset();
    ['nsNameErr', 'nsPhoneErr', 'nsEmailErr'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.hidden = true;
    });

    const sup = supplierId ? suppliers.find(s => s.id === Number(supplierId)) : null;
    $('#nsId').value = sup ? String(sup.id) : '0';
    $('#nsTitle').textContent = sup ? 'Editar proveedor' : 'Registrar proveedor';
    $('#nsName').value = sup ? sup.nombre : '';
    $('#nsContact').value = sup ? sup.atencion : '';
    $('#nsPhone').value = sup ? sup.telefono : '';
    $('#nsEmail').value = sup ? sup.email : '';
    $('#nsAddress').value = sup ? sup.domicilio : '';

    openModal('supplierModal');
  }

  async function saveSupplierFromModal() {
    const id = Number($('#nsId')?.value || 0);
    const nombre = ($('#nsName')?.value || '').trim();
    const atencion = ($('#nsContact')?.value || '').trim();
    const telefono = ($('#nsPhone')?.value || '').trim();
    const email = ($('#nsEmail')?.value || '').trim();
    const domicilio = ($('#nsAddress')?.value || '').trim();

    const nameErr = $('#nsNameErr');
    if (!nombre) {
      if (nameErr) {
        nameErr.textContent = 'Ingresá la razón social o nombre comercial.';
        nameErr.hidden = false;
      }
      $('#nsName')?.focus();
      return;
    }
    if (nameErr) nameErr.hidden = true;

    const btn = $('#btnSaveSupplier');
    if (btn) btn.setAttribute('aria-busy', 'true');

    try {
      const apiUrl = (BOOT.baseUrl || '') + '/modules/suppliers/index.php?action=save_supplier';
      const res = await fetch(apiUrl, {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({
          id_proveedor: id,
          nombre,
          atencion,
          telefono,
          email,
          domicilio,
          csrf_token: csrfToken
        })
      });
      const data = await res.json();
      if (!data.success) {
        toast(data.message || 'No se pudo guardar el proveedor.', 'err');
        return;
      }

      const saved = data.supplier;
      if (data.mode === 'update') {
        const idx = suppliers.findIndex(s => s.id === saved.id);
        if (idx !== -1) suppliers[idx] = saved;
      } else {
        suppliers.push(saved);
        suppliers.sort((a, b) => a.nombre.localeCompare(b.nombre, 'es'));
      }

      closeModal('supplierModal');
      updateKPIs();
      populateSupplierSelects(saved.id);
      renderSuppliers();
      toast(data.message || 'Proveedor guardado.');
    } catch (err) {
      toast('Error de red al guardar el proveedor.', 'err');
    } finally {
      if (btn) btn.removeAttribute('aria-busy');
    }
  }

  async function deleteSupplierById(supplierId) {
    const sup = suppliers.find(s => s.id === Number(supplierId));
    if (!sup) return;

    const hasOrders = orders.some(o => o.id_proveedor === sup.id);
    if (hasOrders) {
      toast(`No se puede eliminar «${sup.nombre}» porque tiene órdenes registradas.`, 'err');
      return;
    }

    let ok = false;
    if (typeof window.showConfirmModal === 'function') {
      ok = await window.showConfirmModal(
        'Eliminar Proveedor',
        `¿Seguro que querés eliminar a «${sup.nombre}» del directorio?`,
        'Sí, eliminar',
        'Cancelar',
        true
      );
    } else {
      ok = window.confirm(`¿Eliminar a «${sup.nombre}» del directorio?`);
    }
    if (!ok) return;

    try {
      const res = await fetch((BOOT.baseUrl || '') + '/modules/suppliers/index.php?action=delete_supplier', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({
          id_proveedor: sup.id,
          csrf_token: csrfToken
        })
      });
      const data = await res.json();
      if (!data.success) {
        toast(data.message || 'No se pudo eliminar el proveedor.', 'err');
        return;
      }
      suppliers = suppliers.filter(s => s.id !== sup.id);
      updateKPIs();
      populateSupplierSelects();
      renderSuppliers();
      toast(data.message || 'Proveedor eliminado.');
    } catch (err) {
      toast('Error de red al eliminar el proveedor.', 'err');
    }
  }

  async function saveQuickProductFromModal() {
    const sku = ($('#qpSku')?.value || '').trim();
    const categoria = ($('#qpCategory')?.value || 'Accesorios').trim();
    const nombre = ($('#qpName')?.value || '').trim();
    const marca = ($('#qpBrand')?.value || '').trim();
    const modelo = ($('#qpModel')?.value || '').trim();
    const costo = Math.max(0, parseFloat($('#qpCost')?.value || '0') || 0);
    const qty = Math.max(1, parseInt($('#qpQty')?.value || '1', 10) || 1);

    const errEl = $('#qpNameErr');
    if (!nombre) {
      if (errEl) {
        errEl.textContent = 'El nombre del producto es obligatorio.';
        errEl.hidden = false;
      }
      $('#qpName')?.focus();
      return;
    }
    if (errEl) errEl.hidden = true;

    const btn = $('#btnSaveQuick');
    if (btn) btn.setAttribute('aria-busy', 'true');

    try {
      const res = await fetch((BOOT.baseUrl || '') + '/modules/suppliers/index.php?action=quick_product', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({
          sku,
          categoria,
          nombre,
          marca,
          modelo,
          costo,
          minimo: 3,
          csrf_token: csrfToken
        })
      });
      const data = await res.json();
      if (!data.success) {
        toast(data.message || 'Error al crear el producto.', 'err');
        return;
      }

      const p = data.product;
      const newCatItem = {
        id: Number(p.id_producto),
        sku: p.sku,
        nombre: p.nombre,
        marca: p.marca || '',
        modelo: p.modelo || '',
        categoria: p.categoria || 'Accesorios',
        stock: 0,
        minimo: Number(p.stock_minimo || 3),
        costo: Number(p.precio || costo)
      };

      catalog.push(newCatItem);
      populateCatalogSelect();
      addProductToManualCart(newCatItem, qty, costo);

      closeModal('quickModal');
      $('#quickForm')?.reset();
      updateKPIs();
      renderSmartReplenishment();
      renderLiveStock();
      switchView('sec3');
      toast(data.message || 'Producto creado y sumado a la orden.');
    } catch (err) {
      toast('Error de conexión al crear el producto.', 'err');
    } finally {
      if (btn) btn.removeAttribute('aria-busy');
    }
  }

  async function cancelOrderById(orderId) {
    const ord = orders.find(o => o.id === Number(orderId));
    if (!ord || ord.estado !== 'Pendiente') return;

    let ok = false;
    if (typeof window.showConfirmModal === 'function') {
      ok = await window.showConfirmModal(
        'Cancelar Orden de Compra',
        `¿Confirmás la cancelación de la orden #OC-${String(ord.id).padStart(4, '0')} (${ord.proveedor_nombre})?`,
        'Sí, cancelar orden',
        'Volver',
        true
      );
    } else {
      ok = window.confirm(`¿Cancelar la orden #OC-${String(ord.id).padStart(4, '0')}?`);
    }
    if (!ok) return;

    try {
      const res = await fetch((BOOT.baseUrl || '') + '/modules/suppliers/index.php?action=cancel_purchase', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({
          id_compra: ord.id,
          csrf_token: csrfToken
        })
      });
      const data = await res.json();
      if (!data.success) {
        toast(data.message || 'No se pudo cancelar la orden.', 'err');
        return;
      }
      ord.estado = 'Cancelado';
      updateKPIs();
      renderHistory();
      toast(data.message || 'Orden cancelada.');
    } catch (err) {
      toast('Error de red al cancelar la orden.', 'err');
    }
  }

  // =====================================================================
  // EVENTOS DELEGADOS E INICIALIZACIÓN
  // =====================================================================

  // Atajo de teclado "/" para enfocar el buscador visible y Escape para modales
  document.addEventListener('keydown', e => {
    if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
      const searchEl = currentView === 'secStock' ? $('#stockSearch') : $('#supSearch');
      if (searchEl && !searchEl.closest('[hidden]')) {
        e.preventDefault();
        searchEl.focus();
      }
    } else if (e.key === 'Escape') {
      const openModals = $$('.nk-modal.is-open:not([hidden])');
      if (openModals.length) {
        closeModal(openModals[openModals.length - 1]);
      }
    }
  });

  // Delegación de cambios en inputs de tablas (Reposición, Manual, Recepción)
  document.addEventListener('input', e => {
    const t = e.target;
    if (!t) return;

    // Reposición inteligente: cantidad o costo
    if (t.matches('[data-smart-qty]')) {
      const id = Number(t.dataset.smartQty);
      const st = smartState.get(id);
      if (st) {
        st.qty = Math.max(1, parseInt(t.value, 10) || 1);
        const subEl = $(`[data-smart-sub="${id}"]`);
        if (subEl) subEl.textContent = money(st.checked ? st.qty * st.costo : 0);
        updateSmartDock();
      }
    } else if (t.matches('[data-smart-cost]')) {
      const id = Number(t.dataset.smartCost);
      const st = smartState.get(id);
      if (st) {
        st.costo = Math.max(0, parseFloat(t.value) || 0);
        const subEl = $(`[data-smart-sub="${id}"]`);
        if (subEl) subEl.textContent = money(st.checked ? st.qty * st.costo : 0);
        updateSmartDock();
      }
    }

    // Orden Manual: cantidad o costo
    else if (t.matches('[data-manual-qty]')) {
      const idx = Number(t.dataset.manualQty);
      if (manualCart[idx]) {
        manualCart[idx].cantidad = Math.max(1, parseInt(t.value, 10) || 1);
        const subEl = $(`[data-manual-sub="${idx}"]`);
        if (subEl) subEl.textContent = money(manualCart[idx].cantidad * manualCart[idx].costo);
        const sum = manualCart.reduce((a, i) => a + (i.cantidad * i.costo), 0);
        const tot = $('#manualTotal');
        if (tot) tot.textContent = money(sum);
      }
    } else if (t.matches('[data-manual-cost]')) {
      const idx = Number(t.dataset.manualCost);
      if (manualCart[idx]) {
        manualCart[idx].costo = Math.max(0, parseFloat(t.value) || 0);
        const subEl = $(`[data-manual-sub="${idx}"]`);
        if (subEl) subEl.textContent = money(manualCart[idx].cantidad * manualCart[idx].costo);
        const sum = manualCart.reduce((a, i) => a + (i.cantidad * i.costo), 0);
        const tot = $('#manualTotal');
        if (tot) tot.textContent = money(sum);
      }
    }

    // Control de Recepción: recibido real o costo
    else if (t.matches('[data-rec-qty]') && activeReceiveOrder) {
      const idx = Number(t.dataset.recQty);
      const item = activeReceiveOrder.items[idx];
      if (item) {
        item.recibido = Math.max(0, parseInt(t.value, 10) || 0);
        renderReceiveModalRows();
      }
    } else if (t.matches('[data-rec-cost]') && activeReceiveOrder) {
      const idx = Number(t.dataset.recCost);
      const item = activeReceiveOrder.items[idx];
      if (item) {
        item.costo = Math.max(0, parseFloat(t.value) || 0);
        const total = activeReceiveOrder.items.reduce((a, i) => a + (i.recibido * i.costo), 0);
        const totEl = $('#recTotal');
        if (totEl) totEl.textContent = money(total);
      }
    }
  });

  document.addEventListener('change', e => {
    const t = e.target;
    if (!t) return;
    if (t.matches('[data-smart-check]')) {
      const id = Number(t.dataset.smartCheck);
      const st = smartState.get(id);
      if (st) {
        st.checked = t.checked;
        const tr = t.closest('tr');
        if (tr) tr.classList.toggle('is-off', !st.checked);
        const subEl = $(`[data-smart-sub="${id}"]`);
        if (subEl) subEl.textContent = money(st.checked ? st.qty * st.costo : 0);
        updateSmartDock();
      }
    }
  });

  // Delegación general de clics (activa de forma inmediata y global)
  document.addEventListener('click', e => {
    const targetEl = e.target instanceof Element ? e.target : e.target?.parentElement;
    if (!targetEl) return;

    // Cerrar modal con [data-nk-close] o clic en fondo oscuro .nk-modal
    const closeBtn = targetEl.closest('[data-nk-close]');
    if (closeBtn) {
      closeModal(closeBtn.closest('.nk-modal'));
      return;
    }
    if (targetEl.classList && typeof targetEl.classList.contains === 'function' && targetEl.classList.contains('nk-modal')) {
      closeModal(targetEl);
      return;
    }

    // Steppers de Reposición
    const smartStep = targetEl.closest('[data-smart-step]');
    if (smartStep) {
      const id = Number(smartStep.dataset.id);
      const delta = Number(smartStep.dataset.smartStep);
      const st = smartState.get(id);
      if (st) {
        st.qty = Math.max(1, st.qty + delta);
        renderSmartReplenishment();
      }
      return;
    }

    // Steppers de Orden Manual
    const manualStep = targetEl.closest('[data-manual-step]');
    if (manualStep) {
      const idx = Number(manualStep.dataset.idx);
      const delta = Number(manualStep.dataset.manualStep);
      if (manualCart[idx]) {
        manualCart[idx].cantidad = Math.max(1, manualCart[idx].cantidad + delta);
        renderManualOrder();
      }
      return;
    }

    // Steppers de Recepción
    const recStep = targetEl.closest('[data-rec-step]');
    if (recStep && activeReceiveOrder) {
      const idx = Number(recStep.dataset.idx);
      const delta = Number(recStep.dataset.recStep);
      if (activeReceiveOrder.items[idx]) {
        activeReceiveOrder.items[idx].recibido = Math.max(0, activeReceiveOrder.items[idx].recibido + delta);
        renderReceiveModalRows();
      }
      return;
    }

    // Control segmentado de Estado en Historial
    const statusBtn = targetEl.closest('#statusSeg button');
    if (statusBtn) {
      statusFilter = statusBtn.dataset.v;
      renderHistory();
      return;
    }

    // Acciones con data-action
    const actEl = targetEl.closest('[data-action]');
    if (!actEl) return;
    const action = actEl.dataset.action;

    switch (action) {
      case 'view':
        switchView(actEl.dataset.view);
        break;

      case 'new-supplier':
        openSupplierModal(0);
        break;

      case 'edit-supplier':
        openSupplierModal(Number(actEl.dataset.id));
        break;

      case 'save-supplier':
        saveSupplierFromModal();
        break;

      case 'delete-supplier':
        deleteSupplierById(Number(actEl.dataset.id));
        break;

      case 'order-supplier': {
        const supId = Number(actEl.dataset.id);
        populateSupplierSelects(supId);
        const hasAlerts = catalog.some(p => p.stock <= p.minimo);
        switchView(hasAlerts ? 'sec2' : 'sec3');
        toast('Proveedor seleccionado en el asistente de pedido.');
        break;
      }

      case 'filter-supplier-orders': {
        supplierOrderFilter = Number(actEl.dataset.id);
        statusFilter = 'ALL';
        renderHistory();
        switchView('sec4');
        break;
      }

      case 'clear-sup-filter':
        supplierOrderFilter = null;
        renderHistory();
        break;

      case 'wa-supplier': {
        openSupplierChatModal(Number(actEl.dataset.id));
        break;
      }

      case 'wa': {
        const supId = Number($('#smartSupplier')?.value || 0);
        const items = getSelectedSmartItems();
        openWhatsAppModal(supId, items);
        break;
      }

      case 'wa-manual': {
        const supId = Number($('#manualSupplier')?.value || 0);
        openWhatsAppModal(supId, manualCart);
        break;
      }

      case 'wa-order': {
        const ord = orders.find(o => o.id === Number(actEl.dataset.id));
        if (ord) {
          openWhatsAppModal(ord.id_proveedor, ord.details || [], `#OC-${String(ord.id).padStart(4, '0')}`);
        }
        break;
      }

      case 'wa-copy': {
        const text = $('#waText')?.value || currentWaPayload.text || '';
        if (text) {
          if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
              toast('Mensaje copiado al portapapeles.');
            }).catch(() => {
              $('#waText')?.select();
              document.execCommand('copy');
              toast('Mensaje copiado al portapapeles.');
            });
          } else {
            $('#waText')?.select();
            document.execCommand('copy');
            toast('Mensaje copiado al portapapeles.');
          }
        }
        break;
      }

      case 'wa-open': {
        const text = $('#waText')?.value || currentWaPayload.text || '';
        const encoded = encodeURIComponent(text);
        const phone = currentWaPayload.phone;
        const url = phone
          ? `https://wa.me/${phone}?text=${encoded}`
          : `https://wa.me/?text=${encoded}`;
        window.open(url, '_blank', 'noopener');
        break;
      }

      case 'print':
        window.print();
        break;

      case 'register-smart': {
        const supId = Number($('#smartSupplier')?.value || 0);
        const items = getSelectedSmartItems();
        submitPurchaseOrder({
          supplierId: supId,
          items,
          descripcion: 'Reposición automática de stock crítico',
          tiempoEntrega: 'A coordinar',
          btnEl: $('#btnRegister')
        });
        break;
      }

      case 'quick-product':
        openModal('quickModal');
        break;

      case 'save-quick':
        saveQuickProductFromModal();
        break;

      case 'manual-add': {
        const prodId = Number($('#manualProduct')?.value || 0);
        const prod = catalog.find(p => p.id === prodId);
        if (!prod) {
          toast('Elegí un producto del catálogo o creá uno nuevo.', 'err');
          return;
        }
        addProductToManualCart(prod, 1);
        toast(`«${prod.nombre}» agregado a la orden manual.`);
        break;
      }

      case 'manual-remove': {
        const idx = Number(actEl.dataset.idx);
        manualCart.splice(idx, 1);
        renderManualOrder();
        break;
      }

      case 'save-manual': {
        const supId = Number($('#manualSupplier')?.value || 0);
        const desc = ($('#manualDesc')?.value || '').trim() || 'Orden de compra manual';
        const eta = ($('#manualEta')?.value || '').trim() || 'A coordinar';
        submitPurchaseOrder({
          supplierId: supId,
          items: manualCart,
          descripcion: desc,
          tiempoEntrega: eta,
          btnEl: $('#btnSaveManual')
        }).then(ok => {
          if (ok) {
            manualCart = [];
            if ($('#manualDesc')) $('#manualDesc').value = '';
            if ($('#manualEta')) $('#manualEta').value = '';
            renderManualOrder();
          }
        });
        break;
      }

      case 'open-receive':
        openReceiveModal(Number(actEl.dataset.id));
        break;

      case 'confirm-receive':
        confirmReceivePurchase();
        break;

      case 'cancel-order':
        cancelOrderById(Number(actEl.dataset.id));
        break;

      case 'view-order':
        openOrderDetailModal(Number(actEl.dataset.id));
        break;

      case 'stock-to-manual': {
        const prod = catalog.find(p => p.id === Number(actEl.dataset.id));
        if (prod) {
          const suggested = Math.max(1, (prod.minimo * 2) - prod.stock);
          addProductToManualCart(prod, suggested);
          switchView('sec3');
          toast(`«${prod.nombre}» enviado a Compra manual (${suggested} u.).`);
        }
        break;
      }
    }
  });

  // Inicialización de la vista
  function initModule() {
    try {
      populateSupplierSelects(BOOT.preselectedSupplier || 0);
      populateCatalogSelect();
      updateKPIs();
      renderSuppliers();
      renderSmartReplenishment();
      renderManualOrder();
      renderHistory();
      renderLiveStock();
      applyRoleMode(currentRole);
      switchView(currentView);
    } catch (err) {
      console.error('[NK_SUPPLIERS] Error durante inicialización:', err);
    }

    // Buscadores en vivo
    $('#supSearch')?.addEventListener('input', renderSuppliers);
    $('#stockSearch')?.addEventListener('input', renderLiveStock);
    $('#smartSupplier')?.addEventListener('change', renderSmartSupplierMeta);

    // Formularios
    $('#supplierForm')?.addEventListener('submit', e => {
      e.preventDefault();
      saveSupplierFromModal();
    });

    $('#quickForm')?.addEventListener('submit', e => {
      e.preventDefault();
      saveQuickProductFromModal();
    });

    $('#smartAll')?.addEventListener('change', e => {
      const checked = e.target.checked;
      for (const st of smartState.values()) {
        st.checked = checked;
      }
      renderSmartReplenishment();
    });
  }

  // Ejecutar inicialización ya sea si el DOM ya cargó o cuando termine de cargar
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initModule);
  } else {
    initModule();
  }

  // Exponer métodos clave a window para interoperabilidad total
  window.NK_SUPPLIERS = {
    openSupplierModal,
    openSupplierChatModal,
    openWhatsAppModal,
    switchView,
    renderSuppliers,
    renderHistory,
    renderLiveStock
  };

  // Alias directos en window por si se llaman desde onclick
  window.openSupplierModal = openSupplierModal;
  window.switchSupplierView = switchView;
})();
