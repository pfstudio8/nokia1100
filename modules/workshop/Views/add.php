<?php
// modules/workshop/Views/add.php

/** @var array<int, array<string, mixed>> $clients */
$clients = $clients ?? [];
/** @var string|null $error */
$error = $error ?? null;

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
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['session_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <?php // CLIENTE ?>
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

                <?php // EQUIPO ?>
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
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-medium text-text-muted">Fotos del Equipo (Opcional)</label>
                                <span id="fotos-counter" class="text-[11px] font-mono font-medium text-text-muted bg-surface px-2 py-0.5 rounded border border-border hidden">0 fotos</span>
                            </div>
                            
                            <div class="bg-surface/30 border border-border rounded-xl p-1 shadow-sm mb-2" id="upload-dropzone">
                                <div class="bg-background rounded-lg overflow-hidden border border-border/50">
                                    <div class="w-full p-5 relative group transition-colors hover:bg-surface/30">
                                        <input type="file" name="fotos[]" id="fotos-input" multiple accept="image/*" class="hidden">
                                        <label for="fotos-input" id="fotos-drop-label" class="cursor-pointer flex flex-col sm:flex-row items-center justify-center gap-4 w-full h-full min-h-[120px] bg-transparent border-2 border-dashed border-border hover:border-primary/70 px-4 py-6 rounded-xl text-sm transition-all text-text-muted group-hover:text-primary">
                                            <div class="w-12 h-12 rounded-full bg-primary/5 text-primary border border-primary/20 flex items-center justify-center group-hover:scale-110 group-hover:bg-primary/10 transition-all shadow-sm shrink-0">
                                                <span class="material-symbols-outlined text-[24px]">cloud_upload</span>
                                            </div>
                                            <div class="text-center sm:text-left flex flex-col">
                                                <span id="file_name_display" class="font-medium text-text-main text-[15px]">Seleccionar o arrastrar imágenes</span>
                                                <span class="text-[11px] font-medium text-text-muted mt-1 uppercase tracking-wider">JPG, PNG, WEBP (MAX. 5MB) · Podés subir varias</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between mt-1">
                                <p class="text-[11px] text-text-muted flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">visibility</span>
                                    <span>Hacé clic sobre cualquier foto para abrirla en pantalla completa.</span>
                                </p>
                                <button type="button" id="clear-fotos-btn" class="hidden text-[11px] font-medium bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white px-3 py-1.5 rounded-lg transition-colors shadow-sm flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">delete</span> Quitar todas
                                </button>
                            </div>
                            <div id="image-preview-container" class="mt-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3"></div>
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
                            <input type="number" step="0.01" min="0" name="presupuesto" placeholder="Dejar en blanco si se debe presupuestar luego" onwheel="this.blur()" class="w-full bg-surface border border-border px-4 py-2.5 rounded-xl text-sm text-text-main focus:outline-none focus:border-primary transition-colors">
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

<!-- Modal Lightbox para visualización ampliada de fotos con navegación -->
<div id="photoLightboxModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/85 backdrop-blur-md hidden opacity-0 transition-opacity duration-200" role="dialog" aria-modal="true">
    <div class="relative max-w-4xl w-full max-h-[92vh] flex flex-col items-center select-none" onclick="event.stopPropagation()">
        <!-- Header: Contador y Botón Cerrar -->
        <div class="w-full flex items-center justify-between pb-3 text-white">
            <div id="lightboxCounter" class="text-xs font-mono px-3 py-1 rounded-full bg-white/10 text-white border border-white/10 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[14px]">photo_library</span>
                <span id="lightboxCounterText">1 / 1</span>
            </div>
            <button type="button" id="closeLightboxBtn" class="text-white/80 hover:text-white transition-colors flex items-center gap-1 font-mono text-xs cursor-pointer px-3 py-1.5 rounded-lg hover:bg-white/10 border border-transparent hover:border-white/20">
                <span>CERRAR</span>
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>

        <!-- Visor de Imagen con Flechas de Navegación -->
        <div class="relative w-full flex items-center justify-center overflow-hidden rounded-2xl border border-white/20 shadow-2xl bg-black/90 min-h-[220px]">
            <!-- Botón Anterior -->
            <button type="button" id="prevLightboxBtn" class="absolute left-3 z-30 w-11 h-11 rounded-full bg-black/60 hover:bg-black/90 text-white border border-white/20 flex items-center justify-center transition-all hover:scale-110 cursor-pointer shadow-lg hidden" title="Foto anterior (Flecha izquierda)">
                <span class="material-symbols-outlined text-[26px]">chevron_left</span>
            </button>

            <!-- Imagen Ampliada -->
            <img id="lightboxImage" src="" alt="Vista previa ampliada" class="max-w-full max-h-[75vh] object-contain select-none transition-all duration-150">

            <!-- Botón Siguiente -->
            <button type="button" id="nextLightboxBtn" class="absolute right-3 z-30 w-11 h-11 rounded-full bg-black/60 hover:bg-black/90 text-white border border-white/20 flex items-center justify-center transition-all hover:scale-110 cursor-pointer shadow-lg hidden" title="Foto siguiente (Flecha derecha)">
                <span class="material-symbols-outlined text-[26px]">chevron_right</span>
            </button>
        </div>

        <!-- Pie de foto: Nombre y Tamaño -->
        <div class="mt-3 flex items-center justify-center text-center px-4 w-full">
            <p id="lightboxCaption" class="text-xs text-white/80 font-mono truncate max-w-full bg-white/5 border border-white/10 px-4 py-1.5 rounded-lg"></p>
        </div>
    </div>
</div>

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

// Gestión de múltiples fotos con acumulación, previsualización interactiva y eliminación individual
const fotosInput = document.getElementById('fotos-input');
const clearFotosBtn = document.getElementById('clear-fotos-btn');
const previewContainer = document.getElementById('image-preview-container');
const fileNameDisplay = document.getElementById('file_name_display');
const uploadLabel = document.getElementById('fotos-drop-label') || document.querySelector('label[for="fotos-input"]');
const fotosCounter = document.getElementById('fotos-counter');

// Estado en memoria: Array de fotos acumuladas
let selectedPhotos = [];
let currentLightboxIndex = 0;

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

function syncInputFiles() {
    try {
        const dt = new DataTransfer();
        selectedPhotos.forEach(item => {
            dt.items.add(item.file);
        });
        fotosInput.files = dt.files;
    } catch (err) {
        console.warn('Sync DataTransfer warning:', err);
    }
}

function updateUploadAreaUI() {
    const count = selectedPhotos.length;
    if (count > 0) {
        clearFotosBtn.classList.remove('hidden');
        clearFotosBtn.innerHTML = `<span class="material-symbols-outlined text-[14px]">delete</span> Quitar todas (${count})`;
        if (fotosCounter) {
            fotosCounter.textContent = `${count} ${count === 1 ? 'foto' : 'fotos'}`;
            fotosCounter.classList.remove('hidden');
        }
        if (fileNameDisplay) {
            fileNameDisplay.textContent = `+ Agregar más fotos (${count} seleccionada${count > 1 ? 's' : ''})`;
        }
        if (uploadLabel) {
            uploadLabel.classList.add('border-primary/50', 'bg-primary/5');
            uploadLabel.classList.remove('border-border', 'bg-transparent');
            const iconWrap = uploadLabel.querySelector('.rounded-full');
            if (iconWrap) {
                iconWrap.classList.add('bg-primary/10', 'border-primary/30', 'text-primary');
                iconWrap.classList.remove('bg-primary/5', 'border-primary/20');
            }
        }
    } else {
        clearFotosBtn.classList.add('hidden');
        if (fotosCounter) fotosCounter.classList.add('hidden');
        if (fileNameDisplay) fileNameDisplay.textContent = 'Seleccionar o arrastrar imágenes';
        if (uploadLabel) {
            uploadLabel.classList.remove('border-primary/50', 'bg-primary/5');
            uploadLabel.classList.add('border-border', 'bg-transparent');
            const iconWrap = uploadLabel.querySelector('.rounded-full');
            if (iconWrap) {
                iconWrap.classList.remove('bg-primary/10', 'border-primary/30', 'text-primary');
                iconWrap.classList.add('bg-primary/5', 'border-primary/20');
            }
        }
    }
}

function renderPreviews() {
    previewContainer.innerHTML = '';

    selectedPhotos.forEach((item, index) => {
        const card = document.createElement('div');
        card.className = 'group relative rounded-xl border border-border bg-surface p-2 shadow-sm flex flex-col gap-2 transition-all hover:border-primary/50 hover:shadow-md';

        // Contenedor de la foto: al hacer clic se abre en el visor Lightbox
        const photoBox = document.createElement('div');
        photoBox.className = 'relative w-full aspect-square overflow-hidden rounded-lg bg-black/5 cursor-pointer';
        photoBox.title = 'Hacé clic para ver la foto en tamaño completo';

        const img = document.createElement('img');
        img.className = 'w-full h-full object-cover select-none transition-transform duration-300 ease-out group-hover:scale-105';
        img.alt = item.name;
        img.src = item.url;

        // Overlay sutil con ícono al pasar el mouse
        const hoverOverlay = document.createElement('div');
        hoverOverlay.className = 'absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-1 text-white pointer-events-none z-10';
        hoverOverlay.innerHTML = '<span class="material-symbols-outlined text-[24px]">visibility</span><span class="text-[10px] font-medium tracking-wide uppercase">Ver foto</span>';

        // Botón de eliminar foto individual (X)
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'absolute top-1.5 right-1.5 w-7 h-7 rounded-full bg-red-600/90 hover:bg-red-600 text-white flex items-center justify-center opacity-90 sm:opacity-0 group-hover:opacity-100 hover:scale-110 transition-all shadow-md z-20 cursor-pointer';
        removeBtn.title = 'Eliminar esta foto';
        removeBtn.innerHTML = '<span class="material-symbols-outlined text-[16px] pointer-events-none">close</span>';
        
        removeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            removePhotoById(item.id);
        });

        // Al hacer clic abre la imagen en Lightbox
        photoBox.addEventListener('click', (e) => {
            if (e.target.closest('button')) return;
            openLightbox(index);
        });

        photoBox.appendChild(img);
        photoBox.appendChild(hoverOverlay);
        photoBox.appendChild(removeBtn);

        // Pie de tarjeta con nombre y peso
        const info = document.createElement('div');
        info.className = 'flex flex-col px-0.5';
        info.innerHTML = `
            <span class="text-xs font-medium text-text-main truncate" title="${item.name}">${item.name}</span>
            <span class="text-[10px] font-mono text-text-muted">${formatFileSize(item.size)}</span>
        `;

        card.appendChild(photoBox);
        card.appendChild(info);
        previewContainer.appendChild(card);
    });

    updateUploadAreaUI();
}

function addFiles(fileList) {
    let addedCount = 0;
    Array.from(fileList).forEach(file => {
        if (!file.type.startsWith('image/')) return;
        // Evitar duplicados exactos
        const exists = selectedPhotos.some(item => 
            item.file.name === file.name && item.file.size === file.size && item.file.lastModified === file.lastModified
        );
        if (!exists) {
            const url = URL.createObjectURL(file);
            selectedPhotos.push({
                id: 'photo_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
                file: file,
                url: url,
                name: file.name,
                size: file.size
            });
            addedCount++;
        }
    });

    if (addedCount > 0) {
        syncInputFiles();
        renderPreviews();
    }
}

function removePhotoById(id) {
    const idx = selectedPhotos.findIndex(item => item.id === id);
    if (idx !== -1) {
        try {
            URL.revokeObjectURL(selectedPhotos[idx].url);
        } catch (e) {}
        selectedPhotos.splice(idx, 1);
        syncInputFiles();
        renderPreviews();
    }
}

// Lightbox modal logic
function openLightbox(index) {
    if (!selectedPhotos || selectedPhotos.length === 0) return;
    if (index < 0 || index >= selectedPhotos.length) index = 0;
    currentLightboxIndex = index;

    const modal = document.getElementById('photoLightboxModal');
    if (!modal) return;

    updateLightboxContent();

    modal.classList.remove('hidden');
    setTimeout(() => modal.classList.remove('opacity-0'), 10);
}

function updateLightboxContent() {
    const current = selectedPhotos[currentLightboxIndex];
    if (!current) return;

    const img = document.getElementById('lightboxImage');
    const caption = document.getElementById('lightboxCaption');
    const counterText = document.getElementById('lightboxCounterText');
    const prevBtn = document.getElementById('prevLightboxBtn');
    const nextBtn = document.getElementById('nextLightboxBtn');

    if (img) img.src = current.url;
    if (caption) caption.textContent = `${current.name} (${formatFileSize(current.size)})`;
    if (counterText) counterText.textContent = `${currentLightboxIndex + 1} / ${selectedPhotos.length}`;

    const hasMultiple = selectedPhotos.length > 1;
    if (prevBtn) {
        if (hasMultiple) prevBtn.classList.remove('hidden');
        else prevBtn.classList.add('hidden');
    }
    if (nextBtn) {
        if (hasMultiple) nextBtn.classList.remove('hidden');
        else nextBtn.classList.add('hidden');
    }
}

function prevLightboxPhoto() {
    if (selectedPhotos.length <= 1) return;
    currentLightboxIndex = (currentLightboxIndex - 1 + selectedPhotos.length) % selectedPhotos.length;
    updateLightboxContent();
}

function nextLightboxPhoto() {
    if (selectedPhotos.length <= 1) return;
    currentLightboxIndex = (currentLightboxIndex + 1) % selectedPhotos.length;
    updateLightboxContent();
}

function closeLightbox() {
    const modal = document.getElementById('photoLightboxModal');
    if (!modal) return;
    modal.classList.add('opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
        const img = document.getElementById('lightboxImage');
        if (img) img.src = '';
    }, 200);
}

// Attach lightbox controls
const closeBtn = document.getElementById('closeLightboxBtn');
if (closeBtn) closeBtn.addEventListener('click', closeLightbox);

const prevBtn = document.getElementById('prevLightboxBtn');
if (prevBtn) prevBtn.addEventListener('click', prevLightboxPhoto);

const nextBtn = document.getElementById('nextLightboxBtn');
if (nextBtn) nextBtn.addEventListener('click', nextLightboxPhoto);

const lightboxModal = document.getElementById('photoLightboxModal');
if (lightboxModal) {
    lightboxModal.addEventListener('click', (e) => {
        if (e.target === lightboxModal) closeLightbox();
    });
}

document.addEventListener('keydown', (e) => {
    const modal = document.getElementById('photoLightboxModal');
    if (!modal || modal.classList.contains('hidden')) return;

    if (e.key === 'Escape') {
        closeLightbox();
    } else if (e.key === 'ArrowLeft') {
        prevLightboxPhoto();
    } else if (e.key === 'ArrowRight') {
        nextLightboxPhoto();
    }
});

// Evento al seleccionar desde el explorador de archivos
fotosInput.addEventListener('change', function() {
    if (this.files && this.files.length > 0) {
        addFiles(this.files);
    }
    this.value = '';
    syncInputFiles();
});

// Drag & Drop sobre el área de carga
if (uploadLabel) {
    ['dragenter', 'dragover'].forEach(eventName => {
        uploadLabel.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            uploadLabel.classList.add('border-primary', 'bg-primary/10');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadLabel.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            uploadLabel.classList.remove('border-primary', 'bg-primary/10');
        });
    });

    uploadLabel.addEventListener('drop', (e) => {
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            addFiles(e.dataTransfer.files);
        }
    });
}

// Botón Quitar Todas
clearFotosBtn.addEventListener('click', function(e) {
    e.preventDefault();
    selectedPhotos.forEach(item => {
        try { URL.revokeObjectURL(item.url); } catch (e) {}
    });
    selectedPhotos = [];
    fotosInput.value = '';
    syncInputFiles();
    renderPreviews();
});

// Garantizar que antes del submit del formulario el input contenga los archivos acumulados
const workshopForm = document.querySelector('form');
if (workshopForm) {
    workshopForm.addEventListener('submit', function() {
        syncInputFiles();
    });
}
</script>

<?php Layout::renderFooter(); ?>
