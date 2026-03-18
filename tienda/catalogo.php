<?php
// tienda/catalogo.php
session_start();
require_once '../config/bd.php';

// --- Parámetros de Filtrado ---
$search = isset($_GET['q']) ? $conn->real_escape_string(trim($_GET['q'])) : '';
$marca = isset($_GET['marca']) ? $_GET['marca'] : []; // Array
$cat = isset($_GET['categoria']) ? $conn->real_escape_string($_GET['categoria']) : '';
$min_price = isset($_GET['min_price']) ? intval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? intval($_GET['max_price']) : 5000000;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 9; // Productos por página
$offset = ($page - 1) * $limit;

// --- Construcción de Query ---
$where = ["p.is_active = 1", "i.cantidad > 0"];

if ($search) {
    $where[] = "(p.nombre LIKE '%$search%' OR d.modelo LIKE '%$search%')";
}
if ($cat) {
    $where[] = "d.categoria = '$cat'";
}
if (!empty($marca)) {
    $marcas_str = implode("','", array_map(function($m) use ($conn) { return $conn->real_escape_string($m); }, $marca));
    $where[] = "d.marca IN ('$marcas_str')";
}
if ($max_price > 0) {
    $where[] = "p.precio BETWEEN $min_price AND $max_price";
}

$where_sql = "WHERE " . implode(" AND ", $where);

// Count Total for Pagination
$sql_count = "SELECT COUNT(*) as total FROM inventario i 
              JOIN producto p ON i.id_producto = p.id_producto
              JOIN producto_detalle d ON p.id_producto = d.id_producto 
              $where_sql";
$total_rows = $conn->query($sql_count)->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch Products
$sql = "SELECT p.id_producto, p.nombre, p.precio, d.marca, d.modelo, d.imagen_url as old_img, d.categoria,
               (SELECT nombre_archivo FROM producto_imagen WHERE id_producto = p.id_producto AND es_principal = 1 LIMIT 1) as main_img
        FROM inventario i
        JOIN producto p ON i.id_producto = p.id_producto
        JOIN producto_detalle d ON p.id_producto = d.id_producto
        $where_sql
        ORDER BY p.nombre ASC
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Fetch Sidebar Data (Brands, Categories)
$brands = $conn->query("SELECT DISTINCT marca FROM producto_detalle ORDER BY marca");
$categories = $conn->query("SELECT DISTINCT categoria FROM producto_detalle WHERE categoria IS NOT NULL ORDER BY categoria");

// Cart Count
$cart_count = isset($_SESSION['carrito']) ? count($_SESSION['carrito']) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo - Nokia Store</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .layout-catalog {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 2rem;
            padding-top: 100px; /* Space for fixed navbar */
            padding-bottom: 5rem;
            align-items: start;
        }
        .sidebar {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            position: sticky;
            top: 100px;
        }
        .filter-group { margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border); }
        .filter-group:last-child { border-bottom: none; }
        .filter-title { font-weight: 700; margin-bottom: 1rem; color: var(--text-main); }
        .checkbox-label { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; cursor: pointer; color: var(--text-muted); font-size: 0.9rem; }
        .checkbox-label:hover { color: var(--text-main); }
        
        .pagination { display: flex; justify-content: center; gap: 0.5rem; margin-top: 3rem; }
        .page-link { 
            padding: 0.5rem 1rem; border: 1px solid var(--border); border-radius: 6px; 
            background: var(--bg-card); color: var(--text-muted); 
        }
        .page-link.active { background: var(--primary); color: black; border-color: var(--primary); }
        .page-link:hover:not(.active) { border-color: var(--text-muted); }

        @media (max-width: 900px) {
            .layout-catalog { grid-template-columns: 1fr; }
            .sidebar { position: static; margin-bottom: 2rem; }
        }
    </style>
</head>
<body>
    
    <!-- Navbar (Reuse) -->
    <nav class="navbar">
        <div class="container nav-inner">
            <a href="index.php" class="logo">NOKIA<span>STORE</span></a>
            
            <div class="nav-links">
                <a href="index.php" class="nav-link">Inicio</a>
                <a href="catalogo.php" class="nav-link active">Catálogo</a>
                <a href="mis_pedidos.php" class="nav-link">Mis Pedidos</a>
                
                <a href="carrito.php" class="nav-link" style="position: relative; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span>CARRITO</span>
                    <?php if($cart_count > 0): ?>
                        <span class="badge-count"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>

                <?php if (isset($_SESSION['cliente_id'])): ?>
                    <div style="display:flex; gap: 1rem; align-items: center; margin-left: 1rem; padding-left: 1rem; border-left: 1px solid rgba(255,255,255,0.1);">
                        <span style="color: var(--text-muted); font-size: 0.85rem;">Hola, <strong><?php echo htmlspecialchars($_SESSION['cliente_email']); ?></strong></span>
                        <a href="auth/logout.php" class="btn btn-sm btn-outline" style="border-color: rgba(239, 68, 68, 0.3); color: var(--error);">Salir</a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="nav-link">Login</a>
                    <a href="register.php" class="btn btn-primary btn-sm">Registro</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container layout-catalog">
        
        <!-- Sidebar -->
        <aside class="sidebar">
            <?php include '../includes/filtros.php'; ?>
        </aside>

        <!-- Main Content -->
        <main>
            <!-- Search Bar & Results Info -->
            <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <h1 style="font-size: 1.5rem; font-weight: 700;">Catálogo de Productos</h1>
                
                <form action="" method="GET" style="display: flex; gap: 0.5rem;">
                    <input type="text" name="q" class="form-control" placeholder="Buscar producto..." value="<?php echo htmlspecialchars($search); ?>" style="width: 200px; margin-bottom: 0;">
                    <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">FILTRAR</button>
                </form>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <div class="grid-products">
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="card">
                            <div class="card-img">
                                <?php 
                                    $img_src = $row['main_img'] 
                                        ? "../uploads/productos/{$row['id_producto']}/{$row['main_img']}" 
                                        : ($row['old_img'] ?? 'https://via.placeholder.com/300');
                                ?>
                                <img src="<?php echo $img_src; ?>" alt="<?php echo htmlspecialchars($row['nombre']); ?>">
                            </div>
                            <div class="card-body">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <span class="card-badge"><?php echo htmlspecialchars($row['marca']); ?></span>
                                    <?php if($row['categoria']): ?>
                                        <span style="font-size: 0.7rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['categoria']); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <h3 class="card-title"><?php echo htmlspecialchars($row['modelo']); ?></h3>
                                <div class="card-price">$<?php echo number_format($row['precio'], 0, ',', '.'); ?></div>
                                <a href="producto.php?id=<?php echo $row['id_producto']; ?>" class="btn btn-outline" style="margin-top: 1rem; width: 100%;">Ver Detalles</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&q=<?php echo urlencode($search); ?>&cat=<?php echo urlencode($cat); ?>" 
                               class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                               <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-error">No se encontraron productos que coincidan con tu búsqueda.</div>
            <?php endif; ?>
        </main>
    </div>


</body>
</html>
