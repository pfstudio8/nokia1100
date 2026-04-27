<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}
require_once __DIR__ . '/../../classes/Layout.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_nombre = $conn->real_escape_string($_POST['cliente_nombre']);
    $cliente_telefono = $conn->real_escape_string($_POST['cliente_telefono']);
    $cliente_email = '';
    
    $equipo_marca = $conn->real_escape_string($_POST['equipo_marca']);
    $equipo_modelo = $conn->real_escape_string($_POST['equipo_modelo']);
    $equipo_imei = $conn->real_escape_string($_POST['equipo_imei']);
    
    $falla = $conn->real_escape_string($_POST['falla_declarada']);
    $observaciones = $conn->real_escape_string($_POST['observaciones']);
    
    $presupuesto = !empty($_POST['presupuesto']) ? (float)$_POST['presupuesto'] : "NULL";
    
    // Generar código de orden (fecha + random)
    $codigo_orden = date('ym') . mt_rand(1000, 9999);
    $id_usuario = $_SESSION['user_id'];
    
    $sql = "INSERT INTO reparacion (codigo_orden, cliente_nombre, cliente_telefono, cliente_email,
            equipo_marca, equipo_modelo, equipo_imei, falla_declarada, observaciones,
            presupuesto, id_usuario_recibe) 
            VALUES ('$codigo_orden', '$cliente_nombre', '$cliente_telefono', '$cliente_email', 
            '$equipo_marca', '$equipo_modelo', '$equipo_imei', '$falla', '$observaciones',
            $presupuesto, $id_usuario)";
            
    if ($conn->query($sql) === TRUE) {
        $id_reparacion = $conn->insert_id;
        
        // Sin manejo de foto de equipo
        
        // Historial
        $conn->query("INSERT INTO reparacion_historial (id_reparacion, estado_nuevo, nota, id_usuario) VALUES ($id_reparacion, 'Recibido', 'Ingreso inicial del equipo', $id_usuario)");
        
        header("Location: view.php?id=$id_reparacion&success=created");
        exit();
    } else {
        $error = "Error al crear la orden: " . $conn->error;
    }
}

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

        <?php if($error): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6 text-sm flex gap-3 items-center">
                <span class="material-symbols-outlined">error</span> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="glass-card rounded-2xl p-6 md:p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- CLIENTE -->
                <div>
                    <h3 class="text-lg font-display font-medium text-primary mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-xl">person</span> Datos del Cliente
                    </h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-text-muted mb-1">Nombre Completo *</label>
                            <input type="text" name="cliente_nombre" required class="w-full bg-surface border border-border px-4 py-2.5 rounded-xl text-sm text-text-main focus:outline-none focus:border-primary transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-muted mb-1">Teléfono (WhatsApp)</label>
                            <input type="tel" name="cliente_telefono" class="w-full bg-surface border border-border px-4 py-2.5 rounded-xl text-sm text-text-main focus:outline-none focus:border-primary transition-colors">
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

<?php Layout::renderFooter(); ?>
