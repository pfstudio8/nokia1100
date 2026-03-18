<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin_auth();

// Only Admins can manage users
if ($_SESSION['admin_role'] !== 'admin') {
    header("Location: ../panel_empleado.php");
    exit();
}

require_once "../../config/bd.php";

// Obtener lista de usuarios de la tabla usuarios_admin
$sql = "SELECT id, nombre_usuario, email, rol 
        FROM usuarios_admin 
        ORDER BY nombre_usuario ASC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Usuarios - Nokia 1100</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

</head>
    <div class="dashboard-container" style="padding-top: 3rem; align-items: stretch; justify-content: flex-start; max-width: 1400px; margin: 0 auto;">
        
        <div style="margin-bottom: 3rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                <div>
                    <p style="text-transform: uppercase; letter-spacing: 0.2em; font-size: 0.75rem; color: var(--primary); font-weight: 800; margin-bottom: 0.5rem;">Seguridad & Personal</p>
                    <h1 style="margin-bottom: 0;">Gestión de Usuarios</h1>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <a href="../register.php" class="btn btn-primary">Registrar Nuevo Usuario</a>
                    <a href="../panel_admin.php" class="btn btn-outline">Volver</a>
                </div>
            </div>
            <div style="height: 2px; width: 40px; background: var(--primary); margin: 1rem 0;"></div>
        </div>

        <div class="glass-card">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nombre de Usuario</th>
                            <th>Email / Correo</th>
                            <th>Rol</th>
                            <th style="text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td style="font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($row['nombre_usuario']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td>
                                        <?php if($row['rol'] === 'admin'): ?>
                                            <span style="color: #d8b4fe; background: rgba(168, 85, 247, 0.1); padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Administrador</span>
                                        <?php else: ?>
                                            <span style="color: #93c5fd; background: rgba(59, 130, 246, 0.1); padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Empleado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <a class="btn btn-sm btn-danger" href="eliminar_usuario.php?id=<?php echo $row['id']; ?>" 
                                           onclick="return confirm('¿Seguro que deseas eliminar este usuario?');">
                                            Eliminar
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 3rem; color: var(--text-dim);">
                                    No se encontraron usuarios registrados en el sistema.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
