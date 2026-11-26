// assets/js/pages/admin_users.js

function openAddUserModal() {
    const modal = document.getElementById('add-user-modal');
    const card = modal.querySelector('.glass-card');
    modal.classList.remove('opacity-0', 'pointer-events-none');
    card.classList.remove('scale-95');
    card.classList.add('scale-100');
}

function closeAddUserModal() {
    const modal = document.getElementById('add-user-modal');
    const card = modal.querySelector('.glass-card');
    card.classList.remove('scale-100');
    card.classList.add('scale-95');
    modal.classList.add('opacity-0', 'pointer-events-none');
    document.getElementById('add-user-form').reset();
    updatePasswordRuleIndicators('', 'add-user-password-rules');
}

// Validación de contraseñas coincidentes
const addUserForm = document.getElementById('add-user-form');
if (addUserForm) {
    addUserForm.addEventListener('submit', function(e) {
        const pass = document.getElementById('modal-password').value;
        const passConfirm = document.getElementById('modal-password-confirm').value;
        
        if (pass.length < 8 || !/[A-Z]/.test(pass) || !/[a-z]/.test(pass) || !/[0-9]/.test(pass) || !/[^a-zA-Z0-9]/.test(pass)) {
            e.preventDefault();
            if (typeof showToast === 'function') {
                showToast('La contraseña debe tener mínimo 8 caracteres e incluir mayúscula, minúscula, número y carácter especial.', 'warning');
            }
            return;
        }

        if (pass !== passConfirm) {
            e.preventDefault();
            if (typeof showToast === 'function') {
                showToast('Las contraseñas no coinciden', 'error');
            }
            return;
        }
    });
}

const editUserForm = document.getElementById('edit-user-form');
if (editUserForm) {
    editUserForm.addEventListener('submit', function(e) {
        const pass = document.getElementById('edit-modal-password').value;
        if (pass && (pass.length < 8 || !/[A-Z]/.test(pass) || !/[a-z]/.test(pass) || !/[0-9]/.test(pass) || !/[^a-zA-Z0-9]/.test(pass))) {
            e.preventDefault();
            if (typeof showToast === 'function') {
                showToast('La nueva contraseña debe tener mínimo 8 caracteres e incluir mayúscula, minúscula, número y carácter especial.', 'warning');
            }
            return;
        }
    });
}

// Actualización dinámica de los indicadores de requisitos de contraseña
function updatePasswordRuleIndicators(password, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const rules = {
        minlength: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[^a-zA-Z0-9]/.test(password)
    };

    for (const [rule, isValid] of Object.entries(rules)) {
        const item = container.querySelector('[data-rule="' + rule + '"]');
        if (!item) continue;
        const icon = item.querySelector('.rule-icon');
        if (isValid) {
            item.classList.remove('text-text-muted');
            item.style.color = '#156835';
            item.style.fontWeight = '600';
            if (icon) {
                icon.textContent = 'check_circle';
                icon.style.color = '#156835';
            }
        } else {
            item.classList.add('text-text-muted');
            item.style.color = '';
            item.style.fontWeight = '';
            if (icon) {
                icon.textContent = 'radio_button_unchecked';
                icon.style.color = '';
            }
        }
    }
}

// Escuchar escritura en el campo de contraseña de creación
const addPasswordInput = document.getElementById('modal-password');
if (addPasswordInput) {
    addPasswordInput.addEventListener('input', function() {
        updatePasswordRuleIndicators(this.value, 'add-user-password-rules');
    });
}

// Escuchar escritura en el campo de contraseña de edición
const editPasswordInput = document.getElementById('edit-modal-password');
if (editPasswordInput) {
    editPasswordInput.addEventListener('input', function() {
        updatePasswordRuleIndicators(this.value, 'edit-user-password-rules');
    });
}


// --- Módulos y Permisos ---
const allSystemModules = [
    { id: 'dashboard', label: 'Inicio', icon: 'home', desc: 'Panel principal de operaciones' },
    { id: 'venta', label: 'Generar Venta / Ventas', icon: 'payments', desc: 'Punto de venta y registro de transacciones' },
    { id: 'venta_anular', parent: 'venta', label: 'Anular ventas / Notas de crédito', icon: 'cancel', desc: 'Permite anular ventas registradas y emitir notas de crédito' },
    { id: 'venta_descuento', parent: 'venta', label: 'Aplicar descuentos (hasta 15%)', icon: 'percent', desc: 'Habilita descuentos en el punto de venta hasta el tope de empleado' },
    { id: 'caja', label: 'Caja', icon: 'point_of_sale', desc: 'Apertura, arqueo, movimientos de efectivo y consulta de caja' },
    { id: 'caja_movimientos', parent: 'caja', label: 'Registrar ingresos/egresos manuales', icon: 'swap_vert', desc: 'Permite cargar movimientos manuales en la caja abierta' },
    { id: 'cierre_caja', parent: 'caja', label: 'Supervisión y Cierre de Caja', icon: 'lock_person', desc: 'Autoriza cerrar caja, registrar contra-asientos y retiros mayores a $50.000' },
    { id: 'cobrar_reparaciones', label: 'Cobrar Reparaciones', icon: 'price_check', desc: 'Caja para entrega de reparaciones terminadas' },
    { id: 'taller', label: 'Taller', icon: 'home_repair_service', desc: 'Gestión y servicio técnico de reparaciones' },
    { id: 'inventario', label: 'Consultar Stock / Inventario', icon: 'inventory_2', desc: 'Catálogo de productos, existencias y precios' },
    { id: 'clientes', label: 'Clientes', icon: 'contact_page', desc: 'Directorio e historial de clientes' },
    { id: 'reportes', label: 'Reportes y Estadísticas', icon: 'analytics', desc: 'Métricas financieras, ventas y taller' },
    { id: 'reportes_financieros', parent: 'reportes', label: 'Ver pestaña Finanzas (ingresos y rentabilidad)', icon: 'account_balance', desc: 'Muestra ingresos netos, rentabilidad y métricas financieras' },
    { id: 'proveedores', label: 'Proveedores y Compras', icon: 'local_shipping', desc: 'Directorio de proveedores y órdenes de compra' },
    { id: 'auditoria', label: 'Auditoría del Sistema', icon: 'security', desc: 'Registro de seguridad y eventos' }
];

function selectAllModules(checked) {
    const checkboxes = document.querySelectorAll('#modules-list input[name="modulos[]"]');
    checkboxes.forEach(cb => { cb.checked = checked; });
}

function toggleModuleRow(event, checkboxId) {
    if (event.target.tagName.toLowerCase() === 'input' || event.target.tagName.toLowerCase() === 'label') {
        return;
    }
    const cb = document.getElementById(checkboxId);
    if (cb) {
        cb.checked = !cb.checked;
        cb.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

function handlePermissionCheckboxChange(cb) {
    const id = cb.value;
    const parentId = cb.dataset.parent || '';
    if (cb.checked && parentId) {
        const parentCb = document.getElementById(`mod-${parentId}`);
        if (parentCb && !parentCb.checked) {
            parentCb.checked = true;
        }
    }
    if (!cb.checked) {
        document.querySelectorAll(`#modules-list input[name="modulos[]"][data-parent="${id}"]`).forEach(childCb => {
            childCb.checked = false;
        });
    }
}

function openModulePermissions(id, fullName, role, modulosStr) {
    if (role === 'admin') {
        if (typeof showToast === 'function') {
            showToast('Los administradores poseen acceso global a todos los módulos por diseño.', 'info');
        }
        return;
    }
    const tableContainer = document.getElementById('users-table-container');
    const permCard = document.getElementById('permission-card');
    
    // Asignar datos básicos
    document.getElementById('perm-user-id').value = id;
    document.getElementById('perm-user-name').textContent = fullName;
    document.getElementById('perm-user-role').textContent = 'Empleado';
    
    // Todos los módulos del sistema disponibles para asignación
    const modules = allSystemModules;
    
    // Parsear módulos permitidos
    let isDefault = (modulosStr === 'DEFAULT' || modulosStr === null || modulosStr === undefined);
    let allowed = [];
    if (!isDefault) {
        allowed = modulosStr ? modulosStr.split(',').map(s => s.trim()).filter(Boolean) : [];
    } else {
        // Por defecto para operarios/empleados: módulos base iniciales
        allowed = ['dashboard', 'venta', 'venta_anular', 'caja', 'caja_movimientos', 'inventario', 'clientes'];
    }
    
    // Renderizar la lista con checkboxes y selector rápido
    const listContainer = document.getElementById('modules-list');
    
    let html = `
        <div class="flex items-center justify-between pb-2 mb-3 border-b border-border/50 text-xs text-text-muted">
            <span class="font-medium text-text-main">Módulos del Sistema (${modules.length})</span>
            <div class="flex items-center gap-2">
                <button type="button" onclick="selectAllModules(true)" class="text-xs text-primary hover:underline font-semibold bg-transparent border-0 cursor-pointer p-0">Marcar todos</button>
                <span class="opacity-40">•</span>
                <button type="button" onclick="selectAllModules(false)" class="text-xs text-text-muted hover:text-text-main font-semibold bg-transparent border-0 cursor-pointer p-0">Desmarcar todos</button>
            </div>
        </div>
    `;
    
    modules.forEach(mod => {
        const isChecked = allowed.includes(mod.id);
        const checkboxId = `mod-${mod.id}`;
        const parentAttr = mod.parent ? `data-parent="${mod.parent}"` : '';
        const childIndent = mod.parent ? 'ml-6 pl-3 border-l-2 border-primary/30' : '';
        
        html += `
            <div onclick="toggleModuleRow(event, '${checkboxId}')" class="${childIndent} flex items-center justify-between p-3.5 rounded-xl border border-border/70 bg-surface/40 hover:bg-surface hover:border-primary/40 transition-all cursor-pointer select-none">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-primary ${mod.parent ? 'text-[19px]' : 'text-[22px]'} flex-shrink-0">${mod.icon}</span>
                    <div>
                        <label for="${checkboxId}" class="text-sm font-semibold text-text-main cursor-pointer select-none block leading-tight tracking-wide">${mod.label}</label>
                        <span class="text-[11px] text-text-muted block mt-1">${mod.desc}</span>
                    </div>
                </div>
                <input type="checkbox" name="modulos[]" value="${mod.id}" id="${checkboxId}" ${parentAttr} ${isChecked ? 'checked' : ''}
                       onchange="handlePermissionCheckboxChange(this)"
                       class="w-4 h-4 rounded border-border text-primary bg-background focus:ring-primary/20 cursor-pointer ml-3 flex-shrink-0" onclick="event.stopPropagation()">
            </div>
        `;
    });
    
    listContainer.innerHTML = html;
    
    // Mensaje informativo sobre estado de permisos
    const noteContainer = document.getElementById('perm-note');
    if (isDefault) {
        noteContainer.innerHTML = `
            <div class="bg-primary/10 border border-primary/20 text-primary px-3.5 py-2.5 rounded-xl text-xs flex gap-2.5 items-center">
                <span class="material-symbols-outlined" style="font-size:1.15rem; flex-shrink:0;">info</span>
                <span>Este usuario utiliza la configuración inicial por defecto. Al guardar se aplicarán los permisos seleccionados.</span>
            </div>
        `;
    } else {
        noteContainer.innerHTML = `
            <div class="bg-secondary/10 border border-secondary/20 text-secondary px-3.5 py-2.5 rounded-xl text-xs flex gap-2.5 items-center">
                <span class="material-symbols-outlined" style="font-size:1.15rem; flex-shrink:0;">shield</span>
                <span>Permisos personalizados activos para este usuario.</span>
            </div>
        `;
    }
    
    // Mostrar Overlay y Panel
    const permOverlay = document.getElementById('permission-overlay');
    
    permOverlay.classList.remove('hidden');
    permCard.classList.remove('hidden');
    
    // Trigger de animación CSS
    requestAnimationFrame(() => {
        permOverlay.classList.remove('opacity-0');
        permCard.classList.remove('translate-x-full');
    });
}

function closeModulePermissions() {
    const permCard = document.getElementById('permission-card');
    const permOverlay = document.getElementById('permission-overlay');
    
    permCard.classList.add('translate-x-full');
    permOverlay.classList.add('opacity-0');
    
    setTimeout(() => {
        permCard.classList.add('hidden');
        permOverlay.classList.add('hidden');
    }, 300);
}

function openEditUserModal(user) {
    const modal = document.getElementById('edit-user-modal');
    const card = modal.querySelector('.glass-card');
    
    document.getElementById('edit-modal-id-usuario').value = user.id_usuario;
    document.getElementById('edit-modal-id-persona').value = user.id_persona;
    document.getElementById('edit-modal-nombre').value = user.nombre;
    document.getElementById('edit-modal-apellido').value = user.apellido;
    document.getElementById('edit-modal-dni').value = user.dni;
    document.getElementById('edit-modal-email').value = user.email;
    document.getElementById('edit-modal-telefono').value = user.telefono || '';
    document.getElementById('edit-modal-direccion').value = user.direccion || '';
    document.getElementById('edit-modal-username').value = user.nombre_usuario;
    document.getElementById('edit-modal-rol').value = user.rol;
    
    modal.classList.remove('opacity-0', 'pointer-events-none');
    card.classList.remove('scale-95');
    card.classList.add('scale-100');
}

function closeEditUserModal() {
    const modal = document.getElementById('edit-user-modal');
    const card = modal.querySelector('.glass-card');
    card.classList.remove('scale-100');
    card.classList.add('scale-95');
    modal.classList.add('opacity-0', 'pointer-events-none');
    document.getElementById('edit-user-form').reset();
    updatePasswordRuleIndicators('', 'edit-user-password-rules');
}
