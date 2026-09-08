<?php
// modules/admin/Views/profile.php

Layout::renderHead('Mi Perfil - NOKIA1100');
Layout::renderAdminSidebar('perfil');
?>
<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen flex items-start justify-center mt-10">
    <div class="glass-card w-full max-w-3xl border border-border/50 shadow-2xl shadow-black/50 p-8 md:p-12 relative overflow-hidden">
        <!-- Abstract background decorations -->
        <div class="absolute -top-32 -right-32 w-64 h-64 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -left-32 w-80 h-80 bg-primary/5 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="dashboard-header mb-10 relative z-10">
            <div class="flex flex-col items-center text-center">
                <div class="w-24 h-24 rounded-full bg-gradient-to-tr from-primary/20 to-primary/5 text-primary flex items-center justify-center font-display text-4xl font-bold border border-primary/30 shadow-[0_0_40px_rgba(33,184,189,0.15)] mb-5">
                    <?php echo strtoupper(substr($current_data['nombre_usuario'], 0, 1)); ?>
                </div>
                <div>
                    <h2 class="text-3xl font-display font-bold text-text-main tracking-tight">Mi Perfil</h2>
                    <p class="text-text-muted text-sm mt-2">Ajustes de cuenta de administrador</p>
                </div>
            </div>
        </div>



        <form id="profileForm" method="POST" action="" class="space-y-8 mt-6">
            <h3 class="text-xs uppercase font-bold tracking-widest text-text-muted border-b border-border/40 pb-2 mb-6">Datos Personales</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="group">
                    <label class="block text-[11px] font-bold text-text-muted mb-2 uppercase tracking-wider group-focus-within:text-primary transition-colors">Nombre</label>
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($current_data['nombre']); ?>" required 
                           class="w-full bg-surface border border-border/60 p-3.5 rounded-xl focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all text-sm text-text-main shadow-sm hover:border-border">
                </div>
                <div class="group">
                    <label class="block text-[11px] font-bold text-text-muted mb-2 uppercase tracking-wider group-focus-within:text-primary transition-colors">Apellido</label>
                    <input type="text" name="apellido" value="<?php echo htmlspecialchars($current_data['apellido']); ?>" required 
                           class="w-full bg-surface border border-border/60 p-3.5 rounded-xl focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all text-sm text-text-main shadow-sm hover:border-border">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-text-muted mb-2 uppercase tracking-wider">DNI (No editable)</label>
                    <input type="text" value="<?php echo htmlspecialchars($current_data['dni']); ?>" disabled 
                           class="w-full bg-surface/30 border border-transparent p-3.5 rounded-xl text-text-muted/60 cursor-not-allowed text-sm">
                </div>
            </div>

            <h3 class="text-xs uppercase font-bold tracking-widest text-text-muted border-b border-border/40 pb-2 mb-6 mt-12">Credenciales de Acceso</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="group">
                    <label class="block text-[11px] font-bold text-primary mb-2 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[14px]">badge</span> Nombre de Usuario
                    </label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($current_data['nombre_usuario']); ?>" required 
                           class="w-full bg-primary/5 border border-primary/30 p-3.5 rounded-xl focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/20 transition-all text-sm text-text-main shadow-sm hover:border-primary/50">
                </div>
            </div>

            <div class="pt-8 mt-8 border-t border-border/40 flex justify-end">
                <button type="submit" id="saveProfileBtn" class="bg-primary text-background font-semibold hover:bg-primary-hover hover:scale-[1.02] active:scale-[0.98] transition-all duration-200 px-8 py-3.5 rounded-xl flex items-center gap-2 shadow-lg shadow-primary/20">
                    <span class="material-symbols-outlined text-[18px] btn-icon">save</span>
                    <span class="btn-text">Guardar Cambios</span>
                </button>
            </div>
        </form>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('profileForm');
    const btn = document.getElementById('saveProfileBtn');
    const btnIcon = btn.querySelector('.btn-icon');
    const btnText = btn.querySelector('.btn-text');

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        
        // Efecto visual de carga
        btn.disabled = true;
        btn.classList.add('opacity-80', 'cursor-not-allowed');
        btnIcon.textContent = 'hourglass_top';
        btnIcon.classList.add('animate-spin');
        btnText.textContent = 'Guardando...';

        // 1 segundo de delay artificial antes de enviar
        setTimeout(() => {
            form.submit();
        }, 1000);
    });
});
</script>
<?php Layout::renderFooter(); ?>
