/**
 * Nokia 1100 - Módulo de Inventario
 * Lógica del lado del cliente: filtrado reactivo, KPIs, ordenamiento,
 * paginado, animaciones Motion y modales CRUD vía AJAX.
 */

(function () {
  'use strict';

  // Configuración y datos bootstrap inyectados por PHP
  const data = window.INVENTORY_DATA || {};
  const CSRF_TOKEN = data.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const IS_ADMIN = Boolean(data.isAdmin);
  const BASE_URL = data.baseUrl || window.BASE_URL || '';
  const CATEGORIES = Array.isArray(data.categories) ? data.categories : ['Celulares', 'Fundas', 'Audio', 'Cargadores', 'Repuestos', 'Perifericos', 'Accesorios'];
  const CAT_COLOR = data.catColors || {
    'Celulares': '#1f5fa8',
    'Fundas': '#b36a00',
    'Audio': '#a63d86',
    'Cargadores': '#5c6165',
    'Repuestos': '#1d6a38',
    'Perifericos': '#2e7d32',
    'Accesorios': '#4338ca'
  };

  let products = Array.isArray(data.products)
    ? data.products.map(p => ({
      ...p,
      id: Number(p.id) || 0,
      cantidad: Number(p.cantidad ?? 0),
      minimo: Number(p.minimo ?? 3),
      precio: Number(p.precio ?? 0),
      activo: Boolean(p.activo)
    }))
    : [];

  /* ---------- Estado Reactivo del Módulo ---------- */
  const S = {
    q: data.searchQuery || '',
    cat: data.selectedCat || 'all',
    estado: 'all',
    stock: data.selectedStock || 'all',
    sort: { key: 'nombre', dir: 1 },
    page: 1,
    per: 10,
    editing: null,
    loading: false
  };

  /* ---------- Selectores y Utilidades ---------- */
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const money = n => '$' + new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n || 0);
  const norm = s => String(s ?? '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  const ico = n => `<svg class="i" aria-hidden="true"><use href="#i-${n}"/></svg>`;
  const reduced = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
  const motionOk = () => window.fx && !reduced();
  const run = fn => { try { fn(); } catch (e) { } };
  const level = p => p.cantidad === 0 ? 'out' : p.cantidad <= p.minimo ? 'low' : 'ok';
  const catColor = c => CAT_COLOR[c] || '#5c6165';
  const catIconSvg = (cat) => {
    const s = norm(cat);
    let id = 'cat-accesorios';
    if (s.includes('audio') || s.includes('auricular') || s.includes('parlante') || s.includes('sound') || s.includes('headphone')) {
      id = 'cat-audio';
    } else if (s.includes('repuesto') || s.includes('pieza') || s.includes('taller') || s.includes('bateria') || s.includes('pantalla') || s.includes('modulo')) {
      id = 'cat-repuestos';
    } else if (s.includes('periferico') || s.includes('mouse') || s.includes('teclado') || s.includes('raton')) {
      id = 'cat-perifericos';
    } else if (s.includes('funda') || s.includes('case') || s.includes('silicona') || s.includes('cover')) {
      id = 'cat-fundas';
    } else if (s.includes('cargador') || s.includes('cable') || s.includes('enchufe') || s.includes('fuente') || s.includes('adaptador') || s.includes('usb')) {
      id = 'cat-cargadores';
    } else if (s.includes('celular') || s.includes('smartphone') || s.includes('telefono') || s.includes('movil')) {
      id = 'cat-celulares';
    } else if (s.includes('accesorio') || s.includes('pendrive') || s.includes('memoria') || s.includes('flash') || s.includes('storage')) {
      id = 'cat-accesorios';
    }
    return `<svg class="cat-icon" aria-hidden="true" focusable="false"><use href="#${id}"/></svg>`;
  };

  /* ---------- Micro-animaciones con Motion ---------- */
  const fx = {
    rows() {
      if (!motionOk()) return;
      const r = $$('#tbody tr:not(.skel)');
      if (r.length) {
        run(() => window.fx.animate(r, { opacity: [0, 1], transform: ['translateY(6px)', 'translateY(0px)'] }, { duration: 0.2, delay: window.fx.stagger(0.02) }));
      }
    },
    dialog(el) {
      if (motionOk()) run(() => window.fx.animate(el, { opacity: [0, 1], transform: ['translateY(10px)', 'translateY(0px)'] }, { duration: 0.2, ease: [0.16, 1, 0.3, 1] }));
    },
    bars() {
      if (!motionOk()) return;
      $$('.meter i').forEach(b => run(() => window.fx.animate(b, { transform: ['scaleX(0)', 'scaleX(1)'] }, { duration: 0.35, ease: 'easeOut' })));
    },
    count(el, to, fmt = v => Math.round(v)) {
      if (!el) return;
      const from = parseFloat(el.dataset.n || '0');
      el.dataset.n = to;
      if (!motionOk() || from === to) {
        el.textContent = fmt(to);
        return;
      }
      run(() => window.fx.animate(from, to, {
        duration: 0.45,
        ease: 'easeOut',
        onUpdate: v => { el.textContent = fmt(v); }
      }));
      setTimeout(() => { el.textContent = fmt(to); }, 500);
    }
  };

  function toast(msg, type = 'alta') {
    if (typeof window.showToast === 'function') {
      window.showToast(msg, type);
    }
  }

  /* ---------- Filtrado, Ordenamiento y Paginación ---------- */
  function filtered() {
    const q = norm(S.q.trim());
    let list = products.filter(p => {
      if (q) {
        const haystack = norm([p.nombre, p.marca, p.modelo, p.sku, p.categoria].join(' '));
        if (!haystack.includes(q)) return false;
      }
      if (S.cat !== 'all' && p.categoria !== S.cat) return false;
      if (S.estado === 'on' && !p.activo) return false;
      if (S.estado === 'off' && p.activo) return false;
      if (S.stock === 'low' && level(p) !== 'low') return false;
      if (S.stock === 'out' && level(p) !== 'out') return false;
      return true;
    });

    const { key, dir } = S.sort;
    list.sort((a, b) => {
      const x = a[key], y = b[key];
      const c = typeof x === 'number' ? x - y : String(x).localeCompare(String(y), 'es', { sensitivity: 'base' });
      return (c || a.marca.localeCompare(b.marca, 'es')) * dir;
    });
    return list;
  }

  /* ---------- Renders de Componentes ---------- */
  function renderKPIs() {
    const low = products.filter(p => level(p) === 'low').length;
    const out = products.filter(p => level(p) === 'out').length;

    fx.count($('#kTotal'), products.length);
    fx.count($('#kLow'), low);
    fx.count($('#kOut'), out);

    $$('[data-kpi]').forEach(b => {
      const k = b.dataset.kpi;
      const on = k === 'all'
        ? (S.stock === 'all' && !S.q && S.cat === 'all' && S.estado === 'all')
        : S.stock === k;
      b.setAttribute('aria-pressed', String(on));
    });
  }

  function renderChips() {
    const chips = [];
    if (S.q.trim()) chips.push(['q', `Búsqueda: "${S.q.trim()}"`]);
    if (S.cat !== 'all') chips.push(['cat', 'Categoría: ' + S.cat]);
    if (S.estado !== 'all') chips.push(['estado', S.estado === 'on' ? 'Solo activos' : 'Solo inactivos']);
    if (S.stock !== 'all') chips.push(['stock', S.stock === 'low' ? 'Stock bajo' : 'Sin stock']);

    const chipsEl = $('#chips');
    if (chipsEl) {
      chipsEl.innerHTML = chips.map(([k, t]) =>
        `<span class="chip">${esc(t)}<button type="button" data-chip="${k}" aria-label="Quitar filtro: ${esc(t)}">${ico('x')}</button></span>`
      ).join('');
    }

    const n = (S.cat !== 'all') + (S.estado !== 'all') + (S.stock !== 'all');
    const b = $('#filterCount');
    if (b) {
      b.hidden = !n;
      b.textContent = n;
    }

    $$('#filterPop .seg').forEach(g => {
      $$('button', g).forEach(btn => {
        btn.setAttribute('aria-pressed', String(btn.dataset.v === S[g.dataset.group]));
      });
    });

    const catEl = $('#cat');
    if (catEl) catEl.value = S.cat;
  }

  function renderHead() {
    $$('th[data-sort]').forEach(th => {
      if (th.dataset.sort === S.sort.key) {
        th.setAttribute('aria-sort', S.sort.dir === 1 ? 'ascending' : 'descending');
      } else {
        th.removeAttribute('aria-sort');
      }
    });
  }

  function stockCell(p) {
    const lv = level(p);
    const pct = Math.min(100, Math.round((p.cantidad / Math.max(1, p.minimo * 3)) * 100));
    const flag = lv === 'low'
      ? '<span class="stock-flag low">Bajo</span>'
      : lv === 'out'
        ? '<span class="stock-flag out">Sin stock</span>'
        : '';

    return `
      <div class="stock-wrap">
        <div class="stock">
          <span class="stock-n${lv === 'ok' ? '' : ' ' + lv}">${p.cantidad}<small>un.</small></span>
          <span class="meter ${lv === 'ok' ? '' : lv}" role="img" aria-label="${p.cantidad} unidades, mínimo ${p.minimo}">
            <i style="--w:${Math.max(pct, p.cantidad ? 8 : 0)}%"></i>
          </span>
        </div>
        ${flag}
      </div>`;
  }

  function renderTable(opts = {}) {
    const tb = $('#tbody');
    if (!tb) return;

    const list = filtered();
    const total = list.length;
    const pages = Math.max(1, Math.ceil(total / S.per));
    S.page = Math.min(S.page, pages);
    const slice = list.slice((S.page - 1) * S.per, S.page * S.per);

    tb.innerHTML = slice.map(p => {
      const rowCls = [
        !p.activo ? 'is-inactive' : '',
        opts.flash === p.id ? (p.activo ? 'flash' : 'flash-off') : ''
      ].filter(Boolean).join(' ');

      return `
      <tr data-id="${p.id}"${rowCls ? ` class="${rowCls}"` : ''}>
        <td data-c="prod">
          <span class="pname">${esc(p.nombre)}</span>
          <span class="pmeta">
            <b>${esc(p.marca)}</b>${p.modelo ? ' · ' + esc(p.modelo) : ''}${p.sku ? ` <span class="mono">· SKU ${esc(p.sku)}</span>` : ''}
          </span>
        </td>
        <td data-c="cat" data-label="Categoría">
          <span class="cat" style="--c:${catColor(p.categoria)}" title="${esc(p.categoria)}">
            ${catIconSvg(p.categoria)}<span>${esc(p.categoria)}</span>
          </span>
        </td>
        <td data-c="stock" data-label="Stock" class="c">${stockCell(p)}</td>
        <td data-c="price" data-label="Precio" class="r">
          <span class="price">${money(p.precio)}</span>
        </td>
        <td data-c="state" data-label="Estado" class="c">
          ${IS_ADMIN ? `
            <button type="button" class="tag ${p.activo ? 'tag-on' : 'tag-off'}" data-action="toggle-status" data-id="${p.id}" title="Hacé clic para cambiar estado">
              ${p.activo ? 'Activo' : 'Inactivo'}
            </button>
          ` : `
            <span class="tag ${p.activo ? 'tag-on' : 'tag-off'}">${p.activo ? 'Activo' : 'Inactivo'}</span>
          `}
        </td>
        <td data-c="act" class="r">
          <div class="tbl-actions">
            ${IS_ADMIN ? `
              <button type="button" class="edit" data-action="edit" data-id="${p.id}" title="Editar producto" aria-label="Editar ${esc(p.nombre)}">
                ${ico('pencil')}
              </button>
            ` : `
              <span class="mono" style="color:var(--ink-3)">—</span>
            `}
          </div>
        </td>
      </tr>
    `;
    }).join('');

    const empty = total === 0;
    const emptyEl = $('#empty');
    const footEl = $('#foot');
    if (emptyEl) emptyEl.hidden = !empty;
    if (footEl) footEl.hidden = false;

    if (empty) {
      const none = !products.length;
      const emptyTitle = $('#emptyTitle');
      const emptyText = $('#emptyText');
      if (emptyTitle) emptyTitle.textContent = none ? 'Todavía no cargaste productos' : 'No encontramos productos';
      if (emptyText) emptyText.textContent = none ? 'Añadí el primero para empezar a controlar el stock.' : 'Probá con otra búsqueda o quitá algún filtro.';
    }

    const rangeEl = $('#range');
    if (rangeEl) {
      rangeEl.textContent = `Página ${S.page} de ${pages} - ${total} ${total === 1 ? 'producto' : 'productos'}`;
    }

    const pagerEl = $('#pager');
    if (pagerEl) {
      if (pages < 2) {
        pagerEl.innerHTML = '';
      } else {
        const items = [];
        if (pages <= 7) {
          for (let i = 1; i <= pages; i++) items.push(i);
        } else {
          items.push(1);
          let start = Math.max(2, S.page - 1);
          let end = Math.min(pages - 1, S.page + 1);
          if (S.page <= 3) { start = 2; end = 4; }
          else if (S.page >= pages - 2) { start = pages - 3; end = pages - 1; }
          if (start > 2) items.push('...');
          for (let i = start; i <= end; i++) items.push(i);
          if (end < pages - 1) items.push('...');
          items.push(pages);
        }

        pagerEl.innerHTML =
          `<button type="button" data-page="${S.page - 1}" ${S.page === 1 ? 'disabled' : ''} aria-label="Página anterior">&lsaquo;</button>` +
          items.map(it => it === '...'
            ? `<span class="pager-ellipsis">&hellip;</span>`
            : `<button type="button" data-page="${it}" ${it === S.page ? 'aria-current="page"' : ''} aria-label="Página ${it}">${it}</button>`
          ).join('') +
          `<button type="button" data-page="${S.page + 1}" ${S.page === pages ? 'disabled' : ''} aria-label="Página siguiente">&rsaquo;</button>`;
      }
    }

    if (opts.animate) {
      fx.rows();
      fx.bars();
    }
  }

  function renderAll(opts = {}) {
    renderKPIs();
    renderChips();
    renderHead();
    renderTable(opts);
  }

  /* ---------- Modal Alta / Edición ---------- */
  let lastFocus = null;

  function openModal() {
    lastFocus = document.activeElement;
    const modal = $('#modal');
    if (!modal) return;
    modal.hidden = false;
    document.body.classList.add('lock');
    fx.dialog($('.dialog'));
    $('[data-autofocus]')?.focus();
  }

  function closeModal() {
    const modal = $('#modal');
    if (!modal) return;
    modal.hidden = true;
    document.body.classList.remove('lock');
    if (lastFocus && document.contains(lastFocus)) lastFocus.focus();
  }

  function setErr(id, msg) {
    const i = $('#' + id);
    const e = $('#' + id + 'Err');
    if (i) i.setAttribute('aria-invalid', msg ? 'true' : 'false');
    if (e) {
      e.hidden = !msg;
      e.textContent = msg || '';
    }
  }

  function setSeg(group, v) {
    $$('button', group).forEach(b => b.setAttribute('aria-pressed', String(b.dataset.v === v)));
  }
  const segVal = group => $$('button', group).find(b => b.getAttribute('aria-pressed') === 'true')?.dataset.v || 'on';

  function openForm(id) {
    const isEditIntent = id !== null && id !== undefined && id !== '';
    const numId = Number(id);
    const p = (isEditIntent && Number.isFinite(numId) && numId > 0)
      ? products.find(x => Number(x.id) === numId)
      : null;

    if (isEditIntent && !p) {
      toast('Sincronizando datos del producto...', 'modificacion');
      window.location.reload();
      return;
    }

    S.editing = p ? Number(p.id) : null;

    const fId = $('#fId');
    const fCat = $('#fCat');
    const mTitle = $('#mTitle');
    const mSub = $('#mSub');
    const fNombre = $('#fNombre');
    const fMarca = $('#fMarca');
    const fModelo = $('#fModelo');
    const fSku = $('#fSku');
    const fCant = $('#fCant');
    const fMin = $('#fMin');
    const fPrecio = $('#fPrecio');
    const fEstado = $('#fEstado');
    const saveBtn = $('#saveBtn');

    const catOptions = (p && p.categoria && !CATEGORIES.includes(p.categoria))
      ? [...CATEGORIES, p.categoria]
      : CATEGORIES;

    if (fId) fId.value = p ? String(p.id) : '';
    if (fCat) {
      fCat.innerHTML = catOptions
        .map(c => `<option value="${esc(c)}"${p && p.categoria === c ? ' selected' : ''}>${esc(c)}</option>`)
        .join('');
    }
    if (mTitle) mTitle.textContent = p ? 'Editar producto' : 'Añadir producto';
    if (mSub) {
      mSub.textContent = p
        ? ([p.nombre, p.marca, p.modelo].filter(Boolean).join(' · ') || 'Modificá los datos del artículo.')
        : 'Completá los datos del artículo.';
    }
    if (saveBtn) {
      saveBtn.innerHTML = `${ico('check')}${p ? 'Guardar cambios' : 'Guardar'}`;
    }

    if (fNombre) fNombre.value = p?.nombre ?? '';
    if (fMarca) fMarca.value = p?.marca ?? '';
    if (fModelo) fModelo.value = p?.modelo ?? '';
    if (fSku) fSku.value = p?.sku ?? '';
    if (fCat) fCat.value = p?.categoria || (S.cat !== 'all' ? S.cat : catOptions[0]);
    if (fCant) fCant.value = p?.cantidad ?? 0;
    if (fMin) fMin.value = p?.minimo ?? 3;
    if (fPrecio) fPrecio.value = p?.precio ?? '';

    if (fEstado) setSeg(fEstado, p && !p.activo ? 'off' : 'on');
    ['fNombre', 'fSku', 'fCant', 'fPrecio'].forEach(i => setErr(i, ''));
    openModal();
  }

  async function saveForm() {
    const nombre = $('#fNombre')?.value.trim() || '';
    const sku = $('#fSku')?.value.trim().toUpperCase() || '';
    const cant = parseInt($('#fCant')?.value, 10);
    const precio = parseFloat($('#fPrecio')?.value);
    const id = parseInt($('#fId')?.value, 10) || (Number(S.editing) || 0);

    setErr('fNombre', nombre ? '' : 'Escribí el nombre del producto.');
    setErr('fSku', sku && products.some(p => String(p.sku || '').toUpperCase() === sku && Number(p.id) !== id) ? 'Ya existe un producto con ese SKU.' : '');
    setErr('fCant', Number.isInteger(cant) && cant >= 0 ? '' : 'Ingresá un número entero (0 o más).');
    setErr('fPrecio', !isNaN(precio) && precio >= 0 ? '' : 'Ingresá un precio válido.');

    const bad = $('#form [aria-invalid=true]');
    if (bad) return bad.focus();

    const btn = $('#saveBtn');
    if (btn) btn.setAttribute('aria-busy', 'true');

    const productFields = {
      nombre: nombre,
      marca: $('#fMarca')?.value.trim() || '',
      modelo: $('#fModelo')?.value.trim() || '',
      sku: sku,
      categoria: $('#fCat')?.value || 'Celulares',
      cantidad: cant,
      minimo: Math.max(0, parseInt($('#fMin')?.value, 10) || 0),
      precio: precio,
      activo: segVal($('#fEstado')) === 'on'
    };

    const payload = {
      csrf_token: CSRF_TOKEN,
      id: id,
      ...productFields
    };

    try {
      const res = await fetch(BASE_URL + '/modules/inventory/index.php?action=ajax_save_product', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload)
      });
      const resData = await res.json();

      if (btn) btn.removeAttribute('aria-busy');

      if (!resData.success) {
        toast(resData.message || 'Error al guardar el producto', 'baja');
        return;
      }

      const savedId = Number(resData.id) || id;
      const savedProduct = { ...productFields, id: savedId };

      if (id > 0) {
        const idx = products.findIndex(p => Number(p.id) === id);
        if (idx !== -1) {
          products[idx] = savedProduct;
        }
        toast('Producto actualizado correctamente.', 'modificacion');
      } else {
        products.unshift(savedProduct);
        toast('Producto añadido al inventario con éxito.', 'alta');
      }

      closeModal();
      renderAll({ flash: savedId, animate: true });
    } catch (err) {
      if (btn) btn.removeAttribute('aria-busy');
      toast('Error de conexión con el servidor', 'baja');
    }
  }

  /* ---------- Alternar Estado (Activo / Inactivo) vía AJAX ---------- */
  async function toggleStatus(id) {
    const numId = Number(id);
    const p = products.find(x => Number(x.id) === numId);
    if (!p) return;

    const fd = new FormData();
    fd.append('id', String(numId));
    fd.append('csrf_token', CSRF_TOKEN);

    try {
      const res = await fetch(BASE_URL + '/api/change_status.php', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: fd
      });
      const resData = await res.json();
      if (resData.success) {
        p.activo = !p.activo;
        toast(`Producto ${p.activo ? 'activado' : 'desactivado'}.`, p.activo ? 'alta' : 'baja');
        renderAll({ flash: numId });
      } else {
        toast(resData.message || 'Error al cambiar estado', 'baja');
      }
    } catch (e) {
      toast('Error al actualizar el estado', 'baja');
    }
  }

  /* ---------- Exportación Excel y PDF con delay cancelable (>= 1s) ---------- */
  function exportInventoryExcel(btn) {
    if (typeof window.runCancelableExport !== 'function') {
      return exportCSVFallback();
    }
    return window.runCancelableExport(btn, async ({ dismissInitToast }) => {
      const list = filtered();
      if (!list.length) {
        dismissInitToast();
        return toast('No hay productos para exportar con estos filtros.', 'baja');
      }

      const headers = ['Producto', 'Marca', 'Modelo', 'SKU', 'Categoría', 'Stock', 'Mínimo', 'Precio', 'Estado'];
      const bodyRows = list.map(p => [
        p.nombre || '',
        p.marca || '',
        p.modelo || '',
        p.sku || '',
        p.categoria || '',
        Number(p.cantidad ?? 0),
        Number(p.minimo ?? 0),
        Number(p.precio ?? 0),
        p.activo ? 'Activo' : 'Inactivo'
      ]);

      try {
        if (typeof XLSX === 'undefined' && typeof window.loadScript === 'function') {
          await window.loadScript('https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js');
        }
        if (typeof XLSX !== 'undefined') {
          const titleRow = [`SISTEMA NOKIA 1100 - REPORTE DE INVENTARIO - ${new Date().toLocaleDateString()}`];
          const aoa = [titleRow, headers, ...bodyRows];
          const ws = XLSX.utils.aoa_to_sheet(aoa);
          const wb = XLSX.utils.book_new();
          XLSX.utils.book_append_sheet(wb, ws, 'Inventario');
          const dateStr = new Date().toISOString().slice(0, 10);
          XLSX.writeFile(wb, `inventario_${dateStr}.xlsx`);
          dismissInitToast();
          toast('Excel descargado con éxito', 'alta');
          return;
        }
      } catch (err) {
        console.warn('Fallback a CSV en exportación de inventario:', err);
      }

      dismissInitToast();
      exportCSVFallback();
    });
  }

  function exportCSVFallback() {
    const list = filtered();
    if (!list.length) return toast('No hay productos para exportar con estos filtros.', 'baja');

    const q = v => '"' + String(v ?? '').replace(/"/g, '""') + '"';
    const rows = [
      ['Producto', 'Marca', 'Modelo', 'SKU', 'Categoría', 'Stock', 'Mínimo', 'Precio', 'Estado']
    ].concat(list.map(p => [
      p.nombre,
      p.marca,
      p.modelo,
      p.sku,
      p.categoria,
      p.cantidad,
      p.minimo,
      p.precio,
      p.activo ? 'Activo' : 'Inactivo'
    ]));

    const blob = new Blob(['\uFEFF' + rows.map(r => r.map(q).join(',')).join('\r\n')], { type: 'text/csv;charset=utf-8' });
    const a = Object.assign(document.createElement('a'), {
      href: URL.createObjectURL(blob),
      download: `inventario_nokia1100_${new Date().toISOString().slice(0, 10)}.csv`
    });
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(a.href), 1000);
    toast(`Exportados ${list.length} productos a Excel.`, 'alta');
  }

  function exportInventoryPDF(btn) {
    if (typeof window.runCancelableExport !== 'function') {
      return window.print();
    }
    return window.runCancelableExport(btn, async ({ dismissInitToast }) => {
      const list = filtered();
      if (!list.length) {
        dismissInitToast();
        return toast('No hay productos para exportar con estos filtros.', 'baja');
      }

      try {
        if (!window.jspdf && typeof window.loadScript === 'function') {
          await window.loadScript('https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js');
        }
        if (!window.jspdf?.jsPDF?.prototype?.autoTable && !window.autoTable && typeof window.loadScript === 'function') {
          await window.loadScript('https://cdn.jsdelivr.net/npm/jspdf-autotable@3.5.29/dist/jspdf.plugin.autotable.min.js');
        }

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'mm', 'a4');

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
        doc.text('INVENTARIO DE PRODUCTOS', 195, 20, { align: 'right' });

        doc.setFontSize(8);
        doc.setTextColor(156, 163, 175);
        doc.text('Generado: ' + new Date().toLocaleString(), 195, 26, { align: 'right' });

        const headers = ['Producto', 'Marca / Modelo', 'SKU', 'Categoría', 'Stock', 'Precio', 'Estado'];
        const body = list.map(p => [
          p.nombre || '',
          [p.marca, p.modelo].filter(Boolean).join(' ') || '—',
          p.sku || '—',
          p.categoria || '—',
          `${p.cantidad} (mín. ${p.minimo})`,
          money(p.precio),
          p.activo ? 'Activo' : 'Inactivo'
        ]);

        doc.autoTable({
          head: [headers],
          body,
          startY: 38,
          theme: 'grid',
          styles: {
            fillColor: [255, 255, 255],
            textColor: [55, 65, 81],
            lineColor: [229, 231, 235],
            fontSize: 8.5,
            font: 'helvetica',
            cellPadding: 3.5
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
        doc.save(`inventario_${dateStr}.pdf`);

        dismissInitToast();
        toast('PDF descargado con éxito', 'alta');
      } catch (err) {
        console.error('Error al exportar PDF de inventario:', err);
        dismissInitToast();
        toast('Error al exportar a PDF', 'baja');
      }
    });
  }

  /* ---------- Eventos Globales y Enrutamiento ---------- */
  const resetFilters = () => {
    S.q = '';
    S.cat = 'all';
    S.estado = 'all';
    S.stock = 'all';
    S.page = 1;
    const qEl = $('#q');
    if (qEl) qEl.value = '';
    const catEl = $('#cat');
    if (catEl) catEl.value = 'all';
  };

  const actions = {
    add: () => openForm(null),
    edit: el => openForm(el.dataset.id ?? el.closest('tr')?.dataset.id),
    save: saveForm,
    'close-modal': closeModal,
    excel: el => exportInventoryExcel(el),
    pdf: el => exportInventoryPDF(el),
    clear: () => { resetFilters(); renderAll({ animate: true }); },
    'close-filter': () => toggleFilter(false),
    'toggle-status': el => toggleStatus(el.dataset.id ?? el.closest('tr')?.dataset.id)
  };

  function toggleFilter(open) {
    const pop = $('#filterPop');
    if (pop) pop.hidden = !open;
    const btn = $('#filterBtn');
    if (btn) btn.setAttribute('aria-expanded', String(open));
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', e => {
      const targetEl = e.target instanceof Element ? e.target : e.target?.parentElement;
      if (!targetEl) return;
      const a = targetEl.closest('[data-action]');
      if (a && actions[a.dataset.action]) return actions[a.dataset.action](a);

      const sb = e.target.closest('[data-sortbtn]');
      if (sb) {
        const k = sb.dataset.sortbtn;
        S.sort = { key: k, dir: S.sort.key === k ? -S.sort.dir : 1 };
        S.page = 1;
        return renderAll({ animate: true });
      }

      const pg = e.target.closest('[data-page]');
      if (pg) {
        S.page = +pg.dataset.page;
        renderTable({ animate: true });
        return $('#main')?.scrollIntoView({ behavior: reduced() ? 'auto' : 'smooth', block: 'start' });
      }

      const kp = e.target.closest('[data-kpi]');
      if (kp) {
        const k = kp.dataset.kpi;
        if (k === 'all') {
          resetFilters();
        } else {
          S.stock = S.stock === k ? 'all' : k;
          S.page = 1;
        }
        return renderAll({ animate: true });
      }

      const ch = e.target.closest('[data-chip]');
      if (ch) {
        if (ch.dataset.chip === 'q') {
          S.q = '';
          const qInput = $('#q');
          if (qInput) qInput.value = '';
        } else {
          S[ch.dataset.chip] = 'all';
        }
        S.page = 1;
        return renderAll({ animate: true });
      }

      const sg = e.target.closest('#filterPop .seg button');
      if (sg) {
        S[sg.parentElement.dataset.group] = sg.dataset.v;
        S.page = 1;
        return renderAll({ animate: true });
      }

      const fs = e.target.closest('#fEstado button');
      if (fs) return setSeg($('#fEstado'), fs.dataset.v);

      if (e.target.closest('#filterBtn')) return toggleFilter($('#filterPop')?.hidden);
      if (!e.target.closest('#filterPop')) toggleFilter(false);

      if (e.target.id === 'modal') closeModal();
    });

    const qInput = $('#q');
    if (qInput) {
      qInput.addEventListener('input', e => {
        S.q = e.target.value;
        S.page = 1;
        renderAll();
      });
    }

    const catSelect = $('#cat');
    if (catSelect) {
      catSelect.addEventListener('change', e => {
        S.cat = e.target.value;
        S.page = 1;
        renderAll({ animate: true });
      });
    }

    const formEl = $('#form');
    if (formEl) {
      formEl.addEventListener('submit', e => {
        e.preventDefault();
        saveForm();
      });
    }

    document.addEventListener('keydown', e => {
      const modal = $('#modal');
      if (modal && !modal.hidden) {
        if (e.key === 'Escape') {
          e.preventDefault();
          return closeModal();
        }
        if (e.key === 'Tab') {
          const f = $$('button:not([disabled]),input,select,a[href]', modal).filter(x => x.offsetParent !== null);
          const first = f[0], last = f[f.length - 1];
          if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
          } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
          }
        }
        return;
      }
      if (e.key === 'Escape') {
        const filterPop = $('#filterPop');
        if (filterPop && !filterPop.hidden) {
          toggleFilter(false);
          $('#filterBtn')?.focus();
        }
        return;
      }
      if (e.key === '/' && !/INPUT|SELECT|TEXTAREA/.test(document.activeElement.tagName)) {
        e.preventDefault();
        $('#q')?.focus();
      }
    });

    // Arranque inicial
    renderAll({ animate: true });
  });

})();
