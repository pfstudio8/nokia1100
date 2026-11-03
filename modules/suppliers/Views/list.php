<?php
// modules/suppliers/Views/list.php
// Nokia 1100 · Abastecimiento y Proveedores (UI Kit v1.0)

if (!class_exists('Layout')) {
  require_once __DIR__ . '/../../../classes/Layout.php';
}

/**
 * Variables inyectadas por SuppliersController
 * @var array $suppliers
 * @var array $inventory
 * @var array $purchases
 * @var string $initial_view
 * @var int $preselected_supplier
 */
$suppliers = $suppliers ?? [];
$inventory = $inventory ?? [];
$purchases = $purchases ?? [];
$initial_view = $initial_view ?? 'sec1';
$preselected_supplier = isset($preselected_supplier) ? (int) $preselected_supplier : 0;

$isAdmin = ($_SESSION['role'] ?? 'admin') === 'admin';
$currentUser = $_SESSION['username'] ?? 'Admin';
$csrfToken = $_SESSION['session_token'] ?? '';

// Normalizar proveedores para hidratación en JS
$normSuppliers = [];
foreach ($suppliers as $s) {
  $normSuppliers[] = [
    'id' => (int) $s['id_proveedor'],
    'nombre' => (string) ($s['nombre'] ?? ''),
    'atencion' => (string) ($s['atencion'] ?? ''),
    'telefono' => (string) ($s['telefono'] ?? ''),
    'email' => (string) ($s['email'] ?? ''),
    'domicilio' => (string) ($s['domicilio'] ?? ''),
    'total_compras' => (int) ($s['total_compras'] ?? 0)
  ];
}

// Normalizar catálogo de productos
$normCatalog = [];
$alertCount = 0;
foreach ($inventory as $p) {
  $idProd = (int) $p['id_producto'];
  $stock = (int) ($p['cantidad'] ?? 0);
  $minimo = max(1, (int) ($p['stock_minimo'] ?? 3));
  if ($stock <= $minimo) {
    $alertCount++;
  }
  $sku = trim((string) ($p['codigo'] ?? ''));
  if ($sku === '') {
    $sku = 'SKU-' . str_pad((string) $idProd, 4, '0', STR_PAD_LEFT);
  }
  $normCatalog[] = [
    'id' => $idProd,
    'sku' => $sku,
    'nombre' => (string) ($p['nombre'] ?? ''),
    'marca' => (string) ($p['marca'] ?? ''),
    'modelo' => (string) ($p['modelo'] ?? ''),
    'categoria' => (string) ($p['categoria'] ?: 'Accesorios'),
    'stock' => $stock,
    'minimo' => $minimo,
    'costo' => (float) ($p['precio'] ?? 0)
  ];
}

// Normalizar historial de órdenes de compra
$normOrders = [];
$pendingCount = 0;
foreach ($purchases as $o) {
  $estado = (string) ($o['estado'] ?? 'Pendiente');
  if ($estado === 'Pendiente') {
    $pendingCount++;
  }
  $details = [];
  foreach (($o['details'] ?? []) as $d) {
    $idProd = (int) ($d['id_producto'] ?? 0);
    $sku = trim((string) ($d['sku'] ?? ''));
    if ($sku === '') {
      $sku = 'SKU-' . str_pad((string) $idProd, 4, '0', STR_PAD_LEFT);
    }
    $details[] = [
      'id_detalle_compra' => (int) ($d['id_detalle_compra'] ?? 0),
      'id_producto' => $idProd,
      'sku' => $sku,
      'nombre' => (string) ($d['nombre_producto'] ?? 'Artículo'),
      'marca' => (string) ($d['marca'] ?? ''),
      'modelo' => (string) ($d['modelo'] ?? ''),
      'cantidad' => (int) ($d['cantidad'] ?? 1),
      'costo' => (float) ($d['precio_compra'] ?? 0)
    ];
  }
  $normOrders[] = [
    'id' => (int) $o['id_compra'],
    'id_proveedor' => (int) $o['id_proveedor'],
    'proveedor_nombre' => (string) ($o['proveedor_nombre'] ?? 'Proveedor'),
    'fecha' => (string) ($o['fecha'] ?? ''),
    'fecha_fmt' => !empty($o['fecha']) ? date('d/m/Y H:i', strtotime($o['fecha'])) : '',
    'descripcion' => (string) ($o['descripcion'] ?? ''),
    'tiempo_entrega' => (string) ($o['tiempo_entrega'] ?? ''),
    'autorizado_por' => (string) ($o['autorizado_por'] ?? ''),
    'total' => (float) ($o['total'] ?? 0),
    'estado' => $estado,
    'details' => $details
  ];
}

Layout::renderHead('Nokia 1100 · Abastecimiento y Proveedores', ['/modules/suppliers/suppliers_list.css']);
if ($isAdmin) {
  Layout::renderAdminSidebar('proveedores');
} else {
  Layout::renderEmployeeSidebar('proveedores');
}
?>

<!-- Sprite de iconos SVG Nokia 1100 UI Kit -->
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
  <symbol id="nk-i-search" viewBox="0 0 24 24">
    <circle cx="11" cy="11" r="7" />
    <path d="m20 20-3.5-3.5" />
  </symbol>
  <symbol id="nk-i-plus" viewBox="0 0 24 24">
    <path d="M12 5v14M5 12h14" />
  </symbol>
  <symbol id="nk-i-minus" viewBox="0 0 24 24">
    <path d="M5 12h14" />
  </symbol>
  <symbol id="nk-i-check" viewBox="0 0 24 24">
    <path d="M20 6 9 17l-5-5" />
  </symbol>
  <symbol id="nk-i-chat" viewBox="0 0 24 24">
    <path
      d="M21 11.5a8.4 8.4 0 0 1-9 8.4 8.6 8.6 0 0 1-4-.9L3 20.5l1.6-4.7a8.3 8.3 0 0 1-1.1-4.3 8.5 8.5 0 0 1 8.5-8.5h.5A8.4 8.4 0 0 1 21 11v.5z" />
  </symbol>
  <symbol id="nk-i-print" viewBox="0 0 24 24">
    <path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6z" />
  </symbol>
  <symbol id="nk-i-save" viewBox="0 0 24 24">
    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
    <path d="M17 21v-8H7v8M7 3v5h8" />
  </symbol>
  <symbol id="nk-i-box" viewBox="0 0 24 24">
    <path d="M21 8 12 3 3 8v8l9 5 9-5V8zM3 8l9 5 9-5M12 13v8" />
  </symbol>
  <symbol id="nk-i-clock" viewBox="0 0 24 24">
    <circle cx="12" cy="12" r="9" />
    <path d="M12 7v5l3 3" />
  </symbol>
  <symbol id="nk-i-x" viewBox="0 0 24 24">
    <path d="M18 6 6 18M6 6l12 12" />
  </symbol>
  <symbol id="nk-i-in" viewBox="0 0 24 24">
    <path d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
  </symbol>
  <symbol id="nk-i-copy" viewBox="0 0 24 24">
    <rect x="9" y="9" width="13" height="13" rx="1" />
    <path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1" />
  </symbol>
  <symbol id="nk-i-ext" viewBox="0 0 24 24">
    <path d="M18 13v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h6M15 3h6v6M10 14 21 3" />
  </symbol>
  <symbol id="nk-i-user" viewBox="0 0 24 24">
    <circle cx="12" cy="8" r="4" />
    <path d="M4 20c1.5-4 4.5-6 8-6s6.5 2 8 6" />
  </symbol>
  <symbol id="nk-i-phone" viewBox="0 0 24 24">
    <path
      d="M22 16.9v3a2 2 0 0 1-2.2 2A19.8 19.8 0 0 1 3.1 5.2 2 2 0 0 1 5.1 3h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L9 11a16 16 0 0 0 4 4l1.4-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7A2 2 0 0 1 22 16.9z" />
  </symbol>
  <symbol id="nk-i-mail" viewBox="0 0 24 24">
    <rect x="3" y="5" width="18" height="14" rx="1" />
    <path d="m3 7 9 6 9-6" />
  </symbol>
  <symbol id="nk-i-pin" viewBox="0 0 24 24">
    <path d="M12 21s-7-5.4-7-11a7 7 0 0 1 14 0c0 5.6-7 11-7 11z" />
    <circle cx="12" cy="10" r="2.5" />
  </symbol>
  <symbol id="nk-i-edit" viewBox="0 0 24 24">
    <path d="M17 3a2.8 2.8 0 0 1 4 4L7.5 20.5 2 22l1.5-5.5z" />
  </symbol>
  <symbol id="nk-i-trash" viewBox="0 0 24 24">
    <path d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14" />
  </symbol>
  <symbol id="nk-i-eye" viewBox="0 0 24 24">
    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" />
    <circle cx="12" cy="12" r="3" />
  </symbol>
</svg>

<main id="main" class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
  <div class="nk-page">
    <!-- ================= ENCABEZADO ================= -->
    <header class="nk-head">
      <div>
        <p class="nk-eyebrow">Módulo · Abastecimiento y proveedores</p>
        <h1 class="nk-title" id="pageTitle" tabindex="-1">Proveedores</h1>
        <p class="nk-sub" id="pageSub">Directorio de distribuidores, reposición inteligente y recepción verificada de
          mercadería.</p>
      </div>
      <div class="nk-actions">
        <button type="button" class="nk-btn" data-action="view" data-view="sec2">
          <svg class="nk-icon" aria-hidden="true">
            <use href="#nk-i-box" />
          </svg>Asistente de reposición
        </button>
        <button type="button" class="nk-btn nk-btn--lcd" data-action="new-supplier" id="btnHeadNewSup">
          <svg class="nk-icon" aria-hidden="true">
            <use href="#nk-i-plus" />
          </svg>Nuevo proveedor
        </button>
      </div>
    </header>

    <!-- ================= BARRA DE SECCIONES DEL MÓDULO (01..05) ================= -->
    <div class="nk-subnav" role="navigation" aria-label="Secciones de abastecimiento">
      <div class="nk-subnav__tabs">
        <button type="button" class="nk-nav__item" data-action="view" data-view="sec1" aria-current="page">
          <span class="nav-n">01</span><span>Proveedores</span>
          <span class="nk-nav__count" id="cntSuppliers"><?php echo count($normSuppliers); ?></span>
        </button>
        <button type="button" class="nk-nav__item" data-action="view" data-view="sec2">
          <span class="nav-n">02</span><span>Reposición</span>
          <span class="nk-nav__count" id="cntAlert"><?php echo $alertCount; ?></span>
        </button>
        <button type="button" class="nk-nav__item" data-action="view" data-view="sec3">
          <span class="nav-n">03</span><span>Compra manual</span>
        </button>
        <button type="button" class="nk-nav__item" data-action="view" data-view="sec4">
          <span class="nav-n">04</span><span>Pedidos</span>
          <span class="nk-nav__count" id="cntPending"><?php echo $pendingCount; ?></span>
        </button>
        <button type="button" class="nk-nav__item" data-action="view" data-view="secStock">
          <span class="nav-n">05</span><span>Stock en vivo</span>
        </button>
      </div>

      <div class="nk-subnav__role" role="group" aria-label="Perfil de operación">
        <span class="nk-subnav__role-lbl" id="roleHint">Vista:</span>
        <div class="nk-seg" id="roleSeg" data-nk-seg>
          <button type="button" data-v="admin" aria-pressed="<?php echo $isAdmin ? 'true' : 'false'; ?>">Admin</button>
          <button type="button" data-v="empleado"
            aria-pressed="<?php echo !$isAdmin ? 'true' : 'false'; ?>">Mostrador</button>
        </div>
      </div>
    </div>

    <!-- ================= KPIs INTERACTIVOS ================= -->
    <div class="nk-kpis kpis-gap" role="group" aria-label="Indicadores de abastecimiento">
      <button type="button" class="nk-kpi" data-action="view" data-view="sec1" aria-pressed="true">
        <span class="nk-kpi__label">Proveedores activos</span>
        <span class="nk-kpi__value" id="kpiSuppliers"
          data-n="<?php echo count($normSuppliers); ?>"><?php echo count($normSuppliers); ?></span>
      </button>
      <button type="button" class="nk-kpi" data-action="view" data-view="sec2" aria-pressed="false">
        <span class="nk-kpi__label">Artículos en alerta</span>
        <span class="nk-kpi__value nk-kpi__value--warn" id="kpiLow"
          data-n="<?php echo $alertCount; ?>"><?php echo $alertCount; ?></span>
      </button>
      <button type="button" class="nk-kpi" data-action="view" data-view="sec4" aria-pressed="false">
        <span class="nk-kpi__label">Pedidos en tránsito</span>
        <span class="nk-kpi__value" id="kpiPending"
          data-n="<?php echo $pendingCount; ?>"><?php echo $pendingCount; ?></span>
      </button>
      <button type="button" class="nk-kpi" data-action="view" data-view="secStock" aria-pressed="false">
        <span class="nk-kpi__label">Catálogo monitoreado</span>
        <span class="nk-kpi__value" id="kpiCatalog"
          data-n="<?php echo count($normCatalog); ?>"><?php echo count($normCatalog); ?></span>
      </button>
    </div>

    <!-- =================================================================
         01 · PROVEEDORES (DIRECTORIO DE DISTRIBUIDORES)
         ================================================================= -->
    <section id="sec1" data-view>
      <div class="nk-panel">
        <div class="nk-panel__bar">
          <div>
            <h2 class="nk-h2">Directorio de distribuidores</h2>
            <p class="nk-panel__desc">Desde acá abrís el asistente de pedido con el proveedor ya elegido o consultás sus
              órdenes.</p>
          </div>
          <div class="nk-tools">
            <div class="nk-search">
              <svg class="nk-icon" aria-hidden="true">
                <use href="#nk-i-search" />
              </svg>
              <label class="nk-vh" for="supSearch">Buscar proveedor</label>
              <input type="search" id="supSearch" class="nk-field" placeholder="Nombre, contacto o ciudad…"
                autocomplete="off">
              <span class="nk-kbd" aria-hidden="true">/</span>
            </div>
            <?php echo Layout::renderExportButtons('suppliers-table', 'Directorio de Proveedores', 'proveedores'); ?>
            <button type="button" class="nk-btn nk-btn--primary" data-action="new-supplier" data-admin-only>
              <svg class="nk-icon" aria-hidden="true">
                <use href="#nk-i-plus" />
              </svg>Nuevo proveedor
            </button>
          </div>
        </div>
        <div class="nk-table-wrap">
          <table class="nk-table nk-table--cards" id="suppliers-table">
            <caption class="nk-vh">Directorio de proveedores</caption>
            <thead>
              <tr>
                <th scope="col"><span class="nk-th">Proveedor</span></th>
                <th scope="col"><span class="nk-th">Contacto</span></th>
                <th scope="col"><span class="nk-th">WhatsApp</span></th>
                <th scope="col"><span class="nk-th">Email de órdenes</span></th>
                <th scope="col"><span class="nk-th">Retiro / Depósito</span></th>
                <th scope="col" class="is-r"><span class="nk-th">Acciones</span></th>
              </tr>
            </thead>
            <tbody id="suppliersTbody"></tbody>
          </table>
        </div>
        <div class="nk-empty" id="suppliersEmpty" hidden>
          <span class="nk-empty__glyph"><svg class="nk-icon" aria-hidden="true">
              <use href="#nk-i-search" />
            </svg></span>
          <strong>Sin resultados</strong>Probá con otro nombre, contacto o ciudad.
        </div>
      </div>
    </section>

    <!-- =================================================================
         02 · REPOSICIÓN (PUNTO DE REPOSICIÓN INTELIGENTE)
         ================================================================= -->
    <section id="sec2" data-view hidden>
      <div class="nk-panel">
        <div class="picker">
          <div>
            <label class="nk-label" for="smartSupplier">Distribuidor destinatario</label>
            <select id="smartSupplier" class="nk-field"></select>
          </div>
          <dl class="picker-meta" id="smartMeta"></dl>
        </div>
      </div>

      <div class="nk-panel">
        <div class="nk-panel__bar">
          <div>
            <h2 class="nk-h2">Artículos en punto de reposición</h2>
            <p class="nk-panel__desc">Stock igual o menor al mínimo. Sugerimos pedir el doble del mínimo menos lo que
              hay; ajustalo con − y +.</p>
          </div>
          <label class="nk-tools" style="gap:8px;font-weight:600;font-size:.84rem;cursor:pointer">
            <input type="checkbox" class="nk-check" id="smartAll" checked> Seleccionar todos
          </label>
        </div>
        <div class="nk-table-wrap">
          <table class="nk-table nk-table--cards">
            <caption class="nk-vh">Artículos a reponer</caption>
            <thead>
              <tr>
                <th scope="col" style="width:48px"><span class="nk-th"><span class="nk-vh">Incluir</span></span></th>
                <th scope="col"><span class="nk-th">SKU</span></th>
                <th scope="col"><span class="nk-th">Artículo</span></th>
                <th scope="col"><span class="nk-th">Stock</span></th>
                <th scope="col"><span class="nk-th">Cantidad a pedir</span></th>
                <th scope="col"><span class="nk-th">Costo unit.</span></th>
                <th scope="col" class="is-r"><span class="nk-th">Subtotal</span></th>
              </tr>
            </thead>
            <tbody id="smartTbody"></tbody>
          </table>
        </div>
        <div class="nk-empty" id="smartEmpty" hidden>
          <span class="nk-empty__glyph"><svg class="nk-icon" aria-hidden="true">
              <use href="#nk-i-check" />
            </svg></span>
          <strong>Todo el stock está por encima del mínimo</strong>No hay nada para reponer por ahora. Si necesitás algo
          puntual, usá Compra manual.
          <div><button type="button" class="nk-btn nk-btn--sm" data-action="view" data-view="sec3">Ir a Compra
              manual</button></div>
        </div>
      </div>

      <div class="nk-dock" id="smartDock">
        <div class="nk-dock__metrics">
          <div><span>Ítems a reponer</span><strong id="dockCount">0</strong></div>
          <div><span>Total estimado</span><strong class="is-total" id="dockTotal">$0</strong></div>
        </div>
        <div class="nk-dock__actions">
          <button type="button" class="nk-btn nk-btn--wa" data-action="wa" id="btnWa">
            <svg class="nk-icon" aria-hidden="true">
              <use href="#nk-i-chat" />
            </svg>Armar WhatsApp
          </button>
          <button type="button" class="nk-btn" data-action="print" id="btnPrint">
            <svg class="nk-icon" aria-hidden="true">
              <use href="#nk-i-print" />
            </svg>Imprimir orden
          </button>
          <button type="button" class="nk-btn nk-btn--lcd" data-action="register-smart" id="btnRegister">
            <svg class="nk-icon" aria-hidden="true">
              <use href="#nk-i-save" />
            </svg>Registrar como pendiente
          </button>
        </div>
      </div>
    </section>

    <!-- =================================================================
         03 · COMPRA MANUAL (ORDEN PERSONALIZADA + ALTA RÁPIDA)
         ================================================================= -->
    <section id="sec3" data-view hidden>
      <div class="nk-panel">
        <div class="nk-panel__bar">
          <div>
            <h2 class="nk-h2">Orden manual</h2>
            <p class="nk-panel__desc">Para lo que no está en alerta: nuevos modelos de fundas, insumos o herramientas de
              laboratorio.</p>
          </div>
          <button type="button" class="nk-btn" data-action="quick-product">
            <svg class="nk-icon" aria-hidden="true">
              <use href="#nk-i-plus" />
            </svg>Crear producto nuevo
          </button>
        </div>

        <div class="manual-form">
          <div>
            <label class="nk-label" for="manualSupplier">Proveedor</label>
            <select id="manualSupplier" class="nk-field"></select>
          </div>
          <div>
            <label class="nk-label" for="manualProduct">Producto del catálogo</label>
            <select id="manualProduct" class="nk-field"></select>
          </div>
          <div>
            <label class="nk-label" for="manualDesc">Referencia / Lote</label>
            <input type="text" id="manualDesc" class="nk-field" placeholder="Ej: Pedido quincenal">
          </div>
          <div>
            <label class="nk-label" for="manualEta">Entrega estimada</label>
            <input type="text" id="manualEta" class="nk-field" placeholder="Ej: 48 hs / Expreso">
          </div>
          <button type="button" class="nk-btn nk-btn--primary" data-action="manual-add">
            <svg class="nk-icon" aria-hidden="true">
              <use href="#nk-i-plus" />
            </svg>Agregar fila
          </button>
        </div>

        <div class="nk-table-wrap">
          <table class="nk-table nk-table--cards">
            <caption class="nk-vh">Artículos de la orden manual</caption>
            <thead>
              <tr>
                <th scope="col"><span class="nk-th">SKU</span></th>
                <th scope="col"><span class="nk-th">Artículo</span></th>
                <th scope="col"><span class="nk-th">Cantidad</span></th>
                <th scope="col"><span class="nk-th">Costo unit.</span></th>
                <th scope="col" class="is-r"><span class="nk-th">Subtotal</span></th>
                <th scope="col" style="width:56px"><span class="nk-th"><span class="nk-vh">Quitar</span></span></th>
              </tr>
            </thead>
            <tbody id="manualTbody"></tbody>
          </table>
        </div>

        <div class="nk-empty" id="manualEmpty" hidden>
          <span class="nk-empty__glyph"><svg class="nk-icon" aria-hidden="true">
              <use href="#nk-i-box" />
            </svg></span>
          <strong>La orden está vacía</strong>Elegí un producto del catálogo y agregalo, o creá uno nuevo.
        </div>

        <div class="nk-ticket">
          <div>
            <span class="nk-label lbl-inline">Total de la orden</span>
            <strong id="manualTotal">$0</strong>
          </div>
          <div class="nk-actions">
            <button type="button" class="nk-btn nk-btn--wa" data-action="wa-manual" id="btnWaManual">
              <svg class="nk-icon" aria-hidden="true">
                <use href="#nk-i-chat" />
              </svg>Armar WhatsApp
            </button>
            <button type="button" class="nk-btn nk-btn--primary" data-action="save-manual" id="btnSaveManual">
              <svg class="nk-icon" aria-hidden="true">
                <use href="#nk-i-save" />
              </svg>Guardar como pendiente
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- =================================================================
         04 · PEDIDOS (HISTORIAL Y RECEPCIÓN VERIFICADA)
         ================================================================= -->
    <section id="sec4" data-view hidden>
      <div class="nk-panel">
        <div class="nk-panel__bar">
          <div>
            <h2 class="nk-h2">Historial y recepción verificada</h2>
            <p class="nk-panel__desc">El stock físico no cambia hasta que controlás el paquete en mostrador.</p>
          </div>
          <div class="nk-tools">
            <span class="nk-chip" id="supFilterChip" hidden></span>
            <div class="nk-seg" id="statusSeg" data-nk-seg role="group" aria-label="Filtrar por estado">
              <button type="button" data-v="ALL" aria-pressed="true">Todas</button>
              <button type="button" data-v="Pendiente" aria-pressed="false">Pendientes</button>
              <button type="button" data-v="Recibido" aria-pressed="false">Recibidas</button>
              <button type="button" data-v="Cancelado" aria-pressed="false">Canceladas</button>
            </div>
            <?php echo Layout::renderExportButtons('purchases-table', 'Historial de Pedidos a Proveedores', 'pedidos_proveedores'); ?>
          </div>
        </div>
        <div class="nk-table-wrap">
          <table class="nk-table nk-table--cards" id="purchases-table">
            <caption class="nk-vh">Historial de órdenes</caption>
            <thead>
              <tr>
                <th scope="col"><span class="nk-th">Orden</span></th>
                <th scope="col"><span class="nk-th">Emisión</span></th>
                <th scope="col"><span class="nk-th">Proveedor</span></th>
                <th scope="col"><span class="nk-th">Detalle</span></th>
                <th scope="col" class="is-r"><span class="nk-th">Importe</span></th>
                <th scope="col"><span class="nk-th">Estado</span></th>
                <th scope="col" class="is-r"><span class="nk-th">Acciones</span></th>
              </tr>
            </thead>
            <tbody id="historyTbody"></tbody>
          </table>
        </div>
        <div class="nk-empty" id="historyEmpty" hidden>
          <span class="nk-empty__glyph"><svg class="nk-icon" aria-hidden="true">
              <use href="#nk-i-clock" />
            </svg></span>
          <strong>No hay órdenes con este filtro</strong>Cambiá el estado o quitá el filtro de proveedor.
        </div>
      </div>
    </section>

    <!-- =================================================================
         05 · STOCK EN VIVO (INVENTARIO FÍSICO MONITOREADO)
         ================================================================= -->
    <section id="secStock" data-view hidden>
      <div class="nk-panel">
        <div class="nk-panel__bar">
          <div>
            <h2 class="nk-h2">Inventario físico</h2>
            <p class="nk-panel__desc">Las barras comparan lo que hay contra el doble del mínimo. Se actualizan al
              recibir una orden.</p>
          </div>
          <div class="nk-tools">
            <div class="nk-search">
              <svg class="nk-icon" aria-hidden="true">
                <use href="#nk-i-search" />
              </svg>
              <label class="nk-vh" for="stockSearch">Buscar en catálogo</label>
              <input type="search" id="stockSearch" class="nk-field" placeholder="Filtrar por SKU, producto o marca…"
                autocomplete="off">
            </div>
          </div>
        </div>
        <div class="nk-table-wrap">
          <table class="nk-table nk-table--cards">
            <caption class="nk-vh">Stock actual por producto</caption>
            <thead>
              <tr>
                <th scope="col"><span class="nk-th">SKU</span></th>
                <th scope="col"><span class="nk-th">Producto</span></th>
                <th scope="col"><span class="nk-th">Disponibilidad</span></th>
                <th scope="col" class="is-r"><span class="nk-th">Mínimo</span></th>
                <th scope="col" class="is-r"><span class="nk-th">Último costo</span></th>
                <th scope="col" class="is-r"><span class="nk-th">Acción</span></th>
              </tr>
            </thead>
            <tbody id="stockTbody"></tbody>
          </table>
        </div>
      </div>
    </section>
  </div>
</main>

<!-- =====================================================================
     MODALES DEL KIT (Control de recepción, WhatsApp, Producto rápido, Proveedor, Detalle)
     ===================================================================== -->

<!-- 1. Control de Recepción -->
<div class="nk-modal" id="receiveModal" hidden>
  <div class="nk-dialog" role="dialog" aria-modal="true" aria-labelledby="recTitle">
    <div class="nk-dialog__head">
      <div>
        <h3 id="recTitle">Control de recepción</h3>
        <p id="recSub">Ajustá las unidades que realmente vinieron en la caja antes de acreditarlas al stock.</p>
      </div>
      <button type="button" class="nk-btn nk-btn--sm" data-nk-close aria-label="Cerrar"><svg class="nk-icon"
          aria-hidden="true">
          <use href="#nk-i-x" />
        </svg></button>
    </div>
    <div class="nk-table-wrap" style="overflow:auto">
      <table class="nk-table nk-table--cards">
        <thead>
          <tr>
            <th scope="col"><span class="nk-th">SKU</span></th>
            <th scope="col"><span class="nk-th">Artículo</span></th>
            <th scope="col"><span class="nk-th">Pedido</span></th>
            <th scope="col"><span class="nk-th">Recibido real</span></th>
            <th scope="col"><span class="nk-th">Estado</span></th>
            <th scope="col"><span class="nk-th">Costo unit.</span></th>
          </tr>
        </thead>
        <tbody id="recBody"></tbody>
      </table>
    </div>
    <div class="nk-dialog__foot foot-split">
      <div>
        <span class="nk-label lbl-inline">Total final liquidado</span>
        <strong id="recTotal" style="font:700 1.25rem var(--nk-f-head);margin-left:8px">$0</strong>
      </div>
      <div class="end">
        <button type="button" class="nk-btn" data-nk-close>Cancelar</button>
        <button type="button" class="nk-btn nk-btn--primary" data-action="confirm-receive" id="btnConfirmReceive">
          <svg class="nk-icon" aria-hidden="true">
            <use href="#nk-i-in" />
          </svg>Acreditar al inventario
        </button>
      </div>
    </div>
  </div>
</div>

<!-- 2. Vista previa de Mensaje de WhatsApp -->
<div class="nk-modal" id="waModal" hidden>
  <div class="nk-dialog nk-dialog--md" role="dialog" aria-modal="true" aria-labelledby="waTitle">
    <div class="nk-dialog__head">
      <div>
        <h3 id="waTitle">Mensaje listo para enviar</h3>
        <p id="waTo"></p>
      </div>
      <button type="button" class="nk-btn nk-btn--sm" data-nk-close aria-label="Cerrar"><svg class="nk-icon"
          aria-hidden="true">
          <use href="#nk-i-x" />
        </svg></button>
    </div>
    <div class="nk-dialog__body">
      <pre class="nk-bubble" id="waText"></pre>
    </div>
    <div class="nk-dialog__foot foot-split">
      <button type="button" class="nk-btn" data-action="wa-copy">
        <svg class="nk-icon" aria-hidden="true">
          <use href="#nk-i-copy" />
        </svg>Copiar texto
      </button>
      <div class="end">
        <button type="button" class="nk-btn nk-btn--wa" data-action="wa-open">
          <svg class="nk-icon" aria-hidden="true">
            <use href="#nk-i-ext" />
          </svg>Abrir WhatsApp
        </button>
      </div>
    </div>
  </div>
</div>

<!-- 3. Alta rápida de Producto -->
<div class="nk-modal" id="quickModal" hidden>
  <div class="nk-dialog nk-dialog--sm" role="dialog" aria-modal="true" aria-labelledby="qpTitle">
    <div class="nk-dialog__head">
      <div>
        <h3 id="qpTitle">Nuevo producto</h3>
        <p>Se incorpora al catálogo y se agrega automáticamente a la orden manual.</p>
      </div>
      <button type="button" class="nk-btn nk-btn--sm" data-nk-close aria-label="Cerrar"><svg class="nk-icon"
          aria-hidden="true">
          <use href="#nk-i-x" />
        </svg></button>
    </div>
    <form class="nk-dialog__body" id="quickForm" novalidate>
      <div class="nk-grid-2">
        <div>
          <label class="nk-label" for="qpSku">Código SKU (opcional)</label>
          <input class="nk-field" id="qpSku" placeholder="Ej: FUN-IP16PM" autocomplete="off">
        </div>
        <div>
          <label class="nk-label" for="qpCategory">Categoría</label>
          <select class="nk-field" id="qpCategory">
            <option value="Accesorios">Accesorios</option>
            <option value="Repuestos">Repuestos</option>
            <option value="Fundas">Fundas</option>
            <option value="Cargadores">Cargadores</option>
            <option value="Audio">Audio</option>
            <option value="Celulares">Celulares</option>
          </select>
        </div>
      </div>
      <div>
        <label class="nk-label" for="qpName">Nombre del producto *</label>
        <input class="nk-field" id="qpName" placeholder="Ej: Funda Silicone Case iPhone 16 Pro Max" autocomplete="off"
          data-autofocus aria-describedby="qpNameErr">
        <p class="nk-error" id="qpNameErr" hidden></p>
      </div>
      <div class="nk-grid-2">
        <div>
          <label class="nk-label" for="qpBrand">Marca</label>
          <input class="nk-field" id="qpBrand" placeholder="Ej: Apple / Genérico" autocomplete="off">
        </div>
        <div>
          <label class="nk-label" for="qpModel">Modelo</label>
          <input class="nk-field" id="qpModel" placeholder="Ej: iPhone 16 Pro Max" autocomplete="off">
        </div>
      </div>
      <div class="nk-grid-2">
        <div>
          <label class="nk-label" for="qpCost">Costo de compra ($)</label>
          <input class="nk-field nk-field--num" type="number" id="qpCost" value="4200" min="0" step="0.01"
            inputmode="decimal">
        </div>
        <div>
          <label class="nk-label" for="qpQty">Cantidad a pedir</label>
          <input class="nk-field nk-field--num" type="number" id="qpQty" value="10" min="1" inputmode="numeric">
        </div>
      </div>
    </form>
    <div class="nk-dialog__foot">
      <button type="button" class="nk-btn" data-nk-close>Cancelar</button>
      <button type="button" class="nk-btn nk-btn--primary" data-action="save-quick" id="btnSaveQuick">Crear y sumar a la
        orden</button>
    </div>
  </div>
</div>

<!-- 4. Registrar / Editar Proveedor -->
<div class="nk-modal" id="supplierModal" hidden>
  <div class="nk-dialog nk-dialog--sm" role="dialog" aria-modal="true" aria-labelledby="nsTitle">
    <div class="nk-dialog__head">
      <div>
        <h3 id="nsTitle">Registrar proveedor</h3>
        <p id="nsSub">Completá los datos comerciales y de contacto para pedidos.</p>
      </div>
      <button type="button" class="nk-btn nk-btn--sm" data-nk-close aria-label="Cerrar"><svg class="nk-icon"
          aria-hidden="true">
          <use href="#nk-i-x" />
        </svg></button>
    </div>
    <form class="nk-dialog__body" id="supplierForm" novalidate>
      <input type="hidden" id="nsId" value="0">
      <div>
        <label class="nk-label" for="nsName">Razón social o nombre *</label>
        <input class="nk-field" id="nsName" placeholder="Ej: Mayorista Celular S.R.L." autocomplete="off" data-autofocus
          aria-describedby="nsNameErr">
        <p class="nk-error" id="nsNameErr" hidden></p>
      </div>
      <div>
        <label class="nk-label" for="nsContact">Contacto (vendedor)</label>
        <input class="nk-field" id="nsContact" placeholder="Ej: Martín Gómez" autocomplete="off">
      </div>
      <div>
        <label class="nk-label" for="nsPhone">WhatsApp con código de país</label>
        <input class="nk-field" id="nsPhone" inputmode="tel" placeholder="5493704112233" autocomplete="off"
          aria-describedby="nsPhoneErr">
        <p class="nk-error" id="nsPhoneErr" hidden></p>
      </div>
      <div>
        <label class="nk-label" for="nsEmail">Email comercial</label>
        <input class="nk-field" id="nsEmail" type="email" placeholder="pedidos@distribuidora.com.ar" autocomplete="off"
          aria-describedby="nsEmailErr">
        <p class="nk-error" id="nsEmailErr" hidden></p>
      </div>
      <div>
        <label class="nk-label" for="nsAddress">Dirección o depósito de retiro</label>
        <input class="nk-field" id="nsAddress" placeholder="Ej: Av. Pueyrredón 480 · CABA" autocomplete="off">
      </div>
    </form>
    <div class="nk-dialog__foot">
      <button type="button" class="nk-btn" data-nk-close>Cancelar</button>
      <button type="button" class="nk-btn nk-btn--primary" data-action="save-supplier" id="btnSaveSupplier">Guardar
        proveedor</button>
    </div>
  </div>
</div>

<!-- 5. Detalle de Orden de Compra -->
<div class="nk-modal" id="orderDetailModal" hidden>
  <div class="nk-dialog nk-dialog--md" role="dialog" aria-modal="true" aria-labelledby="odTitle">
    <div class="nk-dialog__head">
      <div>
        <h3 id="odTitle">Detalle de orden</h3>
        <p id="odMeta"></p>
      </div>
      <button type="button" class="nk-btn nk-btn--sm" data-nk-close aria-label="Cerrar"><svg class="nk-icon"
          aria-hidden="true">
          <use href="#nk-i-x" />
        </svg></button>
    </div>
    <div class="nk-table-wrap" style="overflow:auto">
      <table class="nk-table nk-table--cards">
        <thead>
          <tr>
            <th scope="col"><span class="nk-th">SKU</span></th>
            <th scope="col"><span class="nk-th">Artículo</span></th>
            <th scope="col" class="is-c"><span class="nk-th">Cantidad</span></th>
            <th scope="col" class="is-r"><span class="nk-th">Costo unit.</span></th>
            <th scope="col" class="is-r"><span class="nk-th">Subtotal</span></th>
          </tr>
        </thead>
        <tbody id="odBody"></tbody>
      </table>
    </div>
    <div class="nk-dialog__foot foot-split">
      <div>
        <span class="nk-label lbl-inline">Importe total</span>
        <strong id="odTotal" style="font:700 1.25rem var(--nk-f-head);margin-left:8px">$0</strong>
      </div>
      <div class="end">
        <button type="button" class="nk-btn" data-nk-close>Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Contenedor de Toasts Nokia -->
<div class="nk-toasts" id="nkToasts" role="status" aria-live="polite"></div>

<script>
  window.NK_SUPPLIERS_DATA = <?php echo json_encode([
    'suppliers' => $normSuppliers,
    'catalog' => $normCatalog,
    'orders' => $normOrders,
    'initialView' => $initial_view,
    'preselectedSupplier' => $preselected_supplier,
    'userRole' => $isAdmin ? 'admin' : 'empleado',
    'currentUser' => $currentUser,
    'csrfToken' => $csrfToken,
    'baseUrl' => BASE_URL
  ], JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="<?php echo BASE_URL; ?>/modules/suppliers/suppliers_list.js?v=<?php echo time(); ?>"></script>
<?php Layout::renderFooter(); ?>