<?php
// modules/clients/Views/list.php

Layout::renderHead('Clientes - Nokia 1100');

if ($_SESSION['role'] === 'admin') {
    Layout::renderAdminSidebar('clientes');
} else {
    Layout::renderEmployeeSidebar('clientes');
}
?>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8">
        <div
            class="dashboard-header border-b border-border/50 pb-6 mb-6 flex justify-between items-center flex-wrap gap-4">
            <div>
                <h2 class="text-3xl font-display font-medium text-text-main">Administración de Clientes</h2>
                <p class="text-text-muted mt-1 text-sm">Registro, control y edición de clientes del sistema</p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <!-- Botones de Exportación -->
                <button type="button" onclick="exportTableToExcel('clients-table', 'clientes')"
                    class="px-3 py-2 rounded-xl border border-border bg-surface hover:bg-surface-hover text-xs font-medium text-text-muted hover:text-text-main transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">file_download</span> Excel
                </button>
                <button type="button" onclick="exportTableToPDF('clients-table', 'Listado de Clientes', 'clientes')"
                    class="px-3 py-2 rounded-xl border border-border bg-surface hover:bg-surface-hover text-xs font-medium text-text-muted hover:text-text-main transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">picture_as_pdf</span> PDF
                </button>

                <button onclick="openAddClientModal()"
                    class="bg-primary text-background hover:bg-primary-hover px-4 py-2 rounded-xl font-medium text-sm transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">person_add</span> Nuevo Cliente
                </button>
            </div>
        </div>

        <?php if ($error): ?>
            <div
                class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6 text-sm flex gap-3 items-center">
                <span class="material-symbols-outlined">error</span> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div
                class="bg-primary/10 border border-primary/20 text-primary p-4 rounded-xl mb-6 text-sm flex gap-3 items-center">
                <span class="material-symbols-outlined">check_circle</span> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="GET" action="" class="flex gap-3 mb-6 items-center">
            <div class="relative flex-1 max-w-md">
                <span
                    class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-muted text-[20px]">search</span>
                <input type="text" name="search" placeholder="Buscar por nombre, teléfono, email..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    class="w-full bg-surface border border-border pl-10 py-2.5 pr-4 rounded-xl text-sm text-text-main focus:outline-none focus:border-primary transition-colors">
            </div>
            <button type="submit"
                class="bg-surface hover:bg-surface-hover border border-border px-4 py-2.5 rounded-xl text-sm font-medium transition-colors text-text-main">Buscar</button>
            <?php if ($search): ?>
                <a href="clients.php"
                    class="text-red-400 hover:text-red-300 text-sm font-medium ml-2 transition-colors">Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="table-container rounded-2xl border border-border overflow-x-auto">
            <table class="w-full text-left" id="clients-table">
                <thead class="bg-surface/50 border-b border-border text-xs uppercase text-text-muted tracking-wider">
                    <tr>
                        <th class="p-4 font-semibold">ID</th>
                        <th class="p-4 font-semibold">Nombre Completo</th>
                        <th class="p-4 font-semibold">Teléfono / WhatsApp</th>
                        <th class="p-4 font-semibold">Email</th>
                        <th class="p-4 font-semibold">Fecha Registro</th>
                        <th class="p-4 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/50">
                    <?php if (count($clientes) > 0): ?>
                        <?php foreach ($clientes as $c): ?>
                            <tr class="hover:bg-surface-hover/30 transition-colors">
                                <td class="p-4 text-text-muted text-sm">#CL-<?php echo $c['id_cliente']; ?></td>
                                <td class="p-4 font-display font-medium text-text-main">
                                    <?php echo htmlspecialchars($c['nombre']); ?></td>
                                <td class="p-4 text-text-muted text-sm">
                                    <?php if ($c['telefono']): ?>
                                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $c['telefono']); ?>"
                                            target="_blank" class="hover:text-primary transition-colors flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[16px] text-green-500">call</span>
                                            <?php echo htmlspecialchars($c['telefono']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-text-muted/40">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-text-muted text-sm"><?php echo htmlspecialchars($c['email'] ?: '-'); ?></td>
                                <td class="p-4 text-text-muted text-sm">
                                    <?php echo date('d/m/Y', strtotime($c['created_at'])); ?></td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="edit_client.php?id=<?php echo $c['id_cliente']; ?>"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center bg-surface border border-border text-text-main hover:bg-primary hover:text-background transition-colors"
                                            title="Editar Cliente">
                                            <span class="material-symbols-outlined text-[16px]">edit</span>
                                        </a>
                                        <a href="javascript:void(0)"
                                            onclick="confirmDelete(<?php echo $c['id_cliente']; ?>, '<?php echo htmlspecialchars($c['nombre'], ENT_QUOTES); ?>')"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center bg-surface border border-border text-red-400 hover:bg-red-500 hover:text-white transition-colors"
                                            title="Eliminar Cliente">
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="p-12 text-center text-text-muted">No se encontraron clientes registrados.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal de Registro de Cliente -->
<div id="add-client-modal"
    class="fixed inset-0 bg-background/80 backdrop-blur-[8px] z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300">
    <div class="glass-card max-w-md w-full m-4 shadow-2xl relative border border-border/80 transform scale-95 transition-all duration-300 premium-modal-card"
        style="padding: 2rem; border-top: 4px solid var(--primary-color);">
        <button onclick="closeAddClientModal()"
            class="absolute top-4 right-4 text-text-muted hover:text-text-main transition-colors bg-transparent border-none cursor-pointer">
            <span class="material-symbols-outlined">close</span>
        </button>

        <div class="mb-6">
            <h3 class="text-2xl font-display font-medium text-text-main flex items-center gap-2">
                <span class="material-symbols-outlined text-primary"
                    style="color:var(--primary-color); font-size: 1.8rem;">person_add</span>
                Agregar Nuevo Cliente
            </h3>
            <p class="text-text-muted text-xs mt-1">Registrar los datos de contacto del cliente en el sistema</p>
        </div>

        <form method="POST" action="" class="space-y-4 flex flex-col gap-4">
            <input type="hidden" name="action" value="add">

            <div class="premium-input-container">
                <label for="modal-nombre">Nombre Completo <span class="text-red-400">*</span></label>
                <div class="relative">
                    <span
                        class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">person</span>
                    <input type="text" id="modal-nombre" name="nombre" required placeholder="Ej. Juan Pérez">
                </div>
            </div>

            <div class="premium-input-container">
                <label for="modal-telefono">Teléfono / WhatsApp</label>
                <div class="relative">
                    <span
                        class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">call</span>
                    <input type="text" id="modal-telefono" name="telefono" placeholder="Ej. 1122334455">
                </div>
            </div>

            <div class="premium-input-container">
                <label for="modal-email">Email</label>
                <div class="relative">
                    <span
                        class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">mail</span>
                    <input type="email" id="modal-email" name="email" placeholder="Ej. juan.perez@example.com">
                </div>
            </div>

            <div
                style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                <button type="button" onclick="closeAddClientModal()" class="btn-back"
                    style="width: auto; margin: 0;">Cancelar</button>
                <button type="submit" class="btn-primary"
                    style="width: auto; margin: 0; background: var(--primary-color); color: #0A0A0B;">
                    Guardar Cliente
                </button>
            </div>
        </form>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>/modules/clients/clients_list.js?v=<?php echo time(); ?>"></script>

<?php Layout::renderFooter(); ?>
