<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

require_once __DIR__ . '/../../classes/Layout.php';

// Obtener ventas + productos vendidos
$sql = "SELECT 
            v.id_venta, 
            v.fecha, 
            v.total, 
            v.metodo_de_pago,
            COALESCE(dv.nombre_producto, p.nombre) AS producto,
            dv.cantidad
        FROM venta v
        INNER JOIN detalle_venta dv ON v.id_venta = dv.id_venta
        LEFT JOIN producto p ON dv.id_producto = p.id_producto
        ORDER BY v.fecha DESC";

$result = $conn->query($sql);

Layout::renderHead('Historial de Ventas - Nokia 1100');
Layout::renderAdminSidebar('ventas');
?>
<style>
    /* Premium GSAP Download Button */
    .btn-export-premium {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 150px;
        height: 42px;
        background: var(--text-main);
        color: var(--text-inverse) !important;
        font-weight: 600;
        border-radius: 24px;
        border: none;
        cursor: pointer;
        overflow: hidden;
        transition: all 0.3s ease;
        padding: 0 1.5rem;
    }
    .btn-export-premium:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(255, 255, 255, 0.08);
    }
    .btn-export-premium.loading {
        background: var(--surface) !important;
        border: 1px solid var(--border);
        color: var(--text-main) !important;
        cursor: not-allowed;
    }
    .btn-export-premium.complete {
        background: var(--success) !important;
        color: var(--background) !important;
        box-shadow: 0 0 20px rgba(34, 197, 94, 0.3);
    }
    .btn-export-premium .button-text {
        transition: transform 0.4s ease, opacity 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
    }
    .btn-export-premium.loading .button-text {
        transform: translateY(-30px);
        opacity: 0;
    }
    .btn-export-premium .progress-percent {
        position: absolute;
        left: 1.5rem;
        font-family: monospace;
        font-size: 0.85rem;
        opacity: 0;
        transform: translateY(30px);
        transition: transform 0.4s ease, opacity 0.3s ease;
        color: var(--primary-color);
        font-weight: bold;
    }
    .btn-export-premium.loading .progress-percent {
        opacity: 1;
        transform: translateY(0);
    }
    .btn-export-premium .icon-container {
        position: absolute;
        right: 1.25rem;
        width: 24px;
        height: 24px;
        transition: all 0.4s ease;
    }
    .btn-export-premium.loading .icon-container {
        right: 50%;
        transform: translateX(50%);
    }

    /* SVG and morph path styles requested by the user */
    .button svg {
        display: block;
        fill: none;
        stroke: currentColor;
        stroke-width: var(--sw, 3px); 
        stroke-linecap: round; 
        stroke-linejoin: round;
        width: 24px;
        height: 24px;
    }
    .button circle { 
        width: 24px; 
        height: 24px;
        transform: rotate(-90deg);
        transform-origin: center;
        stroke-dasharray: 62.8;
        stroke-dashoffset: 62.8;
        stroke: var(--primary-color);
        transition: stroke-dashoffset 0.1s linear;
    }
    .button .icon {
        --sw: 2px;
        width: 24px;
        height: 24px;
    }
</style>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8">
        <div class="dashboard-header flex justify-between items-center mb-8" style="flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2>Registro de Ventas</h2>
                <p>Historial completo de productos vendidos</p>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center; justify-content: flex-end; flex: 1;">
                <input type="text" id="search-input" placeholder="Buscar venta..." style="width: 250px; padding: 0.5rem 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 0.9rem;">
                <a href="<?php echo BASE_URL; ?>/modules/admin/dashboard.php" class="btn-back">Volver</a>
                <button type="button" id="exportBtn" class="btn-export-premium button relative">
                    <span class="button-text">
                        <span class="material-symbols-outlined text-sm">download</span> Exportar
                    </span>
                    <span class="progress-percent">0%</span>
                    <div class="icon-container">
                        <div class="icon">
                            <svg viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" />
                                <path class="arrow-path" d="M12 4v12 M8 12l4 4 4-4" />
                                <path class="line-path" d="M2 16 Q12 16 22 16" />
                            </svg>
                        </div>
                    </div>
                </button>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID Venta</th>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Método</th>
                        <th style="text-align: right;">Total</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="text-primary font-medium text-sm">#TX-<?php echo $row['id_venta']; ?></td>
                                <td class="text-text-muted text-sm"><?php echo date('d/m/Y H:i', strtotime($row['fecha'])); ?></td>
                                <td class="font-medium font-display"><?php echo htmlspecialchars($row['producto']); ?></td>
                                <td><?php echo $row['cantidad']; ?> u.</td>
                                <td>
                                    <span class="px-2 py-1 rounded border border-border text-[10px] font-medium text-text-muted uppercase tracking-wider">
                                        <?php echo htmlspecialchars($row['metodo_de_pago']); ?>
                                    </span>
                                </td>
                                <td style="text-align: right; font-weight: 600;">$<?php echo number_format($row['total'], 2); ?></td>
                                <td style="text-align: right;">
                                    <a href="invoice.php?id=<?php echo $row['id_venta']; ?>" target="_blank" class="px-3 py-1 bg-surface border border-border rounded text-sm text-text-main hover:bg-border transition inline-flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[1rem]">print</span> Factura
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                No hay ventas registradas en el sistema.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const button = document.getElementById('exportBtn');
    if (!button) return;

    // Track active tweens for cancellation
    let activeTweens = [];
    let isAnimating = false;

    function resetButton() {
        // Kill all active GSAP tweens
        activeTweens.forEach(tw => tw.kill());
        activeTweens = [];
        isAnimating = false;

        const countElem = button.querySelector('.progress-percent');
        const arrowPath = button.querySelector('.arrow-path');
        const linePath = button.querySelector('.line-path');
        const circle = button.querySelector('circle');

        button.classList.remove('loading', 'complete');
        button.querySelector('.button-text').innerHTML = '<span class="material-symbols-outlined text-sm">download</span> Exportar';
        countElem.innerHTML = '0%';
        circle.style.strokeDashoffset = 62.8;
        arrowPath.style.strokeDasharray = '';
        arrowPath.style.transform = '';
        linePath.setAttribute('d', 'M2 16 Q12 16 22 16');
    }

    button.addEventListener('click', (e) => {
        e.preventDefault();

        // Si está animando, cancelar
        if (isAnimating) {
            resetButton();
            if (typeof window.showToast === 'function') {
                window.showToast('Exportación cancelada', 'info');
            }
            return;
        }

        // Si completó, ignorar hasta que se reinicie solo
        if (button.classList.contains('complete')) return;

        isAnimating = true;
        button.classList.add('loading');
        
        const countElem = button.querySelector('.progress-percent');
        const arrowPath = button.querySelector('.arrow-path');
        const linePath = button.querySelector('.line-path');
        const circle = button.querySelector('circle');
        
        // Proxy reactivo para las propiedades de la animación SVG
        const svgPath = new Proxy({
            f: 0,
            l: 0,
            s: 1,
            y: 22
        }, {
            set(target, key, value) {
                target[key] = value;
                
                if (key === 'y') {
                    linePath.setAttribute('d', `M2 16 Q12 ${value} 22 16`);
                }
                
                if (key === 'f' || key === 'l') {
                    arrowPath.style.strokeDasharray = `${value} 100`;
                }
                
                if (key === 's') {
                    arrowPath.style.transform = `translateY(${svgPath.y - 16}px) scale(${value / 2})`;
                    arrowPath.style.transformOrigin = 'center';
                }
                return true;
            }
        });
        
        // Muevo un poquito la flecha del botón hacia abajo para iniciar la animación
        const tw1 = gsap.to(svgPath, {
            f: 2,
            l: 38,
            duration: 0.2,
            delay: 0.05
        });
        
        // Le meto un rebote elástico a la flecha como si cayera físicamente en el botón
        const tw2 = gsap.to(svgPath, {
            s: 2,
            y: 16,
            duration: 0.5,
            delay: 0.05,
            ease: "elastic.out(1, 0.4)"
        });
        
        // Hago que suba el porcentaje visual de 0% a 100% mientras se llena el círculo de progreso
        const count = { number: 0 };
        const tw3 = gsap.to(count, {
            number: 100,
            duration: 1.5,
            delay: 0.3,
            onUpdate() {
                const currentVal = Math.round(count.number);
                countElem.innerHTML = currentVal + "%";
                
                const offset = 62.8 - (62.8 * currentVal / 100);
                circle.style.strokeDashoffset = offset;
            },
            onComplete() {
                isAnimating = false;
                activeTweens = [];
                button.classList.remove('loading');
                button.classList.add('complete');
                button.querySelector('.button-text').innerHTML = '<span class="material-symbols-outlined text-[1rem]">check</span> ¡Exportado!';
                
                // Desencadenar la descarga física del CSV
                const iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                iframe.src = 'export_sales.php';
                document.body.appendChild(iframe);
                setTimeout(() => iframe.remove(), 1000);
                
                // Restaurar el botón a su estado inicial después de 2.5s
                setTimeout(() => {
                    resetButton();
                }, 2500);
            }
        });

        activeTweens = [tw1, tw2, tw3];
    });
});
</script>
<?php Layout::renderFooter(); ?>
