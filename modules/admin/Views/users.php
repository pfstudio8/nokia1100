<?php
// modules/admin/Views/users.php

Layout::renderHead('Administrar Usuarios - Nokia 1100');
Layout::renderAdminSidebar('usuarios');
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/admin/admin_users.css?v=<?php echo time(); ?>">

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <!-- Contenedor Grid para permitir vista en dos columnas cuando se abre la gestión de permisos -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        
        <!-- Columna de la Tabla de Usuarios -->
        <div class="lg:col-span-3 transition-all duration-500" id="users-table-container">
            <div class="glass-card mb-8">
                <div class="dashboard-header" style="flex-wrap:wrap; gap:1rem;">
                    <div>
                        <h2>Administrar Usuarios</h2>
                        <p>Gestión de personal y accesos</p>
                    </div>
                    <div style="display:flex; gap:1rem; align-items:center; justify-content:flex-end; flex:1;">
                        <input type="text" id="search-input" placeholder="Buscar usuario..."
                            style="width:200px; padding:.5rem 1rem; background:var(--surface); border:1px solid var(--border); border-radius:8px; color:var(--text-main); font-size:.9rem;">

                        <!-- Filtro activos / inactivos -->
                        <div style="display:flex; gap:.5rem;">
                            <a href="?filtro=activos"
                               style="padding:.4rem .8rem; border-radius:8px; font-size:.8rem; font-weight:600;
                                      border:1px solid var(--border);
                                      background: <?php echo $filtro==='activos'?'var(--primary-color)':'var(--surface)'; ?>;
                                      color: <?php echo $filtro==='activos'?'#0A0A0B':'var(--text-muted)'; ?>">Activos</a>
                            <a href="?filtro=inactivos"
                               style="padding:.4rem .8rem; border-radius:8px; font-size:.8rem; font-weight:600;
                                      border:1px solid var(--border);
                                      background: <?php echo $filtro==='inactivos'?'#ef4444':'var(--surface)'; ?>;
                                      color: <?php echo $filtro==='inactivos'?'#fff':'var(--text-muted)'; ?>">Inactivos</a>
                            <a href="?filtro=todos"
                               style="padding:.4rem .8rem; border-radius:8px; font-size:.8rem; font-weight:600;
                                      border:1px solid var(--border);
                                      background: <?php echo $filtro==='todos'?'var(--surface-hover)':'var(--surface)'; ?>;
                                      color:var(--text-muted)">Todos</a>
                        </div>

                        <!-- Botones de Exportación -->
                        <button type="button" onclick="exportTableToExcel('users-table', 'usuarios')" class="px-3 py-1.5 rounded-lg border border-border bg-surface hover:bg-surface-hover text-xs font-medium text-text-muted hover:text-text-main transition-colors flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]">file_download</span> Excel
                        </button>
                        <button type="button" onclick="exportTableToPDF('users-table', 'Listado de Usuarios', 'usuarios')" class="px-3 py-1.5 rounded-lg border border-border bg-surface hover:bg-surface-hover text-xs font-medium text-text-muted hover:text-text-main transition-colors flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]">picture_as_pdf</span> PDF
                        </button>

                        <button type="button" onclick="openAddUserModal()" style="width:auto; padding:.45rem .9rem; border-radius:8px; font-size:.8rem; font-weight:600; background:var(--primary-color); color:#0A0A0B; border:none; cursor:pointer; display:inline-flex; align-items:center; gap:0.25rem; transition: all 0.2s;">
                            <span class="material-symbols-outlined" style="font-size:1.1rem; font-weight:600;">person_add</span>
                            Nuevo
                        </button>
                        <a href="<?php echo BASE_URL; ?>/modules/admin/dashboard.php" class="btn-back">Volver</a>
                    </div>
                </div>

                <div class="table-container">
                    <table id="users-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($clientes) > 0): ?>
                                <?php foreach ($clientes as $row): ?>
                                    <tr style="opacity: <?php echo $row['is_active'] ? '1' : '.55'; ?>">
                                        <td style="font-weight:500; font-family:'Outfit',sans-serif;">
                                            <?php echo htmlspecialchars($row['nombre'] . " " . $row['apellido']); ?>
                                        </td>
                                        <td style="color:var(--text-muted);"><?php echo htmlspecialchars($row['nombre_usuario']); ?></td>
                                        <td>
                                            <span class="role-badge <?php echo $row['rol']==='admin'?'role-admin':'role-employee'; ?>">
                                                <?php echo ucfirst($row['rol']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($row['is_active']): ?>
                                                <span class="role-badge" style="background:rgba(34,197,94,.1); color:#4ade80; border:1px solid rgba(34,197,94,.2);">Activo</span>
                                            <?php else: ?>
                                                <span class="badge-inactive role-badge" title="Baja: <?php echo $row['fecha_baja'] ?? ''; ?>">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['is_active']): ?>
                                                <div style="display:inline-flex; align-items:center; gap:.5rem; flex-wrap:wrap;">
                                                    <!-- Cambio de rol -->
                                                    <form action="<?php echo BASE_URL; ?>/modules/admin/update_user_role.php"
                                                          method="POST" style="display:inline-flex; gap:.5rem; align-items:center; margin-right:.5rem; margin-bottom:0;">
                                                        <input type="hidden" name="id_usuario" value="<?php echo (int)$row['id_usuario']; ?>">
                                                        <div class="relative inline-block">
                                                            <select name="rol" style="padding:.35rem 1.8rem .35rem .6rem; border-radius:8px; border:1px solid var(--border); background:var(--surface); color:var(--text-main); font-size:.8rem; appearance: none; cursor: pointer; transition: border-color 0.2s;" onchange="this.form.submit()">
                                                                <option value="empleado" <?php echo $row['rol']==='empleado'?'selected':''; ?>>Empleado</option>
                                                                <option value="admin"    <?php echo $row['rol']==='admin'   ?'selected':''; ?>>Admin</option>
                                                            </select>
                                                            <span class="material-symbols-outlined absolute right-1.5 top-1/2 -translate-y-1/2 text-text-muted text-[16px] pointer-events-none">expand_more</span>
                                                        </div>
                                                    </form>

                                                    <?php
                                                     $user_data = json_encode([
                                                         'id_usuario' => $row['id_usuario'],
                                                         'id_persona' => $row['id_persona'],
                                                         'nombre' => $row['nombre'],
                                                         'apellido' => $row['apellido'],
                                                         'dni' => $row['dni'],
                                                         'email' => $row['email'],
                                                         'telefono' => $row['telefono'],
                                                         'direccion' => $row['direccion'],
                                                         'nombre_usuario' => $row['nombre_usuario'],
                                                         'rol' => $row['rol']
                                                     ], JSON_HEX_APOS | JSON_HEX_QUOT);
                                                     ?>
                                                     <!-- Editar datos personales -->
                                                     <a href="javascript:void(0)" 
                                                        onclick='openEditUserModal(<?php echo htmlspecialchars($user_data, ENT_QUOTES); ?>)'
                                                        class="text-primary hover:underline text-sm font-medium mr-3 inline-flex items-center gap-1"
                                                        style="color: var(--primary-color);"
                                                        title="Editar datos personales">
                                                         <span class="material-symbols-outlined" style="font-size:1.05rem;">edit</span>
                                                         Editar
                                                     </a>

                                                     <!-- Módulos de Acceso -->
                                                    <a href="javascript:void(0)" 
                                                       onclick="openModulePermissions(<?php echo $row['id_usuario']; ?>, '<?php echo htmlspecialchars($row['nombre'] . ' ' . $row['apellido'], ENT_QUOTES); ?>', '<?php echo $row['rol']; ?>', '<?php echo htmlspecialchars($row['modulos_permitidos'] ?? 'DEFAULT', ENT_QUOTES); ?>')"
                                                       class="text-primary hover:underline text-sm font-medium mr-3 inline-flex items-center gap-1"
                                                       style="color: var(--primary-color);"
                                                       title="Asignar módulos permitidos">
                                                        <span class="material-symbols-outlined" style="font-size:1.05rem;">vpn_key</span>
                                                        Módulos
                                                    </a>

                                                    <!-- Baja lógica -->
                                                    <a class="btn-delete text-red-500 font-medium hover:underline text-sm"
                                                       href="delete_user.php?id=<?php echo $row['id_usuario']; ?>"
                                                       data-confirm="¿Desactivar a este usuario? Podrá restaurarse luego."
                                                       data-confirm-title="Baja Lógica">
                                                        Desactivar
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <!-- Restaurar -->
                                                <a class="font-medium text-sm hover:underline"
                                                   href="delete_user.php?id=<?php echo $row['id_usuario']; ?>"
                                                   data-confirm="¿Restaurar la cuenta de este usuario?"
                                                   data-confirm-title="Restaurar Usuario"
                                                   style="color:var(--primary-color);">
                                                    Restaurar
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center; padding:3rem; color:var(--text-muted);">
                                        No hay usuarios en esta categoría
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tarjeta de Permisos de Módulos (Lado Derecho, oculto por defecto) -->
        <div id="permission-card" class="lg:col-span-1 glass-card p-6 hidden transform opacity-0 scale-95 transition-all duration-300 relative" style="border-top: 4px solid var(--secondary-color);">
            
            <div class="mb-5">
                <h3 class="text-xl font-display font-medium text-text-main flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary" style="color:var(--secondary-color); font-size:1.6rem;">shield_person</span>
                    Accesos de Módulos
                </h3>
                <p class="text-text-muted text-xs mt-1">Configurar visibilidad para: <span id="perm-user-name" class="text-text-main font-semibold"></span></p>
                <div class="mt-2 inline-block text-[10px] uppercase font-bold tracking-widest bg-surface border border-border px-2 py-0.5 rounded text-primary" id="perm-user-role"></div>
            </div>

            <form id="permission-form" action="save_user_modules.php" method="POST" class="space-y-4">
                <input type="hidden" name="id_usuario" id="perm-user-id" value="0">
                
                <div id="perm-note"></div>

                <div id="modules-list" class="space-y-2.5 mt-4" style="max-height: 380px; overflow-y: auto; padding-right: 4px;">
                    <!-- Insertado dinámicamente vía JS -->
                </div>

                <div class="flex gap-3 pt-4 border-t border-border mt-5">
                    <button type="button" onclick="closeModulePermissions()" class="btn-back flex-1 text-center py-2" style="margin:0; width:auto;">Cancelar</button>
                    <button type="submit" class="btn-primary flex-1 text-center py-2" style="margin:0; width:auto; background:var(--primary-color); color:#0A0A0B;">Guardar</button>
                </div>
            </form>
        </div>

    </div>
</main>

<!-- Modal para agregar usuario -->
<div id="add-user-modal" class="fixed inset-0 bg-background/80 backdrop-blur-[8px] z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300">
    <div class="glass-card max-w-lg w-full m-4 shadow-2xl relative border border-border/80 transform scale-95 transition-all duration-300 premium-modal-card" style="padding: 2rem; border-top: 4px solid var(--primary-color);">
        <button onclick="closeAddUserModal()" class="absolute top-4 right-4 text-text-muted hover:text-text-main transition-colors bg-transparent border-none cursor-pointer">
            <span class="material-symbols-outlined">close</span>
        </button>
        
        <div class="mb-6">
            <h3 class="text-2xl font-display font-medium text-text-main flex items-center gap-2">
                <span class="material-symbols-outlined text-primary" style="color:var(--primary-color); font-size: 1.8rem;">person_add</span>
                Agregar Nuevo Usuario
            </h3>
            <p class="text-text-muted text-xs mt-1">Registrar una nueva persona y su cuenta de acceso de forma directa</p>
        </div>

        <form id="add-user-form" action="add_user.php" method="POST" class="space-y-4" style="display: flex; flex-direction: column; gap: 1rem;">
            <!-- Datos Personales -->
            <div style="border-bottom: 1px solid var(--border); padding-bottom: 1rem;">
                <h4 class="text-xs font-bold text-primary uppercase tracking-wider mb-3" style="color:var(--primary-color);">Datos Personales</h4>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div class="premium-input-container">
                        <label for="modal-nombre">Nombre <span class="text-red-400" style="color:#f87171;">*</span></label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">person</span>
                            <input type="text" id="modal-nombre" name="nombre" required placeholder="Ej. Juan">
                        </div>
                    </div>
                    <div class="premium-input-container">
                        <label for="modal-apellido">Apellido <span class="text-red-400" style="color:#f87171;">*</span></label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">person</span>
                            <input type="text" id="modal-apellido" name="apellido" required placeholder="Ej. Pérez">
                        </div>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div class="premium-input-container">
                        <label for="modal-dni">DNI <span class="text-red-400" style="color:#f87171;">*</span></label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">badge</span>
                            <input type="text" id="modal-dni" name="dni" required placeholder="Ej. 34567890" pattern="[0-9]{7,10}" title="DNI debe tener entre 7 y 10 dígitos">
                        </div>
                    </div>
                    <div class="premium-input-container">
                        <label for="modal-email">Email <span class="text-red-400" style="color:#f87171;">*</span></label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">mail</span>
                            <input type="email" id="modal-email" name="email" required placeholder="juan.perez@example.com">
                        </div>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="premium-input-container">
                        <label for="modal-telefono">Teléfono</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">call</span>
                            <input type="text" id="modal-telefono" name="telefono" placeholder="Ej. 1122334455">
                        </div>
                    </div>
                    <div class="premium-input-container">
                        <label for="modal-direccion">Dirección</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">home</span>
                            <input type="text" id="modal-direccion" name="direccion" placeholder="Ej. Av. Siempreviva 742">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Datos de la Cuenta -->
            <div>
                <h4 class="text-xs font-bold text-primary uppercase tracking-wider mb-3" style="color:var(--primary-color);">Datos de la Cuenta</h4>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div class="premium-input-container">
                        <label for="modal-username">Usuario <span class="text-red-400" style="color:#f87171;">*</span></label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">account_circle</span>
                            <input type="text" id="modal-username" name="username" required placeholder="Ej. jperez">
                        </div>
                    </div>
                    <div class="premium-input-container">
                        <label for="modal-rol">Rol <span class="text-red-400" style="color:#f87171;">*</span></label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">shield_person</span>
                            <select id="modal-rol" name="rol" required class="w-full appearance-none">
                                <option value="empleado" selected>Empleado</option>
                                <option value="admin">Administrador</option>
                            </select>
                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">expand_more</span>
                        </div>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="premium-input-container">
                        <label for="modal-password">Contraseña <span class="text-red-400" style="color:#f87171;">*</span></label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">lock</span>
                            <input type="password" id="modal-password" name="password" required placeholder="Mínimo 6 caracteres" minlength="6">
                        </div>
                    </div>
                    <div class="premium-input-container">
                        <label for="modal-password-confirm">Confirmar Contraseña <span class="text-red-400" style="color:#f87171;">*</span></label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">key</span>
                            <input type="password" id="modal-password-confirm" name="password_confirm" required placeholder="Repetir contraseña" minlength="6">
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                <button type="button" onclick="closeAddUserModal()" class="btn-back" style="width: auto; margin: 0;">Cancelar</button>
                <button type="submit" class="btn-primary" style="width: auto; margin: 0; background: var(--primary-color); color: #0A0A0B;">
                    Registrar Usuario
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para editar usuario -->
<div id="edit-user-modal" class="fixed inset-0 bg-background/80 backdrop-blur-[8px] z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300">
    <div class="glass-card max-w-lg w-full m-4 shadow-2xl relative border border-border/80 transform scale-95 transition-all duration-300 premium-modal-card" style="padding: 2rem; border-top: 4px solid var(--secondary-color);">
        <button onclick="closeEditUserModal()" class="absolute top-4 right-4 text-text-muted hover:text-text-main transition-colors bg-transparent border-none cursor-pointer">
            <span class="material-symbols-outlined">close</span>
        </button>
        
        <div class="mb-6">
            <h3 class="text-2xl font-display font-medium text-text-main flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary" style="color:var(--secondary-color); font-size: 1.8rem;">edit</span>
                Editar Usuario
            </h3>
            <p class="text-text-muted text-xs mt-1">Modificar los datos personales y de acceso del usuario</p>
        </div>

        <form id="edit-user-form" action="edit_user.php" method="POST" class="space-y-4" style="display: flex; flex-direction: column; gap: 1rem;">
            <input type="hidden" id="edit-modal-id-usuario" name="id_usuario">
            <input type="hidden" id="edit-modal-id-persona" name="id_persona">

            <!-- Datos Personales -->
            <div style="border-bottom: 1px solid var(--border); padding-bottom: 1rem;">
                <h4 class="text-xs font-bold text-secondary uppercase tracking-wider mb-3" style="color:var(--secondary-color);">Datos Personales</h4>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div class="premium-input-container">
                        <label for="edit-modal-nombre">Nombre <span class="text-red-400" style="color:#f87171;">*</span></label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">person</span>
                            <input type="text" id="edit-modal-nombre" name="nombre" required placeholder="Ej. Juan">
                        </div>
                    </div>
                    <div class="premium-input-container">
                        <label for="edit-modal-apellido">Apellido <span class="text-red-400" style="color:#f87171;">*</span></label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">person</span>
                            <input type="text" id="edit-modal-apellido" name="apellido" required placeholder="Ej. Pérez">
                        </div>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div class="premium-input-container">
                        <label for="edit-modal-dni">DNI <span class="text-red-400" style="color:#f87171;">*</span></label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">badge</span>
                            <input type="text" id="edit-modal-dni" name="dni" required placeholder="Ej. 34567890" pattern="[0-9]{7,10}" title="DNI debe tener entre 7 y 10 dígitos">
                        </div>
                    </div>
                    <div class="premium-input-container">
                        <label for="edit-modal-email">Email <span class="text-red-400" style="color:#f87171;">*</span></label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">mail</span>
                            <input type="email" id="edit-modal-email" name="email" required placeholder="juan.perez@example.com">
                        </div>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="premium-input-container">
                        <label for="edit-modal-telefono">Teléfono</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">call</span>
                            <input type="text" id="edit-modal-telefono" name="telefono" placeholder="Ej. 1122334455">
                        </div>
                    </div>
                    <div class="premium-input-container">
                        <label for="edit-modal-direccion">Dirección</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">home</span>
                            <input type="text" id="edit-modal-direccion" name="direccion" placeholder="Ej. Av. Siempreviva 742">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Datos de la Cuenta -->
            <div>
                <h4 class="text-xs font-bold text-secondary uppercase tracking-wider mb-3" style="color:var(--secondary-color);">Datos de la Cuenta</h4>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div class="premium-input-container">
                        <label for="edit-modal-username">Usuario <span class="text-red-400" style="color:#f87171;">*</span></label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">account_circle</span>
                            <input type="text" id="edit-modal-username" name="username" required placeholder="Ej. jperez">
                        </div>
                    </div>
                    <div class="premium-input-container">
                        <label for="edit-modal-rol">Rol <span class="text-red-400" style="color:#f87171;">*</span></label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">shield_person</span>
                            <select id="edit-modal-rol" name="rol" required class="w-full appearance-none">
                                <option value="empleado">Empleado</option>
                                <option value="admin">Administrador</option>
                            </select>
                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">expand_more</span>
                        </div>
                    </div>
                </div>
                
                <div class="premium-input-container">
                    <label for="edit-modal-password">Contraseña (Dejar en blanco para mantener la actual)</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-[18px] pointer-events-none">lock</span>
                        <input type="password" id="edit-modal-password" name="password" placeholder="Min. 6 caracteres si se modifica" minlength="6">
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                <button type="button" onclick="closeEditUserModal()" class="btn-back" style="width: auto; margin: 0;">Cancelar</button>
                <button type="submit" class="btn-primary" style="width: auto; margin: 0; background: var(--secondary-color); color: #fff;">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>/modules/admin/admin_users.js?v=<?php echo time(); ?>"></script>

<?php Layout::renderFooter(); ?>
