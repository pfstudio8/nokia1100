<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}
require_once __DIR__ . '/../../classes/Layout.php';

$estado_filter = isset($_GET['estado']) ? $_GET['estado'] : '';
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$where_parts = [];
if ($estado_filter) {
    $where_parts[] = "estado = '" . $conn->real_escape_string($estado_filter) . "'";
}
if ($search) {
    $where_parts[] = "(codigo_orden LIKE '%$search%' OR cliente_nombre LIKE '%$search%' OR equipo_marca LIKE '%$search%' OR equipo_modelo LIKE '%$search%')";
}

$where_clause = count($where_parts) > 0 ? " WHERE " . implode(" AND ", $where_parts) : "";

$sql = "SELECT id_reparacion, codigo_orden, cliente_nombre, equipo_marca, equipo_modelo, estado, fecha_ingreso, presupuesto 
        FROM reparacion 
        $where_clause 
        ORDER BY fecha_ingreso DESC";

$result = $conn->query($sql);
$repairs = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $repairs[] = $row;
    }
}

Layout::renderHead('Taller - Nokia 1100');

if ($_SESSION['role'] === 'admin') {
    Layout::renderAdminSidebar('taller');
} else {
    Layout::renderEmployeeSidebar('taller');
}
?>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8">
        <div class="dashboard-header border-b border-border/50 pb-6 mb-6">
            <div>
                <h2 class="text-3xl font-display font-medium text-text-main">Taller de Reparaciones</h2>
                <p class="text-text-muted mt-1 text-sm">Gestión de órdenes, presupuestos y control de equipos</p>
            </div>
            <div class="flex items-center gap-4">
                <a href="add.php" class="bg-primary text-background hover:bg-primary-hover px-4 py-2 rounded-lg font-medium text-sm transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">add</span> Nueva Orden
                </a>
            </div>
        </div>

        <form method="GET" action="" class="flex gap-3 mb-6 items-center flex-wrap">
            <div class="relative flex-1 max-w-md">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-muted text-[20px]">search</span>
                <input type="text" name="search" placeholder="Buscar por código, cliente, modelo..." value="<?php echo htmlspecialchars($search); ?>" class="w-full bg-surface border border-border pl-10 pt-2.5 pb-2.5 pr-4 rounded-xl text-sm text-text-main focus:outline-none focus:border-primary transition-colors">
            </div>
            <select name="estado" class="bg-surface border border-border px-4 py-2.5 rounded-xl text-sm text-text-main focus:outline-none focus:border-primary transition-colors">
                <option value="">Todos los estados</option>
                <option value="Recibido" <?php if($estado_filter==='Recibido') echo 'selected'; ?>>Recibido</option>
                <option value="En diagnóstico" <?php if($estado_filter==='En diagnóstico') echo 'selected'; ?>>En diagnóstico</option>
                <option value="En reparación" <?php if($estado_filter==='En reparación') echo 'selected'; ?>>En reparación</option>
                <option value="Listo" <?php if($estado_filter==='Listo') echo 'selected'; ?>>Listo</option>
                <option value="Entregado" <?php if($estado_filter==='Entregado') echo 'selected'; ?>>Entregado</option>
                <option value="Cancelado" <?php if($estado_filter==='Cancelado') echo 'selected'; ?>>Cancelado</option>
            </select>
            <button type="submit" class="bg-surface hover:bg-surface-hover border border-border px-4 py-2.5 rounded-xl text-sm font-medium transition-colors text-text-main">Filtrar</button>
            <?php if ($search || $estado_filter): ?>
                <a href="index.php" class="text-red-400 hover:text-red-300 text-sm font-medium ml-2 transition-colors">Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="table-container rounded-2xl border border-border overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface/50 border-b border-border text-xs uppercase text-text-muted tracking-wider">
                    <tr>
                        <th class="p-4 font-semibold">Orden</th>
                        <th class="p-4 font-semibold">Cliente</th>
                        <th class="p-4 font-semibold">Equipo</th>
                        <th class="p-4 font-semibold">Estado</th>
                        <th class="p-4 font-semibold">Fecha Ingreso</th>
                        <th class="p-4 font-semibold">Presupuesto</th>
                        <th class="p-4 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/50">
                    <?php if (count($repairs) > 0): ?>
                        <?php foreach($repairs as $r): 
                            $statusClass = 'bg-surface border-border text-text-muted';
                            if ($r['estado'] === 'Recibido') $statusClass = 'bg-blue-500/10 text-blue-500 border border-blue-500/20';
                            if ($r['estado'] === 'En diagnóstico') $statusClass = 'bg-yellow-500/10 text-yellow-500 border border-yellow-500/20';
                            if ($r['estado'] === 'En reparación') $statusClass = 'bg-orange-500/10 text-orange-500 border border-orange-500/20';
                            if ($r['estado'] === 'Listo') $statusClass = 'bg-green-500/10 text-green-500 border border-green-500/20';
                            if ($r['estado'] === 'Entregado') $statusClass = 'bg-primary/10 text-primary border border-primary/20';
                            if ($r['estado'] === 'Cancelado') $statusClass = 'bg-red-500/10 text-red-500 border border-red-500/20';
                        ?>
                            <tr class="hover:bg-surface-hover/30 transition-colors">
                                <td class="p-4 font-display font-medium text-primary">#<?php echo htmlspecialchars($r['codigo_orden']); ?></td>
                                <td class="p-4 font-medium text-text-main"><?php echo htmlspecialchars($r['cliente_nombre']); ?></td>
                                <td class="p-4 text-text-muted text-sm"><?php echo htmlspecialchars($r['equipo_marca'] . ' ' . $r['equipo_modelo']); ?></td>
                                <td class="p-4">
                                    <span class="inline-block px-2.5 py-1 rounded text-[10px] uppercase font-bold tracking-widest <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($r['estado']); ?>
                                    </span>
                                </td>
                                <td class="p-4 text-text-muted text-sm"><?php echo date('d/m/Y H:i', strtotime($r['fecha_ingreso'])); ?></td>
                                <td class="p-4 text-text-main font-medium">
                                    <?php echo $r['presupuesto'] ? '$' . number_format($r['presupuesto'], 2) : '<span class="text-text-muted text-xs">Pte.</span>'; ?>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="view.php?id=<?php echo $r['id_reparacion']; ?>" class="w-8 h-8 rounded-lg flex items-center justify-center bg-surface border border-border text-text-main hover:bg-primary-hover hover:text-white transition-colors" title="Ver / Gestionar">
                                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                                        </a>
                                        <a href="print_receipt.php?id=<?php echo $r['id_reparacion']; ?>" target="_blank" class="w-8 h-8 rounded-lg flex items-center justify-center bg-surface border border-border text-text-main hover:bg-surface-hover transition-colors" title="Imprimir Comprobante">
                                            <span class="material-symbols-outlined text-[16px]">print</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="p-12 text-center text-text-muted">No se encontraron órdenes de reparación.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php Layout::renderFooter(); ?>
