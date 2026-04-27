<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}
require_once __DIR__ . '/../../classes/Layout.php';

$id_reparacion = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id_reparacion) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if (isset($_GET['success']) && $_GET['success'] === 'created') {
    $success = 'Orden de reparación creada con éxito.';
}

// PROCESAR CAMBIO DE ESTADO O PRESUPUESTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $nuevo_estado = $conn->real_escape_string($_POST['estado']);
    $nuevo_presupuesto = !empty($_POST['presupuesto']) ? (float)$_POST['presupuesto'] : "NULL";
    $nota = $conn->real_escape_string($_POST['nota_historial']);
    $id_usuario = $_SESSION['user_id'];
    
    // get old state
    $res_old = $conn->query("SELECT estado FROM reparacion WHERE id_reparacion = $id_reparacion");
    $old_state = $res_old->fetch_assoc()['estado'];
    
    $sql_update = "UPDATE reparacion SET estado = '$nuevo_estado', presupuesto = $nuevo_presupuesto";
    if ($nuevo_estado === 'Entregado' && $old_state !== 'Entregado') {
        $sql_update .= ", fecha_entrega = CURRENT_TIMESTAMP";
    }
    $sql_update .= " WHERE id_reparacion = $id_reparacion";
    
    if ($conn->query($sql_update) === TRUE) {
        if ($old_state !== $nuevo_estado || !empty($nota)) {
            $conn->query("INSERT INTO reparacion_historial (id_reparacion, estado_anterior, estado_nuevo, nota, id_usuario) 
                            VALUES ($id_reparacion, '$old_state', '$nuevo_estado', '$nota', $id_usuario)");
        }
        $success = 'Orden actualizada correctamente.';
    } else {
        $error = 'Error al actualizar: ' . $conn->error;
    }
}

// PROCESAR ADD REPUESTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_repuesto') {
    $id_producto = (int)$_POST['id_producto'];
    $cantidad = 1; // Simplificado a 1 un. a la vez
    
    // Check inventario
    $res_inv = $conn->query("SELECT cantidad FROM inventario WHERE id_producto = $id_producto");
    $row_inv = $res_inv->fetch_assoc();
    if ($row_inv && $row_inv['cantidad'] >= $cantidad) {
        $res_prod = $conn->query("SELECT precio FROM producto WHERE id_producto = $id_producto");
        $precio = $res_prod->fetch_assoc()['precio'];
        
        $conn->begin_transaction();
        try {
            // Add to reparacion_repuesto
            $conn->query("INSERT INTO reparacion_repuesto (id_reparacion, id_producto, cantidad, precio_unitario) VALUES ($id_reparacion, $id_producto, $cantidad, $precio)");
            
            // Deduct from inventario
            $conn->query("UPDATE inventario SET cantidad = cantidad - $cantidad WHERE id_producto = $id_producto");
            
            // Update reparacion costo_total
            $conn->query("UPDATE reparacion SET costo_total = costo_total + $precio WHERE id_reparacion = $id_reparacion");
            
            $conn->commit();
            $success = 'Repuesto asignado y descontado del inventario.';
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Error al asignar repuesto: ' . $e->getMessage();
        }
    } else {
        $error = 'No hay stock suficiente de ese repuesto.';
    }
}

// CARGAR DATOS
$sql_rep = "SELECT * FROM reparacion WHERE id_reparacion = $id_reparacion";
$res_rep = $conn->query($sql_rep);
if ($res_rep->num_rows === 0) {
    header("Location: index.php");
    exit();
}
$repair = $res_rep->fetch_assoc();

// Repuestos asignados
$repuestos = [];
$res_repuestos = $conn->query("SELECT rr.*, p.nombre FROM reparacion_repuesto rr JOIN producto p ON rr.id_producto = p.id_producto WHERE rr.id_reparacion = $id_reparacion");
while($row = $res_repuestos->fetch_assoc()) $repuestos[] = $row;

// Historial
$historial = [];
$res_historial = $conn->query("SELECT h.*, u.nombre_usuario FROM reparacion_historial h LEFT JOIN usuario u ON h.id_usuario = u.id_usuario WHERE id_reparacion = $id_reparacion ORDER BY fecha_cambio DESC");
while($row = $res_historial->fetch_assoc()) $historial[] = $row;

// Sin imágenes

// Obtener lista de productos para el select (solo activos y con stock)
$productos_opt = [];
$res_prod_all = $conn->query("SELECT p.id_producto, p.nombre, p.precio, i.cantidad FROM producto p JOIN inventario i ON p.id_producto = i.id_producto WHERE p.is_active = 1 AND i.cantidad > 0 ORDER BY p.nombre ASC");
while($row = $res_prod_all->fetch_assoc()) $productos_opt[] = $row;

Layout::renderHead('Detalle de Orden - Nokia 1100');

if ($_SESSION['role'] === 'admin') {
    Layout::renderAdminSidebar('taller');
} else {
    Layout::renderEmployeeSidebar('taller');
}
?>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="max-w-6xl mx-auto">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-4">
                <a href="index.php" class="w-10 h-10 rounded-full bg-surface border border-border flex items-center justify-center text-text-muted hover:text-text-main transition-colors">
                    <span class="material-symbols-outlined">arrow_back</span>
                </a>
                <div>
                    <h2 class="text-3xl font-display font-medium text-text-main">Orden #<?php echo htmlspecialchars($repair['codigo_orden']); ?></h2>
                    <p class="text-text-muted mt-1 text-sm">Registrada el <?php echo date('d/m/Y H:i', strtotime($repair['fecha_ingreso'])); ?></p>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <a href="print_receipt.php?id=<?php echo $id_reparacion; ?>" target="_blank" class="bg-surface hover:bg-surface-hover border border-border px-4 py-2 rounded-lg font-medium text-sm transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">print</span> Comprobante
                </a>
            </div>
        </div>

        <?php if($success): ?>
            <div class="bg-green-500/10 border border-green-500/20 text-green-500 p-4 rounded-xl mb-6 text-sm flex gap-3 items-center">
                <span class="material-symbols-outlined">check_circle</span> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6 text-sm flex gap-3 items-center">
                <span class="material-symbols-outlined">error</span> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- COLS LEFT (INFO) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- CLIENT & DEVICE INFO -->
                <div class="glass-card rounded-2xl p-6">
                    <h3 class="text-lg font-display font-medium text-primary mb-4 border-b border-border/50 pb-2">Información General</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs text-text-muted uppercase tracking-wider font-semibold mb-1">Cliente</p>
                            <p class="font-medium text-text-main"><?php echo htmlspecialchars($repair['cliente_nombre']); ?></p>
                            <p class="text-sm text-text-muted"><?php echo htmlspecialchars($repair['cliente_telefono']); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-text-muted uppercase tracking-wider font-semibold mb-1">Equipo</p>
                            <p class="font-medium text-text-main"><?php echo htmlspecialchars($repair['equipo_marca'] . ' ' . $repair['equipo_modelo']); ?></p>
                            <p class="text-sm text-text-muted">IMEI/Serie: <?php echo htmlspecialchars($repair['equipo_imei']); ?></p>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <p class="text-xs text-text-muted uppercase tracking-wider font-semibold mb-1">Falla Declarada</p>
                        <div class="bg-surface border border-border p-3 rounded-xl text-sm whitespace-pre-wrap"><?php echo htmlspecialchars($repair['falla_declarada']); ?></div>
                    </div>
                    
                    <?php if($repair['observaciones']): ?>
                    <div class="mt-4">
                        <p class="text-xs text-text-muted uppercase tracking-wider font-semibold mb-1">Observaciones Físicas</p>
                        <div class="bg-surface border border-border p-3 rounded-xl text-sm whitespace-pre-wrap"><?php echo htmlspecialchars($repair['observaciones']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- SIN IMAGENES -->

                <!-- REPUESTOS -->
                <div class="glass-card rounded-2xl p-6">
                    <div class="flex justify-between items-center border-b border-border/50 pb-2 mb-4">
                        <h3 class="text-lg font-display font-medium text-text-main flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-xl">build</span> Repuestos Utilizados / Cotizados
                        </h3>
                    </div>
                    
                    <form method="POST" action="" class="flex gap-2 mb-6">
                        <input type="hidden" name="action" value="add_repuesto">
                        <select name="id_producto" required class="flex-1 bg-surface border border-border px-3 py-2 rounded-lg text-sm text-text-main focus:outline-none focus:border-primary">
                            <option value="">Seleccionar repuesto del inventario...</option>
                            <?php foreach($productos_opt as $po): ?>
                                <option value="<?php echo $po['id_producto']; ?>"><?php echo htmlspecialchars($po['nombre']); ?> - $<?php echo number_format($po['precio'],2); ?> (Stock: <?php echo $po['cantidad']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="bg-surface hover:bg-surface-hover border border-border px-4 py-2 rounded-lg text-sm font-medium transition-colors text-text-main whitespace-nowrap">Asignar Pieza</button>
                    </form>
                    
                    <div class="table-container rounded-xl border border-border overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-surface/50 border-b border-border text-xs uppercase text-text-muted">
                                <tr>
                                    <th class="p-3">Repuesto</th>
                                    <th class="p-3">Cant.</th>
                                    <th class="p-3 text-right">Precio</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/50">
                                <?php if(count($repuestos)>0): ?>
                                    <?php foreach($repuestos as $rep): ?>
                                    <tr>
                                        <td class="p-3"><?php echo htmlspecialchars($rep['nombre']); ?></td>
                                        <td class="p-3"><?php echo $rep['cantidad']; ?></td>
                                        <td class="p-3 text-right">$<?php echo number_format($rep['precio_unitario'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="p-4 text-center text-text-muted text-xs">Aún no se han asignado repuestos a esta orden.</td></tr>
                                <?php endif; ?>
                            </tbody>
                            <?php if($repair['costo_total'] > 0): ?>
                            <tfoot class="bg-surface/30">
                                <tr>
                                    <td colspan="2" class="p-3 text-right font-medium text-text-muted">Subtotal Repuestos:</td>
                                    <td class="p-3 text-right font-medium text-text-main">$<?php echo number_format($repair['costo_total'], 2); ?></td>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                    <p class="text-xs text-text-muted mt-2"><span class="material-symbols-outlined text-[14px] align-middle">info</span> Los repuestos asignados se descuentan automáticamente del stock general de inventario.</p>
                </div>
            </div>

            <!-- COLS RIGHT (STATUS & TIMELINE) -->
            <div class="space-y-6">
                
                <!-- CHANGE STATUS & BUDGET -->
                <form method="POST" action="" class="glass-card rounded-2xl p-6 border-l-4 border-l-primary">
                    <input type="hidden" name="action" value="update_status">
                    <h3 class="text-lg font-display font-medium text-text-main mb-4 border-b border-border/50 pb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-xl">update</span> Actualizar Orden
                    </h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-text-muted mb-1">Estado de Reparación</label>
                            <select name="estado" class="w-full bg-surface border border-border px-3 py-2.5 rounded-lg text-sm text-text-main focus:outline-none focus:border-primary">
                                <?php 
                                $estados = ['Recibido', 'En diagnóstico', 'En reparación', 'Listo', 'Entregado', 'Cancelado'];
                                foreach ($estados as $est) {
                                    $sel = ($repair['estado'] === $est) ? 'selected' : '';
                                    echo "<option value='$est' $sel>$est</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-text-muted mb-1">Presupuesto / Precio Final ($)</label>
                            <input type="number" step="0.01" name="presupuesto" value="<?php echo $repair['presupuesto']; ?>" class="w-full bg-surface border border-border px-3 py-2.5 rounded-lg text-sm text-text-main focus:outline-none focus:border-primary">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-text-muted mb-1">Nota Interna (Para el historial)</label>
                            <textarea name="nota_historial" rows="2" placeholder="Opcional..." class="w-full bg-surface border border-border px-3 py-2 rounded-lg text-sm text-text-main focus:outline-none focus:border-primary"></textarea>
                        </div>
                        
                        <button type="submit" class="w-full bg-primary text-background rounded-lg py-2.5 text-sm font-medium hover:bg-primary-hover transition-colors mt-2">Guardar Cambios</button>
                    </div>
                </form>
                
                <!-- TRACKING / TIMELINE -->
                <div class="glass-card rounded-2xl p-6">
                    <h3 class="text-lg font-display font-medium text-text-main mb-4 border-b border-border/50 pb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-xl">history_toggle_off</span> Historial
                    </h3>
                    
                    <div class="relative pl-6 border-l-2 border-border/40 space-y-6 pt-2 pb-2">
                        <?php foreach($historial as $idx => $h): 
                            $isFirst = ($idx === 0);
                        ?>
                        <div class="relative group">
                            <!-- Icon -->
                            <div class="absolute -left-[31px] top-1.5 w-3.5 h-3.5 rounded-full border-2 transition-colors duration-300 <?php echo $isFirst ? 'border-primary bg-background shadow-[0_0_10px_rgba(33,184,189,0.5)]' : 'border-text-muted/50 bg-background group-hover:border-primary/50'; ?>"></div>
                            
                            <!-- Content -->
                            <div class="bg-surface/40 border border-border/60 p-3.5 rounded-xl hover:bg-surface transition-colors duration-300 hover:border-border">
                                <div class="flex justify-between items-start mb-2 gap-2">
                                    <span class="text-[11px] font-bold <?php echo $isFirst ? 'text-primary' : 'text-text-main'; ?> uppercase tracking-widest leading-none mt-1">
                                        <?php echo htmlspecialchars($h['estado_nuevo']); ?>
                                    </span>
                                    <span class="text-[9px] text-text-muted font-medium bg-background px-2 py-1 rounded-md border border-border/50 shrink-0 uppercase tracking-wider">
                                        <?php echo date('d M, H:i', strtotime($h['fecha_cambio'])); ?>
                                    </span>
                                </div>
                                <?php if($h['nota']): ?>
                                    <p class="text-[11px] text-text-muted mb-3 leading-relaxed bg-background/50 p-2 rounded-lg border border-border/30 border-l-2 border-l-primary/50">
                                        <?php echo nl2br(htmlspecialchars($h['nota'])); ?>
                                    </p>
                                <?php endif; ?>
                                <div class="flex items-center gap-1.5 mt-auto pt-2 border-t border-border/30">
                                    <div class="w-4 h-4 rounded-full bg-primary/10 border border-primary/20 text-primary flex items-center justify-center text-[8px] font-bold">
                                        <span class="material-symbols-outlined text-[10px]">person</span>
                                    </div>
                                    <p class="text-[10px] text-text-muted font-medium"><?php echo htmlspecialchars($h['nombre_usuario'] ?? 'Sistema'); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>

    </div>
</main>

<?php Layout::renderFooter(); ?>
