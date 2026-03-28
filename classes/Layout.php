<?php
class Layout {
    public static function renderHead($title = "NOKIA1100") {
        echo '<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>' . htmlspecialchars($title) . '</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="' . BASE_URL . '/assets/css/style.css?v=' . time() . '">
    <script>
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
              background: "#0A0A0B",
              surface: "#111113",
              "surface-hover": "#18181B",
              primary: "#21b8bd",
              secondary: "#E04FEE",
              border: "#27272A",
              "text-main": "#FAFAFA",
              "text-muted": "#A1A1AA",
            },
            fontFamily: {
              sans: ["Inter", "sans-serif"],
              display: ["Outfit", "sans-serif"],
            },
          },
        },
      }
    </script>
    <style>
      .material-symbols-outlined { font-variation-settings: \'FILL\' 0, \'wght\' 300, \'GRAD\' 0, \'opsz\' 24; }
      body { background-color: #0A0A0B; color: #FAFAFA; }
      .glass-card {
        background: rgba(17, 17, 19, 0.7);
        backdrop-filter: blur(16px);
        border: 1px solid #27272A;
      }
      main a { transition: all 0.2s ease; }
    </style>
</head>
<body class="font-sans antialiased text-text-main selection:bg-primary/20 selection:text-primary">';
    }

    public static function renderAdminSidebar($activePage = '') {
        $username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin';
        $initial = strtoupper(substr($username, 0, 1));
        
        $links = [
            ['id' => 'dashboard', 'url' => BASE_URL . '/modules/admin/dashboard.php', 'icon' => 'dashboard', 'label' => 'Dashboard'],
            ['id' => 'inventario', 'url' => BASE_URL . '/modules/inventory/inventory.php', 'icon' => 'inventory_2', 'label' => 'Inventario'],
            ['id' => 'usuarios', 'url' => BASE_URL . '/modules/admin/users.php', 'icon' => 'group', 'label' => 'Usuarios'],
            ['id' => 'ventas', 'url' => BASE_URL . '/modules/sales/sales.php', 'icon' => 'payments', 'label' => 'Ventas'],
            ['id' => 'proveedores', 'url' => BASE_URL . '/modules/suppliers/suppliers.php', 'icon' => 'local_shipping', 'label' => 'Proveedores'],
            ['id' => 'perfil', 'url' => BASE_URL . '/modules/admin/profile.php', 'icon' => 'person', 'label' => 'Mi Perfil'],
        ];

        echo '
<aside class="fixed left-0 top-0 h-screen w-64 border-r border-border bg-background flex flex-col z-40 hidden md:flex">
    <div class="p-8">
        <h1 class="text-2xl font-bold font-display tracking-tight text-text-main">NOKIA<span class="text-primary">1100</span></h1>
        <p class="text-[10px] text-text-muted mt-1 uppercase tracking-widest font-medium">Administration</p>
    </div>
    
    <nav class="flex-1 px-4 space-y-1 mt-4">';
        
        foreach ($links as $l) {
            $isActive = ($activePage === $l['id']);
            if ($isActive) {
                echo '<a href="'.$l['url'].'" class="flex items-center gap-3 px-4 py-3 bg-surface rounded-lg border border-border text-primary transition-colors">
                        <span class="material-symbols-outlined">'.$l['icon'].'</span>
                        <span class="text-sm font-medium">'.$l['label'].'</span>
                      </a>';
            } else {
                echo '<a href="'.$l['url'].'" class="flex items-center gap-3 px-4 py-3 text-text-muted hover:text-text-main hover:bg-surface/50 rounded-lg transition-colors">
                        <span class="material-symbols-outlined">'.$l['icon'].'</span>
                        <span class="text-sm font-medium">'.$l['label'].'</span>
                      </a>';
            }
        }

        echo '
    </nav>
    
    <div class="p-6 border-t border-border mt-auto">
        <a href="' . BASE_URL . '/modules/auth/logout.php" class="flex items-center gap-3 text-red-500 hover:text-red-400 transition-colors mb-6 font-medium text-sm">
            <span class="material-symbols-outlined">logout</span>
            Cerrar Sesión
        </a>
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-surface border border-border flex items-center justify-center text-primary font-display font-medium">
                '.$initial.'
            </div>
            <div>
                <p class="text-sm font-medium text-text-main">'.$username.'</p>
                <p class="text-[10px] text-text-muted uppercase tracking-widest font-medium">Admin Level</p>
            </div>
        </div>
    </div>
</aside>';
    }

    public static function renderEmployeeSidebar($activePage = '') {
        $username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Empleado';
        $initial = strtoupper(substr($username, 0, 1));

        $links = [
            ['id' => 'dashboard', 'url' => BASE_URL . '/modules/employee/dashboard.php', 'icon' => 'home', 'label' => 'Inicio'],
            ['id' => 'venta', 'url' => BASE_URL . '/modules/sales/new_sale.php', 'icon' => 'point_of_sale', 'label' => 'Generar Venta'],
            ['id' => 'inventario', 'url' => BASE_URL . '/modules/inventory/inventory.php', 'icon' => 'inventory_2', 'label' => 'Consultar Stock'],
        ];

        echo '
<header class="md:hidden border-b border-border bg-background p-4 flex justify-between items-center fixed top-0 w-full z-50">
    <div class="font-display font-bold text-xl tracking-tight">NOKIA<span class="text-primary">1100</span></div>
    <a href="' . BASE_URL . '/modules/auth/logout.php" class="text-text-muted hover:text-red-500"><span class="material-symbols-outlined">logout</span></a>
</header>
<nav class="fixed left-0 top-0 h-screen w-64 border-r border-border bg-background flex-col z-40 hidden md:flex">
    <div class="p-8 pb-4">
        <h1 class="text-2xl font-bold font-display tracking-tight text-text-main">NOKIA<span class="text-primary">1100</span></h1>
    </div>
    
    <div class="px-6 mb-8 mt-4">
        <div class="flex items-center gap-3 p-3 rounded-xl border border-border bg-surface">
            <div class="w-10 h-10 flex-shrink-0 rounded-full bg-border flex items-center justify-center text-primary font-display font-medium">
                '.$initial.'
            </div>
            <div class="overflow-hidden">
                <div class="text-sm font-medium text-text-main truncate">'.$username.'</div>
                <div class="text-[10px] text-text-muted uppercase tracking-widest font-semibold">Operario</div>
            </div>
        </div>
    </div>
    
    <div class="flex-1 px-4 space-y-1">';

        foreach ($links as $l) {
            $isActive = ($activePage === $l['id']);
            if ($isActive) {
                echo '<a href="'.$l['url'].'" class="flex items-center gap-3 px-4 py-3 bg-surface rounded-lg border border-border text-primary transition-colors">
                        <span class="material-symbols-outlined">'.$l['icon'].'</span>
                        <span class="text-sm font-medium">'.$l['label'].'</span>
                      </a>';
            } else {
                echo '<a href="'.$l['url'].'" class="flex items-center gap-3 px-4 py-3 text-text-muted hover:text-text-main hover:bg-surface/50 rounded-lg transition-colors">
                        <span class="material-symbols-outlined">'.$l['icon'].'</span>
                        <span class="text-sm font-medium">'.$l['label'].'</span>
                      </a>';
            }
        }

        echo '
    </div>
    <div class="p-6 border-t border-border mt-auto space-y-4">
        <a href="' . BASE_URL . '/modules/auth/logout.php" class="flex items-center justify-center gap-2 w-full py-2.5 text-sm font-medium text-red-500 hover:text-red-400 transition-colors">
            <span class="material-symbols-outlined">logout</span>
            Cerrar Sesión
        </a>
    </div>
</nav>';
    }

    public static function renderFooter() {
        echo '
<div id="toast-container" class="fixed bottom-4 right-4 z-50 flex flex-col items-end pointer-events-none"></div>
<script src="' . BASE_URL . '/assets/js/main.js?v=' . time() . '"></script>
</body></html>';
    }
}
?>
