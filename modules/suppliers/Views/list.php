<?php
// modules/suppliers/Views/list.php

/**
 * Variables inyectadas por SuppliersController::suppliers()
 * @var array $suppliers
 */
$suppliers = $suppliers ?? [];

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
            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <a href="index.php?action=new_purchase" class="px-4 py-2 rounded-xl bg-primary text-background font-medium hover:bg-primary/90 transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">add_shopping_cart</span> Registrar Pedido
                </a>
                <a href="index.php?action=purchase_history" class="px-4 py-2 rounded-xl border border-border bg-surface hover:bg-surface-hover text-sm font-medium transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">history</span> Historial de Pedidos
                </a>
            </div>
        </div>
        
        <div class="dashboard-header flex justify-between items-center mb-8" style="flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                <input type="text" id="search-input" placeholder="Buscar proveedor..." class="w-full sm:w-[220px]" style="padding: 0.5rem 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 0.9rem;">
                <?php echo Layout::renderExportButtons('suppliers-table', 'Listado de Proveedores', 'proveedores'); ?>
                <a href="<?php echo BASE_URL; ?>/modules/admin/index.php" class="btn-back">Volver</a>
            </div>
        </div>



        <?php // Formulario para agregar proveedor ?>
        <form method="POST" action="" style="background: var(--surface-hover); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid var(--border);">
            <h3 style="margin-bottom: 1rem; font-size: 1.1rem; font-family: 'Outfit', sans-serif;">Agregar Proveedor</h3>
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['session_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
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
            <table id="suppliers-table" data-paginate="10" data-entity-singular="proveedor" data-entity-plural="proveedores">
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
                                    <a href="index.php?action=edit_supplier&id=<?php echo $row['id_proveedor']; ?>" style="color: var(--primary-color); font-weight: 600; font-size: 0.85rem; margin-right: 1rem;">Editar</a>
                                    <form method="POST" action="index.php" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_supplier">
                                        <input type="hidden" name="id_proveedor" value="<?php echo (int)$row['id_proveedor']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['session_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="btn-delete" style="background:none; border:none; cursor:pointer; padding:0; font-family:inherit; font-size:inherit;" data-confirm="¿Seguro que deseas eliminar este proveedor?" data-confirm-title="Eliminar Proveedor">Eliminar</button>
                                    </form>
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
