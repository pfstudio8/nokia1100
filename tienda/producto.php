<?php
// tienda/producto.php
session_start();
require_once '../config/bd.php';

if (!isset($_GET['id'])) { header("Location: catalogo.php"); exit(); }
$id = intval($_GET['id']);

// Fetch Product
$sql = "SELECT p.id_producto, p.nombre, p.precio, 
               d.marca, d.modelo, d.descripcion as desc_larga, d.imagen_url, i.cantidad
        FROM inventario i
        JOIN producto p ON i.id_producto = p.id_producto
        JOIN producto_detalle d ON p.id_producto = d.id_producto
        WHERE p.id_producto = $id";

$result = $conn->query($sql);
if (!$result) {
    die("Error al cargar producto: " . $conn->error);
}
if ($result->num_rows == 0) {
    header("Location: catalogo.php");
    exit();
}
$item = $result->fetch_assoc();

$sql_img = "SELECT * FROM producto_imagen WHERE id_producto = $id ORDER BY es_principal DESC, orden ASC";
$res_img = $conn->query($sql_img);
$imagenes = [];
while($img = $res_img->fetch_assoc()) {
    $imagenes[] = $img;
}

// Fallback image if empty
if (empty($imagenes)) {
    // Check if old image link exists in detail (optional fallback)
    $old_img = $item['imagen_url'] ?? 'https://via.placeholder.com/500?text=Sin+Imagen';
    $imagenes[] = ['nombre_archivo' => null, 'url_absoluta' => $old_img];
}

// Cart Count
$cart_count = isset($_SESSION['carrito']) ? count($_SESSION['carrito']) : 0;

// Agregar al Carrito
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $qty = intval($_POST['cantidad']);
    if ($qty > 0 && $qty <= $item['cantidad']) {
        if (!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];
        
        $found = false;
        foreach ($_SESSION['carrito'] as &$c) {
            if ($c['id'] == $id) {
                if ($c['cantidad'] + $qty <= $item['cantidad']) {
                    $c['cantidad'] += $qty;
                    $found = true;
                } else {
                    $error = "No hay suficiente stock disponible. Tienes " . $c['cantidad'] . " en el carrito.";
                }
                break;
            }
        }
        if (!$found && !isset($error)) {
            $_SESSION['carrito'][] = [
                'id' => $id,
                'nombre' => $item['nombre'] . ' ' . $item['modelo'],
                'precio' => $item['precio'],
                'cantidad' => $qty,
                'imagen' => $item['imagen_url'] // Guardar imagen para el carrito
            ];
        }
        if (!isset($error)) {
            header("Location: carrito.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($item['nombre']); ?> - Nokia Store</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .product-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            padding-top: 100px; /* Space for fixed navbar */
            padding-bottom: 5rem;
        }
        .gallery-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .main-image {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 500px;
            width: 100%;
            overflow: hidden;
        }
        .main-image img { 
            max-height: 100%; 
            max-width: 100%; 
            object-fit: contain; 
            transition: opacity 0.3s;
        }
        .thumbs-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.5rem;
        }
        .thumb {
            aspect-ratio: 1;
            border: 1px solid var(--border);
            border-radius: 8px;
            cursor: pointer;
            overflow: hidden;
            opacity: 0.7;
            transition: all 0.2s;
        }
        .thumb:hover, .thumb.active {
            opacity: 1;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px var(--primary-low);
        }
        .thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .specs-list {
            margin: 2rem 0;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        .spec-item {
            background: rgba(255,255,255,0.03);
            padding: 1rem;
            border-radius: 8px;
        }
        .spec-label { color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; }
        .spec-value { font-weight: 600; font-size: 1.1rem; }

        @media (max-width: 800px) {
            .product-grid { grid-template-columns: 1fr; gap: 2rem; }
            .main-image { height: 300px; }
        }
    </style>
</head>
<body>
    
    <nav class="navbar">
        <div class="container nav-inner">
            <a href="index.php" class="logo">NOKIA<span>STORE</span></a>
            
            <div class="nav-links">
                <a href="index.php" class="nav-link">Inicio</a>
                <a href="catalogo.php" class="nav-link">Catálogo</a>
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

    <div class="container product-grid">
        <!-- Image Section -->
        <div class="gallery-container">
            <div class="main-image">
                <?php 
                    $main_src = isset($imagenes[0]['url_absoluta']) ? $imagenes[0]['url_absoluta'] : "../uploads/productos/$id/" . $imagenes[0]['nombre_archivo'];
                ?>
                <img id="mainImg" src="<?php echo $main_src; ?>" alt="Producto">
            </div>
            <?php if(count($imagenes) > 1): ?>
                <div class="thumbs-grid">
                    <?php foreach($imagenes as $idx => $img): 
                        $src = isset($img['url_absoluta']) ? $img['url_absoluta'] : "../uploads/productos/$id/" . $img['nombre_archivo'];
                    ?>
                        <div class="thumb <?php echo $idx === 0 ? 'active' : ''; ?>" onclick="changeImage(this, '<?php echo $src; ?>')">
                            <img src="<?php echo $src; ?>" alt="Thumbnail">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    <script>
        function changeImage(el, src) {
            document.getElementById('mainImg').style.opacity = 0;
            document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
            el.classList.add('active');
            
            setTimeout(() => {
                document.getElementById('mainImg').src = src;
                document.getElementById('mainImg').style.opacity = 1;
            }, 200);
        }
    </script>

        <!-- Info Section -->
        <div>
            <span class="card-badge"><?php echo htmlspecialchars($item['marca']); ?></span>
            <h1 style="font-size: 2.5rem; margin: 0.5rem 0; line-height: 1.1;"><?php echo htmlspecialchars($item['modelo']); ?></h1>
            <p style="font-size: 1.2rem; color: var(--text-muted);"><?php echo htmlspecialchars($item['desc_corta'] ?? $item['nombre']); ?></p>

            <div style="font-size: 2.5rem; font-weight: 700; color: var(--primary); margin: 2rem 0;">
                $<?php echo number_format($item['precio'], 0, ',', '.'); ?>
                <span style="font-size: 1rem; color: var(--text-muted); font-weight: 400;">Final</span>
            </div>

            <?php if ($item['cantidad'] > 0): ?>
                <div class="alert alert-success" style="display: inline-block; padding: 0.5rem 1rem; font-size: 0.9rem; font-weight: 600;">
                    STOCK DISPONIBLE (<?php echo $item['cantidad']; ?> unidades)
                </div>
                
                <form method="POST" style="margin-top: 2rem;">
                    <div style="display: flex; gap: 1rem; align-items: center; justify-content: flex-start;">
                         <input type="number" name="cantidad" value="1" min="1" max="<?php echo $item['cantidad']; ?>" 
                                class="form-control" style="width: 80px; margin-bottom: 0; text-align: center; font-size: 1.2rem;">
                         <button type="submit" name="add_to_cart" class="btn btn-primary" style="margin-top:0; padding: 1rem 3rem; font-size: 1.1rem; letter-spacing: 0.05em;">
                            AÑADIR AL CARRITO
                         </button>
                    </div>
                    <?php if(isset($error)): ?><p style="color: var(--error); margin-top: 0.5rem;"><?php echo $error; ?></p><?php endif; ?>
                </form>
            <?php else: ?>
                <div class="alert alert-error" style="font-weight: 700;">SIN STOCK</div>
            <?php endif; ?>

            <div class="specs-list">
                <div class="spec-item">
                    <div class="spec-label">Marca</div>
                    <div class="spec-value"><?php echo htmlspecialchars($item['marca']); ?></div>
                </div>
                <div class="spec-item">
                    <div class="spec-label">Modelo</div>
                    <div class="spec-value"><?php echo htmlspecialchars($item['modelo']); ?></div>
                </div>
                <!-- Extend with more specs if available -->
            </div>
            
            <h3 style="margin-top: 2rem;">Descripción</h3>
            <p style="color: var(--text-muted); margin-top: 0.5rem; line-height: 1.6;">
                <?php echo nl2br(htmlspecialchars($item['desc_larga'] ?? 'Sin descripción adicional.')); ?>
            </p>
        </div>
    </div>
    
    <!-- Related Products -->
    <?php
    $cat_safe = $conn->real_escape_string($item['marca']); // Using Brand as primary relational factor for now, or use Category if available
    // Better: Use category from existing logic
    // Just fetch random products for simplicity or use same brand
    $sql_rel = "SELECT p.id_producto, p.nombre, p.precio, d.marca, d.modelo, 
                (SELECT nombre_archivo FROM producto_imagen WHERE id_producto = p.id_producto AND es_principal = 1 LIMIT 1) as main_img,
                d.imagen_url as old_img
                FROM inventario i
                JOIN producto p ON i.id_producto = p.id_producto
                JOIN producto_detalle d ON p.id_producto = d.id_producto
                WHERE p.id_producto != $id AND p.is_active = 1 AND i.cantidad > 0
                ORDER BY RAND() LIMIT 4";
    $related = $conn->query($sql_rel);
    ?>
    
    <?php if($related && $related->num_rows > 0): ?>
    <div class="container" style="margin-top: 4rem; padding-bottom: 4rem; border-top: 1px solid var(--border); padding-top: 2rem;">
        <h2 style="font-size: 1.5rem; margin-bottom: 2rem;">Productos Relacionados</h2>
        <div class="grid-products">
            <?php while($rel = $related->fetch_assoc()): ?>
                <div class="card">
                    <div class="card-img">
                         <?php 
                            $img_src = $rel['main_img'] 
                                ? "../uploads/productos/{$rel['id_producto']}/{$rel['main_img']}" 
                                : ($rel['old_img'] ?? 'https://via.placeholder.com/300');
                        ?>
                        <img src="<?php echo $img_src; ?>" alt="<?php echo htmlspecialchars($rel['nombre']); ?>">
                    </div>
                    <div class="card-body">
                        <span class="card-badge"><?php echo htmlspecialchars($rel['marca']); ?></span>
                        <h3 class="card-title"><?php echo htmlspecialchars($rel['modelo']); ?></h3>
                        <div class="card-price">$<?php echo number_format($rel['precio'], 0, ',', '.'); ?></div>
                        <a href="producto.php?id=<?php echo $rel['id_producto']; ?>" class="btn btn-outline" style="margin-top: 1rem; width: 100%;">Ver Detalles</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

</body>
</html>
