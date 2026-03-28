<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
// Solo admins pueden agregar productos
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/modules/inventory/inventory.php");
    exit();
}
require_once __DIR__ . '/../../classes/Layout.php';

Layout::renderHead('Agregar Nuevo Producto - Nokia 1100');
Layout::renderAdminSidebar('inventario');
?>

<main class="md:ml-64 p-6 md:p-10 pt-20 md:pt-10 min-h-screen">
    <div class="glass-card mb-8 max-w-3xl mx-auto">
        <div class="dashboard-header flex justify-between items-center mb-8">
            <div>
                <h2>Nuevo Equipo</h2>
                <p>Registrar un nuevo modelo en el catálogo</p>
            </div>
            <a href="inventory.php" class="btn-back flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Volver
            </a>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-600 text-sm p-4 rounded-xl mb-6 font-medium flex gap-3 items-center">
                <span class="material-symbols-outlined text-lg">error</span>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="process_product.php" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Información Principal -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-text-muted mb-2 uppercase tracking-wide">Nombre del Equipo</label>
                    <input type="text" name="nombre" required placeholder="Ej: Nokia 3310 Original" class="w-full p-3 bg-background border border-border rounded-lg text-text-main focus:outline-none focus:border-primary transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-bold text-text-muted mb-2 uppercase tracking-wide">Marca</label>
                    <input type="text" name="marca" required placeholder="Nokia" class="w-full p-3 bg-background border border-border rounded-lg text-text-main focus:outline-none focus:border-primary transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-bold text-text-muted mb-2 uppercase tracking-wide">Modelo</label>
                    <input type="text" name="modelo" required placeholder="3310" class="w-full p-3 bg-background border border-border rounded-lg text-text-main focus:outline-none focus:border-primary transition-colors">
                </div>

                <div class="h-px bg-border/40 md:col-span-2 my-2"></div>

                <!-- Detalles Comerciales -->
                <div>
                    <label class="block text-xs font-bold text-text-muted mb-2 uppercase tracking-wide">Precio de Venta ($)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-text-muted font-bold">$</span>
                        <input type="number" step="0.01" min="0" name="precio" required placeholder="50000.00" class="w-full pl-8 p-3 bg-background border border-border rounded-lg text-text-main focus:outline-none focus:border-primary transition-colors font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-text-muted mb-2 uppercase tracking-wide">Stock Inicial (Unidades)</label>
                    <input type="number" min="0" name="cantidad" required placeholder="10" class="w-full p-3 bg-background border border-border rounded-lg text-text-main focus:outline-none focus:border-primary transition-colors font-mono">
                </div>
            </div>

            <div class="pt-6 mt-6 border-t border-border flex justify-end">
                <button type="submit" class="btn-primary w-full md:w-auto px-8 py-3 bg-primary text-text-inverse font-bold rounded-lg hover:bg-primary-hover transition-colors shadow-[0_4px_14px_0_rgba(79,224,229,0.39)]">
                    REGISTRAR EQUIPO
                </button>
            </div>
        </form>
    </div>
</main>

<?php Layout::renderFooter(); ?>
