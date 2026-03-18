<?php
// tienda/index.php
session_start();
require_once '../config/bd.php';

// Cart Count
$cart_count = isset($_SESSION['carrito']) ? count($_SESSION['carrito']) : 0;

// Productos Destacados (Limit 4)
// Productos Destacados (Limit 4)
$sql = "SELECT p.id_producto, p.nombre, p.precio, d.marca, d.modelo, d.imagen_url as old_img,
               (SELECT nombre_archivo FROM producto_imagen WHERE id_producto = p.id_producto AND es_principal = 1 LIMIT 1) as main_img
        FROM inventario i
        JOIN producto p ON i.id_producto = p.id_producto
        JOIN producto_detalle d ON p.id_producto = d.id_producto
        WHERE p.is_active = 1 AND i.cantidad > 0
        ORDER BY RAND() LIMIT 4";
$featured = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nokia Store - Tecnología Profesional</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time() + 10; ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container nav-inner">
            <a href="index.php" class="logo">NOKIA<span>STORE</span></a>
            
            <div class="nav-links">
                <a href="index.php" class="nav-link active">Inicio</a>
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

    <!-- Hero Section (2 Columns) -->
    <header class="hero">
        <div class="container">
            <div class="hero-grid">
                <div class="hero-content">
                    <h1 class="hero-title">Tecnología que conecta<br><span class="text-gradient">tu mundo.</span></h1>
                    <p class="hero-subtitle">Celulares y accesorios originales con garantía oficial y envíos rápidos a todo el país.</p>
                    <div class="hero-actions">
                        <a href="catalogo.php" class="btn btn-primary">Ver Catálogo</a>
                        <a href="#destacados" class="btn btn-outline">Ofertas Especiales</a>
                    </div>
                </div>
                <div class="hero-image-container">
                    <img src="../assets/img/hero_phone.png" alt="Featured Smartphone" class="hero-phone-mockup">
                </div>
            </div>
        </div>
    </header>

    <!-- Benefits Bar -->
    <section class="benefits-bar">
        <div class="container">
            <div class="benefits-grid">
                <div class="benefit-item">
                    <div class="benefit-text">
                        <h4>Envíos nacionales</h4>
                        <p>A todo el país en 24/48h</p>
                    </div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-text">
                        <h4>Compra segura</h4>
                        <p>Protección total en tu pago</p>
                    </div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-text">
                        <h4>Originales</h4>
                        <p>Garantía directa de fábrica</p>
                    </div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-text">
                        <h4>Facturación A/B</h4>
                        <p>Emitimos facturas legales</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section id="destacados" class="container" style="padding-bottom: 6rem;">
        <div class="section-header">
            <div>
                <span class="fire-badge">DESTACADO</span>
                <h2 class="section-title">Productos destacados</h2>
            </div>
            <a href="catalogo.php" class="nav-link" style="font-weight: 700;">Ver todo</a>
        </div>
        
        <div class="grid-products">
            <?php while($row = $featured->fetch_assoc()): ?>
                <div class="card">
                    <div class="card-img">
                        <!-- Placeholder if no image -->
                        <?php 
                            $img_src = $row['main_img'] 
                                ? "../uploads/productos/{$row['id_producto']}/{$row['main_img']}" 
                                : ($row['old_img'] ?? 'https://via.placeholder.com/300');
                        ?>
                        <img src="<?php echo $img_src; ?>" alt="<?php echo htmlspecialchars($row['nombre']); ?>">
                    </div>
                    <div class="card-body">
                        <span class="card-badge"><?php echo htmlspecialchars($row['marca']); ?></span>
                        <h3 class="card-title"><?php echo htmlspecialchars($row['modelo']); ?></h3>
                        <div class="card-price">$<?php echo number_format($row['precio'], 0, ',', '.'); ?></div>
                        <a href="producto.php?id=<?php echo $row['id_producto']; ?>" class="btn btn-outline" style="margin-top: 1.25rem; width: 100%;">Ver Detalles</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <div style="text-align: center; margin-top: 5rem;">
            <a href="catalogo.php" class="btn btn-primary" style="padding: 1.25rem 4rem; font-size: 1.1rem;">Explorar catálogo completo</a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Nokia Store. Todos los derechos reservados.</p>
            <p style="font-size: 0.8rem; margin-top: 0.5rem; color: #666;">Diseño profesional por Antigravity.</p>
        </div>
    </footer>

</body>
</html>
