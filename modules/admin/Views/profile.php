<?php
// modules/admin/Views/profile.php

Layout::renderHead('Mi Perfil - NOKIA1100');
Layout::renderAdminSidebar('perfil');
?>
<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8 max-w-3xl border border-border/50">
        <div class="dashboard-header mb-8">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-primary/10 text-primary flex items-center justify-center font-display text-2xl font-semibold border border-primary/20">
                    <?php echo strtoupper(substr($current_data['nombre_usuario'], 0, 1)); ?>
                </div>
                <div>
                    <h2 class="text-2xl font-display font-medium text-text-main">Mi Perfil</h2>
                    <p class="text-text-muted text-sm mt-1">Ajustes de cuenta de administrador</p>
                </div>
            </div>
        </div>

        <?php if ($success_msg): ?>
            <div class="bg-primary/10 border border-primary/20 text-primary text-sm p-4 rounded-xl mb-6 font-medium flex gap-3 items-center">
                <span class="material-symbols-outlined text-lg">check_circle</span>
                <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-500 text-sm p-4 rounded-xl mb-6 font-medium flex gap-3 items-center">
                <span class="material-symbols-outlined text-lg">error</span>
                <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <h3 class="text-xs uppercase font-semibold tracking-widest text-text-muted border-b border-border/50 pb-2 mb-6">Datos Personales</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Nombre</label>
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($current_data['nombre']); ?>" required 
                           class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Apellido</label>
                    <input type="text" name="apellido" value="<?php echo htmlspecialchars($current_data['apellido']); ?>" required 
                           class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">DNI (No editable)</label>
                    <input type="text" value="<?php echo htmlspecialchars($current_data['dni']); ?>" disabled 
                           class="w-full bg-surface/50 border border-transparent p-3 rounded-xl text-text-muted opacity-70 cursor-not-allowed text-sm">
                </div>
            </div>

            <h3 class="text-xs uppercase font-semibold tracking-widest text-text-muted border-b border-border/50 pb-2 mb-6 mt-10">Credenciales de Acceso</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <label class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide text-primary">Nombre de Usuario</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($current_data['nombre_usuario']); ?>" required 
                           class="w-full bg-primary/5 border border-primary/20 p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm">
                </div>
            </div>

            <div class="pt-8 mt-4 border-t border-border/50 flex justify-end">
                <button type="submit" class="bg-text-main text-background font-medium hover:bg-text-muted transition-colors px-8 py-3 rounded-xl flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">save</span>
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</main>
<?php Layout::renderFooter(); ?>
