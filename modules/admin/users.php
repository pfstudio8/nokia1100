<?php
session_start();
require_once __DIR__ . "/../../config/db.php";
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

require_once __DIR__ . "/../../classes/Layout.php";

// Obtener lista de usuarios
$sql = "SELECT u.id_usuario, u.nombre_usuario, u.rol, p.nombre, p.apellido 
        FROM usuario u
        INNER JOIN persona p ON u.id_persona = p.id_persona
        ORDER BY p.nombre ASC";

$result = $conn->query($sql);

Layout::renderHead('Administrar Usuarios - Nokia 1100');
Layout::renderAdminSidebar('usuarios');
?>
<style>
    .role-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .role-admin {
        background: rgba(224, 79, 238, 0.1); /* Magenta tint */
        color: var(--secondary-color);
        border: 1px solid rgba(224, 79, 238, 0.2);
    }
    .role-employee {
        background: rgba(79, 224, 229, 0.1); /* Cyan tint */
        color: var(--primary-color);
        border: 1px solid rgba(79, 224, 229, 0.2);
    }
</style>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8">
        <div class="dashboard-header" style="flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2>Administrar Usuarios</h2>
                <p>Gestión de personal y accesos</p>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center; justify-content: flex-end; flex: 1;">
                <input type="text" id="search-input" placeholder="Buscar usuario..." style="width: 250px; padding: 0.5rem 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 0.9rem;">
                <a href="<?php echo BASE_URL; ?>/modules/admin/dashboard.php" class="btn-back">Volver</a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div style="margin: 1rem 0; padding: 0.75rem 1rem; border-radius: 10px; border: 1px solid rgba(34,197,94,.25); background: rgba(34,197,94,.1); color: #86efac;">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div style="margin: 1rem 0; padding: 0.75rem 1rem; border-radius: 10px; border: 1px solid rgba(239,68,68,.25); background: rgba(239,68,68,.1); color: #fca5a5;">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight: 500; font-family: 'Outfit', sans-serif;"><?php echo htmlspecialchars($row['nombre'] . " " . $row['apellido']); ?></td>
                                <td style="color: var(--text-muted);"><?php echo htmlspecialchars($row['nombre_usuario']); ?></td>
                                <td>
                                    <span class="role-badge <?php echo $row['rol'] === 'admin' ? 'role-admin' : 'role-employee'; ?>">
                                        <?php echo ucfirst($row['rol']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form action="<?php echo BASE_URL; ?>/modules/admin/update_user_role.php" method="POST" style="display:inline-flex; gap: .5rem; align-items: center; margin-right: .75rem;">
                                        <input type="hidden" name="id_usuario" value="<?php echo (int)$row['id_usuario']; ?>">
                                        <select name="rol" style="padding: .35rem .6rem; border-radius: 8px; border: 1px solid var(--border); background: var(--surface); color: var(--text-main); font-size: .8rem;">
                                            <option value="empleado" <?php echo $row['rol'] === 'empleado' ? 'selected' : ''; ?>>Empleado</option>
                                            <option value="admin" <?php echo $row['rol'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                        <button type="submit" class="text-cyan-400 font-medium hover:underline text-sm">Guardar rol</button>
                                    </form>
                                    <a class="btn-delete text-red-500 font-medium hover:underline text-sm" href="delete_user.php?id=<?php echo $row['id_usuario']; ?>" 
                                       data-confirm="¿Seguro que deseas eliminar este usuario?" data-confirm-title="Eliminar Usuario">
                                        Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                No hay usuarios registrados
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php Layout::renderFooter(); ?>
