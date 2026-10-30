<?php
// modules/inventory/Views/inventory.php
// Modelo visual Nokia 1100: Papel Cálido + Tinta + Grafito + Verde LCD
// Integrado con Layout unificado para mantener el mismo menú lateral en todo el sistema

if (!class_exists('Layout')) {
    require_once __DIR__ . '/../../../classes/Layout.php';
}

/** @var array $items */
/** @var array $all_products */
/** @var array $stats */
/** @var string $search */
/** @var string $filter */
/** @var string $categoria */
/** @var array $categorias */
/** @var int $page */
/** @var int $total_pages */
/** @var int $total_items */
/** @var int $limit */

$all_products = isset($all_products) && is_array($all_products) ? $all_products : (isset($items) && is_array($items) ? $items : []);
$stats = isset($stats) && is_array($stats) ? $stats : [
    'total_count' => count($all_products),
    'low_stock' => 0,
    'out_of_stock' => 0,
    'total_value' => 0
];
$baseCategorias = ['Celulares', 'Fundas', 'Audio', 'Cargadores', 'Repuestos', 'Perifericos', 'Accesorios'];
$categorias = isset($categorias) && is_array($categorias) && count($categorias) > 0
    ? array_values(array_unique(array_merge($baseCategorias, $categorias)))
    : $baseCategorias;

$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
$csrf_token = $_SESSION['session_token'] ?? '';

// Mapeo de categorías y colores de marca
$catColors = [
    'Celulares' => '#1f5fa8',
    'Fundas' => '#b36a00',
    'Audio' => '#a63d86',
    'Cargadores' => '#5c6165',
    'Repuestos' => '#1d6a38',
    'Perifericos' => '#2e7d32',
    'Accesorios' => '#4338ca'
];

// Preparamos los datos normalizados para JavaScript
$jsonProducts = [];
foreach ($all_products as $p) {
    $cant = (int)($p['cantidad'] ?? 0);
    $min = isset($p['stock_minimo']) ? (int)$p['stock_minimo'] : 3;
    $precio = (float)($p['precio'] ?? 0);
    $jsonProducts[] = [
        'id' => (int)$p['id_producto'],
        'nombre' => (string)($p['nombre'] ?? ''),
        'marca' => (string)($p['marca'] ?? ''),
        'modelo' => (string)($p['modelo'] ?? ''),
        'sku' => (string)($p['codigo'] ?? ''),
        'categoria' => (string)($p['categoria'] ?: 'Celulares'),
        'cantidad' => $cant,
        'minimo' => $min,
        'precio' => $precio,
        'activo' => !empty($p['is_active'])
    ];
}

$jsVersion = file_exists(__DIR__ . '/../../../assets/js/inventory.js') ? filemtime(__DIR__ . '/../../../assets/js/inventory.js') : time();

Layout::renderHead('NOKIA1100 | Inventario', ['/assets/css/inventory.css']);
if ($isAdmin) {
    Layout::renderAdminSidebar('inventario');
} else {
    Layout::renderEmployeeSidebar('inventario');
}
?>

<!-- Iconos SVG del módulo de inventario -->
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
  <symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></symbol>
  <symbol id="i-filter" viewBox="0 0 24 24"><path d="M4 6h16M7 12h10M10 18h4"/></symbol>
  <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
  <symbol id="i-x" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></symbol>
  <symbol id="i-check" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></symbol>
  <symbol id="i-alert" viewBox="0 0 24 24"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0zM12 9v4M12 17h.01"/></symbol>
  <symbol id="i-down" viewBox="0 0 24 24"><path d="M12 3v12M7 10l5 5 5-5M4 21h16"/></symbol>
  <symbol id="i-pencil" viewBox="0 0 24 24"><path d="M17 3a2.8 2.8 0 0 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></symbol>
  <symbol id="i-arrow" viewBox="0 0 24 24"><path d="M12 19V5M5 12l7-7 7 7"/></symbol>
  <symbol id="i-box" viewBox="0 0 24 24"><path d="M21 8 12 3 3 8v8l9 5 9-5V8zM3 8l9 5 9-5M12 13v8"/></symbol>
  <symbol id="i-left" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></symbol>
  <symbol id="i-right" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></symbol>
  <!-- Iconos de Categorías Oficiales Nokia 1100 -->
  <symbol id="cat-audio" viewBox="0 0 24 24">
    <path d="M4 14.5V10.5C4 6.08 7.58 2.5 12 2.5s8 3.58 8 8v4" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/>
    <rect x="2" y="13" width="4.5" height="8" rx="2.25" fill="currentColor"/>
    <rect x="17.5" y="13" width="4.5" height="8" rx="2.25" fill="currentColor"/>
  </symbol>
  <symbol id="cat-repuestos" viewBox="0 0 24 24">
    <path fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" d="M10 2h4v2.4a7.6 7.6 0 0 1 2.3.9l1.7-1.7 2.8 2.8-1.7 1.7c.4.7.7 1.5.9 2.3H22v4h-2.4a7.6 7.6 0 0 1-.9 2.3l1.7 1.7-2.8 2.8-1.7-1.7a7.6 7.6 0 0 1-2.3.9V22h-4v-2.4a7.6 7.6 0 0 1-2.3-.9l-1.7 1.7-2.8-2.8 1.7-1.7a7.6 7.6 0 0 1-.9-2.3H2v-4h2.4a7.6 7.6 0 0 1 .9-2.3l-1.7-1.7 2.8-2.8 1.7 1.7c.7-.4 1.5-.7 2.3-.9V2zm2 5.5a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9z"/>
  </symbol>
  <symbol id="cat-perifericos" viewBox="0 0 24 24">
    <rect x="5.5" y="2" width="13" height="20" rx="6.5" fill="none" stroke="currentColor" stroke-width="2.4"/>
    <line x1="5.5" y1="10" x2="18.5" y2="10" stroke="currentColor" stroke-width="2.4"/>
    <line x1="12" y1="2" x2="12" y2="10" stroke="currentColor" stroke-width="2.4"/>
  </symbol>
  <symbol id="cat-fundas" viewBox="0 0 24 24">
    <rect x="5" y="2" width="14" height="20" rx="4.5" fill="none" stroke="currentColor" stroke-width="2.4"/>
    <rect x="7.8" y="5" width="4.6" height="4.6" rx="1.4" fill="currentColor"/>
  </symbol>
  <symbol id="cat-celulares" viewBox="0 0 24 24">
    <rect x="5.5" y="2" width="13" height="20" rx="3.5" fill="none" stroke="currentColor" stroke-width="2.4"/>
    <line x1="10" y1="5.5" x2="14" y2="5.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <circle cx="12" cy="18" r="1.2" fill="currentColor"/>
  </symbol>
  <symbol id="cat-cargadores" viewBox="0 0 24 24">
    <line x1="9" y1="2" x2="9" y2="6.5" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/>
    <line x1="15" y1="2" x2="15" y2="6.5" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/>
    <rect x="5.5" y="6" width="13" height="8.5" rx="3" fill="currentColor"/>
    <path d="M12 14.5v2.5c0 1.8 1.8 2.8 1.8 4.2 0 1.2-.8 1.8-1.8 1.8" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
  </symbol>
  <symbol id="cat-accesorios" viewBox="0 0 24 24">
    <path fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" d="M7.5 1.5A1.5 1.5 0 0 0 6 3v4.2C4.8 7.8 4 9 4 10.5v8A3.5 3.5 0 0 0 7.5 22h9a3.5 3.5 0 0 0 3.5-3.5v-8c0-1.5-.8-2.7-2-3.3V3a1.5 1.5 0 0 0-1.5-1.5h-9zM8.5 3.5h7v3h-7v-3zM12 16.7a1.8 1.8 0 1 0 0 3.6 1.8 1.8 0 0 0 0-3.6z"/>
    <rect x="9.8" y="4.2" width="1.4" height="1.6" rx="0.3" fill="currentColor"/>
    <rect x="12.8" y="4.2" width="1.4" height="1.6" rx="0.3" fill="currentColor"/>
  </symbol>
</svg>

<main id="main" class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen flex flex-col bg-[#eeebe3]">
  <div class="max-w-7xl mx-auto w-full flex-grow inv-page">
    <header class="head">
      <div>
        <h1 id="title" tabindex="-1">Inventario</h1>
        <p class="sub">Control de stock, repuestos y equipos tecnológicos</p>
      </div>
      <div class="head-actions">
        <button type="button" class="btn btn-export" data-action="excel" title="Exportar a Excel">
          <svg class="i" aria-hidden="true"><use href="#i-down"/></svg>Excel
        </button>
        <button type="button" class="btn btn-export" data-action="pdf" title="Exportar a PDF">
          <svg class="i" aria-hidden="true"><use href="#i-down"/></svg>PDF
        </button>
        <?php if ($isAdmin): ?>
          <button type="button" class="btn btn-lcd" data-action="add">
            <svg class="i" aria-hidden="true"><use href="#i-plus"/></svg>Añadir
          </button>
        <?php endif; ?>
      </div>
    </header>

    <!-- Resumen (KPIs interactivos: tocar un dato filtra la lista) -->
    <div class="kpis" role="group" aria-label="Resumen de inventario. Tocá un dato para filtrar.">
      <button type="button" class="kpi" data-kpi="all" aria-pressed="true" title="Mostrar todos los productos">
        <span class="kpi-l">Productos</span>
        <span class="kpi-v" id="kTotal" data-n="<?php echo $stats['total_count']; ?>"><?php echo $stats['total_count']; ?></span>
      </button>
      <button type="button" class="kpi" data-kpi="low" aria-pressed="false" title="Filtrar por productos con stock bajo">
        <span class="kpi-l">Stock bajo</span>
        <span class="kpi-v warn" id="kLow" data-n="<?php echo $stats['low_stock']; ?>"><?php echo $stats['low_stock']; ?></span>
      </button>
      <button type="button" class="kpi" data-kpi="out" aria-pressed="false" title="Filtrar por productos agotados">
        <span class="kpi-l">Sin stock</span>
        <span class="kpi-v bad" id="kOut" data-n="<?php echo $stats['out_of_stock']; ?>"><?php echo $stats['out_of_stock']; ?></span>
      </button>
    </div>

    <!-- Barra de Herramientas y Filtros -->
    <div class="tools">
      <div class="search">
        <svg class="i" aria-hidden="true"><use href="#i-search"/></svg>
        <label class="vh" for="q">Buscar por producto, marca, modelo o SKU</label>
        <input type="search" id="q" class="field" placeholder="Buscar por producto, marca, modelo, SKU…" autocomplete="off" value="<?php echo htmlspecialchars($search); ?>">
        <span class="kbd" aria-hidden="true">/</span>
      </div>
      <div class="tool-select">
        <label class="vh" for="cat">Categoría</label>
        <select id="cat" class="field">
          <option value="all">Todas las categorías</option>
          <?php foreach ($categorias as $c): ?>
            <option value="<?php echo htmlspecialchars($c); ?>" <?php echo ($categoria === $c) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($c); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="button" class="btn" id="filterBtn" aria-expanded="false" aria-controls="filterPop">
        <svg class="i" aria-hidden="true"><use href="#i-filter"/></svg>Filtrar<span class="count-badge" id="filterCount" hidden>0</span>
      </button>

      <div class="pop" id="filterPop" hidden role="dialog" aria-label="Filtros avanzados">
        <h3>Filtros</h3>
        <div>
          <span class="lbl" id="lblEstado">Estado</span>
          <div class="seg" role="group" aria-labelledby="lblEstado" data-group="estado">
            <button type="button" data-v="all" aria-pressed="true">Todos</button>
            <button type="button" data-v="on" aria-pressed="false">Activos</button>
            <button type="button" data-v="off" aria-pressed="false">Inactivos</button>
          </div>
        </div>
        <div>
          <span class="lbl" id="lblStock">Nivel de stock</span>
          <div class="seg" role="group" aria-labelledby="lblStock" data-group="stock">
            <button type="button" data-v="all" aria-pressed="true">Todos</button>
            <button type="button" data-v="low" aria-pressed="false">Bajo</button>
            <button type="button" data-v="out" aria-pressed="false">Sin stock</button>
          </div>
        </div>
        <div class="pop-foot">
          <button type="button" class="btn btn-sm" data-action="clear">Limpiar todo</button>
          <button type="button" class="btn btn-sm btn-primary" data-action="close-filter">Listo</button>
        </div>
      </div>
    </div>

    <div class="chips" id="chips" aria-label="Filtros activos"></div>

    <!-- Panel de Tabla -->
    <section class="panel" aria-label="Listado de productos">
      <div class="tbl-wrap">
        <table class="tbl" id="inventory-table">
          <caption class="vh">Productos del inventario</caption>
          <thead>
            <tr>
              <th scope="col" data-sort="nombre"><button type="button" class="th-btn" data-sortbtn="nombre">Producto<svg class="i arrow" aria-hidden="true"><use href="#i-arrow"/></svg></button></th>
              <th scope="col" data-sort="categoria"><button type="button" class="th-btn" data-sortbtn="categoria">Categoría<svg class="i arrow" aria-hidden="true"><use href="#i-arrow"/></svg></button></th>
              <th scope="col" class="c" data-sort="cantidad"><button type="button" class="th-btn" data-sortbtn="cantidad">Stock<svg class="i arrow" aria-hidden="true"><use href="#i-arrow"/></svg></button></th>
              <th scope="col" class="r" data-sort="precio"><button type="button" class="th-btn" data-sortbtn="precio">Precio<svg class="i arrow" aria-hidden="true"><use href="#i-arrow"/></svg></button></th>
              <th scope="col" class="c"><span class="th-static">Estado</span></th>
              <th scope="col" class="r"><span class="th-static">Acciones</span></th>
            </tr>
          </thead>
          <tbody id="tbody"></tbody>
        </table>
      </div>

      <div class="empty" id="empty" hidden>
        <div class="glyph"><svg class="i" aria-hidden="true"><use href="#i-box"/></svg></div>
        <strong id="emptyTitle">No encontramos productos</strong>
        <span id="emptyText">Probá con otra búsqueda o quitá algún filtro.</span><br>
        <button type="button" class="btn btn-sm" data-action="clear">Limpiar filtros</button>
      </div>

      <div class="foot" id="foot">
        <span id="range" class="sys-pagination-info">Página 1 de 1 - 0 productos</span>
        <nav class="pager" id="pager" aria-label="Paginación"></nav>
      </div>
    </section>
  </div>
</main>

<!-- Modal Añadir / Editar Producto -->
<div class="modal" id="modal" hidden>
  <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="mTitle">
    <div class="d-head">
      <div>
        <h3 id="mTitle">Añadir producto</h3>
        <p id="mSub">Completá los datos del artículo.</p>
      </div>
      <button type="button" class="icon-btn" data-action="close-modal" aria-label="Cerrar modal">
        <svg class="i" aria-hidden="true"><use href="#i-x"/></svg>
      </button>
    </div>
    <form class="d-body" id="form" novalidate>
      <input type="hidden" id="fId" value="">
      <div class="full">
        <label class="lbl" for="fNombre">Producto *</label>
        <input class="field" id="fNombre" autocomplete="off" required aria-describedby="fNombreErr" data-autofocus placeholder="Ej: Pantalla OLED, Cargador...">
        <p class="err" id="fNombreErr" hidden></p>
      </div>
      <div>
        <label class="lbl" for="fMarca">Marca</label>
        <input class="field" id="fMarca" autocomplete="off" placeholder="Ej: Samsung, Nokia, Apple...">
      </div>
      <div>
        <label class="lbl" for="fModelo">Modelo</label>
        <input class="field" id="fModelo" autocomplete="off" placeholder="Ej: A15, 1100, USB-C...">
      </div>
      <div>
        <label class="lbl" for="fSku">SKU / Código (opcional)</label>
        <input class="field mono" id="fSku" autocomplete="off" aria-describedby="fSkuErr" placeholder="Ej: CEL-NK1100">
        <p class="err" id="fSkuErr" hidden></p>
      </div>
      <div>
        <label class="lbl" for="fCat">Categoría</label>
        <select class="field" id="fCat">
          <?php foreach ($categorias as $c): ?>
            <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="lbl" for="fCant">Stock actual *</label>
        <input class="field field-num" id="fCant" type="number" min="0" inputmode="numeric" required aria-describedby="fCantErr" value="0">
        <p class="err" id="fCantErr" hidden></p>
      </div>
      <div>
        <label class="lbl" for="fMin">Stock mínimo</label>
        <input class="field field-num" id="fMin" type="number" min="0" inputmode="numeric" aria-describedby="fMinHint" value="3">
        <p class="hint" id="fMinHint">Por debajo se marca automáticamente como "Bajo".</p>
      </div>
      <div>
        <label class="lbl" for="fPrecio">Precio de venta *</label>
        <div class="money">
          <span>$</span>
          <input class="field field-num" id="fPrecio" type="number" min="0" step="0.01" inputmode="decimal" required aria-describedby="fPrecioErr" placeholder="0.00">
        </div>
        <p class="err" id="fPrecioErr" hidden></p>
      </div>
      <div>
        <span class="lbl" id="lblFEstado">Estado</span>
        <div class="seg" role="group" aria-labelledby="lblFEstado" id="fEstado">
          <button type="button" data-v="on" aria-pressed="true">Activo</button>
          <button type="button" data-v="off" aria-pressed="false">Inactivo</button>
        </div>
      </div>
    </form>
    <div class="d-foot">
      <button type="button" class="btn" data-action="close-modal">Cancelar</button>
      <button type="button" class="btn btn-primary" data-action="save" id="saveBtn">
        <svg class="i" aria-hidden="true"><use href="#i-check"/></svg>Guardar
      </button>
    </div>
  </div>
</div>

<!-- Soporte Motion Vanilla JS para micro-animaciones fluidas -->
<script type="module">
  try {
    const { animate, stagger } = await import("https://cdn.jsdelivr.net/npm/motion@11/+esm");
    window.fx = { animate, stagger };
    document.dispatchEvent(new Event("fx-ready"));
  } catch (e) {}
</script>

<!-- Inyección de Datos PHP para el Motor Reactivo de JavaScript -->
<script>
  window.INVENTORY_DATA = {
    csrfToken: <?php echo json_encode($csrf_token); ?>,
    isAdmin: <?php echo $isAdmin ? 'true' : 'false'; ?>,
    baseUrl: <?php echo json_encode(BASE_URL); ?>,
    categories: <?php echo json_encode(array_values($categorias), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
    catColors: <?php echo json_encode($catColors, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
    products: <?php echo json_encode($jsonProducts, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
    searchQuery: <?php echo json_encode($search); ?>,
    selectedCat: <?php echo json_encode(!empty($categoria) ? $categoria : 'all'); ?>,
    selectedStock: <?php echo json_encode($filter === 'low_stock' ? 'low' : 'all'); ?>,
    stats: <?php echo json_encode($stats); ?>
  };
</script>

<!-- Script Externo del Módulo de Inventario -->
<script src="<?php echo BASE_URL; ?>/assets/js/inventory.js?v=<?php echo $jsVersion; ?>"></script>

<?php Layout::renderFooter(); ?>
