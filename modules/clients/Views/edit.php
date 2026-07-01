<?php
// modules/clients/Views/edit.php

Layout::renderHead('Editar Cliente - NOKIA1100');

if ($_SESSION['role'] === 'admin') {
    Layout::renderAdminSidebar('clientes');
} else {
    Layout::renderEmployeeSidebar('clientes');
}
?>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8 border border-border/50 max-w-2xl mx-auto">
        <div class="flex justify-between items-center mb-8 pb-4 border-b border-border/50">
            <div>
                <h2 class="text-2xl font-display font-medium text-text-main">Editar Cliente</h2>
                <p class="text-text-muted text-sm mt-1">Actualizar los datos de contacto del cliente</p>
            </div>
            <a href="clients.php"
                class="px-4 py-2 rounded-xl border border-border bg-surface hover:bg-surface-hover text-sm font-medium text-text-main transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span> Volver
            </a>
        </div>

        <?php if ($message): ?>
            <div
                class="p-4 rounded-xl mb-6 text-sm font-medium flex gap-3 items-center <?php echo $message_type === 'success' ? 'bg-primary/10 border border-primary/20 text-primary' : 'bg-red-500/10 border border-red-500/20 text-red-500'; ?>">
                <span
                    class="material-symbols-outlined"><?php echo $message_type === 'success' ? 'check_circle' : 'error'; ?></span>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <div>
                <label for="nombre"
                    class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Nombre Completo
                    *</label>
                <input type="text" id="nombre" name="nombre" required
                    value="<?php echo htmlspecialchars($nombre ?? ''); ?>"
                    class="w-full bg-surface border border-border p-3 rounded-xl focus:outline-none focus:border-primary transition-colors text-sm text-text-main">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="telefono"
                        class="block text-xs font-semibold text-text-muted mb-2 uppercase tracking-wide">Teléfono /
                        WhatsApp</label>
                    <input type="text" id="telefono" name="telefono"
                        value="<?php echo htmlspecialchars($telefono ?? ''); ?>"
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
                <span class="material-symbols-outlined text-[20px]">save</span> Actualizar Cliente
            </button>
        </form>
    </div>
</main>

<?php Layout::renderFooter(); ?>
