<?php
// modules/workshop/Views/add.php

Layout::renderHead('Ingresar Equipo - Nokia 1100');

if ($_SESSION['role'] === 'admin') {
    Layout::renderAdminSidebar('taller');
} else {
    Layout::renderEmployeeSidebar('taller');
}
?>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6 flex items-center gap-4">
            <a href="index.php" class="w-10 h-10 rounded-full bg-surface border border-border flex items-center justify-center text-text-muted hover:text-text-main transition-colors">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <div>
                <h2 class="text-3xl font-display font-medium text-text-main">Nueva Orden de Reparación</h2>
                <p class="text-text-muted mt-1 text-sm">Registrar el ingreso de un equipo al taller</p>
            </div>
        </div>



        <form method="POST" action="" enctype="multipart/form-data" class="glass-card rounded-2xl p-6 md:p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- CLIENTE -->
                <div>
                    <h3 class="text-lg font-display font-medium text-primary mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-xl">person</span> Datos del Cliente
                    </h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-text-muted mb-1">Cliente Existente</label>
                            <select name="id_cliente_existente" id="id_cliente_existente" class="w-full bg-surface border border-border px-4 py-2.5 rounded-xl text-sm text-text-main focus:outline-none focus:border-primary transition-colors">
                                <option value="0">-- Crear Nuevo Cliente --</option>
                                <?php foreach($clients as $c): ?>
                                    <option value="<?php echo $c['id_cliente']; ?>" data-telefono="<?php echo htmlspecialchars($c['telefono']); ?>" data-nombre="<?php echo htmlspecialchars($c['nombre']); ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="new_client_fields" class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-text-muted mb-1">Nombre Completo *</label>
                                <input type="text" name="cliente_nombre" id="cliente_nombre" required class="w-full bg-surface border border-border px-4 py-2.5 rounded-xl text-sm text-text-main focus:outline-none focus:border-primary transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-text-muted mb-1">Teléfono (WhatsApp)</label>
                                <input type="tel" name="cliente_telefono" id="cliente_telefono" class="w-full bg-surface border border-border px-4 py-2.5 rounded-xl text-sm text-text-main focus:outline-none focus:border-primary transition-colors">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- EQUIPO -->
                <div>
                    <h3 class="text-lg font-display font-medium text-primary mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-xl">smartphone</span> Datos del Equipo
                    </h3>
                    
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-text-muted mb-1">Marca *</label>
                                <input type="text" name="equipo_marca" required class="w-full bg-surface border border-border px-4 py-2.5 rounded-xl text-sm text-text-main focus:outline-none focus:border-primary transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-text-muted mb-1">Modelo *</label>
                                <input type="text" name="equipo_modelo" required class="w-full bg-surface border border-border px-4 py-2.5 rounded-xl text-sm text-text-main focus:outline-none focus:border-primary transition-colors">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-muted mb-1">IMEI / Nro Serie</label>
                            <input type="text" name="equipo_imei" class="w-full bg-surface border border-border px-4 py-2.5 rounded-xl text-sm text-text-main focus:outline-none focus:border-primary transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-muted mb-1">Falla Declarada (Motivo de ingreso) *</label>
                            <textarea name="falla_declarada" required rows="3" class="w-full bg-surface border border-border px-4 py-2.5 rounded-xl text-sm text-text-main focus:outline-none focus:border-primary transition-colors"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-muted mb-1">Fotos del Equipo (Opcional)</label>
                            <div class="flex items-center gap-2">
                                <input type="file" id="fotos-input" name="fotos[]" multiple accept="image/*" class="flex-1 bg-surface border border-border px-4 py-2 rounded-xl text-sm text-text-main focus:outline-none focus:border-primary transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                                <button type="button" id="clear-fotos-btn" class="hidden w-10 h-10 bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white rounded-xl flex items-center justify-center transition-colors shadow-sm" title="Quitar fotos">
                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                </button>
                            </div>
                            <p class="text-xs text-text-muted mt-1">Se pueden subir varias imágenes para documentar el estado del equipo al ingresar.</p>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="border-border/50 my-8">

            <div class="grid grid-cols-1 gap-8 mb-8">
                <div>
                    <h3 class="text-lg font-display font-medium text-primary mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-xl">receipt_long</span> Detalles Comerciales
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-text-muted mb-1">Presupuesto Estimado ($)</label>
                            <input type="number" step="0.01" name="presupuesto" placeholder="Dejar en blanco si se debe presupuestar luego" class="w-full bg-surface border border-border px-4 py-2.5 rounded-xl text-sm text-text-main focus:outline-none focus:border-primary transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-muted mb-1">Observaciones (Rayones, roturas previas, etc)</label>
                            <textarea name="observaciones" rows="3" class="w-full bg-surface border border-border px-4 py-2.5 rounded-xl text-sm text-text-main focus:outline-none focus:border-primary transition-colors"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-border/50">
                <a href="index.php" class="px-6 py-2.5 bg-surface border border-border text-text-main rounded-xl text-sm font-medium hover:bg-surface-hover transition-colors">Cancelar</a>
                <button type="submit" class="px-6 py-2.5 bg-primary text-background rounded-xl text-sm font-medium hover:bg-primary-hover transition-all shadow-lg hover:shadow-primary/25">Generar Orden</button>
            </div>
        </form>
    </div>
</main>

<script>
document.getElementById('id_cliente_existente').addEventListener('change', function() {
    const newClientFields = document.getElementById('new_client_fields');
    const nameInput = document.getElementById('cliente_nombre');
    const phoneInput = document.getElementById('cliente_telefono');
    
    if (this.value === '0') {
        newClientFields.style.display = 'block';
        nameInput.required = true;
        nameInput.value = '';
        phoneInput.value = '';
        nameInput.readOnly = false;
        phoneInput.readOnly = false;
    } else {
        newClientFields.style.display = 'block';
        nameInput.required = false;
        const selectedOption = this.options[this.selectedIndex];
        nameInput.value = selectedOption.getAttribute('data-nombre');
        phoneInput.value = selectedOption.getAttribute('data-telefono');
        nameInput.readOnly = true;
        phoneInput.readOnly = true;
    }
});

// Lógica para limpiar el input de fotos
const fotosInput = document.getElementById('fotos-input');
const clearFotosBtn = document.getElementById('clear-fotos-btn');

fotosInput.addEventListener('change', function() {
    if (this.files.length > 0) {
        clearFotosBtn.classList.remove('hidden');
    } else {
        clearFotosBtn.classList.add('hidden');
    }
});

clearFotosBtn.addEventListener('click', function() {
    fotosInput.value = '';
    clearFotosBtn.classList.add('hidden');
});
</script>

<?php Layout::renderFooter(); ?>
