<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin_auth();
require_once '../../config/bd.php';

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
        header("Location: proveedores.php?msg=added");
    } else {
        header("Location: proveedores.php?error=failed");
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
        header("Location: proveedores.php?error=has_purchases");
    } else {
        $conn->query("DELETE FROM proveedor WHERE id_proveedor = $id");
        header("Location: proveedores.php?msg=deleted");
    }
    exit();
}

$result = $conn->query("SELECT * FROM proveedor ORDER BY nombre ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proveedores - Nokia 1100</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container" style="padding-top: 3rem;">
        
        <div style="margin-bottom: 3rem; width: 100%; max-width: 1000px; margin-left: auto; margin-right: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <p style="text-transform: uppercase; letter-spacing: 0.2em; font-size: 0.7rem; color: var(--primary); font-weight: 800; margin-bottom: 0.25rem; opacity: 0.8;">Admin & Logística</p>
                    <h1 style="margin-bottom: 0; font-size: 2.2rem; letter-spacing: -0.02em;">Panel de Proveedores</h1>
                </div>
                <div style="display: flex; gap: 0.75rem;">
                    <a href="../panel_admin.php" class="btn btn-outline" style="padding: 0.6rem 1.2rem; font-size: 0.85rem;">Cerrar</a>
                </div>
            </div>
            <div style="height: 3px; width: 30px; background: var(--primary); margin-top: 1.5rem; border-radius: 2px;"></div>
        </div>

        <div class="container" style="max-width: 1000px; margin: 0 auto; padding: 0;">
            <div class="glass-card" style="padding: 2rem;">

            <?php if (isset($_GET['error']) && $_GET['error'] === 'has_purchases'): ?>
                <div class="alert alert-error">No se puede eliminar este proveedor porque tiene compras registradas.</div>
            <?php endif; ?>

            <!-- Add Supplier Form -->
            <form method="POST" action="" style="background: rgba(30, 41, 59, 0.4); padding: 2rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid var(--border);">
                <h3 style="margin-bottom: 1.5rem; font-size: 1.1rem; color: var(--primary); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 800;">Nuevo Proveedor</h3>
                <input type="hidden" name="action" value="add">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label>Nombre Empresa</label>
                        <input type="text" name="nombre" required placeholder="Ej: Tech Distribuidora">
                    </div>
                    <div class="form-group">
                        <label>Domicilio</label>
                        <input type="text" name="domicilio" placeholder="Ej: Av. Central 123">
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" placeholder="Ej: +54 11 ...">
                    </div>
                    <div class="form-group">
                        <label>Persona de Contacto</label>
                        <input type="text" name="atencion" placeholder="Ej: Juan Pérez">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Email Corporativo</label>
                        <input type="email" name="email" placeholder="proveedor@empresa.com">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width: auto; padding: 0.75rem 2.5rem; margin-top: 1rem;">GUARDAR PROVEEDOR</button>
            </form>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th>Domicilio / Contacto</th>
                            <th>Teléfono / Email</th>
                            <th>Estado</th>
                            <th style="text-align: right;">Acciones</th>
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
                                    <td>
                                        <div style="font-weight: 700; color: var(--text-main);"><?php echo htmlspecialchars($row['nombre']); ?></div>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.85rem;"><?php echo htmlspecialchars($row['domicilio']); ?></div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['atencion']); ?></div>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.85rem;"><?php echo htmlspecialchars($row['telefono']); ?></div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['email']); ?></div>
                                    </td>
                                    <td>
                                        <span class="btn-history" onclick="toggleHistory(<?php echo $row['id_proveedor']; ?>)" style="cursor: pointer; font-size: 0.75rem; font-weight: 700; color: var(--primary);">
                                            <?php echo $total_compras; ?> COMPRAS
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="proveedores.php?delete=<?php echo $row['id_proveedor']; ?>" class="btn btn-sm btn-danger" style="font-size: 0.7rem; padding: 0.3rem 0.6rem;" onclick="return confirm('¿Eliminar este proveedor?');">ELIMINAR</a>
                                    </td>
                                </tr>
                                <tr id="history-<?php echo $row['id_proveedor']; ?>" style="display: none;" class="history-row">
                                    <td colspan="6">
                                        <div style="padding: 1rem;">
                                            <h4 style="margin-bottom: 1rem;">Historial de Compras</h4>
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
                                                                <td>$<?php echo number_format($h['total'], 2); ?></td>
                                                                <td><?php echo $h['iva']; ?>%</td>
                                                                <td><?php echo htmlspecialchars($h['autorizado_por']); ?></td>
                                                            </tr>
                                                        <?php endwhile; ?>
                                                    </tbody>
                                                </table>
                                            <?php else: ?>
                                                <p style="color: var(--text-muted);">No hay compras registradas</p>
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
    </div>

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
</body>
</html>
