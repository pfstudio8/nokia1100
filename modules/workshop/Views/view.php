<?php
// modules/workshop/Views/view.php

// Variables inyectadas por WorkshopController::view()
$repair = $repair ?? [];
$repuestos = $repuestos ?? [];
$historial = $historial ?? [];
$productos_opt = $productos_opt ?? [];
$images = $images ?? [];
$id_reparacion = $id_reparacion ?? 0;
$error = $error ?? '';
$success = $success ?? '';

Layout::renderHead('Detalle de Orden - Nokia 1100');

if ($_SESSION['role'] === 'admin') {
    Layout::renderAdminSidebar('taller');
} else {
    Layout::renderEmployeeSidebar('taller');
}
?>

<main class="md:ml-64 p-6 md:p-10 pt-24 md:pt-14 pb-16 min-h-screen">
    <div class="max-w-6xl mx-auto">
        <?php // Encabezado de la Orden ?>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 pt-2">
            <div class="flex items-center gap-4">
                <a href="index.php" class="w-10 h-10 rounded-full bg-surface border border-border flex items-center justify-center text-text-muted hover:text-text-main transition-colors shadow-sm" title="Volver al listado">
                    <span class="material-symbols-outlined">arrow_back</span>
                </a>
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h2 class="text-2xl md:text-3xl font-display font-semibold text-text-main tracking-tight leading-tight">
                            Orden #<?php echo htmlspecialchars($repair['codigo_orden']); ?>
                        </h2>
                        <?php 
                        $badgeClasses = [
                            'Recibido' => 'bg-blue-500/10 text-blue-400 border-blue-500/30',
                            'En diagnóstico' => 'bg-amber-500/10 text-amber-400 border-amber-500/30',
                            'En reparación' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/30',
                            'Listo' => 'bg-primary/20 text-primary border-primary/40 shadow-sm shadow-primary/10',
                            'Entregado' => 'bg-green-500/10 text-green-400 border-green-500/30',
                            'Cancelado' => 'bg-red-500/10 text-red-400 border-red-500/30',
                        ];
                        $st = $repair['estado'];
                        $bClass = $badgeClasses[$st] ?? 'bg-surface text-text-muted border-border';
                        ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border uppercase tracking-wider <?php echo $bClass; ?>">
                            <span class="w-1.5 h-1.5 rounded-full <?php echo ($st === 'Listo' || $st === 'Entregado') ? 'bg-primary' : 'bg-current'; ?>"></span>
                            <?php echo htmlspecialchars($st); ?>
                        </span>
                    </div>
                    <p class="text-text-muted mt-1 text-xs flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[15px]">event</span>
                        Ingresada el <?php echo date('d/m/Y H:i', strtotime($repair['fecha_ingreso'])); ?> hs
                    </p>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <a href="index.php?action=print_receipt&id=<?php echo $id_reparacion; ?>" target="_blank" class="bg-surface hover:bg-surface-hover border border-border px-4 py-2.5 rounded-xl font-medium text-xs text-text-main transition-all flex items-center gap-2 shadow-sm">
                    <span class="material-symbols-outlined text-[18px]">print</span> Comprobante Ingreso
                </a>
            </div>
        </div>

        <?php // Barra de acción según el estado ?>
        <?php if ($repair['estado'] === 'Listo'): ?>
            <div class="glass-card rounded-xl px-4 py-2.5 mb-5 border border-primary/30 bg-primary/5 flex items-center justify-between gap-3 flex-wrap text-xs">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-bold uppercase tracking-wider text-primary text-[11px]">Equipo Listo</span>
                    <span class="text-text-muted">•</span>
                    <span class="text-text-muted">
                        Total a Cobrar: <strong class="text-primary font-semibold text-sm">$<?php echo number_format(floatval($repair['presupuesto']) + floatval($repair['costo_total']), 2); ?></strong>
                        <span class="text-[11px] text-text-muted">($<?php echo number_format($repair['presupuesto'], 2); ?> m.o.<?php if (floatval($repair['costo_total']) > 0) echo ' + $' . number_format($repair['costo_total'], 2) . ' rep.'; ?>)</span>
                    </span>
                </div>

                <button type="button" onclick="openSendToCashierModal()" class="px-3.5 py-1.5 rounded-lg bg-primary hover:bg-primary-hover text-background font-semibold text-xs tracking-wide transition-all flex items-center gap-1.5 cursor-pointer shadow-sm">
                    <span class="material-symbols-outlined text-[16px]">send</span>
                    Mandar a Ventas
                </button>
            </div>
        <?php elseif ($repair['estado'] === 'Entregado'): ?>
            <div class="glass-card rounded-2xl p-4 sm:p-5 mb-6 border border-green-500/30 bg-green-500/5 shadow-sm">
                <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-green-500/15 text-green-400 border border-green-500/30 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[22px]">verified</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-green-400">
                                Cobrado y Entregado
                            </span>
                            <span class="text-text-muted text-xs">•</span>
                            <span class="text-xs text-text-muted">
                                Total abonado: <strong class="text-green-400 font-semibold text-sm">$<?php echo number_format(floatval($repair['presupuesto']) + floatval($repair['costo_total']), 2); ?></strong>
                            </span>
                            <?php if (!empty($repair['fecha_entrega'])): ?>
                                <span class="text-xs text-text-muted">• <?php echo date('d/m/Y H:i', strtotime($repair['fecha_entrega'])); ?> hs</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-text-muted mt-0.5">La reparación fue cobrada en Caja y el dispositivo fue entregado al cliente.</p>
                    </div>
                </div>
            </div>
        <?php elseif ($repair['estado'] !== 'Cancelado'): ?>
            <div class="glass-card rounded-2xl p-4 sm:p-5 mb-6 border border-amber-500/25 bg-amber-500/5 shadow-sm">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/15 text-amber-400 border border-amber-500/30 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[22px]">engineering</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-400">
                                    En Taller (<?php echo htmlspecialchars($repair['estado']); ?>)
                                </span>
                                <span class="text-text-muted text-xs">•</span>
                                <span class="text-xs text-text-muted">
                                    Mano de obra estimada: <strong class="text-text-main">$<?php echo number_format($repair['presupuesto'], 2); ?></strong>
                                    <?php if (floatval($repair['costo_total']) > 0): ?>
                                        + Repuestos: <strong class="text-text-main">$<?php echo number_format($repair['costo_total'], 2); ?></strong>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <p class="text-xs text-text-muted mt-0.5">Al terminar el trabajo, cambiá el estado a <strong>"Listo"</strong> para habilitar el cobro en Caja.</p>
                        </div>
                    </div>
                    <div class="shrink-0">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-surface border border-border text-text-muted text-[11px] font-medium">
                            <span class="material-symbols-outlined text-[15px] text-amber-400">lock</span>
                            Requiere "Listo" para cobrar
                        </span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <?php // Columnas izquierdas (Información) ?>
            <div class="lg:col-span-2 space-y-6">
                <?php // Información de cliente y dispositivo ?>
                <div class="glass-card rounded-2xl p-6 border border-border/70 shadow-sm">
                    <div class="flex items-center justify-between border-b border-border/50 pb-3 mb-5">
                        <h3 class="text-lg font-display font-semibold text-text-main flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-xl">devices</span>
                            Información del Dispositivo y Cliente
                        </h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                        <div class="p-4 rounded-xl bg-surface/60 border border-border/60">
                            <span class="text-[11px] font-bold text-text-muted uppercase tracking-wider flex items-center gap-1.5 mb-2">
                                <span class="material-symbols-outlined text-[16px] text-primary">person</span>
                                Cliente
                            </span>
                            <p class="text-base font-semibold text-text-main"><?php echo htmlspecialchars($repair['cliente_nombre']); ?></p>
                            <?php if (!empty($repair['cliente_telefono'])): ?>
                                <p class="text-xs text-text-muted mt-1.5 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[15px] text-text-muted">call</span>
                                    <span><?php echo htmlspecialchars($repair['cliente_telefono']); ?></span>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="p-4 rounded-xl bg-surface/60 border border-border/60">
                            <span class="text-[11px] font-bold text-text-muted uppercase tracking-wider flex items-center gap-1.5 mb-2">
                                <span class="material-symbols-outlined text-[16px] text-primary">smartphone</span>
                                Equipo
                            </span>
                            <p class="text-base font-semibold text-text-main"><?php echo htmlspecialchars($repair['equipo_marca'] . ' ' . $repair['equipo_modelo']); ?></p>
                            <p class="text-xs text-text-muted mt-2">
                                <span class="font-mono text-text-main font-medium bg-background/80 px-2 py-1 rounded border border-border/50 text-[11px]">
                                    IMEI / Serie: <?php echo htmlspecialchars($repair['equipo_imei'] ?: 'No registrado'); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="p-4 rounded-xl bg-amber-500/5 border border-amber-500/25 flex items-start gap-3">
                            <span class="material-symbols-outlined text-amber-400 text-[20px] shrink-0 mt-0.5">report_problem</span>
                            <div class="flex-1">
                                <span class="text-[11px] font-bold text-amber-400 uppercase tracking-wider block mb-1">Falla Declarada por el Cliente</span>
                                <p class="text-sm text-text-main whitespace-pre-wrap leading-relaxed"><?php echo htmlspecialchars($repair['falla_declarada']); ?></p>
                            </div>
                        </div>
                        
                        <?php if($repair['observaciones']): ?>
                        <div class="p-4 rounded-xl bg-surface/40 border border-border/60 flex items-start gap-3">
                            <span class="material-symbols-outlined text-text-muted text-[20px] shrink-0 mt-0.5">notes</span>
                            <div class="flex-1">
                                <span class="text-[11px] font-bold text-text-muted uppercase tracking-wider block mb-1">Observaciones Físicas de Recepción</span>
                                <p class="text-sm text-text-main whitespace-pre-wrap leading-relaxed"><?php echo htmlspecialchars($repair['observaciones']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php // FOTOS ?>
                <div class="glass-card rounded-2xl p-6">
                    <div class="flex justify-between items-center border-b border-border/50 pb-2 mb-4">
                        <h3 class="text-lg font-display font-medium text-text-main flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-xl">photo_camera</span> Fotos del Equipo
                        </h3>
                    </div>

                    <form method="POST" action="" enctype="multipart/form-data" class="mb-6 space-y-4" id="image_upload_form">
                        <input type="hidden" name="action" value="add_image">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['session_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        
                        <div class="bg-surface/30 border border-border rounded-xl p-1 shadow-sm">
                            <div class="flex flex-col md:flex-row bg-background rounded-lg overflow-hidden border border-border/50">
                                
                                <?php // Sección Tipo de Foto ?>
                                <div class="w-full md:w-1/3 bg-surface/40 p-5 flex flex-col justify-center border-b md:border-b-0 md:border-r border-border/50 relative overflow-hidden">
                                    <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-full blur-2xl -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
                                    <div class="relative z-10">
                                        <label class="text-xs font-semibold uppercase tracking-wider text-text-muted mb-2 flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-[16px]">category</span>
                                            Etiqueta
                                        </label>
                                        <div class="relative">
                                            <select name="tipo_imagen" class="w-full appearance-none bg-background border border-border hover:border-primary/50 focus:border-primary px-4 py-2.5 rounded-lg text-sm font-medium text-text-main focus:outline-none transition-all cursor-pointer shadow-sm">
                                                <option value="Progreso">Progreso</option>
                                                <option value="Finalizado">Finalizado</option>
                                                <option value="Otro">Otro</option>
                                            </select>
                                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-text-muted text-[18px]">expand_more</span>
                                        </div>
                                        <p class="text-[11px] text-text-muted mt-2 leading-relaxed">Clasifica la foto para el historial.</p>
                                    </div>
                                </div>
                                
                                <?php // Sección Subir Foto ?>
                                <div class="w-full md:w-2/3 p-5 bg-background relative group transition-colors hover:bg-surface/30">
                                    <input type="file" name="foto" id="foto_upload" required accept="image/*" class="hidden">
                                    <label for="foto_upload" class="cursor-pointer flex flex-col sm:flex-row items-center justify-center gap-4 w-full h-full min-h-[120px] bg-transparent border-2 border-dashed border-border hover:border-primary/70 px-4 py-6 rounded-xl text-sm transition-all text-text-muted group-hover:text-primary">
                                        <div class="w-12 h-12 rounded-full bg-primary/5 text-primary border border-primary/20 flex items-center justify-center group-hover:scale-110 group-hover:bg-primary/10 transition-all shadow-sm shrink-0">
                                            <span class="material-symbols-outlined text-[24px]">cloud_upload</span>
                                        </div>
                                        <div class="text-center sm:text-left flex flex-col">
                                            <span id="file_name_display" class="font-medium text-text-main text-[15px]">Seleccionar o arrastrar imagen</span>
                                            <span class="text-[11px] font-medium text-text-muted mt-1 uppercase tracking-wider">JPG, PNG, WEBP (MAX. 5MB)</span>
                                        </div>
                                    </label>
                                </div>
                                
                            </div>
                        </div>
                        
                        <?php // Contenedor de vista previa ?>
                        <div id="image_preview_container" class="hidden flex flex-col sm:flex-row items-center gap-5 bg-surface/80 border border-border p-5 rounded-xl shadow-sm">
                            <div class="w-28 h-28 sm:w-36 sm:h-36 rounded-lg overflow-hidden border border-border shrink-0 bg-background flex items-center justify-center shadow-inner relative group/preview">
                                <img id="image_preview" src="" alt="Vista previa" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover/preview:opacity-100 transition-opacity flex items-center justify-center">
                                    <span class="material-symbols-outlined text-white text-3xl">visibility</span>
                                </div>
                            </div>
                            <div class="flex-1 flex flex-col items-center sm:items-start gap-3 w-full">
                                <div class="text-center sm:text-left w-full">
                                    <h4 class="text-text-main font-medium mb-1 flex items-center justify-center sm:justify-start gap-1.5">
                                        <span class="material-symbols-outlined text-primary text-[18px]">check_circle</span>
                                        Imagen seleccionada
                                    </h4>
                                    <p class="text-[13px] text-text-muted">Revisa que la imagen sea correcta antes de subirla.</p>
                                </div>
                                <div class="flex items-center gap-2 w-full mt-2">
                                    <button type="button" id="btn_cancelar_imagen" class="flex-1 sm:flex-none px-4 py-2 rounded-lg border border-border bg-background hover:bg-surface-hover text-sm font-medium transition-colors text-text-main">Cancelar</button>
                                    <button type="submit" class="flex-1 sm:flex-none bg-primary text-background px-6 py-2 rounded-lg text-sm font-medium hover:bg-primary-hover transition-colors flex items-center justify-center gap-2 shadow-sm">
                                        Subir Foto <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <script>
                    document.getElementById('foto_upload').addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        const previewContainer = document.getElementById('image_preview_container');
                        const previewImage = document.getElementById('image_preview');
                        const fileNameDisplay = document.getElementById('file_name_display');
                        const uploadLabel = document.querySelector('label[for="foto_upload"]');
                    
                        if (file) {
                            if (!file.type.startsWith('image/')) {
                                alert('Por favor, selecciona un archivo de imagen válido.');
                                this.value = '';
                                resetImagePreview();
                                return;
                            }
                            
                            fileNameDisplay.textContent = file.name;
                            
                            uploadLabel.classList.add('border-primary/50', 'bg-primary/5');
                            uploadLabel.classList.remove('border-border', 'bg-transparent');
                            uploadLabel.querySelector('.rounded-full').classList.add('bg-primary/10', 'border-primary/30', 'text-primary');
                            uploadLabel.querySelector('.rounded-full').classList.remove('bg-primary/5', 'border-primary/20');
                    
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                previewImage.src = e.target.result;
                                previewContainer.classList.remove('hidden');
                                setTimeout(() => {
                                    previewContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                                }, 100);
                            }
                            reader.readAsDataURL(file);
                        } else {
                            resetImagePreview();
                        }
                    });
                    
                    document.getElementById('btn_cancelar_imagen').addEventListener('click', function() {
                        document.getElementById('foto_upload').value = '';
                        resetImagePreview();
                    });
                    
                    function resetImagePreview() {
                        const previewContainer = document.getElementById('image_preview_container');
                        const previewImage = document.getElementById('image_preview');
                        const fileNameDisplay = document.getElementById('file_name_display');
                        const uploadLabel = document.querySelector('label[for="foto_upload"]');
                        
                        previewContainer.classList.add('hidden');
                        previewImage.src = '';
                        
                        fileNameDisplay.textContent = 'Seleccionar o arrastrar imagen';
                        
                        uploadLabel.classList.remove('border-primary/50', 'bg-primary/5');
                        uploadLabel.classList.add('border-border', 'bg-transparent');
                        uploadLabel.querySelector('.rounded-full').classList.remove('bg-primary/10', 'border-primary/30', 'text-primary');
                        uploadLabel.querySelector('.rounded-full').classList.add('bg-primary/5', 'border-primary/20');
                    }

                    document.getElementById('image_upload_form').addEventListener('submit', function(e) {
                        e.preventDefault();
                        const formData = new FormData(this);
                        
                        const btnSubmit = this.querySelector('button[type="submit"]');
                        const originalText = btnSubmit.innerHTML;
                        btnSubmit.innerHTML = 'Subiendo...';
                        btnSubmit.disabled = true;

                        fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            if (response.url.includes('&error=')) {
                                const params = new URLSearchParams(response.url.split('?')[1]);
                                const errorMsg = params.get('error') || 'Ocurrió un error en el servidor.';
                                if (typeof showToast === 'function') {
                                    showToast(errorMsg, 'error');
                                } else {
                                    alert(errorMsg);
                                }
                                btnSubmit.innerHTML = originalText;
                                btnSubmit.disabled = false;
                                return Promise.reject('Backend error');
                            }
                            return response.text();
                        })
                        .then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const newGrid = doc.getElementById('images_grid_container');
                            if(newGrid) {
                                document.getElementById('images_grid_container').innerHTML = newGrid.innerHTML;
                            } else {
                                if (typeof showToast === 'function') showToast('Error en el servidor al cargar la imagen.', 'error');
                            }
                            
                            this.reset();
                            resetImagePreview();
                            
                            btnSubmit.innerHTML = originalText;
                            btnSubmit.disabled = false;
                            
                            if (typeof showToast === 'function' && newGrid) {
                                showToast('Foto subida correctamente', 'success');
                            } else {
                                alert('¡Foto subida correctamente!');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            if (typeof showToast === 'function') {
                                showToast('Ocurrió un error al subir la imagen.', 'error');
                            } else {
                                alert('Ocurrió un error al subir la imagen.');
                            }
                            btnSubmit.innerHTML = originalText;
                            btnSubmit.disabled = false;
                        });
                    });
                    </script>

                    <div id="images_grid_container">
                        <?php if(!empty($images)): ?>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <?php foreach($images as $img): ?>
                            <div class="relative group">
                                <a href="<?php echo BASE_URL; ?>/assets/img/reparaciones/<?php echo htmlspecialchars($img['ruta_archivo']); ?>" target="_blank" class="block aspect-square overflow-hidden rounded-xl border border-border relative">
                                    <img src="<?php echo BASE_URL; ?>/assets/img/reparaciones/<?php echo htmlspecialchars($img['ruta_archivo']); ?>" alt="Foto" class="w-full h-full object-cover hover:scale-110 transition-transform duration-300">
                                </a>
                                <div class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[10px] p-1 text-center font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                                    <?php echo htmlspecialchars($img['tipo']); ?>
                                </div>
                                <form method="POST" action="" class="absolute top-2 right-2">
                                    <input type="hidden" name="action" value="delete_image">
                                    <input type="hidden" name="id_imagen" value="<?php echo $img['id_imagen']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['session_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" data-confirm="¿Seguro que deseas eliminar esta foto?" data-confirm-title="Eliminar Foto" data-destructive="true" class="w-7 h-7 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center shadow-lg transition-transform hover:scale-110">
                                        <span class="material-symbols-outlined text-[16px] pointer-events-none">close</span>
                                    </button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                            <p class="text-sm text-text-muted text-center py-4">No hay fotos subidas para esta reparación.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php // REPUESTOS ?>
                <div class="glass-card rounded-2xl p-6" id="repuestos_container">
                    <div class="flex justify-between items-center border-b border-border/50 pb-2 mb-4">
                        <h3 class="text-lg font-display font-medium text-text-main flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-xl">build</span> Repuestos Utilizados / Cotizados
                        </h3>
                    </div>
                    
                    <?php if($repair['estado'] === 'Entregado'): ?>
                        <div class="bg-surface/50 border border-border/50 text-text-muted text-xs p-3 rounded-lg mb-6 flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px] text-green-500">lock</span>
                            <span>Esta orden ya fue entregada y cobrada. La asignación de repuestos está bloqueada.</span>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="" class="flex gap-2 mb-6 ajax-repuesto-form">
                            <input type="hidden" name="action" value="add_repuesto">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['session_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <select name="id_producto" required class="flex-1 bg-surface border border-border px-3 py-2 rounded-lg text-sm text-text-main focus:outline-none focus:border-primary">
                                <option value="">Seleccionar repuesto del inventario...</option>
                                <?php foreach($productos_opt as $po): ?>
                                    <option value="<?php echo $po['id_producto']; ?>"><?php echo htmlspecialchars($po['nombre']); ?> - $<?php echo number_format($po['precio'],2); ?> (Stock: <?php echo $po['cantidad']; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="bg-surface hover:bg-surface-hover border border-border px-4 py-2 rounded-lg text-sm font-medium transition-colors text-text-main whitespace-nowrap">Asignar Pieza</button>
                        </form>
                    <?php endif; ?>
                    
                    <div class="table-container rounded-xl border border-border overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-surface/50 border-b border-border text-xs uppercase text-text-muted">
                                <tr>
                                    <th class="p-3">Repuesto</th>
                                    <th class="p-3 text-center">Cant.</th>
                                    <th class="p-3 text-right">Precio</th>
                                    <th class="p-3 text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/50">
                                <?php if(count($repuestos)>0): ?>
                                    <?php foreach($repuestos as $rep): ?>
                                    <tr class="hover:bg-surface/30 transition-colors">
                                        <td class="p-3"><?php echo htmlspecialchars($rep['nombre']); ?></td>
                                        <td class="p-3 text-center"><?php echo $rep['cantidad']; ?></td>
                                        <td class="p-3 text-right">$<?php echo number_format($rep['precio_unitario'], 2); ?></td>
                                        <td class="p-3 text-center">
                                            <?php if($repair['estado'] !== 'Entregado'): ?>
                                                <form method="POST" action="" class="inline-block ajax-repuesto-form">
                                                    <input type="hidden" name="action" value="remove_repuesto">
                                                    <input type="hidden" name="id_reparacion_repuesto" value="<?php echo $rep['id_reparacion_repuesto']; ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['session_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                    <button type="submit" class="text-red-400 hover:text-red-500 hover:bg-red-500/10 p-1.5 rounded-lg transition-colors">
                                                        <span class="material-symbols-outlined text-[16px] pointer-events-none">delete</span>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-text-muted text-xs inline-flex items-center gap-1" title="Orden entregada">
                                                    <span class="material-symbols-outlined text-[16px] text-text-muted">lock</span>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="p-4 text-center text-text-muted text-xs">Aún no se han asignado repuestos a esta orden.</td></tr>
                                <?php endif; ?>
                            </tbody>
                            <?php if($repair['costo_total'] > 0): ?>
                            <tfoot class="bg-surface/30">
                                <tr>
                                    <td colspan="2" class="p-3 text-right font-medium text-text-muted">Subtotal Repuestos:</td>
                                    <td class="p-3 text-right font-medium text-text-main">$<?php echo number_format($repair['costo_total'], 2); ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                    <p class="text-xs text-text-muted mt-2"><span class="material-symbols-outlined text-[14px] align-middle">info</span> Los repuestos asignados se descuentan automáticamente del stock general de inventario.</p>
                </div>
                
                <script>
                function attachRepuestoAjax() {
                    document.querySelectorAll('.ajax-repuesto-form').forEach(form => {
                        // Avoid attaching multiple listeners
                        if (form.dataset.ajaxAttached) return;
                        form.dataset.ajaxAttached = "true";

                        form.addEventListener('submit', async function(e) {
                            e.preventDefault();
                            
                            const actionInput = this.querySelector('input[name="action"]');
                            if (actionInput && actionInput.value === 'remove_repuesto') {
                                if (typeof showConfirmModal === 'function') {
                                    const confirmed = await showConfirmModal('Quitar Repuesto', '¿Seguro que deseas quitar este repuesto de la orden?', 'Sí, quitar', 'Cancelar', true);
                                    if (!confirmed) return;
                                } else {
                                    if (!confirm('¿Quitar este repuesto?')) return;
                                }
                            }

                            const formData = new FormData(this);
                            const btnSubmit = this.querySelector('button[type="submit"]');
                            const originalHtml = btnSubmit.innerHTML;
                            
                            btnSubmit.innerHTML = '<span class="material-symbols-outlined animate-spin text-[16px]">sync</span>';
                            btnSubmit.disabled = true;

                            fetch(window.location.href, {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => {
                                if (response.url.includes('&error=')) {
                                    const params = new URLSearchParams(response.url.split('?')[1]);
                                    const errorMsg = params.get('error') || 'Ocurrió un error en el servidor.';
                                    if (typeof showToast === 'function') {
                                        showToast(errorMsg, 'error');
                                    } else {
                                        alert(errorMsg);
                                    }
                                    btnSubmit.innerHTML = originalHtml;
                                    btnSubmit.disabled = false;
                                    return Promise.reject('Backend error');
                                }
                                return response.text();
                            })
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                const newContainer = doc.getElementById('repuestos_container');
                                
                                if(newContainer) {
                                    document.getElementById('repuestos_container').innerHTML = newContainer.innerHTML;
                                    attachRepuestoAjax(); // Re-attach listeners to new elements
                                } else {
                                    if (typeof showToast === 'function') showToast('Error en el servidor al cargar los datos.', 'error');
                                    btnSubmit.innerHTML = originalHtml;
                                    btnSubmit.disabled = false;
                                }
                                
                                if (typeof showToast === 'function' && newContainer) {
                                    if (actionInput && actionInput.value === 'remove_repuesto') {
                                        showToast('Repuesto devuelto al inventario', 'info');
                                    } else {
                                        showToast('Repuesto asignado correctamente', 'success');
                                    }
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                if (typeof showToast === 'function') {
                                    showToast('Ocurrió un error al procesar el repuesto.', 'error');
                                } else {
                                    alert('Ocurrió un error al procesar el repuesto.');
                                }
                                btnSubmit.innerHTML = originalHtml;
                                btnSubmit.disabled = false;
                            });
                        });
                    });
                }
                // Run once on load
                attachRepuestoAjax();
                </script>
            </div>

            <?php // Columnas derechas (Estado y Línea de tiempo) ?>
            <div class="space-y-6">
                
                <?php // Cambio de estado y presupuesto ?>
                <form method="POST" action="" class="glass-card rounded-2xl p-6 border border-border/70 shadow-sm">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['session_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <h3 class="text-lg font-display font-semibold text-text-main mb-4 border-b border-border/50 pb-2.5 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-xl">tune</span> Control de la Orden
                    </h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-text-muted mb-1.5">Estado de Reparación</label>
                            <select name="estado" id="estado_select" class="w-full bg-surface border border-border px-3.5 py-2.5 rounded-xl text-sm font-medium text-text-main focus:outline-none focus:border-primary transition-colors cursor-pointer">
                                <?php 
                                $estados = ['Recibido', 'En diagnóstico', 'En reparación', 'Listo', 'Cancelado'];
                                foreach ($estados as $est) {
                                    $sel = ($repair['estado'] === $est) ? 'selected' : '';
                                    echo "<option value='$est' $sel>$est</option>";
                                }
                                if ($repair['estado'] === 'Entregado') {
                                    echo "<option value='Entregado' selected>Entregado (Por Ventas)</option>";
                                }
                                ?>
                            </select>
                            <?php if ($repair['estado'] === 'Listo'): ?>
                                <p class="text-[11px] text-primary flex items-center gap-1 mt-1.5 font-medium">
                                    <span class="material-symbols-outlined text-[15px]">check_circle</span>
                                    Estado Listo activo: habilitado para mandar a Ventas.
                                </p>
                            <?php else: ?>
                                <p class="text-[11px] text-text-muted flex items-center gap-1 mt-1.5">
                                    <span class="material-symbols-outlined text-[15px]">info</span>
                                    Al terminar el trabajo, cambiá a "Listo" para cobrar.
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-text-muted mb-1.5">Presupuesto / Mano de Obra ($)</label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-sm font-bold">$</span>
                                <input type="number" step="0.01" min="0" name="presupuesto" id="presupuesto_input" value="<?php echo $repair['presupuesto']; ?>" class="w-full bg-surface border border-border pl-8 pr-3.5 py-2.5 rounded-xl text-sm font-medium text-text-main focus:outline-none focus:border-primary transition-colors">
                            </div>
                            <p class="text-[11px] text-text-muted mt-1.5">Mano de obra o servicio base. Se conservará al mandar a caja.</p>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-text-muted mb-1.5">Nota Interna (Historial)</label>
                            <textarea name="nota_historial" rows="2" placeholder="Opcional: detalles del trabajo técnico..." class="w-full bg-surface border border-border px-3.5 py-2 rounded-xl text-sm text-text-main focus:outline-none focus:border-primary transition-colors"></textarea>
                        </div>

                        <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-background font-bold rounded-xl py-2.5 text-sm transition-all flex items-center justify-center gap-2 shadow-md shadow-primary/20 cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]">save</span> Guardar Cambios
                        </button>

                        <?php if ($repair['estado'] === 'Listo'): ?>
                            <button type="button" onclick="openSendToCashierModal()" class="w-full bg-emerald-500 hover:bg-emerald-600 text-background font-bold rounded-xl py-2.5 text-sm transition-all flex items-center justify-center gap-2 shadow-md shadow-emerald-500/20 cursor-pointer">
                                <span class="material-symbols-outlined text-[18px]">send</span> Mandar a Ventas para Cobro
                            </button>
                        <?php endif; ?>
                    </div>
                </form>

                <script>
                    // Validaciones u otros scripts del form si son necesarios
                </script>
                
                <?php // Seguimiento / Línea de tiempo ?>
                <div class="glass-card rounded-2xl p-6">
                    <h3 class="text-lg font-display font-medium text-text-main mb-4 border-b border-border/50 pb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-xl">history_toggle_off</span> Historial
                    </h3>
                    
                    <div class="relative pl-6 border-l-2 border-border/40 space-y-6 pt-2 pb-2">
                        <?php foreach($historial as $idx => $h): 
                            $isFirst = ($idx === 0);
                        ?>
                        <div class="relative group">
                            <?php // Icono ?>
                            <div class="absolute -left-[31px] top-1.5 w-3.5 h-3.5 rounded-full border-2 transition-colors duration-300 <?php echo $isFirst ? 'border-primary bg-background shadow-[0_0_10px_rgba(14,165,160,0.5)]' : 'border-text-muted/50 bg-background group-hover:border-primary/50'; ?>"></div>
                            
                            <?php // Contenido ?>
                            <div class="bg-surface/40 border border-border/60 p-3.5 rounded-xl hover:bg-surface transition-colors duration-300 hover:border-border">
                                <div class="flex justify-between items-start mb-2 gap-2">
                                    <span class="text-[11px] font-bold <?php echo $isFirst ? 'text-primary' : 'text-text-main'; ?> uppercase tracking-widest leading-none mt-1">
                                        <?php echo htmlspecialchars($h['estado_nuevo']); ?>
                                    </span>
                                    <span class="text-[9px] text-text-muted font-medium bg-background px-2 py-1 rounded-md border border-border/50 shrink-0 uppercase tracking-wider">
                                        <?php echo date('d M, H:i', strtotime($h['fecha_cambio'])); ?>
                                    </span>
                                </div>
                                <?php if($h['nota']): ?>
                                    <p class="text-[11px] text-text-muted mb-3 leading-relaxed bg-background/50 p-2 rounded-lg border border-border/30 border-l-2 border-l-primary/50">
                                        <?php echo nl2br(htmlspecialchars($h['nota'])); ?>
                                    </p>
                                <?php endif; ?>
                                <div class="flex items-center gap-1.5 mt-auto pt-2 border-t border-border/30">
                                    <div class="w-4 h-4 rounded-full bg-primary/10 border border-primary/20 text-primary flex items-center justify-center text-[8px] font-bold">
                                        <span class="material-symbols-outlined text-[10px]">person</span>
                                    </div>
                                    <p class="text-[10px] text-text-muted font-medium"><?php echo htmlspecialchars($h['nombre_usuario'] ?? 'Sistema'); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>

    </div>
</main>

<?php // Modal: Enviar a Caja ?>
<div id="modal-send-to-cashier" class="fixed inset-0 bg-background/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-surface border border-border/80 rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
        <div class="p-6 border-b border-border/50 flex justify-between items-center bg-surface-hover/30">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/20 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[24px]">point_of_sale</span>
                </div>
                <div>
                    <h3 class="text-lg font-display font-semibold text-text-main">Mandar a Ventas para Cobro</h3>
                    <p class="text-xs text-text-muted">Orden #<?php echo htmlspecialchars($repair['codigo_orden']); ?> - <?php echo htmlspecialchars($repair['equipo_marca'] . ' ' . $repair['equipo_modelo']); ?></p>
                </div>
            </div>
            <button type="button" onclick="closeSendToCashierModal()" class="w-8 h-8 rounded-lg bg-surface border border-border text-text-muted hover:text-text-main flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>

        <form method="POST" action="" class="p-6 space-y-4">
            <input type="hidden" name="action" value="send_to_cashier">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['session_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

            <div class="bg-background/60 p-4 rounded-xl border border-border/60 space-y-2">
                <div class="flex justify-between text-xs text-text-muted">
                    <span>Mano de Obra / Servicio Técnico:</span>
                    <span class="font-medium text-text-main" id="preview_mano_obra">$<?php echo number_format($repair['presupuesto'], 2); ?></span>
                </div>
                <div class="flex justify-between text-xs text-text-muted">
                    <span>Repuestos Utilizados (<?php echo count($repuestos); ?> pieza/s):</span>
                    <span class="font-medium text-text-main">$<?php echo number_format($repair['costo_total'], 2); ?></span>
                </div>
                <div class="border-t border-border/40 pt-2 flex justify-between text-sm font-semibold text-text-main">
                    <span>Total a Cobrar al Cliente:</span>
                    <span class="text-primary text-base font-display" id="preview_total_cobrar">$<?php echo number_format(floatval($repair['presupuesto']) + floatval($repair['costo_total']), 2); ?></span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-text-muted uppercase tracking-wider mb-1">Presupuesto / Mano de Obra Base ($)</label>
                <input type="number" step="0.01" min="0" name="presupuesto" id="cashier_presupuesto" value="<?php echo $repair['presupuesto']; ?>" required oninput="recalcCashierTotal(<?php echo floatval($repair['costo_total']); ?>)" class="w-full bg-surface border border-border px-3 py-2.5 rounded-lg text-sm text-text-main focus:outline-none focus:border-primary">
                <span class="text-[11px] text-text-muted mt-0.5 block">Se conserva el presupuesto fijado previamente en la orden. Podés editarlo aquí si lo deseas antes de enviarlo.</span>
            </div>

            <div>
                <label class="block text-xs font-semibold text-text-muted uppercase tracking-wider mb-1">Informe de Resolución / Trabajo Realizado (Opcional)</label>
                <textarea name="nota_resolucion" rows="2" placeholder="Ej: Se instaló módulo nuevo original y se limpió pin de carga. Equipo probado al 100%." class="w-full bg-surface border border-border px-3 py-2 rounded-lg text-sm text-text-main focus:outline-none focus:border-primary"></textarea>
            </div>

            <div class="pt-2 flex justify-end gap-3 border-t border-border/40">
                <button type="button" onclick="closeSendToCashierModal()" class="px-4 py-2.5 rounded-lg border border-border text-xs font-medium text-text-muted hover:text-text-main transition-colors">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-lg bg-primary hover:bg-primary-hover text-background font-semibold text-xs transition-colors flex items-center gap-1.5 shadow-lg shadow-primary/20">
                    <span class="material-symbols-outlined text-[16px]">send</span>
                    Confirmar y Enviar a Ventas
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openSendToCashierModal() {
    // Sincronizar con el presupuesto ingresado en el input debajo del estado
    const mainInput = document.getElementById('presupuesto_input');
    const cashierInput = document.getElementById('cashier_presupuesto');
    const costoRepuestos = <?php echo floatval($repair['costo_total'] ?? 0); ?>;
    
    if (mainInput && cashierInput && mainInput.value.trim() !== '') {
        const parsedVal = parseFloat(mainInput.value.toString().replace(',', '.'));
        if (!isNaN(parsedVal) && parsedVal >= 0) {
            cashierInput.value = parsedVal.toFixed(2);
        }
    }
    
    recalcCashierTotal(costoRepuestos);
    const modal = document.getElementById('modal-send-to-cashier');
    if (modal) modal.classList.remove('hidden');
}

function closeSendToCashierModal() {
    const modal = document.getElementById('modal-send-to-cashier');
    if (modal) modal.classList.add('hidden');
}

function recalcCashierTotal(costoRepuestos) {
    const input = document.getElementById('cashier_presupuesto');
    const manoObraEl = document.getElementById('preview_mano_obra');
    const totalEl = document.getElementById('preview_total_cobrar');
    if (!input) return;
    const val = parseFloat(input.value.toString().replace(',', '.')) || 0;
    const total = val + (parseFloat(costoRepuestos) || 0);
    if (manoObraEl) manoObraEl.textContent = '$' + val.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (totalEl) totalEl.textContent = '$' + total.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Sonido nítido e inmediato al poner la reparación en "Listo"
function playInstantReadySound() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        if (ctx.state === 'suspended') {
            ctx.resume();
        }
        const notes = [
            { freq: 659.25, time: 0, dur: 0.12 },
            { freq: 880.00, time: 0.10, dur: 0.28 }
        ];
        notes.forEach(function(n) {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(n.freq, ctx.currentTime + n.time);
            gain.gain.setValueAtTime(0.001, ctx.currentTime + n.time);
            gain.gain.exponentialRampToValueAtTime(0.3, ctx.currentTime + n.time + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + n.time + n.dur);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(ctx.currentTime + n.time);
            osc.stop(ctx.currentTime + n.time + n.dur);
        });
    } catch(e) {}
}

function broadcastOrderReady() {
    try {
        localStorage.setItem('nokia1100_order_ready_ping', Date.now().toString());
        if (window.BroadcastChannel) {
            const bc = new BroadcastChannel('nokia1100_repairs');
            bc.postMessage({ type: 'order_ready', timestamp: Date.now() });
        }
    } catch(e) {}
}

document.addEventListener('DOMContentLoaded', function() {
    const estadoSelect = document.getElementById('estado_select');

    // Solo suena al enviar el formulario con "Guardar Cambios" si está seleccionado "Listo"
    const formControl = estadoSelect ? estadoSelect.closest('form') : null;
    if (formControl) {
        formControl.addEventListener('submit', function() {
            if (estadoSelect && estadoSelect.value === 'Listo') {
                playInstantReadySound();
                broadcastOrderReady();
            }
        });
    }

    <?php if (isset($_GET['success']) && ($repair['estado'] === 'Listo' || strpos($_GET['success'], 'Listo') !== false)): ?>
    playInstantReadySound();
    broadcastOrderReady();
    <?php endif; ?>
});
</script>

<?php if ($repair['estado'] !== 'Entregado'): ?>
<!-- Modal de Notificación en Tiempo Real: Cobrado y Entregado -->
<div id="modal-realtime-delivered" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-background/80 backdrop-blur-md hidden transition-all duration-300">
    <div class="glass-card max-w-md w-full rounded-2xl border-2 border-emerald-500/50 shadow-2xl shadow-emerald-500/25 p-6 relative overflow-hidden">
        <div class="absolute -top-12 -right-12 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="text-center">
            <div class="w-16 h-16 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center mx-auto mb-4 shadow-lg shadow-emerald-500/20">
                <span class="material-symbols-outlined text-[36px]">point_of_sale</span>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 mb-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                ¡Aviso en Tiempo Real!
            </span>
            <h3 class="text-2xl font-display font-bold text-text-main mb-2">
                ¡Equipo Cobrado y Entregado!
            </h3>
            <p class="text-sm text-text-muted mb-4">
                El cajero acaba de registrar el cobro y entregar la orden <strong class="text-text-main font-semibold">#<?php echo htmlspecialchars($repair['codigo_orden']); ?></strong> al cliente en caja.
            </p>
            <div class="bg-surface/80 rounded-xl p-3 border border-border/60 text-xs text-text-muted mb-5 text-left space-y-1.5">
                <div class="flex justify-between">
                    <span>Equipo:</span>
                    <strong class="text-text-main"><?php echo htmlspecialchars($repair['equipo_marca'] . ' ' . $repair['equipo_modelo']); ?></strong>
                </div>
                <div class="flex justify-between">
                    <span>Cliente:</span>
                    <strong class="text-text-main"><?php echo htmlspecialchars($repair['cliente_nombre']); ?></strong>
                </div>
                <div class="flex justify-between">
                    <span>Estado en sistema:</span>
                    <strong class="text-emerald-400 font-semibold">Entregado</strong>
                </div>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="window.location.reload();" class="w-full py-3 px-4 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-background font-bold text-sm shadow-lg shadow-emerald-500/25 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">refresh</span>
                    Actualizar Ficha Ahora
                </button>
            </div>
            <p class="text-[11px] text-text-muted mt-3" id="realtime-countdown-txt">Recargando automáticamente en 5s...</p>
        </div>
    </div>
</div>

<script>
// Monitoreo en tiempo real de cobro y entrega en caja
(function() {
    const repairId = <?php echo (int)$id_reparacion; ?>;
    let pollInterval = null;
    let isDeliveredNotified = false;

    function playDeliveryChime() {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            const notes = [
                { freq: 523.25, time: 0, dur: 0.15 },
                { freq: 659.25, time: 0.12, dur: 0.18 },
                { freq: 783.99, time: 0.25, dur: 0.35 }
            ];
            notes.forEach(function(n) {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(n.freq, ctx.currentTime + n.time);
                gain.gain.setValueAtTime(0.001, ctx.currentTime + n.time);
                gain.gain.exponentialRampToValueAtTime(0.25, ctx.currentTime + n.time + 0.03);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + n.time + n.dur);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(ctx.currentTime + n.time);
                osc.stop(ctx.currentTime + n.time + n.dur);
            });
        } catch (e) {
            // Audio no soportado o bloqueado
        }
    }

    function checkStatus() {
        if (isDeliveredNotified) return;
        if (document.hidden) return;

        fetch('index.php?action=check_status&id=' + repairId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data && data.success && data.is_delivered) {
                isDeliveredNotified = true;
                if (pollInterval) clearInterval(pollInterval);

                // Sonido de aviso
                playDeliveryChime();

                // Mostrar modal
                const modal = document.getElementById('modal-realtime-delivered');
                if (modal) {
                    modal.classList.remove('hidden');
                }

                // Cuenta regresiva para auto-recarga
                let seconds = 5;
                const countEl = document.getElementById('realtime-countdown-txt');
                const timer = setInterval(function() {
                    seconds--;
                    if (countEl) {
                        countEl.textContent = 'Recargando automáticamente en ' + seconds + 's...';
                    }
                    if (seconds <= 0) {
                        clearInterval(timer);
                        window.location.reload();
                    }
                }, 1000);
            }
        })
        .catch(function(err) {
            // Silencioso ante fallos temporales de red
        });
    }

    // Polling cada 10 segundos
    pollInterval = setInterval(checkStatus, 10000);
})();
</script>
<?php endif; ?>

<?php Layout::renderFooter(); ?>
