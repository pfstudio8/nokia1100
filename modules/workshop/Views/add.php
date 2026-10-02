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
                            <label class="block text-xs font-medium text-text-muted mb-2">Fotos del Equipo (Opcional)</label>
                            
                            <div class="bg-surface/30 border border-border rounded-xl p-1 shadow-sm mb-2">
                                <div class="bg-background rounded-lg overflow-hidden border border-border/50">
                                    <div class="w-full p-5 relative group transition-colors hover:bg-surface/30">
                                        <input type="file" name="fotos[]" id="fotos-input" multiple accept="image/*" class="hidden">
                                        <label for="fotos-input" class="cursor-pointer flex flex-col sm:flex-row items-center justify-center gap-4 w-full h-full min-h-[120px] bg-transparent border-2 border-dashed border-border hover:border-primary/70 px-4 py-6 rounded-xl text-sm transition-all text-text-muted group-hover:text-primary">
                                            <div class="w-12 h-12 rounded-full bg-primary/5 text-primary border border-primary/20 flex items-center justify-center group-hover:scale-110 group-hover:bg-primary/10 transition-all shadow-sm shrink-0">
                                                <span class="material-symbols-outlined text-[24px]">cloud_upload</span>
                                            </div>
                                            <div class="text-center sm:text-left flex flex-col">
                                                <span id="file_name_display" class="font-medium text-text-main text-[15px]">Seleccionar o arrastrar imágenes</span>
                                                <span class="text-[11px] font-medium text-text-muted mt-1 uppercase tracking-wider">JPG, PNG, WEBP (MAX. 5MB)</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between mt-1">
                                <p class="text-[11px] text-text-muted">Se pueden subir varias imágenes para documentar el estado del equipo.</p>
                                <button type="button" id="clear-fotos-btn" class="hidden text-[11px] font-medium bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white px-3 py-1.5 rounded-lg transition-colors shadow-sm flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">delete</span> Quitar
                                </button>
                            </div>
                            <div id="image-preview-container" class="mt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3"></div>
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

// Lógica para limpiar el input de fotos y mostrar vista previa
const fotosInput = document.getElementById('fotos-input');
const clearFotosBtn = document.getElementById('clear-fotos-btn');
const previewContainer = document.getElementById('image-preview-container');

fotosInput.addEventListener('change', function() {
    previewContainer.innerHTML = ''; // Limpiar vistas previas anteriores
    const fileNameDisplay = document.getElementById('file_name_display');
    const uploadLabel = document.querySelector('label[for="fotos-input"]');
    
    if (this.files.length > 0) {
        clearFotosBtn.classList.remove('hidden');
        
        if (this.files.length === 1) {
            fileNameDisplay.textContent = this.files[0].name;
        } else {
            fileNameDisplay.textContent = this.files.length + ' imágenes seleccionadas';
        }
        
        uploadLabel.classList.add('border-primary/50', 'bg-primary/5');
        uploadLabel.classList.remove('border-border', 'bg-transparent');
        uploadLabel.querySelector('.rounded-full').classList.add('bg-primary/10', 'border-primary/30', 'text-primary');
        uploadLabel.querySelector('.rounded-full').classList.remove('bg-primary/5', 'border-primary/20');
        
        // Generar vistas previas
        Array.from(this.files).forEach(file => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'w-full aspect-square object-cover rounded-lg border border-border shadow-sm';
                    previewContainer.appendChild(img);
                };
                reader.readAsDataURL(file);
            }
        });
    } else {
        clearFotosBtn.classList.add('hidden');
        if(fileNameDisplay) fileNameDisplay.textContent = 'Seleccionar o arrastrar imágenes';
        if(uploadLabel) {
            uploadLabel.classList.remove('border-primary/50', 'bg-primary/5');
            uploadLabel.classList.add('border-border', 'bg-transparent');
            uploadLabel.querySelector('.rounded-full').classList.remove('bg-primary/10', 'border-primary/30', 'text-primary');
            uploadLabel.querySelector('.rounded-full').classList.add('bg-primary/5', 'border-primary/20');
        }
    }
});

clearFotosBtn.addEventListener('click', function() {
    fotosInput.value = '';
    clearFotosBtn.classList.add('hidden');
    previewContainer.innerHTML = ''; // Limpiar vistas previas
    
    const fileNameDisplay = document.getElementById('file_name_display');
    const uploadLabel = document.querySelector('label[for="fotos-input"]');
    
    if(fileNameDisplay) fileNameDisplay.textContent = 'Seleccionar o arrastrar imágenes';
    if(uploadLabel) {
        uploadLabel.classList.remove('border-primary/50', 'bg-primary/5');
        uploadLabel.classList.add('border-border', 'bg-transparent');
        uploadLabel.querySelector('.rounded-full').classList.remove('bg-primary/10', 'border-primary/30', 'text-primary');
        uploadLabel.querySelector('.rounded-full').classList.add('bg-primary/5', 'border-primary/20');
    }
});
</script>

<?php Layout::renderFooter(); ?>
