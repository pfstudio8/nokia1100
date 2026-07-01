<?php
// modules/suppliers/Views/edit.php

Layout::renderHead('Editar Proveedor - Nokia 1100');
Layout::renderAdminSidebar('proveedores');
?>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8 border border-border/50 max-w-2xl mx-auto">
        <div class="flex justify-between items-center mb-8 pb-4 border-b border-border/50">
            <div>
                <h2 class="text-2xl font-display font-medium text-text-main">Editar Proveedor</h2>
                <p class="text-text-muted text-sm mt-1">Actualizar datos del proveedor en el directorio</p>
            </div>
            <a href="suppliers.php"
                class="px-4 py-2 rounded-xl border border-border bg-surface hover:bg-surface-hover text-sm font-medium text-text-main transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span> Volver
            </a>
        </div>

        <?php if ($error): ?>
            <div
                class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6 text-sm flex gap-3 items-center">
                <span class="material-symbols-outlined">error</span> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div
                class="bg-primary/10 border border-primary/20 text-primary p-4 rounded-xl mb-6 text-sm flex gap-3 items-center">
                <span class="material-symbols-outlined">check_circle</span> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <div>
                <label for="nombre"
                    class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Nombre Empresa
                    *</label>
                <input type="text" id="nombre" name="nombre" required
                    value="<?php echo htmlspecialchars($nombre ?? ''); ?>"
                    class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="domicilio"
                        class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Domicilio</label>
                    <input type="text" id="domicilio" name="domicilio"
                        value="<?php echo htmlspecialchars($domicilio ?? ''); ?>"
                        class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
                </div>
                <div>
                    <label for="telefono"
                        class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Teléfono</label>
                    <input type="text" id="telefono" name="telefono"
                        value="<?php echo htmlspecialchars($telefono ?? ''); ?>"
                        class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="atencion"
                        class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Persona de
                        Contacto</label>
                    <input type="text" id="atencion" name="atencion"
                        value="<?php echo htmlspecialchars($atencion ?? ''); ?>"
                        class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
                </div>
                <div>
                    <label for="email"
                        class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>"
                        class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
                </div>
            </div>

            <button type="submit"
                class="w-full bg-primary/10 text-primary border border-primary/20 hover:bg-primary hover:text-background font-medium py-3 rounded-xl transition-all flex justify-center items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">save</span> Guardar Cambios
            </button>
        </form>
    </div>
</main>

<?php Layout::renderFooter(); ?>
