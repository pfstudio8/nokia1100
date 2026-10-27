<?php
class Layout
{
    private static function getAssetVersion(string $path): int {
        $fullPath = __DIR__ . '/..' . $path;
        return file_exists($fullPath) ? filemtime($fullPath) : time();
    }

    public static function renderHead($title = "NOKIA1100")
    {
        $currentUser = $_SESSION['username'] ?? '';
        echo '<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="current-user" content="' . htmlspecialchars($currentUser) . '"/>
    <title>' . htmlspecialchars($title) . '</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="' . BASE_URL . '/assets/css/style.css?v=' . self::getAssetVersion('/assets/css/style.css') . '">
    <script>window.BASE_URL = "' . BASE_URL . '";</script>
    <script src="' . BASE_URL . '/assets/js/tailwind_config.js?v=' . self::getAssetVersion('/assets/js/tailwind_config.js') . '"></script>
    <script src="' . BASE_URL . '/assets/js/session-cache.js?v=' . self::getAssetVersion('/assets/js/session-cache.js') . '"></script>
</head>
<body class="font-sans antialiased text-text-main selection:bg-primary/20 selection:text-primary">
    <div id="top-right-user" class="absolute top-4 right-6 z-50 hidden md:flex items-center gap-3 bg-surface/80 backdrop-blur-md border border-border px-4 py-2 rounded-xl shadow-lg opacity-0 transition-opacity duration-300">
        <div class="w-8 h-8 rounded-lg bg-primary/20 text-primary flex items-center justify-center font-bold text-sm" id="tr-initial"></div>
        <span id="tr-username" class="text-sm font-medium text-text-main"></span>
    </div>';
    }

    public static function getAllowedModules(int|string|null $userId): ?array
    {
        if (!$userId || !is_numeric($userId)) {
            return null;
        }
        $userId = (int)$userId;
        global $conn;
        if (!isset($conn)) {
            return null;
        }
        $stmt = $conn->prepare("SELECT rol, modulos_permitidos FROM usuario WHERE id_usuario = ?");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                // Los administradores SIEMPRE tienen acceso total: se ignora modulos_permitidos
                if ($row['rol'] === 'admin') {
                    $stmt->close();
                    return null;
                }
                $stmt->close();
                if ($row['modulos_permitidos'] !== null) {
                    $trimmed = trim($row['modulos_permitidos']);
                    if ($trimmed === '') {
                        return [];
                    }
                    return array_values(array_filter(array_map('trim', explode(',', $trimmed))));
                }
                // Fallback seguro: Si es un empleado y no tiene módulos explícitos (NULL), limitar a módulos estándar
                return ['dashboard', 'venta', 'inventario', 'clientes'];
            }
            $stmt->close();
        }
        return null;
    }

    public static function getReadyRepairsCount(): int
    {
        try {
            $modelPath = __DIR__ . '/../modules/workshop/Models/WorkshopModel.php';
            if (file_exists($modelPath)) {
                require_once $modelPath;
                if (class_exists('WorkshopModel')) {
                    $wm = new WorkshopModel();
                    return $wm->get_ready_to_charge_count();
                }
            }
        } catch (\Throwable $e) {
            // Silencioso para evitar romper layout en caso de error
        }
        return 0;
    }

    // Versión sin redirección de checkAccess(): indica si la sesión actual puede usar el módulo.
    public static function hasAccess(string $moduleId): bool
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        $rol = $_SESSION['role'] ?? 'empleado';
        if ($rol === 'admin') {
            return true;
        }
        if ($rol === 'guest') {
            return in_array($moduleId, ['dashboard', 'inventario'], true);
        }
        $allowed = self::getAllowedModules($_SESSION['user_id']);
        if ($allowed === null) {
            return true;
        }
        if ($moduleId === 'ventas' && in_array('venta', $allowed, true)) {
            return true;
        }
        if ($moduleId === 'venta' && in_array('ventas', $allowed, true)) {
            return true;
        }
        if ($moduleId === 'cobrar_reparaciones' && (in_array('cobrar_reparaciones', $allowed, true) || in_array('venta', $allowed, true) || in_array('ventas', $allowed, true))) {
            return true;
        }
        return in_array($moduleId, $allowed, true);
    }

    public static function checkAccess(string $moduleId): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "/index.php");
            exit();
        }

        $rol = $_SESSION['role'] ?? 'empleado';

        // Los administradores tienen acceso a todos los módulos sin restricción
        if ($rol === 'admin') {
            return;
        }

        // Restricción para rol Invitado (guest)
        if ($rol === 'guest') {
            $allowedModules = ['dashboard', 'inventario'];
            if (!in_array($moduleId, $allowedModules, true)) {
                header("Location: " . BASE_URL . "/modules/employee/index.php?error=" . urlencode("No tiene permiso para acceder a ese módulo"));
                exit();
            }
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            if (strpos($requestUri, '/modules/admin/') !== false) {
                header("Location: " . BASE_URL . "/modules/employee/index.php?error=" . urlencode("No tiene permiso para acceder a ese módulo"));
                exit();
            }
            return;
        }

        $userId = $_SESSION['user_id'];
        $allowed = self::getAllowedModules($userId);

        // Si el usuario no es admin e intenta acceder a páginas bajo /modules/admin/
        if ($rol !== 'admin') {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            if (strpos($requestUri, '/modules/admin/') !== false) {
                // Solo se permite si el módulo específico (usuarios, auditoria) está explícitamente en sus permisos y no es el dashboard de admin
                $isAdminDashboard = (strpos($requestUri, '/modules/admin/dashboard.php') !== false) ||
                                    (strpos($requestUri, '/modules/admin/index.php') !== false && (!isset($_GET['action']) || $_GET['action'] === 'dashboard')) ||
                                    rtrim(parse_url($requestUri, PHP_URL_PATH), '/') === rtrim(BASE_URL . '/modules/admin', '/');
                if ($allowed === null || !in_array($moduleId, $allowed, true) || $isAdminDashboard) {
                    header("Location: " . BASE_URL . "/modules/employee/index.php?error=" . urlencode("No tiene permiso para acceder a ese módulo"));
                    exit();
                }
            }
        }

        if ($allowed !== null) {
            $hasPerm = in_array($moduleId, $allowed, true)
                || ($moduleId === 'ventas' && in_array('venta', $allowed, true))
                || ($moduleId === 'venta' && in_array('ventas', $allowed, true))
                || ($moduleId === 'cobrar_reparaciones' && (in_array('cobrar_reparaciones', $allowed, true) || in_array('venta', $allowed, true) || in_array('ventas', $allowed, true)));

            if (!$hasPerm) {
                $redirectUrl = ($rol === 'admin')
                    ? BASE_URL . "/modules/admin/index.php?error=" . urlencode("No tiene permiso para acceder a ese módulo")
                    : BASE_URL . "/modules/employee/index.php?error=" . urlencode("No tiene permiso para acceder a ese módulo");
                header("Location: " . $redirectUrl);
                exit();
            }
        }
    }

    public static function renderAdminSidebar($activePage = '')
    {
        if (($_SESSION['role'] ?? 'admin') !== 'admin') {
            self::renderEmployeeSidebar($activePage);
            return;
        }

        $username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin';
        $initial = strtoupper(substr($username, 0, 1));
        $userId = $_SESSION['user_id'] ?? null;
        $allowed = $userId ? self::getAllowedModules($userId) : null;

        $readyRepairs = self::getReadyRepairsCount();

        $links = [
            ['id' => 'dashboard', 'url' => BASE_URL . '/modules/admin/index.php', 'icon' => 'dashboard', 'label' => 'Dashboard'],
            ['id' => 'inventario', 'url' => BASE_URL . '/modules/inventory/index.php', 'icon' => 'inventory_2', 'label' => 'Inventario'],
            ['id' => 'usuarios', 'url' => BASE_URL . '/modules/admin/index.php?action=users', 'icon' => 'group', 'label' => 'Usuarios'],
            ['id' => 'taller', 'url' => BASE_URL . '/modules/workshop/index.php', 'icon' => 'home_repair_service', 'label' => 'Taller'],
            ['id' => 'cobrar_reparaciones', 'url' => BASE_URL . '/modules/sales/index.php?action=repairs_to_charge', 'icon' => 'price_check', 'label' => 'Cobrar Reparaciones', 'badge' => $readyRepairs, 'badge_title' => 'Reparaciones listas para cobrar'],
            ['id' => 'ventas', 'url' => BASE_URL . '/modules/sales/index.php', 'icon' => 'payments', 'label' => 'Ventas'],
            ['id' => 'clientes', 'url' => BASE_URL . '/modules/clients/index.php', 'icon' => 'contact_page', 'label' => 'Clientes'],
            ['id' => 'reportes', 'url' => BASE_URL . '/modules/reports/index.php', 'icon' => 'analytics', 'label' => 'Reportes'],
            ['id' => 'proveedores', 'url' => BASE_URL . '/modules/suppliers/index.php', 'icon' => 'local_shipping', 'label' => 'Proveedores'],
            ['id' => 'auditoria', 'url' => BASE_URL . '/modules/admin/index.php?action=audit', 'icon' => 'security', 'label' => 'Auditoría'],
            ['id' => 'perfil', 'url' => BASE_URL . '/modules/admin/index.php?action=profile', 'icon' => 'person', 'label' => 'Mi Perfil'],
        ];

        echo '
<!-- Encabezado Móvil RWD -->
<header id="mobileTopBar" class="md:hidden border-b border-border bg-surface/95 backdrop-blur-md px-4 py-3 flex justify-between items-center fixed top-0 left-0 right-0 z-40 shadow-md">
    <div class="flex items-center gap-3">
        <button id="mobileSidebarToggle" type="button" aria-label="Abrir menú" class="w-10 h-10 rounded-xl bg-card border border-border flex items-center justify-center text-text-main hover:text-primary active:scale-95 transition-all">
            <span class="material-symbols-outlined text-[24px]">menu</span>
        </button>
        <div class="flex items-center gap-2">
            <span class="font-display font-bold text-lg tracking-tight text-text-main">NOKIA<span class="text-primary">1100</span></span>
            <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border border-primary/30 text-primary bg-primary/10">Admin</span>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <a href="' . BASE_URL . '/help.php" class="w-9 h-9 rounded-lg bg-card/60 border border-border flex items-center justify-center text-text-muted hover:text-primary transition-colors" title="Ayuda">
            <span class="material-symbols-outlined text-[18px]">help</span>
        </a>
        <a href="' . BASE_URL . '/modules/auth/index.php?action=logout" class="w-9 h-9 rounded-lg bg-card/60 border border-border flex items-center justify-center text-text-muted hover:text-red-400 transition-colors" title="Cerrar Sesión">
            <span class="material-symbols-outlined text-[18px]">logout</span>
        </a>
    </div>
</header>

<!-- Telón de fondo móvil (Backdrop) -->
<div id="sidebarBackdrop" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-40 hidden transition-opacity duration-300 md:hidden" aria-hidden="true"></div>

<!-- Botón de colapso para escritorio -->
<button id="sidebarToggle" class="fixed top-7 z-50 items-center justify-center w-10 h-10 rounded-lg bg-surface/90 backdrop-blur-md border border-border shadow-lg text-text-main hover:text-primary transition-colors hidden md:flex hover:scale-105 active:scale-95">
    <span class="material-symbols-outlined">menu</span>
</button>

<aside id="mainSidebar" class="fixed left-0 top-0 h-screen w-64 border-r border-border bg-background flex flex-col z-50 md:z-40 transition-transform duration-300 -translate-x-full md:translate-x-0">
    <div class="p-6 md:p-8 flex items-center justify-between border-b border-border/50 md:border-b-0">
        <div>
            <h1 class="text-2xl font-bold font-display tracking-tight text-text-main">NOKIA<span class="text-primary">1100</span></h1>
            <p class="text-[10px] text-text-muted mt-1 uppercase tracking-widest font-medium">Administración</p>
        </div>
        <button id="closeSidebarMobile" type="button" aria-label="Cerrar menú" class="md:hidden w-8 h-8 rounded-lg bg-surface border border-border flex items-center justify-center text-text-muted hover:text-text-main active:scale-95 transition-colors">
            <span class="material-symbols-outlined text-[20px]">close</span>
        </button>
    </div>
    
    <nav class="flex-1 px-4 space-y-1 mt-4 overflow-y-auto custom-scrollbar">';

        foreach ($links as $l) {
            $perm = $l['permission'] ?? $l['id'];
            if ($allowed !== null && !in_array($perm, $allowed)) {
                continue;
            }
            $isActive = ($activePage === $l['id']);
            $badgeCount = (int)($l['badge'] ?? 0);
            $badgeAttr = isset($l['badge']) ? ' data-badge="ready-repairs"' : '';
            $badgeHidden = ($badgeCount <= 0) ? 'hidden' : '';
            $badgeHtml = isset($l['badge'])
                ? '<span' . $badgeAttr . ' class="ml-auto inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30 animate-pulse ' . $badgeHidden . '" title="' . ($l['badge_title'] ?? 'Pendientes') . '">' . $badgeCount . '</span>'
                : '';
            if ($isActive) {
                echo '<a href="' . $l['url'] . '" class="flex items-center gap-3 px-4 py-3 bg-card rounded-lg border border-border text-primary transition-colors">
                        <span class="material-symbols-outlined">' . $l['icon'] . '</span>
                        <span class="text-sm font-medium">' . $l['label'] . '</span>' . $badgeHtml . '
                      </a>';
            } else {
                echo '<a href="' . $l['url'] . '" class="flex items-center gap-3 px-4 py-3 text-text-muted hover:text-text-main hover:bg-surface/50 rounded-lg transition-colors">
                        <span class="material-symbols-outlined">' . $l['icon'] . '</span>
                        <span class="text-sm font-medium">' . $l['label'] . '</span>' . $badgeHtml . '
                      </a>';
            }
        }

        echo '
    </nav>
    
    <div class="p-6 border-t border-border mt-auto">
        <a href="' . BASE_URL . '/help.php" class="flex items-center gap-3 text-primary hover:text-primary/80 transition-colors mb-4 font-medium text-sm w-full">
            <span class="material-symbols-outlined">help</span>
            Ayuda
        </a>
        <a href="' . BASE_URL . '/modules/auth/index.php?action=logout" class="flex items-center gap-3 text-red-500 hover:text-red-400 transition-colors mb-6 font-medium text-sm">
            <span class="material-symbols-outlined">logout</span>
            Cerrar Sesión
        </a>
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-surface border border-border flex items-center justify-center text-primary font-display font-medium">
                ' . $initial . '
            </div>
            <div>
                <p class="text-sm font-medium text-text-main">' . $username . '</p>
                <p class="text-[10px] text-text-muted uppercase tracking-widest font-medium">Administrador</p>
            </div>
        </div>
    </div>
</aside>';
    }

    public static function renderEmployeeSidebar($activePage = '')
    {
        $username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Empleado';
        $initial = strtoupper(substr($username, 0, 1));
        $userId = $_SESSION['user_id'] ?? null;
        $allowed = ($userId && is_numeric($userId)) ? self::getAllowedModules((int)$userId) : null;
        $role = $_SESSION['role'] ?? 'empleado';
        $readyRepairs = ($role !== 'guest') ? self::getReadyRepairsCount() : 0;

        $links = [
            ['id' => 'dashboard', 'url' => BASE_URL . '/modules/employee/index.php', 'icon' => 'home', 'label' => 'Inicio'],
            ['id' => 'venta', 'url' => BASE_URL . '/modules/sales/index.php', 'icon' => 'payments', 'label' => 'Ventas'],
            ['id' => 'cobrar_reparaciones', 'url' => BASE_URL . '/modules/sales/index.php?action=repairs_to_charge', 'icon' => 'price_check', 'label' => 'Cobrar Reparaciones', 'badge' => $readyRepairs, 'badge_title' => 'Reparaciones listas para cobrar'],
            ['id' => 'taller', 'url' => BASE_URL . '/modules/workshop/index.php', 'icon' => 'home_repair_service', 'label' => 'Taller'],
            ['id' => 'inventario', 'url' => BASE_URL . '/modules/inventory/index.php', 'icon' => 'inventory_2', 'label' => 'Consultar Stock'],
            ['id' => 'clientes', 'url' => BASE_URL . '/modules/clients/index.php', 'icon' => 'contact_page', 'label' => 'Clientes'],
            ['id' => 'reportes', 'url' => BASE_URL . '/modules/reports/index.php', 'icon' => 'analytics', 'label' => 'Reportes'],
            ['id' => 'proveedores', 'url' => BASE_URL . '/modules/suppliers/index.php', 'icon' => 'local_shipping', 'label' => 'Proveedores'],
            ['id' => 'usuarios', 'url' => BASE_URL . '/modules/admin/index.php?action=users', 'icon' => 'group', 'label' => 'Usuarios'],
            ['id' => 'auditoria', 'url' => BASE_URL . '/modules/admin/index.php?action=audit', 'icon' => 'security', 'label' => 'Auditoría'],
        ];

        // Restringir enlaces visibles si es invitado
        if ($role === 'guest') {
            $links = array_filter($links, function($l) {
                return in_array($l['id'], ['dashboard', 'inventario']);
            });
        }

        echo '
<!-- Encabezado Móvil RWD -->
<header id="mobileTopBar" class="md:hidden border-b border-border bg-surface/95 backdrop-blur-md px-4 py-3 flex justify-between items-center fixed top-0 left-0 right-0 z-40 shadow-md">
    <div class="flex items-center gap-3">
        <button id="mobileSidebarToggle" type="button" aria-label="Abrir menú" class="w-10 h-10 rounded-xl bg-card border border-border flex items-center justify-center text-text-main hover:text-primary active:scale-95 transition-all">
            <span class="material-symbols-outlined text-[24px]">menu</span>
        </button>
        <div class="flex items-center gap-2">
            <span class="font-display font-bold text-lg tracking-tight text-text-main">NOKIA<span class="text-primary">1100</span></span>
            <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border border-primary/30 text-primary bg-primary/10">' . ($role === 'guest' ? 'Invitado' : 'Operario') . '</span>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <a href="' . BASE_URL . '/help.php" class="w-9 h-9 rounded-lg bg-card/60 border border-border flex items-center justify-center text-text-muted hover:text-primary transition-colors" title="Ayuda">
            <span class="material-symbols-outlined text-[18px]">help</span>
        </a>
        <a href="' . BASE_URL . '/modules/auth/index.php?action=logout" class="w-9 h-9 rounded-lg bg-card/60 border border-border flex items-center justify-center text-text-muted hover:text-red-400 transition-colors" title="Cerrar Sesión">
            <span class="material-symbols-outlined text-[18px]">logout</span>
        </a>
    </div>
</header>

<!-- Telón de fondo móvil (Backdrop) -->
<div id="sidebarBackdrop" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-40 hidden transition-opacity duration-300 md:hidden" aria-hidden="true"></div>

<!-- Botón de colapso para escritorio -->
<button id="sidebarToggle" class="fixed top-7 z-50 items-center justify-center w-10 h-10 rounded-lg bg-surface/90 backdrop-blur-md border border-border shadow-lg text-text-main hover:text-primary transition-colors hidden md:flex hover:scale-105 active:scale-95">
    <span class="material-symbols-outlined">menu</span>
</button>

<nav id="mainSidebar" class="fixed left-0 top-0 h-screen w-64 border-r border-border bg-background flex flex-col z-50 md:z-40 transition-transform duration-300 -translate-x-full md:translate-x-0">
    <div class="p-6 md:p-8 pb-4 flex items-center justify-between border-b border-border/50 md:border-b-0">
        <div>
            <h1 class="text-2xl font-bold font-display tracking-tight text-text-main">NOKIA<span class="text-primary">1100</span></h1>
            <p class="text-[10px] text-text-muted mt-1 uppercase tracking-widest font-medium">' . ($role === 'guest' ? 'Invitado' : 'Operario') . '</p>
        </div>
        <button id="closeSidebarMobile" type="button" aria-label="Cerrar menú" class="md:hidden w-8 h-8 rounded-lg bg-surface border border-border flex items-center justify-center text-text-muted hover:text-text-main active:scale-95 transition-colors">
            <span class="material-symbols-outlined text-[20px]">close</span>
        </button>
    </div>
    
    <div class="px-6 mb-6 mt-4">
        <div class="flex items-center gap-3 p-3 rounded-xl border border-border bg-surface">
            <div class="w-10 h-10 flex-shrink-0 rounded-full bg-border flex items-center justify-center text-primary font-display font-medium">
                ' . $initial . '
            </div>
            <div class="overflow-hidden">
                <div class="text-sm font-medium text-text-main truncate">' . $username . '</div>
                <div class="text-[10px] text-text-muted uppercase tracking-widest font-semibold">' . ($role === 'guest' ? 'Invitado' : 'Operario') . '</div>
            </div>
        </div>
    </div>
    
    <div class="flex-1 px-4 space-y-1 overflow-y-auto custom-scrollbar">';

        foreach ($links as $l) {
            if (!self::hasAccess($l['id'])) {
                continue;
            }
            $isActive = ($activePage === $l['id'] || ($activePage === 'ventas' && $l['id'] === 'venta') || ($activePage === 'venta' && $l['id'] === 'ventas'));
            $badgeCount = (int)($l['badge'] ?? 0);
            $badgeAttr = isset($l['badge']) ? ' data-badge="ready-repairs"' : '';
            $badgeHidden = ($badgeCount <= 0) ? 'hidden' : '';
            $badgeHtml = isset($l['badge'])
                ? '<span' . $badgeAttr . ' class="ml-auto inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30 animate-pulse ' . $badgeHidden . '" title="' . ($l['badge_title'] ?? 'Pendientes') . '">' . $badgeCount . '</span>'
                : '';
            if ($isActive) {
                echo '<a href="' . $l['url'] . '" class="flex items-center gap-3 px-4 py-3 bg-card rounded-lg border border-border text-primary transition-colors">
                        <span class="material-symbols-outlined">' . $l['icon'] . '</span>
                        <span class="text-sm font-medium">' . $l['label'] . '</span>' . $badgeHtml . '
                      </a>';
            } else {
                echo '<a href="' . $l['url'] . '" class="flex items-center gap-3 px-4 py-3 text-text-muted hover:text-text-main hover:bg-surface/50 rounded-lg transition-colors">
                        <span class="material-symbols-outlined">' . $l['icon'] . '</span>
                        <span class="text-sm font-medium">' . $l['label'] . '</span>' . $badgeHtml . '
                      </a>';
            }
        }

        echo '
    </div>
    <div class="p-6 border-t border-border mt-auto space-y-4">
        <a href="' . BASE_URL . '/help.php" class="flex items-center justify-center gap-2 w-full py-2.5 text-sm font-medium text-primary hover:text-primary/80 transition-colors bg-primary/10 rounded-lg">
            <span class="material-symbols-outlined">help</span>
            Ayuda
        </a>
        <a href="' . BASE_URL . '/modules/auth/index.php?action=logout" class="flex items-center justify-center gap-2 w-full py-2.5 text-sm font-medium text-red-500 hover:text-red-400 transition-colors">
            <span class="material-symbols-outlined">logout</span>
            Cerrar Sesión
        </a>
    </div>
</nav>';
    }

    /**
     * Renderiza los botones de exportación individuales (Excel y PDF)
     */
    public static function renderExportButtons(string $tableId, string $title, string $filename): string
    {
        $tableIdEsc = htmlspecialchars($tableId, ENT_QUOTES, 'UTF-8');
        $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $filenameEsc = htmlspecialchars($filename, ENT_QUOTES, 'UTF-8');

        return '
        <button type="button" onclick="exportTableToExcel(\'' . $tableIdEsc . '\', \'' . $filenameEsc . '\', this)" class="px-3 py-2 rounded-xl border border-green-500/30 bg-green-500/10 hover:bg-green-500/20 text-xs font-medium text-green-400 hover:text-green-300 transition-colors flex items-center gap-1.5 cursor-pointer">
            <span class="material-symbols-outlined text-[16px]">download</span> Excel
        </button>
        <button type="button" onclick="exportTableToPDF(\'' . $tableIdEsc . '\', \'' . $titleEsc . '\', \'' . $filenameEsc . '\', this)" class="px-3 py-2 rounded-xl border border-red-500/30 bg-red-500/10 hover:bg-red-500/20 text-xs font-medium text-red-400 hover:text-red-300 transition-colors flex items-center gap-1.5 cursor-pointer">
            <span class="material-symbols-outlined text-[16px]">download</span> PDF
        </button>';
    }

    /**
     * Compatibilidad hacia atrás
     */
    public static function renderExportSplitButton(string $tableId, string $title, string $filename): string
    {
        return self::renderExportButtons($tableId, $title, $filename);
    }

    public static function renderFooter()
    {
        echo '

<script src="' . BASE_URL . '/assets/js/framer-toaster.js?v=' . self::getAssetVersion('/assets/js/framer-toaster.js') . '"></script>
<script src="' . BASE_URL . '/assets/js/export-helper.js?v=' . self::getAssetVersion('/assets/js/export-helper.js') . '"></script>
<script src="' . BASE_URL . '/assets/js/main.js?v=' . self::getAssetVersion('/assets/js/main.js') . '"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const toggleBtn = document.getElementById("sidebarToggle");
        if (toggleBtn) {
            // Restore desktop state
            if (localStorage.getItem("sidebarCollapsed") === "true") {
                document.body.classList.add("sidebar-collapsed");
            }
            toggleBtn.addEventListener("click", function() {
                document.body.classList.toggle("sidebar-collapsed");
                localStorage.setItem("sidebarCollapsed", document.body.classList.contains("sidebar-collapsed"));
            });
        }

        // Control del Drawer Móvil RWD
        const mobileToggle = document.getElementById("mobileSidebarToggle");
        const mobileClose = document.getElementById("closeSidebarMobile");
        const backdrop = document.getElementById("sidebarBackdrop");

        function openMobileMenu() {
            document.body.classList.add("mobile-sidebar-open");
        }
        function closeMobileMenu() {
            document.body.classList.remove("mobile-sidebar-open");
        }

        if (mobileToggle) {
            mobileToggle.addEventListener("click", openMobileMenu);
        }
        if (mobileClose) {
            mobileClose.addEventListener("click", closeMobileMenu);
        }
        if (backdrop) {
            backdrop.addEventListener("click", closeMobileMenu);
        }

        // Cerrar menú móvil al hacer clic en cualquier enlace de navegación
        document.querySelectorAll("#mainSidebar a").forEach(function(link) {
            link.addEventListener("click", function() {
                if (window.innerWidth < 768) {
                    closeMobileMenu();
                }
            });
        });

        // Cerrar al pulsar la tecla Escape
        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape" && document.body.classList.contains("mobile-sidebar-open")) {
                closeMobileMenu();
            }
        });

        // Cerrar menú móvil automáticamente si se ensancha la pantalla a escritorio
        window.addEventListener("resize", function() {
            if (window.innerWidth >= 768 && document.body.classList.contains("mobile-sidebar-open")) {
                closeMobileMenu();
            }
        });
        
        // Global Toast Notification Interceptor for CRUD actions
        const urlParams = new URLSearchParams(window.location.search);
        let msgShowed = false;
        
        if (urlParams.has("success")) {
            if (typeof showToast === "function") showToast(urlParams.get("success"), "success");
            urlParams.delete("success");
            msgShowed = true;
        }
        if (urlParams.has("error")) {
            if (typeof showToast === "function") showToast(urlParams.get("error"), "error");
            urlParams.delete("error");
            msgShowed = true;
        }
        
        // Clean URL if messages were shown
        if (msgShowed) {
            const newUrl = window.location.pathname + (urlParams.toString() ? "?" + urlParams.toString() : "");
            window.history.replaceState({}, document.title, newUrl);
        }
    });
</script>
</body></html>';
    }
}
