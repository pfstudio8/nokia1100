<?php
class Layout
{
    private static function getAssetVersion(string $path): int {
        $fullPath = __DIR__ . '/..' . $path;
        return file_exists($fullPath) ? filemtime($fullPath) : time();
    }

    public static function renderHead($title = "NOKIA1100", array $extraCss = [])
    {
        $currentUser = $_SESSION['username'] ?? '';
        $csrfToken = $_SESSION['session_token'] ?? '';
        $extraLinks = '';
        foreach ($extraCss as $cssPath) {
            $ver = self::getAssetVersion($cssPath);
            $extraLinks .= "\n    " . '<link rel="stylesheet" href="' . BASE_URL . htmlspecialchars($cssPath, ENT_QUOTES, 'UTF-8') . '?v=' . $ver . '">';
        }

        echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="current-user" content="' . htmlspecialchars($currentUser, ENT_QUOTES, 'UTF-8') . '"/>
    <meta name="csrf-token" content="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '"/>
    <title>' . htmlspecialchars($title) . '</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&family=IBM+Plex+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="' . BASE_URL . '/assets/css/style.css?v=' . self::getAssetVersion('/assets/css/style.css') . '">' . $extraLinks . '
    <script>window.BASE_URL = "' . BASE_URL . '";</script>
    <script src="' . BASE_URL . '/assets/js/tailwind_config.js?v=' . self::getAssetVersion('/assets/js/tailwind_config.js') . '"></script>
    <script src="' . BASE_URL . '/assets/js/session-cache.js?v=' . self::getAssetVersion('/assets/js/session-cache.js') . '"></script>
</head>
<body class="nk-base font-sans antialiased text-[#15171a] bg-[#eeebe3] selection:bg-[#c6e24a] selection:text-[#15171a]">
    <script>
        (function() {
            try {
                document.body.classList.add("sidebar-preload");
                if (localStorage.getItem("sidebarCollapsed") === "true") {
                    document.body.classList.add("sidebar-collapsed");
                }
            } catch (e) {}
        })();
    </script>
    <div id="top-right-user" class="fixed top-[9px] right-6 z-40 hidden md:flex items-center gap-3 bg-[#f8f6f0] border border-[#d3cebf] px-3.5 py-1.5 rounded-[2px] opacity-0 transition-opacity duration-300">
        <div class="w-7 h-7 rounded-[2px] bg-[#15181a] text-[#c6e24a] flex items-center justify-center font-mono font-bold text-xs" id="tr-initial"></div>
        <span id="tr-username" class="text-xs font-mono font-semibold text-[#15171a]"></span>
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
            if (!class_exists('WorkshopModel') && file_exists($modelPath)) {
                require_once $modelPath;
            }
            if (class_exists('WorkshopModel')) {
                $wm = new WorkshopModel();
                return $wm->get_ready_to_charge_count();
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

    private static function resolveModuleLabel(string $activePage): string
    {
        $role = $_SESSION['role'] ?? 'admin';
        $map = [
            'dashboard'           => ($role === 'admin') ? 'DASHBOARD' : 'INICIO',
            'inventario'          => 'INVENTARIO',
            'usuarios'            => 'USUARIOS',
            'taller'              => 'TALLER',
            'cobrar_reparaciones' => 'COBRAR REPARACIONES',
            'ventas'              => 'VENTAS',
            'venta'               => 'VENTAS',
            'clientes'            => 'CLIENTES',
            'reportes'            => 'REPORTES',
            'graficos'            => 'REPORTES',
            'proveedores'         => 'PROVEEDORES',
            'auditoria'           => 'AUDITORÍA',
            'perfil'              => 'MI PERFIL',
            'ayuda'               => 'AYUDA',
        ];

        $key = strtolower(trim($activePage));
        if (isset($map[$key])) {
            return $map[$key];
        }
        return $key !== '' ? mb_strtoupper(str_replace('_', ' ', $key), 'UTF-8') : 'SISTEMA';
    }

    private static function renderModuleTopBar(string $activePage): string
    {
        $moduleLabel = htmlspecialchars(self::resolveModuleLabel($activePage), ENT_QUOTES, 'UTF-8');

        return '
<!-- Barra Superior Unificada con Indicador de Módulo -->
<header id="moduleTopBar" class="sys-topbar">
    <div class="sys-topbar-left">
        <button id="sidebarToggle" type="button" class="sys-topbar-btn" aria-label="Alternar menú lateral" title="Mostrar / ocultar menú">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" aria-hidden="true">
                <path d="M4 7h16M4 12h16M4 17h16"/>
            </svg>
        </button>
        <nav class="sys-breadcrumb" aria-label="Ubicación actual">
            <span class="sys-crumb-root">NOKIA 1100</span>
            <span class="sys-crumb-sep" aria-hidden="true">/</span>
            <span class="sys-crumb-current">' . $moduleLabel . '</span>
        </nav>
    </div>
</header>

<!-- Telón de fondo móvil (Backdrop) -->
<div id="sidebarBackdrop" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-40 hidden md:hidden" aria-hidden="true"></div>';
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

        echo self::renderModuleTopBar((string)$activePage) . '

<aside id="mainSidebar" class="fixed left-0 top-0 h-screen w-64 border-r border-[#222729] bg-[#15181a] flex flex-col z-50 md:z-40">
    <div class="sys-sidebar-header flex items-center justify-between border-b border-[#222729]">
        <div>
            <h1 class="sys-sidebar-brand font-bold font-display tracking-tight text-[#e9e6db]">NOKIA<span class="text-[#c6e24a] font-mono ml-1">1100</span></h1>
            <p class="sys-sidebar-role text-[#9ba19f] font-mono mt-0.5 uppercase">Administración</p>
        </div>
        <button id="closeSidebarMobile" type="button" aria-label="Cerrar menú" class="md:hidden w-8 h-8 rounded-[2px] bg-[#222729] border border-[#30363a] flex items-center justify-center text-[#9ba19f] hover:text-[#e9e6db] transition-colors">
            <span class="material-symbols-outlined text-[19px]">close</span>
        </button>
    </div>
    
    <nav class="sys-sidebar-nav flex-1 overflow-y-auto custom-scrollbar">';

        foreach ($links as $l) {
            $perm = $l['permission'] ?? $l['id'];
            if ($allowed !== null && !in_array($perm, $allowed)) {
                continue;
            }
            $isActive = ($activePage === $l['id']);
            $badgeCount = (int)($l['badge'] ?? 0);
            $badgeAttr = isset($l['badge']) ? ' data-badge="ready-repairs"' : '';
            $badgeHidden = ($badgeCount <= 0) ? ' hidden' : '';
            $badgeColors = $isActive ? 'bg-[#15171a] text-[#c6e24a]' : 'bg-[#c6e24a] text-[#15171a]';
            $badgeHtml = isset($l['badge'])
                ? '<span' . $badgeAttr . ' class="ml-auto inline-flex items-center px-1.5 py-0.5 rounded-[2px] text-[11px] font-mono font-bold ' . $badgeColors . $badgeHidden . '" title="' . ($l['badge_title'] ?? 'Pendientes') . '">' . $badgeCount . '</span>'
                : '';

            echo '<a href="' . $l['url'] . '" class="sys-sidebar-link' . ($isActive ? ' is-active' : '') . '">
                    <span class="material-symbols-outlined">' . $l['icon'] . '</span>
                    <span class="truncate">' . $l['label'] . '</span>' . $badgeHtml . '
                  </a>';
        }

        $isHelpActive = ($activePage === 'ayuda');

        echo '
    </nav>
    
    <div class="sys-sidebar-footer border-t border-[#222729] mt-auto">
        <a href="' . BASE_URL . '/help.php" class="sys-sidebar-help' . ($isHelpActive ? ' is-active' : '') . '">
            <span class="material-symbols-outlined">help</span>
            <span>Ayuda</span>
        </a>
        <a href="' . BASE_URL . '/modules/auth/index.php?action=logout" class="sys-sidebar-logout">
            <span class="material-symbols-outlined">logout</span>
            <span>Cerrar Sesión</span>
        </a>
        <div class="sys-sidebar-user flex items-center gap-3 border-t border-[#222729]">
            <div class="w-9 h-9 flex-shrink-0 rounded-[2px] bg-[#222729] border border-[#30363a] flex items-center justify-center text-[#c6e24a] font-mono font-bold text-sm">
                ' . $initial . '
            </div>
            <div class="min-w-0">
                <p class="text-[13.5px] font-semibold text-[#e9e6db] truncate leading-tight">' . $username . '</p>
                <p class="text-[10.5px] text-[#9ba19f] font-mono uppercase tracking-wider mt-0.5 leading-none">Administrador</p>
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

        echo self::renderModuleTopBar((string)$activePage) . '

<aside id="mainSidebar" class="fixed left-0 top-0 h-screen w-64 border-r border-[#222729] bg-[#15181a] flex flex-col z-50 md:z-40">
    <div class="sys-sidebar-header flex items-center justify-between border-b border-[#222729]">
        <div>
            <h1 class="sys-sidebar-brand font-bold font-display tracking-tight text-[#e9e6db]">NOKIA<span class="text-[#c6e24a] font-mono ml-1">1100</span></h1>
            <p class="sys-sidebar-role text-[#9ba19f] font-mono mt-0.5 uppercase">' . ($role === 'guest' ? 'Invitado' : 'Operario') . '</p>
        </div>
        <button id="closeSidebarMobile" type="button" aria-label="Cerrar menú" class="md:hidden w-8 h-8 rounded-[2px] bg-[#222729] border border-[#30363a] flex items-center justify-center text-[#9ba19f] hover:text-[#e9e6db] transition-colors">
            <span class="material-symbols-outlined text-[19px]">close</span>
        </button>
    </div>
    
    <nav class="sys-sidebar-nav flex-1 overflow-y-auto custom-scrollbar">';

        foreach ($links as $l) {
            if (!self::hasAccess($l['id'])) {
                continue;
            }
            $isActive = ($activePage === $l['id'] || ($activePage === 'ventas' && $l['id'] === 'venta') || ($activePage === 'venta' && $l['id'] === 'ventas'));
            $badgeCount = (int)($l['badge'] ?? 0);
            $badgeAttr = isset($l['badge']) ? ' data-badge="ready-repairs"' : '';
            $badgeHidden = ($badgeCount <= 0) ? ' hidden' : '';
            $badgeColors = $isActive ? 'bg-[#15171a] text-[#c6e24a]' : 'bg-[#c6e24a] text-[#15171a]';
            $badgeHtml = isset($l['badge'])
                ? '<span' . $badgeAttr . ' class="ml-auto inline-flex items-center px-1.5 py-0.5 rounded-[2px] text-[11px] font-mono font-bold ' . $badgeColors . $badgeHidden . '" title="' . ($l['badge_title'] ?? 'Pendientes') . '">' . $badgeCount . '</span>'
                : '';

            echo '<a href="' . $l['url'] . '" class="sys-sidebar-link' . ($isActive ? ' is-active' : '') . '">
                    <span class="material-symbols-outlined">' . $l['icon'] . '</span>
                    <span class="truncate">' . $l['label'] . '</span>' . $badgeHtml . '
                  </a>';
        }

        $isHelpActive = ($activePage === 'ayuda');

        echo '
    </nav>
    <div class="sys-sidebar-footer border-t border-[#222729] mt-auto">
        <a href="' . BASE_URL . '/help.php" class="sys-sidebar-help' . ($isHelpActive ? ' is-active' : '') . '">
            <span class="material-symbols-outlined">help</span>
            <span>Ayuda</span>
        </a>
        <a href="' . BASE_URL . '/modules/auth/index.php?action=logout" class="sys-sidebar-logout">
            <span class="material-symbols-outlined">logout</span>
            <span>Cerrar Sesión</span>
        </a>
        <div class="sys-sidebar-user flex items-center gap-3 border-t border-[#222729]">
            <div class="w-9 h-9 flex-shrink-0 rounded-[2px] bg-[#222729] border border-[#30363a] flex items-center justify-center text-[#c6e24a] font-mono font-bold text-sm">
                ' . $initial . '
            </div>
            <div class="min-w-0">
                <p class="text-[13.5px] font-semibold text-[#e9e6db] truncate leading-tight">' . $username . '</p>
                <p class="text-[10.5px] text-[#9ba19f] font-mono uppercase tracking-wider mt-0.5 leading-none">' . ($role === 'guest' ? 'Invitado' : 'Operario') . '</p>
            </div>
        </div>
    </div>
</aside>';
    }

    /**
     * Renderiza los botones de exportación individuales (Excel y PDF)
     */
    public static function renderExportButtons(string $tableId, string $title, string $filename): string
    {
        $tableIdEsc = htmlspecialchars($tableId, ENT_QUOTES, 'UTF-8');
        $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $filenameEsc = htmlspecialchars($filename, ENT_QUOTES, 'UTF-8');
        $svgDown = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M4 21h16"/></svg>';

        return '
        <button type="button" onclick="exportTableToExcel(\'' . $tableIdEsc . '\', \'' . $filenameEsc . '\', this)" class="btn-export" title="Exportar a Excel">
            ' . $svgDown . 'Excel
        </button>
        <button type="button" onclick="exportTableToPDF(\'' . $tableIdEsc . '\', \'' . $titleEsc . '\', \'' . $filenameEsc . '\', this)" class="btn-export" title="Exportar a PDF">
            ' . $svgDown . 'PDF
        </button>';
    }

    /**
     * Compatibilidad hacia atrás
     */
    public static function renderExportSplitButton(string $tableId, string $title, string $filename): string
    {
        return self::renderExportButtons($tableId, $title, $filename);
    }

    /**
     * Devuelve la clase CSS sutil correspondiente a cada medio de pago.
     */
    public static function getPaymentBadgeClass(?string $method): string
    {
        $raw = trim((string)($method ?? ''));
        $m = mb_strtolower($raw, 'UTF-8');

        if (str_contains($m, 'efectivo') || str_contains($m, 'contado')) {
            return 'badge-payment badge-payment-efectivo';
        }
        if (
            str_contains($m, 'tarjeta') ||
            str_contains($m, 'debito') ||
            str_contains($m, 'débito') ||
            str_contains($m, 'credito') ||
            str_contains($m, 'crédito') ||
            str_contains($m, 'posnet')
        ) {
            return 'badge-payment badge-payment-tarjeta';
        }
        if (
            str_contains($m, 'transf') ||
            str_contains($m, 'banco') ||
            str_contains($m, 'deposito') ||
            str_contains($m, 'depósito') ||
            str_contains($m, 'cbu') ||
            str_contains($m, 'cvu') ||
            str_contains($m, 'alias')
        ) {
            return 'badge-payment badge-payment-transferencia';
        }
        if (
            str_contains($m, 'mercado') ||
            preg_match('/\bmp\b/u', $m) === 1 ||
            str_contains($m, 'qr') ||
            str_contains($m, 'billetera')
        ) {
            return 'badge-payment badge-payment-mp';
        }
        return 'badge-payment badge-payment-default';
    }

    /**
     * Renderiza la insignia HTML con colores sutiles para diferenciar medios de pago en tablas.
     */
    public static function renderPaymentBadge(?string $method): string
    {
        $raw = trim((string)($method ?? ''));
        if ($raw === '') {
            $raw = 'N/A';
        }
        $cls = self::getPaymentBadgeClass($raw);
        return '<span class="' . $cls . '">' . htmlspecialchars($raw, ENT_QUOTES, 'UTF-8') . '</span>';
    }

    public static function renderPagination(
        int $page,
        int $totalPages,
        int $totalItems,
        string $singularLabel,
        string $pluralLabel,
        array $queryArgs = []
    ): string {
        $totalItems = max(0, $totalItems);
        $totalPages = max(1, $totalPages);
        $page = max(1, min($page, $totalPages));
        $entity = ($totalItems === 1) ? $singularLabel : $pluralLabel;

        $infoHtml = '<span class="sys-pagination-info">Página ' . $page . ' de ' . $totalPages . ' - ' . $totalItems . ' ' . htmlspecialchars($entity, ENT_QUOTES, 'UTF-8') . '</span>';

        if ($totalPages <= 1) {
            return '<div class="sys-pagination">' . $infoHtml . '</div>';
        }

        $items = [];
        if ($totalPages <= 7) {
            for ($i = 1; $i <= $totalPages; $i++) {
                $items[] = $i;
            }
        } else {
            $items[] = 1;
            $start = max(2, $page - 1);
            $end = min($totalPages - 1, $page + 1);
            if ($page <= 3) {
                $start = 2;
                $end = 4;
            } elseif ($page >= $totalPages - 2) {
                $start = $totalPages - 3;
                $end = $totalPages - 1;
            }
            if ($start > 2) {
                $items[] = '...';
            }
            for ($i = $start; $i <= $end; $i++) {
                $items[] = $i;
            }
            if ($end < $totalPages - 1) {
                $items[] = '...';
            }
            $items[] = $totalPages;
        }

        $navHtml = '<nav class="sys-pagination-nav" aria-label="Paginación">';

        if ($page > 1) {
            $prevArgs = $queryArgs;
            $prevArgs['page'] = $page - 1;
            $navHtml .= '<a href="?' . htmlspecialchars(http_build_query($prevArgs), ENT_QUOTES, 'UTF-8') . '" class="sys-page-btn" aria-label="Página anterior" title="Página anterior">&lsaquo;</a>';
        } else {
            $navHtml .= '<span class="sys-page-btn is-disabled" aria-disabled="true">&lsaquo;</span>';
        }

        foreach ($items as $item) {
            if ($item === '...') {
                $navHtml .= '<span class="sys-page-ellipsis">&hellip;</span>';
            } else {
                $numArgs = $queryArgs;
                $numArgs['page'] = $item;
                $activeCls = ($item === $page) ? ' is-active' : '';
                $ariaCurrent = ($item === $page) ? ' aria-current="page"' : '';
                $navHtml .= '<a href="?' . htmlspecialchars(http_build_query($numArgs), ENT_QUOTES, 'UTF-8') . '" class="sys-page-btn' . $activeCls . '"' . $ariaCurrent . '>' . $item . '</a>';
            }
        }

        if ($page < $totalPages) {
            $nextArgs = $queryArgs;
            $nextArgs['page'] = $page + 1;
            $navHtml .= '<a href="?' . htmlspecialchars(http_build_query($nextArgs), ENT_QUOTES, 'UTF-8') . '" class="sys-page-btn" aria-label="Página siguiente" title="Página siguiente">&rsaquo;</a>';
        } else {
            $navHtml .= '<span class="sys-page-btn is-disabled" aria-disabled="true">&rsaquo;</span>';
        }

        $navHtml .= '</nav>';

        return '<div class="sys-pagination">' . $infoHtml . $navHtml . '</div>';
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
        const mobileClose = document.getElementById("closeSidebarMobile");
        const backdrop = document.getElementById("sidebarBackdrop");

        function openMobileMenu() {
            document.body.classList.remove("sidebar-preload");
            document.body.classList.add("mobile-sidebar-open");
        }
        function closeMobileMenu() {
            document.body.classList.remove("mobile-sidebar-open");
        }

        function syncSidebarToggleState() {
            const isCollapsed = document.body.classList.contains("sidebar-collapsed");
            if (toggleBtn) {
                toggleBtn.setAttribute("aria-expanded", String(!isCollapsed));
                toggleBtn.setAttribute("title", isCollapsed ? "Desplegar menú lateral" : "Ocultar menú lateral");
            }
        }

        if (toggleBtn) {
            if (localStorage.getItem("sidebarCollapsed") === "true") {
                document.body.classList.add("sidebar-collapsed");
            }
            syncSidebarToggleState();

            toggleBtn.addEventListener("click", function() {
                document.body.classList.remove("sidebar-preload");
                if (window.innerWidth < 768) {
                    if (document.body.classList.contains("mobile-sidebar-open")) {
                        closeMobileMenu();
                    } else {
                        openMobileMenu();
                    }
                    return;
                }
                document.body.classList.toggle("sidebar-collapsed");
                const collapsed = document.body.classList.contains("sidebar-collapsed");
                try {
                    localStorage.setItem("sidebarCollapsed", String(collapsed));
                } catch (e) {}
                syncSidebarToggleState();
            });
        }

        // Habilitar transiciones suaves tras el primer pintado para evitar saltos entre módulos
        requestAnimationFrame(function() {
            requestAnimationFrame(function() {
                document.body.classList.remove("sidebar-preload");
            });
        });

        // Reloj en tiempo real para paneles con [data-live-clock] y [data-live-date]
        const clockNodes = document.querySelectorAll("[data-live-clock]");
        const dateNodes = document.querySelectorAll("[data-live-date]");
        if (clockNodes.length > 0 || dateNodes.length > 0) {
            const timeFmt = new Intl.DateTimeFormat("es-AR", {
                timeZone: "America/Argentina/Buenos_Aires",
                hour: "2-digit",
                minute: "2-digit",
                second: "2-digit",
                hour12: false
            });
            const dateFmt = new Intl.DateTimeFormat("es-AR", {
                timeZone: "America/Argentina/Buenos_Aires",
                day: "2-digit",
                month: "2-digit",
                year: "numeric"
            });
            const tickClock = function() {
                const now = new Date();
                const tStr = timeFmt.format(now);
                const dStr = dateFmt.format(now);
                clockNodes.forEach(function(el) { el.textContent = tStr; });
                dateNodes.forEach(function(el) { el.textContent = dStr; });
            };
            tickClock();
            setInterval(tickClock, 1000);
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
    });
</script>
</body></html>';
    }
}
