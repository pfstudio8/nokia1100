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
}

// Validación de contraseñas coincidentes
const addUserForm = document.getElementById('add-user-form');
if (addUserForm) {
    addUserForm.addEventListener('submit', function(e) {
        const pass = document.getElementById('modal-password').value;
        const passConfirm = document.getElementById('modal-password-confirm').value;
        if (pass !== passConfirm) {
            e.preventDefault();
            if (typeof showToast === 'function') {
                showToast('Las contraseñas no coinciden', 'error');
            } else {
                alert('Las contraseñas no coinciden');
            }
        }
    });
}

// --- Módulos y Permisos ---
const adminModules = [
    { id: 'dashboard', label: 'Dashboard', icon: 'dashboard' },
    { id: 'inventario', label: 'Inventario', icon: 'inventory_2' },
    { id: 'usuarios', label: 'Usuarios (Módulo Actual)', icon: 'group' },
    { id: 'taller', label: 'Taller', icon: 'home_repair_service' },
    { id: 'clientes', label: 'Clientes', icon: 'contact_page' },
    { id: 'ventas', label: 'Ventas', icon: 'payments' },
    { id: 'graficos', label: 'Estadísticas', icon: 'bar_chart' },
    { id: 'proveedores', label: 'Proveedores', icon: 'local_shipping' },
    { id: 'perfil', label: 'Mi Perfil', icon: 'person' }
];

const employeeModules = [
    { id: 'dashboard', label: 'Inicio', icon: 'home' },
    { id: 'venta', label: 'Generar Venta', icon: 'point_of_sale' },
    { id: 'taller', label: 'Taller', icon: 'home_repair_service' },
    { id: 'clientes', label: 'Clientes', icon: 'contact_page' },
    { id: 'inventario', label: 'Consultar Stock', icon: 'inventory_2' }
];

function openModulePermissions(id, fullName, role, modulosStr) {
    const tableContainer = document.getElementById('users-table-container');
    const permCard = document.getElementById('permission-card');
    
    // Asignar datos básicos
    document.getElementById('perm-user-id').value = id;
    document.getElementById('perm-user-name').textContent = fullName;
    document.getElementById('perm-user-role').textContent = role === 'admin' ? 'Administrador' : 'Empleado';
    
    // Elegir la lista de módulos correspondientes al rol
    const modules = role === 'admin' ? adminModules : employeeModules;
    
    // Parsear módulos permitidos
    let allowed = [];
    let isDefault = (modulosStr === 'DEFAULT' || modulosStr === '');
    if (!isDefault) {
        allowed = modulosStr.split(',');
    }
    
    // Renderizar la lista con checkboxes
    const listContainer = document.getElementById('modules-list');
    listContainer.innerHTML = '';
    
    modules.forEach(mod => {
        // Si es por defecto, todos están activos. De lo contrario, comprobamos si está en la lista de permitidos
        const isChecked = isDefault || allowed.includes(mod.id);
        const checkboxId = `mod-${mod.id}`;
        
        listContainer.innerHTML += `
            <div class="flex items-center justify-between p-3 rounded-xl border border-border/60 bg-surface/30 hover:bg-surface/60 transition-colors">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-text-muted" style="font-size: 1.25rem;">${mod.icon}</span>
                    <label for="${checkboxId}" class="text-sm font-medium text-text-main cursor-pointer select-none">${mod.label}</label>
                </div>
                <input type="checkbox" name="modulos[]" value="${mod.id}" id="${checkboxId}" ${isChecked ? 'checked' : ''}
                       class="w-4 h-4 rounded border-border text-primary bg-background focus:ring-primary/20 cursor-pointer">
            </div>
        `;
    });
    
    // Mensaje informativo sobre estado de permisos
    const noteContainer = document.getElementById('perm-note');
    if (isDefault) {
        noteContainer.innerHTML = `
            <div class="bg-primary/10 border border-primary/20 text-primary px-3 py-2 rounded-lg text-xs mt-1 mb-3 flex gap-2 items-center">
                <span class="material-symbols-outlined" style="font-size:1.1rem; flex-shrink:0;">info</span>
                <span>El usuario usa los permisos por defecto para su rol. Al guardar se personalizarán sus accesos.</span>
            </div>
        `;
    } else {
        noteContainer.innerHTML = `
            <div class="bg-secondary/10 border border-secondary/20 text-secondary px-3 py-2 rounded-lg text-xs mt-1 mb-3 flex gap-2 items-center">
                <span class="material-symbols-outlined" style="font-size:1.1rem; flex-shrink:0;">shield</span>
                <span>Permisos personalizados activos para este usuario.</span>
            </div>
        `;
    }
    
    // Modificar grid de la tabla a 2 columnas para dar espacio a la card
    tableContainer.classList.replace('lg:col-span-3', 'lg:col-span-2');
    permCard.classList.remove('hidden');
    
    // Trigger de animación CSS
    requestAnimationFrame(() => {
        permCard.classList.remove('opacity-0', 'scale-95');
        permCard.classList.add('opacity-100', 'scale-100');
    });
}

function closeModulePermissions() {
    const tableContainer = document.getElementById('users-table-container');
    const permCard = document.getElementById('permission-card');
    
    permCard.classList.remove('opacity-100', 'scale-100');
    permCard.classList.add('opacity-0', 'scale-95');
    
    setTimeout(() => {
        permCard.classList.add('hidden');
        tableContainer.classList.replace('lg:col-span-2', 'lg:col-span-3');
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
}
