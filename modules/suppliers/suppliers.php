<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}
require_once __DIR__ . '/../../classes/Layout.php';

// Handle Add Supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $nombre = $_POST['nombre'];
    $domicilio = $_POST['domicilio'];
    $telefono = $_POST['telefono'];
    $atencion = $_POST['atencion'];
    $email = $_POST['email'];

    $stmt = $conn->prepare("INSERT INTO proveedor (nombre, domicilio, telefono, atencion, email) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $nombre, $domicilio, $telefono, $atencion, $email);
    if ($stmt->execute()) {
        header("Location: " . BASE_URL . "/modules/suppliers/suppliers.php?msg=added");
    } else {
        header("Location: " . BASE_URL . "/modules/suppliers/suppliers.php?error=failed");
    }
    exit();
}

// Handle Delete Supplier
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Check for dependencies (purchases)
    $check = $conn->query("SELECT COUNT(*) as count FROM compra WHERE id_proveedor = $id");
    $row = $check->fetch_assoc();
    if ($row['count'] > 0) {
        header("Location: " . BASE_URL . "/modules/suppliers/suppliers.php?error=has_purchases");
    } else {
        $conn->query("DELETE FROM proveedor WHERE id_proveedor = $id");
        header("Location: " . BASE_URL . "/modules/suppliers/suppliers.php?msg=deleted");
    }
    exit();
}

$result = $conn->query("SELECT * FROM proveedor ORDER BY nombre ASC");

Layout::renderHead('Proveedores - Nokia 1100');
Layout::renderAdminSidebar('proveedores');
?>
<style>
    .btn-delete { color: #ef4444; font-weight: 600; font-size: 0.85rem; }
    .btn-delete:hover { text-decoration: underline; }
    .btn-history { color: var(--primary-color); font-weight: 600; cursor: pointer; font-size: 0.85rem; }
    .btn-history:hover { text-decoration: underline; }
    .history-row { background: var(--surface-hover); }
    .history-table { margin: 1rem 0; font-size: 0.85rem; width: 100%; border-collapse: collapse; }
    .history-table th { background: rgba(255, 255, 255, 0.02); padding: 0.75rem; text-transform: uppercase; font-size: 0.7rem; color: var(--text-muted); }
    .history-table td { padding: 0.75rem; border-bottom: 1px solid var(--border); }
    input { width: 100%; padding: 0.75rem 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 0.9rem; }
</style>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8">
        <div class="dashboard-header flex justify-between items-center mb-8" style="flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2>Proveedores</h2>
                <p>Gestión del directorio y compras</p>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <input type="text" id="search-input" placeholder="Buscar proveedor..." style="width: 250px; padding: 0.5rem 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 0.9rem;">
                <a href="<?php echo BASE_URL; ?>/modules/admin/dashboard.php" class="btn-back">Volver</a>
            </div>
        </div>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'has_purchases'): ?>
            <div class="alert alert-error">No se puede eliminar este proveedor porque tiene compras registradas.</div>
        <?php endif; ?>

        <!-- Add Supplier Form -->
        <form method="POST" action="" style="background: var(--surface-hover); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid var(--border);">
            <h3 style="margin-bottom: 1rem; font-size: 1.1rem; font-family: 'Outfit', sans-serif;">Agregar Proveedor</h3>
            <input type="hidden" name="action" value="add">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Nombre Empresa</label>
                    <input type="text" name="nombre" required>
                </div>
                <div class="form-group">
                    <label>Domicilio</label>
                    <input type="text" name="domicilio" placeholder="Ej: Av. Central 123">
                </div>
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono">
                </div>
                <div class="form-group">
                    <label>Persona de Contacto</label>
                    <input type="text" name="atencion" placeholder="Ej: Gabriel Martínez">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Email</label>
                    <input type="email" name="email">
                </div>
            </div>
            <button type="submit" style="width: auto; padding: 0.75rem 2rem; margin-top: 1rem;">Guardar Proveedor</button>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Domicilio</th>
                        <th>Atención</th>
                        <th>Teléfono</th>
                        <th>Compras</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <?php 
                                $id_prov = $row['id_proveedor'];
                                $count_query = $conn->query("SELECT COUNT(*) as total FROM compra WHERE id_proveedor = $id_prov");
                                $count_row = $count_query->fetch_assoc();
                                $total_compras = $count_row['total'];
                            ?>
                            <tr>
                                <td style="font-weight: 500; font-family: 'Outfit', sans-serif;"><?php echo htmlspecialchars($row['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($row['domicilio']); ?></td>
                                <td><?php echo htmlspecialchars($row['atencion']); ?></td>
                                <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                                <td>
                                    <span class="btn-history" onclick="toggleHistory(<?php echo $row['id_proveedor']; ?>)">
                                        <?php echo $total_compras; ?> compra(s) ▼
                                    </span>
                                </td>
                                <td>
                                    <a href="suppliers.php?delete=<?php echo $row['id_proveedor']; ?>" class="btn-delete" data-confirm="¿Seguro que deseas eliminar este proveedor?" data-confirm-title="Eliminar Proveedor">Eliminar</a>
                                </td>
                            </tr>
                            <tr id="history-<?php echo $row['id_proveedor']; ?>" style="display: none;" class="history-row">
                                <td colspan="6">
                                    <div style="padding: 1rem;">
                                        <h4 style="margin-bottom: 1rem; font-family: 'Outfit', sans-serif;">Historial de Compras</h4>
                                        <?php
                                            $history_query = $conn->query("SELECT c.*, 
                                                (SELECT COUNT(*) FROM detalle_compra WHERE id_compra = c.id_compra) as items
                                                FROM compra c 
                                                WHERE c.id_proveedor = $id_prov 
                                                ORDER BY c.fecha DESC");
                                            
                                            if ($history_query && $history_query->num_rows > 0):
                                        ?>
                                            <table class="history-table">
                                                <thead>
                                                    <tr>
                                                        <th>Fecha</th>
                                                        <th>Descripción</th>
                                                        <th>Items</th>
                                                        <th>Total</th>
                                                        <th>IVA</th>
                                                        <th>Autorizado Por</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while($h = $history_query->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?php echo date('d/m/Y', strtotime($h['fecha'])); ?></td>
                                                            <td><?php echo htmlspecialchars($h['descripcion']); ?></td>
                                                            <td><?php echo $h['items']; ?></td>
                                                            <td style="font-weight: 500;">$<?php echo number_format($h['total'], 2); ?></td>
                                                            <td><?php echo $h['iva']; ?>%</td>
                                                            <td><?php echo htmlspecialchars($h['autorizado_por']); ?></td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        <?php else: ?>
                                            <p style="color: var(--text-muted); font-size: 0.85rem;">No hay compras registradas para este proveedor.</p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">No hay proveedores registrados</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
    function toggleHistory(id) {
        const row = document.getElementById('history-' + id);
        if (row.style.display === 'none') {
            row.style.display = 'table-row';
        } else {
            row.style.display = 'none';
        }
    }
</script>
<?php Layout::renderFooter(); ?>
