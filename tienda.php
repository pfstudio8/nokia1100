<?php
session_start();
require_once 'config/bd.php';

// Obtener marcas disponibles para el filtro
$brands_sql = "SELECT DISTINCT marca FROM producto_detalle ORDER BY marca ASC";
$brands_result = $conn->query($brands_sql);

// Filtros
$selected_brands = isset($_GET['brands']) ? $_GET['brands'] : [];
$max_price = isset($_GET['price']) ? $_GET['price'] : 5000000; // Valor alto por defecto
$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Construir consulta dinámica
$sql = "SELECT p.id_producto, p.nombre, d.marca, d.modelo, i.cantidad, p.precio, d.descripcion,
               (SELECT nombre_archivo FROM producto_imagen WHERE id_producto = p.id_producto AND es_principal = 1 LIMIT 1) as main_img
        FROM inventario i
        JOIN producto p ON i.id_producto = p.id_producto
        JOIN producto_detalle d ON p.id_producto = d.id_producto
        WHERE p.is_active = 1 AND i.cantidad > 0";

// Aplicar búsqueda por categoría/texto
if ($search_query) {
    $sql .= " AND (p.nombre LIKE '%$search_query%' OR d.marca LIKE '%$search_query%' OR d.modelo LIKE '%$search_query%' OR d.descripcion LIKE '%$search_query%')";
}

// Aplicar filtro de marca
if (!empty($selected_brands)) {
    $brands_list = implode("','", array_map(function($b) use ($conn) { return $conn->real_escape_string($b); }, $selected_brands));
    $sql .= " AND d.marca IN ('$brands_list')";
}

// Aplicar filtro de precio
if (isset($_GET['price'])) {
    $sql .= " AND p.precio <= " . intval($max_price);
}

$sql .= " ORDER BY p.nombre ASC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda Online - Nokia 1100</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Estilos específicos para la tienda */
        .store-header {
            text-align: center;
            padding: 4rem 1rem;
            background: radial-gradient(circle at center, rgba(16, 185, 129, 0.15) 0%, transparent 70%);
        }

        .store-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: linear-gradient(to right, #10b981, #38bdf8);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -1px;
        }

        .store-subtitle {
            color: var(--text-muted);
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Layout con Sidebar */
        /* Layout con Sidebar */
        .store-layout {
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            align-items: start;
        }

        @media (max-width: 1024px) {
            .store-layout {
                grid-template-columns: 1fr;
            }
        }

        /* Sidebar Styles */
        .sidebar {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            position: sticky;
            top: 6rem;
        }

        .filter-section {
            margin-bottom: 2rem;
        }

        .filter-title {
            color: var(--primary);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1.25rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border);
        }

        .filter-option {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            cursor: pointer;
            color: var(--text-muted);
            font-size: 0.875rem;
            transition: color 0.2s;
        }

        .filter-option:hover {
            color: var(--text-main);
        }

        .filter-option input[type="checkbox"] {
            width: 16px;
            height: 16px;
            margin-right: 0.75rem;
            accent-color: var(--primary);
            cursor: pointer;
        }

        /* Grid Styles */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1.5rem;
        }

        .product-card {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .product-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .product-image-container {
            width: 100%;
            height: 180px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .product-content {
            padding: 1.25rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-brand {
            font-size: 0.7rem;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 800;
            margin-bottom: 0.25rem;
        }

        .product-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--text-main);
            line-height: 1.4;
        }

        .product-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-main);
            margin-top: auto;
            margin-bottom: 1rem;
        }

        .stock-tag {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            background: var(--primary-soft);
            color: var(--primary);
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 700;
            backdrop-filter: blur(4px);
            border: 1px solid var(--primary-soft);
        }
        
        .nav-bar {
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .nav-logo {
            font-weight: 700;
            font-size: 1.2rem;
            color: white;
            text-decoration: none;
        }

        /* FIX: Override global body styles that center content */
        body {
            display: block !important;
            justify-content: unset !important;
            align-items: unset !important;
            padding-bottom: 2rem;
        }

        /* Refined Header Typography */
        .store-title {
            font-size: 2.5rem; /* Smaller and finer */
            font-weight: 600; /* Lighter weight */
            margin-bottom: 0.5rem;
            background: linear-gradient(to right, #10b981, #38bdf8);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }

        .store-subtitle {
            color: var(--text-muted);
            font-size: 1rem;
            font-weight: 400;
            max-width: 600px;
            margin: 0 auto;
            opacity: 0.8;
        }

        /* Category Navigation */
        .category-nav {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 3rem;
            flex-wrap: wrap;
        }

        .category-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: var(--text-muted);
            transition: all 0.3s ease;
            gap: 0.5rem;
            min-width: 80px;
        }

        .category-item:hover, .category-item.active {
            color: var(--primary-color);
            transform: translateY(-5px);
        }

        .category-icon {
            font-size: 1.5rem;
            background: rgba(255,255,255,0.05);
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .category-item:hover .category-icon, .category-item.active .category-icon {
            border-color: var(--primary-color);
            background: rgba(16, 185, 129, 0.1);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        .category-item span {
            font-size: 0.85rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <nav class="nav-bar">
        <a href="index.php" class="nav-logo">NOKIA 1100 SYSTEM</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="<?php echo $_SESSION['role'] === 'admin' ? 'panel_admin.php' : 'panel_empleado.php'; ?>" style="color: var(--text-muted); text-decoration: none;">Volver al Panel</a>
        <?php else: ?>
            <a href="index.php" style="color: var(--text-muted); text-decoration: none;">Iniciar Sesión</a>
        <?php endif; ?>
    </nav>

    <header class="store-header">
        <h1 class="store-title">Nuestra Tienda</h1>
        <p class="store-subtitle">Descubrí lo último en tecnología móvil y accesorios con la mejor garantía.</p>
        
        <div class="category-nav">
            <a href="tienda.php?search=celular" class="category-item <?php echo (isset($_GET['search']) && $_GET['search'] == 'celular') ? 'active' : ''; ?>">
                <div class="category-icon">📱</div>
                <span>Celulares</span>
            </a>
            <a href="tienda.php?search=accesorio" class="category-item <?php echo (isset($_GET['search']) && $_GET['search'] == 'accesorio') ? 'active' : ''; ?>">
                <div class="category-icon">⌚</div>
                <span>Accesorios</span>
            </a>
            <a href="tienda.php?search=audio" class="category-item <?php echo (isset($_GET['search']) && $_GET['search'] == 'audio') ? 'active' : ''; ?>">
                <div class="category-icon">🎧</div>
                <span>Audio</span>
            </a>
        </div>
    </header>

    <div class="store-layout">
        <!-- Sidebar de Filtros -->
        <aside class="sidebar">
            <form action="" method="GET" id="filterForm">
                <div class="filter-section">
                    <h3 class="filter-title">Filtrar por Marca</h3>
                    <?php if ($brands_result && $brands_result->num_rows > 0): ?>
                        <?php while($brand = $brands_result->fetch_assoc()): ?>
                            <label class="filter-option">
                                <input type="checkbox" name="brands[]" value="<?php echo htmlspecialchars($brand['marca']); ?>"
                                    <?php echo in_array($brand['marca'], $selected_brands) ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($brand['marca']); ?>
                            </label>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">No hay marcas disponibles</p>
                    <?php endif; ?>
                </div>

                <div class="filter-section">
                    <h3 class="filter-title">Precio Máximo</h3>
                    <div class="price-slider-container">
                        <input type="range" name="price" min="0" max="2000000" step="10000" 
                               value="<?php echo $max_price; ?>" 
                               style="width: 100%; accent-color: var(--primary);"
                               oninput="document.getElementById('priceValue').innerText = '$' + parseInt(this.value).toLocaleString('es-AR')">
                        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 600; margin-top: 0.5rem;">
                            <span>$0</span>
                            <span id="priceValue" style="color: var(--primary);">$<?php echo number_format($max_price, 0, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Aplicar Filtros</button>
                <?php if (!empty($selected_brands) || isset($_GET['price'])): ?>
                    <a href="tienda.php" style="display: block; text-align: center; margin-top: 1rem; color: var(--text-dim); font-size: 0.8rem; text-decoration: none;">Limpiar Filtros</a>
                <?php endif; ?>
            </form>
        </aside>

        <!-- Grid de Productos -->
        <main class="products-grid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="product-card">
                        <div class="product-image-container">
                            <?php if($row['main_img']): ?>
                                <img src="uploads/productos/<?php echo $row['id_producto']; ?>/<?php echo $row['main_img']; ?>" 
                                     alt="Producto" 
                                     style="max-width:100%; max-height: 100%; object-fit: contain;">
                            <?php else: ?>
                                <span style="font-size: 3rem;">📱</span>
                            <?php endif; ?>
                            <span class="stock-tag">Stock: <?php echo $row['cantidad']; ?></span>
                        </div>
                        <div class="product-content">
                            <div class="product-brand"><?php echo htmlspecialchars($row['marca']); ?></div>
                            <h3 class="product-title"><?php echo htmlspecialchars($row['modelo']); ?></h3>
                            <div class="product-price">$<?php echo number_format($row['precio'], 0, ',', '.'); ?></div>
                            <a href="tienda/producto.php?id=<?php echo $row['id_producto']; ?>" class="btn btn-primary" style="font-size: 0.85rem; padding: 0.6rem;">Ver Detalles</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 4rem; color: var(--text-muted);">
                    <h3>No se encontraron productos con los filtros seleccionados.</h3>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <footer style="text-align: center; padding: 4rem; color: var(--text-muted); border-top: 1px solid var(--border-color); margin-top: 4rem;">
        <p>&copy; <?php echo date('Y'); ?> Nokia 1100 System. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
