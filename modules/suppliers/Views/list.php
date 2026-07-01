<?php
// modules/suppliers/Views/list.php

Layout::renderHead('Proveedores - Nokia 1100');
Layout::renderAdminSidebar('proveedores');
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/suppliers/suppliers_list.css?v=<?php echo time(); ?>">

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8">
        <div class="dashboard-header flex justify-between items-center mb-8" style="flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2>Proveedores</h2>
                <p>Gestión del directorio y compras</p>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <input type="text" id="search-input" placeholder="Buscar proveedor..." style="width: 250px; padding: 0.5rem 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 0.9rem;">
                <!-- Botones de Exportación -->
                <button type="button" onclick="exportTableToExcel('suppliers-table', 'proveedores')" style="width: auto; padding: 0.5rem 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; color: var(--text-muted); font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">Excel</button>
                <button type="button" onclick="exportTableToPDF('suppliers-table', 'Listado de Proveedores', 'proveedores')" style="width: auto; padding: 0.5rem 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; color: var(--text-muted); font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">PDF</button>
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
                    <input type="text" name="nombre" class="supplier-input" required>
                </div>
                <div class="form-group">
                    <label>Domicilio</label>
                    <input type="text" name="domicilio" class="supplier-input" placeholder="Ej: Av. Central 123">
                </div>
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" class="supplier-input">
                </div>
                <div class="form-group">
                    <label>Persona de Contacto</label>
                    <input type="text" name="atencion" class="supplier-input" placeholder="Ej: Gabriel Martínez">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Email</label>
                    <input type="email" name="email" class="supplier-input">
                </div>
            </div>
            <button type="submit" style="width: auto; padding: 0.75rem 2rem; margin-top: 1rem;" class="btn-primary">Guardar Proveedor</button>
        </form>

        <div class="table-container">
            <table id="suppliers-table">
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
                    <?php if (count($suppliers) > 0): ?>
                        <?php foreach($suppliers as $row): ?>
                            <tr>
                                <td style="font-weight: 500; font-family: 'Outfit', sans-serif;"><?php echo htmlspecialchars($row['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($row['domicilio']); ?></td>
                                <td><?php echo htmlspecialchars($row['atencion']); ?></td>
                                <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                                <td>
                                    <span class="btn-history" onclick="toggleHistory(<?php echo $row['id_proveedor']; ?>)">
                                        <?php echo $row['total_compras']; ?> compra(s) ▼
                                    </span>
                                </td>
                                <td>
                                    <a href="edit_supplier.php?id=<?php echo $row['id_proveedor']; ?>" style="color: var(--primary-color); font-weight: 600; font-size: 0.85rem; margin-right: 1rem;">Editar</a>
                                    <a href="suppliers.php?delete=<?php echo $row['id_proveedor']; ?>" class="btn-delete" data-confirm="¿Seguro que deseas eliminar este proveedor?" data-confirm-title="Eliminar Proveedor">Eliminar</a>
                                </td>
                            </tr>
                            <tr id="history-<?php echo $row['id_proveedor']; ?>" style="display: none;" class="history-row">
                                <td colspan="6">
                                    <div style="padding: 1rem;">
                                        <h4 style="margin-bottom: 1rem; font-family: 'Outfit', sans-serif;">Historial de Compras</h4>
                                        <?php if (count($row['history']) > 0): ?>
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
                                                    <?php foreach($row['history'] as $h): ?>
                                                        <tr>
                                                            <td><?php echo date('d/m/Y', strtotime($h['fecha'])); ?></td>
                                                            <td><?php echo htmlspecialchars($h['descripcion']); ?></td>
                                                            <td><?php echo $h['items']; ?></td>
                                                            <td style="font-weight: 500;">$<?php echo number_format($h['total'], 2); ?></td>
                                                            <td><?php echo $h['iva']; ?>%</td>
                                                            <td><?php echo htmlspecialchars($h['autorizado_por']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php else: ?>
                                            <p style="color: var(--text-muted); font-size: 0.85rem;">No hay compras registradas para este proveedor.</p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">No hay proveedores registrados</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script src="<?php echo BASE_URL; ?>/modules/suppliers/suppliers_list.js?v=<?php echo time(); ?>"></script>
<?php Layout::renderFooter(); ?>
