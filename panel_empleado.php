<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'empleado') {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Empleado - Nokia 1100</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Reutiliza el mismo estilo visual del panel admin -->
    <style>
        .employee-grid {
            display: grid;
            gap: 1.5rem;
            margin-top: 2rem;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .employee-card {
            background: var(--card-bg);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            padding: 1.8rem;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: var(--glass-shadow);
            transition: 0.3s ease;
        }

        .employee-card:hover {
            transform: translateY(-4px);
            border-color: var(--primary-color);
            box-shadow: 0 0 18px rgba(99,102,241,0.2);
        }

        .employee-card h3 {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: var(--text-color);
        }

        .employee-link {
            display: inline-block;
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .employee-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container dashboard-container">

        <div class="glass-card">
            <div class="dashboard-header">
                <h2>Panel de Empleado</h2>
                <a href="auth/cerrar_sesion.php" class="logout-btn" style="text-decoration: none; color: white; border-radius: 8px;">Cerrar Sesión</a>
            </div>

            <p>Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
            <p style="color: var(--text-muted); margin-top: .5rem;">Acceso limitado a funciones operativas.</p>

            <!-- GRID DE OPCIONES -->
            <div class="employee-grid">

                <div class="employee-card">
                    <h3>Nueva Venta</h3>
                    <a href="nueva_venta.php" class="employee-link">Crear venta →</a>
                </div>

                <div class="employee-card">
                    <h3>Consultar Stock</h3>
                    <a href="inventario.php" class="employee-link">Ver inventario →</a>
                </div>

                <div class="employee-card">
                    <h3>Tienda Online</h3>
                    <a href="tienda/index.php" class="employee-link">Ver Tienda →</a>
                </div>

            </div>
        </div>

    </div>
</body>
</html>
