<?php
// modules/caja/Views/index.php

/**
 * Variables inyectadas por CajaController
 * @var array|null $open_caja
 * @var array|null $last_closed
 * @var array $cajas
 */

$open_caja = $open_caja ?? null;
$last_closed = $last_closed ?? null;
$cajas = $cajas ?? [];

Layout::renderHead('Caja - Nokia 1100', ['/modules/caja/caja.css']);

if (($_SESSION['role'] ?? '') === 'admin') {
  Layout::renderAdminSidebar('caja');
} else {
  Layout::renderEmployeeSidebar('caja');
}

// Utilidades locales de formateo
$money = fn($n) => '$' . number_format((float) ($n ?: 0), 2, ',', '.');
$smoney = function ($n) use ($money) {
  if ($n > 0)
    return '+' . $money($n);
  if ($n < 0)
    return '−' . $money(abs($n));
  return $money(0);
};
$fdate = fn($s) => $s ? date('d/m/Y', strtotime($s)) : '';
$todayISO = date('Y-m-d');

// Extraer meses únicos disponibles en las cajas para el selector
$uniqueMonths = [];
foreach ($cajas as $c) {
  if (!empty($c['fecha'])) {
    $ym = substr($c['fecha'], 0, 7);
    if (!isset($uniqueMonths[$ym])) {
      $monthTime = strtotime($c['fecha']);
      $mesesNom = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
      $mIdx = (int) date('n', $monthTime) - 1;
      $uniqueMonths[$ym] = $mesesNom[$mIdx] . ' ' . date('Y', $monthTime);
    }
  }
}
krsort($uniqueMonths);
?>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
  <div class="caja-wrapper">
    <!-- Encabezado del Módulo -->
    <header class="caja-head">
      <p class="nk-eyebrow">Módulo · Operación</p>
      <h1 class="nk-title" id="pageTitle" tabindex="-1">Caja</h1>
      <p class="nk-sub">Abrí la caja con el efectivo inicial, registrá lo que entra y sale durante el día y cerrala
        contando lo que hay.</p>
    </header>

    <!-- Estado actual: Pantalla LCD + Acciones -->
    <section class="nk-panel status" aria-labelledby="stTitle">
      <!-- Pantalla LCD -->
      <div class="lcd" id="lcd" data-open="<?php echo $open_caja ? 'true' : 'false'; ?>">
        <div class="lcd__row">
          <span class="lcd__tag">Caja del día</span>
          <span class="lcd__led" id="lcdLed" role="status"><?php echo $open_caja ? 'Abierta' : 'Cerrada'; ?></span>
        </div>
        <div>
          <span class="lcd__label" id="lcdLabel">
            <?php echo $open_caja ? 'Efectivo esperado en caja' : ($last_closed ? 'Último cierre' : 'Sin movimientos'); ?>
          </span>
          <div class="lcd__amount" id="lcdAmount">
            <?php
            if ($open_caja) {
              echo $money($open_caja['monto_esperado'] ?? $open_caja['monto_apertura']);
            } elseif ($last_closed) {
              echo $money($last_closed['monto_cierre'] ?? 0);
            } else {
              echo '$0,00';
            }
            ?>
          </div>
        </div>
        <div class="lcd__meta" id="lcdMeta">
          <?php if ($open_caja): ?>
            <div><span class="lcd__label">Fecha</span><strong><?php echo $fdate($open_caja['fecha']); ?></strong></div>
            <div><span
                class="lcd__label">Apertura</span><strong><?php echo $money($open_caja['monto_apertura']); ?></strong>
            </div>
            <div><span class="lcd__label">Ingresos</span><strong
                class="mv-in"><?php echo ($open_caja['ingresos'] > 0 ? '+' : '') . $money($open_caja['ingresos']); ?></strong>
            </div>
            <div><span class="lcd__label">Egresos</span><strong
                class="mv-out"><?php echo ($open_caja['egresos'] > 0 ? '−' : '') . $money($open_caja['egresos']); ?></strong>
            </div>
          <?php elseif ($last_closed): ?>
            <div><span class="lcd__label">Fecha</span><strong><?php echo $fdate($last_closed['fecha']); ?></strong></div>
            <div><span class="lcd__label">Apertura de ese
                día</span><strong><?php echo $money($last_closed['monto_apertura']); ?></strong></div>
          <?php else: ?>
            <div><span class="lcd__label">Fecha</span><strong><?php echo $fdate($todayISO); ?></strong></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Panel de Apertura (visible si la caja está cerrada) -->
      <form class="action" id="openForm" <?php if ($open_caja)
        echo 'hidden'; ?> novalidate>
        <h2 class="nk-h2" id="stTitle">Abrir caja</h2>
        <p>Cargá el efectivo con el que arrancás. Solo puede haber una caja abierta a la vez en el sistema.</p>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; max-width: 440px;">
          <div>
            <label class="nk-label" for="opDate">Fecha</label>
            <input class="nk-field" id="opDate" value="<?php echo date('d/m/Y'); ?>" readonly>
          </div>
          <div>
            <label class="nk-label" for="opAmount">Monto de apertura</label>
            <div class="nk-money">
              <span aria-hidden="true">$</span>
              <input class="nk-field nk-field--num" id="opAmount" type="number" min="0" step="0.01" inputmode="decimal"
                placeholder="0,00" required>
            </div>
            <p class="nk-error" id="opAmountErr" hidden></p>
          </div>
        </div>
        <?php if ($last_closed && $last_closed['monto_cierre'] !== null): ?>
          <p class="nk-hint" id="opSuggest" style="margin:0">
            El último cierre fue <?php echo $money($last_closed['monto_cierre']); ?>.
            <button type="button" class="linkbtn" data-action="use-last"
              data-amount="<?php echo (float) $last_closed['monto_cierre']; ?>">Usar como apertura</button>
          </p>
        <?php endif; ?>
        <div class="btn-row">
          <button type="submit" class="nk-btn nk-btn--lcd big" id="btnOpen">
            <span class="material-symbols-outlined text-[19px]">lock_open</span> Abrir caja
          </button>
        </div>
      </form>

      <!-- Panel de Caja Abierta (visible si la caja está abierta) -->
      <div class="action" id="closePane" <?php if (!$open_caja)
        echo 'hidden'; ?>>
        <h2 class="nk-h2">Caja abierta</h2>
        <?php
        $isStale = ($open_caja && $open_caja['fecha'] !== $todayISO);
        if ($isStale):
          ?>
          <div class="warnbox" id="staleWarn">
            Esta caja es del <?php echo $fdate($open_caja['fecha']); ?> y quedó sin cerrar. Cerrala antes de abrir la de
            hoy.
          </div>
        <?php endif; ?>
        <p>Anotá cada ingreso o egreso de efectivo y, al terminar, contá lo que hay y cerrá la caja. Una vez cerrada no
          se modifica.</p>
        <div class="btn-row">
          <button type="button" class="nk-btn big" data-action="focus-mov">
            <span class="material-symbols-outlined text-[19px]">add</span> Registrar movimiento
          </button>
          <button type="button" class="nk-btn nk-btn--primary big" data-action="close-caja" id="btnClose">
            <span class="material-symbols-outlined text-[19px]">lock</span> Cerrar caja
          </button>
        </div>
      </div>
    </section>

    <!-- Movimientos de la caja abierta -->
    <?php if ($open_caja): ?>
      <section class="nk-panel" id="movPanel" aria-labelledby="mvTitle">
        <div class="nk-panel__bar">
          <div>
            <h2 class="nk-h2" id="mvTitle">Movimientos de hoy</h2>
            <p class="nk-panel__desc">Ventas en efectivo, cobros, pagos y retiros. Alimentan el monto esperado al cerrar.
            </p>
          </div>
        </div>

        <!-- Formulario para agregar movimiento -->
        <form class="mv-form" id="mvForm" novalidate>
          <div>
            <span class="nk-label" id="mvTipoL">Tipo</span>
            <div class="nk-seg" id="mvTipo" role="group" aria-labelledby="mvTipoL">
              <button type="button" data-v="in" aria-pressed="true">Ingreso</button>
              <button type="button" data-v="out" aria-pressed="false">Egreso</button>
            </div>
          </div>
          <div>
            <label class="nk-label" for="mvConcepto">Concepto</label>
            <select class="nk-field" id="mvConcepto"></select>
          </div>
          <div>
            <label class="nk-label" for="mvDetalle">Detalle (opcional)</label>
            <input class="nk-field" id="mvDetalle" maxlength="100" autocomplete="off"
              placeholder="Ej: reparación Samsung A15">
          </div>
          <div>
            <label class="nk-label" for="mvMonto">Monto</label>
            <div class="nk-money">
              <span aria-hidden="true">$</span>
              <input class="nk-field nk-field--num" id="mvMonto" type="number" min="0.01" step="0.01" inputmode="decimal"
                placeholder="0,00" required>
            </div>
            <p class="nk-error" id="mvMontoErr" hidden></p>
          </div>
          <button type="submit" class="nk-btn nk-btn--primary" style="height:38px;">
            <span class="material-symbols-outlined text-[17px]">add</span> Agregar
          </button>
        </form>

        <!-- Tabla de movimientos de la caja abierta -->
        <div class="nk-table-wrap">
          <table class="nk-table">
            <thead>
              <tr>
                <th scope="col" style="width:90px;">Hora</th>
                <th scope="col" style="width:110px;">Tipo</th>
                <th scope="col">Concepto</th>
                <th scope="col" class="is-r" style="width:140px;">Monto</th>
                <th scope="col" class="is-r" style="width:60px;"><span class="sr-only">Acciones</span></th>
              </tr>
            </thead>
            <tbody id="mvTbody">
              <?php
              $movs = $open_caja['movs'] ?? [];
              if (!empty($movs)):
                foreach ($movs as $m):
                  $isIn = ($m['tipo'] === 'in');
                  ?>
                  <tr>
                    <td><span class="nk-mono"><?php echo htmlspecialchars($m['hora']); ?></span></td>
                    <td><span
                        class="nk-tag nk-tag--<?php echo $isIn ? 'ok' : 'bad'; ?>"><?php echo $isIn ? 'Ingreso' : 'Egreso'; ?></span>
                    </td>
                    <td>
                      <span class="nk-cell-main"><?php echo htmlspecialchars($m['concepto']); ?></span>
                      <?php if (!empty($m['detalle'])): ?>
                        <span class="nk-cell-meta"><?php echo htmlspecialchars($m['detalle']); ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="nk-num <?php echo $isIn ? 'mv-in' : 'mv-out'; ?>">
                      <?php echo ($isIn ? '+' : '−') . $money($m['monto']); ?>
                    </td>
                    <td class="is-r">
                      <button type="button" class="nk-btn nk-btn--sm nk-btn--danger" data-action="mv-remove"
                        data-id="<?php echo $m['id_movimiento']; ?>"
                        data-concepto="<?php echo htmlspecialchars($m['concepto'], ENT_QUOTES); ?>"
                        data-monto="<?php echo (float) $m['monto']; ?>" title="Quitar movimiento">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                      </button>
                    </td>
                  </tr>
                <?php
                endforeach;
              endif;
              ?>
            </tbody>
          </table>
        </div>

        <!-- Estado vacío si no hay movimientos -->
        <div class="nk-empty" id="mvEmpty" <?php if (!empty($movs))
          echo 'hidden'; ?> style="padding:32px 20px;">
          <strong>Todavía no hay movimientos registrados hoy</strong>
          Registrá el primero arriba. Si no hay movimientos, el efectivo esperado es igual a la apertura.
        </div>

        <!-- Ticket con suma y desglose -->
        <div class="nk-ticket">
          <div>
            <span class="nk-label" style="display:inline; margin:0;">Efectivo esperado</span>
            <strong id="mvExpected"><?php echo $money($open_caja['monto_esperado']); ?></strong>
          </div>
          <span class="nk-cell-meta" id="mvTotals" style="margin:0;">
            Apertura <?php echo $money($open_caja['monto_apertura']); ?> + ingresos
            <?php echo $money($open_caja['ingresos']); ?> − egresos <?php echo $money($open_caja['egresos']); ?>
          </span>
        </div>
      </section>
    <?php endif; ?>

    <!-- Historial de Cajas -->
    <section class="nk-panel" aria-labelledby="hTitle">
      <div class="nk-panel__bar">
        <div>
          <h2 class="nk-h2" id="hTitle">Historial de cajas</h2>
          <p class="nk-panel__desc">Cada fila es una caja: con qué se abrió, con cuánto se cerró y la diferencia contra
            lo esperado.</p>
        </div>
        <div class="nk-tools">
          <div class="nk-seg" id="stSeg" role="group" aria-label="Filtrar por estado">
            <button type="button" data-v="all" aria-pressed="true">Todas</button>
            <button type="button" data-v="open" aria-pressed="false">Abierta</button>
            <button type="button" data-v="closed" aria-pressed="false">Cerradas</button>
          </div>

          <label class="sr-only" for="monthSel">Filtrar por mes</label>
          <select class="nk-field nk-field--sm" id="monthSel" style="width:auto; min-width:140px;">
            <option value="all">Todos los meses</option>
            <?php foreach ($uniqueMonths as $ym => $label): ?>
              <option value="<?php echo $ym; ?>"><?php echo htmlspecialchars($label); ?></option>
            <?php endforeach; ?>
          </select>

          <button type="button" class="nk-btn nk-btn--sm" data-action="export" title="Exportar reporte en CSV">
            <span class="material-symbols-outlined text-[16px]">download</span> CSV
          </button>
          <button type="button" class="nk-btn nk-btn--sm" data-action="print" title="Imprimir historial">
            <span class="material-symbols-outlined text-[16px]">print</span> Imprimir
          </button>
        </div>
      </div>

      <div class="nk-table-wrap">
        <table class="nk-table">
          <thead>
            <tr>
              <th scope="col">Fecha</th>
              <th scope="col" class="is-r">Monto de apertura</th>
              <th scope="col" class="is-r">Monto de cierre</th>
              <th scope="col" class="is-r">Diferencia</th>
              <th scope="col">Estado</th>
              <th scope="col" class="is-r">Acciones</th>
            </tr>
          </thead>
          <tbody id="cajaTbody">
            <?php
            foreach ($cajas as $c):
              $isOpen = ($c['estado'] === 'abierta');
              $d = $c['diferencia'];
              $monthKey = substr($c['fecha'], 0, 7);
              $isCuadra = ($d !== null && abs((float) $d) < 0.005);
              ?>
              <tr data-status="<?php echo $isOpen ? 'open' : 'closed'; ?>" data-month="<?php echo $monthKey; ?>"
                data-diff="<?php echo (float) ($d ?? 0); ?>">
                <td>
                  <span class="nk-cell-main"><?php echo $fdate($c['fecha']); ?></span>
                  <span class="nk-cell-meta"><?php echo htmlspecialchars($c['hora_apertura']); ?> →
                    <?php echo $c['hora_cierre'] ? htmlspecialchars($c['hora_cierre']) : 'en curso'; ?></span>
                </td>
                <td class="nk-num"><?php echo $money($c['monto_apertura']); ?></td>
                <td class="nk-num">
                  <?php echo $isOpen ? '<span class="nk-cell-meta">Pendiente</span>' : $money($c['monto_cierre']); ?>
                </td>
                <td class="nk-num">
                  <?php if ($isOpen): ?>
                    <span class="nk-cell-meta">—</span>
                  <?php elseif ($isCuadra): ?>
                    <span class="nk-tag nk-tag--ok">Cuadra</span>
                  <?php else: ?>
                    <span class="<?php echo $d > 0 ? 'diff--pos' : 'diff--neg'; ?>"><?php echo $smoney($d); ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($isOpen): ?>
                    <span class="nk-tag nk-tag--lcd">Abierta</span>
                  <?php else: ?>
                    <span class="nk-tag nk-tag--neutral">Cerrada</span>
                  <?php endif; ?>
                </td>
                <td class="is-r">
                  <div style="display:inline-flex; gap:6px;">
                    <button type="button" class="nk-btn nk-btn--sm" data-action="detail"
                      data-id="<?php echo $c['id_caja']; ?>">
                      Ver detalle
                    </button>
                    <?php if ($isOpen): ?>
                      <button type="button" class="nk-btn nk-btn--sm nk-btn--primary" data-action="close-caja">
                        Cerrar caja
                      </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="nk-empty" id="emptyHist" <?php if (!empty($cajas))
        echo 'hidden'; ?>>
        <strong>No hay cajas registradas con este filtro</strong>
        Cambiá el estado o el mes seleccionado.
      </div>

      <div class="nk-foot">
        <span id="footCount"><?php echo count($cajas); ?> cajas</span>
        <span id="footDiff"></span>
      </div>
    </section>

    <p style="text-align:right; margin:12px 0 0 0;">
      <button type="button" class="linkbtn nk-cell-meta" data-action="reset-demo">Restablecer datos de demo</button>
    </p>
  </div>
</main>

<!-- Modal: Cerrar caja -->
<div class="caja-modal-backdrop" id="closeModal">
  <div class="caja-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="clTitle">
    <div class="caja-modal-head">
      <div>
        <h3 id="clTitle">Cerrar caja</h3>
        <p id="clSub">
          <?php if ($open_caja)
            echo "Caja del " . $fdate($open_caja['fecha']) . " · abierta " . htmlspecialchars($open_caja['hora_apertura']) . " hs"; ?>
        </p>
      </div>
      <button type="button" class="nk-btn nk-btn--sm" data-caja-close aria-label="Cerrar ventana">
        <span class="material-symbols-outlined text-[17px]">close</span>
      </button>
    </div>

    <form id="closeForm" novalidate>
      <input type="hidden" id="closeExpectedAmount" value="<?php echo (float) ($open_caja['monto_esperado'] ?? 0); ?>">
      <div class="caja-modal-body">
        <dl class="sum" id="clSum">
          <?php if ($open_caja): ?>
            <dt>Monto de apertura</dt>
            <dd><?php echo $money($open_caja['monto_apertura']); ?></dd>
            <dt>Ingresos</dt>
            <dd class="mv-in"><?php echo ($open_caja['ingresos'] > 0 ? '+' : '') . $money($open_caja['ingresos']); ?></dd>
            <dt>Egresos</dt>
            <dd class="mv-out"><?php echo ($open_caja['egresos'] > 0 ? '−' : '') . $money($open_caja['egresos']); ?></dd>
            <span class="rule"></span>
            <dt class="is-big">Efectivo esperado</dt>
            <dd class="is-big"><?php echo $money($open_caja['monto_esperado']); ?></dd>
          <?php endif; ?>
        </dl>

        <div>
          <label class="nk-label" for="clAmount">Monto de cierre (efectivo contado)</label>
          <div class="nk-money">
            <span aria-hidden="true">$</span>
            <input class="nk-field nk-field--num" id="clAmount" type="number" min="0" step="0.01" inputmode="decimal"
              placeholder="0,00" data-autofocus required>
          </div>
          <p class="nk-error" id="clAmountErr" hidden></p>
        </div>

        <p class="nk-hint" id="clDiffMsg" role="status" style="margin:0; font-weight:600;"></p>

        <div>
          <label class="nk-label" for="clObs">Observación</label>
          <textarea class="nk-field" id="clObs" maxlength="255"
            placeholder="Opcional si cuadra con exactitud. Obligatoria si hay diferencia de efectivo."></textarea>
          <p class="nk-error" id="clObsErr" hidden></p>
        </div>
      </div>

      <div class="caja-modal-foot">
        <button type="button" class="nk-btn" data-caja-close>Volver</button>
        <button type="submit" class="nk-btn nk-btn--primary" id="btnConfirmClose">Confirmar cierre</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Detalle de una caja -->
<div class="caja-modal-backdrop" id="detailModal">
  <div class="caja-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="dtTitle">
    <div class="caja-modal-head">
      <div>
        <h3 id="dtTitle">Detalle de caja</h3>
        <p id="dtSub"></p>
      </div>
      <button type="button" class="nk-btn nk-btn--sm" data-caja-close aria-label="Cerrar ventana">
        <span class="material-symbols-outlined text-[17px]">close</span>
      </button>
    </div>
    <div class="caja-modal-body">
      <dl class="sum" id="dtSum"></dl>
      <p class="nk-cell-meta" id="dtObs" style="margin:0;"></p>
      <div class="nk-table-wrap" style="border:1px solid var(--nk-line, #d3cebf); border-radius:2px;">
        <table class="nk-table">
          <thead>
            <tr>
              <th scope="col" style="width:80px;">Hora</th>
              <th scope="col">Concepto</th>
              <th scope="col" class="is-r" style="width:110px;">Ingreso</th>
              <th scope="col" class="is-r" style="width:110px;">Egreso</th>
            </tr>
          </thead>
          <tbody id="dtTbody"></tbody>
        </table>
      </div>
    </div>
    <div class="caja-modal-foot">
      <button type="button" class="nk-btn" data-action="print-detail">
        <span class="material-symbols-outlined text-[16px]">print</span> Imprimir
      </button>
      <button type="button" class="nk-btn nk-btn--primary" data-caja-close>Listo</button>
    </div>
  </div>
</div>

<script src="<?php echo BASE_URL; ?>/modules/caja/caja.js?v=<?php echo time(); ?>"></script>

<?php Layout::renderFooter(); ?>