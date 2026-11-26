<?php
// modules/inventory/Views/labels.php

$products = $products ?? [];
$preselectedIds = $preselectedIds ?? [];
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';

Layout::renderHead('Etiquetas de Código de Barras - Nokia 1100');
if ($isAdmin) {
    Layout::renderAdminSidebar('etiquetas');
} else {
    Layout::renderEmployeeSidebar('etiquetas');
}
?>
<style>
@page {
    size: A4;
    margin: 8mm;
}
.label-sheet {
    display: grid;
    grid-template-columns: repeat(3, 50mm);
    gap: 4mm;
    justify-content: start;
}
.nk-label-card {
    width: 50mm;
    height: 30mm;
    border: 1px dashed #b5af9c;
    background: #ffffff;
    color: #15171a;
    padding: 2mm 2.5mm;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
    page-break-inside: avoid;
}
.nk-label-name {
    font-size: 8.5pt;
    font-weight: 700;
    line-height: 1.1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.nk-label-sub {
    font-size: 7pt;
    color: #5c6165;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.nk-label-price {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 9pt;
    font-weight: 800;
    color: #15171a;
}
.nk-label-svg {
    width: 100%;
    height: 13mm;
    display: block;
}
@media print {
    body * {
        visibility: hidden !important;
    }
    #labelsPrintArea, #labelsPrintArea * {
        visibility: visible !important;
    }
    #labelsPrintArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        padding: 0 !important;
        margin: 0 !important;
        background: #ffffff !important;
        border: none !important;
    }
    .nk-label-card {
        border: 1px solid #d3cebf;
    }
}
</style>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="max-w-6xl mx-auto space-y-6">
        <div class="glass-card no-print">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-border/50 pb-5 mb-5">
                <div class="flex items-center gap-4">
                    <a href="index.php" class="w-10 h-10 rounded-full bg-surface border border-border flex items-center justify-center text-text-muted hover:text-text-main transition-colors shrink-0">
                        <span class="material-symbols-outlined">arrow_back</span>
                    </a>
                    <div>
                        <h2 class="text-2xl font-display font-medium text-text-main">Impresión de Etiquetas</h2>
                        <p class="text-text-muted text-sm mt-0.5">Seleccioná productos y cantidad de copias (CODE128 · 50×30 mm en A4)</p>
                    </div>
                </div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <form method="POST" action="index.php?action=labels" style="display:inline;">
                        <input type="hidden" name="op" value="generate_codes">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['session_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="px-4 py-2 rounded-lg border border-border bg-surface hover:bg-surface-hover text-xs font-semibold text-text-main transition-colors flex items-center gap-1.5 cursor-pointer">
                            <span class="material-symbols-outlined text-[16px]">auto_fix_high</span> Generar códigos faltantes
                        </button>
                    </form>
                    <button type="button" id="btnPrintLabels" class="px-4 py-2 rounded-lg bg-primary hover:bg-primary-hover text-background text-xs font-bold transition-colors flex items-center gap-1.5 cursor-pointer">
                        <span class="material-symbols-outlined text-[16px]">print</span> Imprimir etiquetas
                    </button>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="mb-4 p-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 text-emerald-500 text-xs font-medium">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="mb-4 p-3 rounded-xl border border-red-500/30 bg-red-500/10 text-red-500 text-xs font-medium">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <div class="mb-4 flex flex-col sm:flex-row gap-3 items-center justify-between">
                <input type="text" id="labelSearch" placeholder="Filtrar productos por nombre, marca, modelo o código..."
                       class="w-full sm:max-w-md bg-surface border border-border px-3.5 py-2 rounded-xl text-sm text-text-main focus:outline-none focus:border-primary">
                <span id="labelsCountInfo" class="text-xs font-mono text-text-muted">0 etiquetas seleccionadas</span>
            </div>

            <div class="overflow-x-auto max-h-72 overflow-y-auto border border-border rounded-xl">
                <table class="w-full text-left text-sm">
                    <thead class="bg-surface/60 border-b border-border text-xs uppercase text-text-muted sticky top-0">
                        <tr>
                            <th class="p-3 w-10">Sel.</th>
                            <th class="p-3">Código</th>
                            <th class="p-3">Producto</th>
                            <th class="p-3 text-right">Precio</th>
                            <th class="p-3 text-center w-28">Copias</th>
                        </tr>
                    </thead>
                    <tbody id="labelsProdBody" class="divide-y divide-border/40">
                        <?php foreach ($products as $p):
                            $pid = (int)$p['id_producto'];
                            $code = trim((string)($p['codigo'] ?? ''));
                            $hasCode = $code !== '';
                            $checked = $hasCode && in_array($pid, $preselectedIds, true);
                            $sub = trim(($p['marca'] ?? '') . ' ' . ($p['modelo'] ?? ''));
                        ?>
                            <tr class="label-prod-row hover:bg-surface/40"
                                data-search="<?php echo htmlspecialchars(strtolower($p['nombre'] . ' ' . $sub . ' ' . $code)); ?>">
                                <td class="p-3">
                                    <input type="checkbox" class="lbl-chk rounded border-border"
                                           data-id="<?php echo $pid; ?>"
                                           data-code="<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>"
                                           data-nombre="<?php echo htmlspecialchars((string)$p['nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                                           data-sub="<?php echo htmlspecialchars($sub, ENT_QUOTES, 'UTF-8'); ?>"
                                           data-precio="<?php echo (float)$p['precio']; ?>"
                                           <?php echo $hasCode ? '' : 'disabled'; ?>
                                           <?php echo $checked ? 'checked' : ''; ?>>
                                </td>
                                <td class="p-3 font-mono text-xs">
                                    <?php if ($hasCode): ?>
                                        <span class="font-bold text-text-main"><?php echo htmlspecialchars($code); ?></span>
                                    <?php else: ?>
                                        <span class="text-amber-500 font-semibold">Sin código — generá códigos</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3">
                                    <span class="font-medium text-text-main"><?php echo htmlspecialchars((string)$p['nombre']); ?></span>
                                    <?php if ($sub !== ''): ?>
                                        <span class="text-xs text-text-muted ml-1">· <?php echo htmlspecialchars($sub); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 text-right font-mono text-xs">$ <?php echo number_format((float)$p['precio'], 2, ',', '.'); ?></td>
                                <td class="p-3 text-center">
                                    <input type="number" min="1" max="100" value="1" class="lbl-qty w-16 bg-surface border border-border rounded-lg px-2 py-1 text-center text-xs font-mono"
                                           <?php echo $hasCode ? '' : 'disabled'; ?>>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="glass-card" id="labelsPrintArea">
            <h3 class="text-sm font-mono font-bold uppercase text-text-muted mb-4 no-print">Vista previa de impresión</h3>
            <div id="labelsPreviewGrid" class="label-sheet"></div>
            <p id="labelsPreviewEmpty" class="text-xs text-text-muted no-print">Marcá uno o más productos con código arriba para previsualizar e imprimir sus etiquetas.</p>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
(function() {
    const searchInput = document.getElementById('labelSearch');
    const previewGrid = document.getElementById('labelsPreviewGrid');
    const previewEmpty = document.getElementById('labelsPreviewEmpty');
    const countInfo = document.getElementById('labelsCountInfo');
    const printBtn = document.getElementById('btnPrintLabels');

    function esc(v) {
        return String(v ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
    }

    function fmtArs(n) {
        return '$ ' + Number(n || 0).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function rebuildPreview() {
        if (!previewGrid) return;
        previewGrid.innerHTML = '';
        let totalLabels = 0;

        document.querySelectorAll('.label-prod-row').forEach(row => {
            const chk = row.querySelector('.lbl-chk');
            const qtyEl = row.querySelector('.lbl-qty');
            if (!chk || !chk.checked || chk.disabled) return;
            const copies = Math.max(1, Math.min(100, parseInt(qtyEl?.value || '1', 10) || 1));
            const code = chk.dataset.code || '';
            const nombre = chk.dataset.nombre || '';
            const sub = chk.dataset.sub || '';
            const precio = parseFloat(chk.dataset.precio || '0');

            for (let i = 0; i < copies; i++) {
                totalLabels++;
                const card = document.createElement('div');
                card.className = 'nk-label-card';
                const svgId = `bc_${chk.dataset.id}_${i}`;
                card.innerHTML = `
                    <div style="display:flex; justify-content:space-between; align-items:baseline; gap:4px;">
                        <div class="nk-label-name">${esc(nombre)}</div>
                        <div class="nk-label-price">${fmtArs(precio)}</div>
                    </div>
                    <div class="nk-label-sub">${esc(sub || 'Nokia 1100')}</div>
                    <svg id="${svgId}" class="nk-label-svg"></svg>
                `;
                previewGrid.appendChild(card);

                if (typeof window.JsBarcode === 'function') {
                    try {
                        window.JsBarcode('#' + svgId, code, {
                            format: 'CODE128',
                            width: 1.6,
                            height: 40,
                            displayValue: true,
                            fontSize: 10,
                            margin: 0
                        });
                    } catch (e) {}
                }
            }
        });

        if (previewEmpty) previewEmpty.style.display = totalLabels > 0 ? 'none' : 'block';
        if (countInfo) countInfo.textContent = `${totalLabels} ${totalLabels === 1 ? 'etiqueta seleccionada' : 'etiquetas seleccionadas'}`;
    }

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const q = searchInput.value.trim().toLowerCase();
            document.querySelectorAll('.label-prod-row').forEach(row => {
                const hay = row.dataset.search || '';
                row.style.display = (!q || hay.includes(q)) ? '' : 'none';
            });
        });
    }

    document.addEventListener('change', (e) => {
        if (e.target && (e.target.classList.contains('lbl-chk') || e.target.classList.contains('lbl-qty'))) {
            rebuildPreview();
        }
    });
    document.addEventListener('input', (e) => {
        if (e.target && e.target.classList.contains('lbl-qty')) {
            rebuildPreview();
        }
    });

    if (printBtn) {
        printBtn.addEventListener('click', () => {
            if (!previewGrid || previewGrid.children.length === 0) {
                if (typeof showToast === 'function') showToast('Seleccioná al menos un producto con código.', 'warning');
                return;
            }
            window.print();
        });
    }

    rebuildPreview();
})();
</script>
<?php Layout::renderFooter(); ?>
