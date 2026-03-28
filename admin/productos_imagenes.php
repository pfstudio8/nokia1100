<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin_auth();

if ($_SESSION['admin_role'] !== 'admin') {
    header("Location: panel_empleado.php");
    exit();
}
require_once '../config/bd.php';

$id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_producto == 0) {
    header("Location: inventario/inventario.php");
    exit();
}

// Obtener info del producto
$prod_sql = "SELECT p.nombre, d.marca, d.modelo FROM producto p JOIN producto_detalle d ON p.id_producto = d.id_producto WHERE p.id_producto = $id_producto";
$prod_res = $conn->query($prod_sql);
$producto = $prod_res->fetch_assoc();

// Obtener imágenes
$img_sql = "SELECT * FROM producto_imagen WHERE id_producto = $id_producto ORDER BY es_principal DESC, orden ASC";
$imagenes = $conn->query($img_sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Imágenes - <?php echo htmlspecialchars($producto['nombre']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .img-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            transition: transform 0.2s;
        }
        .img-card:hover {
            transform: translateY(-2px);
        }
        .img-preview {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #000;
        }
        .img-actions {
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0,0,0,0.2);
        }
        .badge-main {
            position: absolute;
            top: 10px;
            left: 10px;
            background: var(--primary-color);
            color: #fff;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            padding: 3rem;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s;
            margin-bottom: 2rem;
        }
        .upload-area:hover {
            border-color: var(--primary-color);
            background: rgba(255,255,255,0.01);
        }
    </style>
</head>
<body>
    <div class="container" style="max-width: 1000px; margin-top: 3rem;">
        <div class="glass-card">
            <div class="header-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h2>Gestión de Imágenes</h2>
                    <p style="color: var(--text-muted);"><?php echo htmlspecialchars($producto['marca'] . ' ' . $producto['modelo']); ?></p>
                </div>
                <a href="inventario/inventario.php" class="btn-back" style="background: transparent; border: 1px solid var(--border-color); color: var(--text-color); padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 8px;">Volver</a>
            </div>

            <!-- Upload Zone -->
            <div id="uploadArea" class="upload-area">
                <p style="margin-bottom: 1rem;">Arrastra imágenes aquí o haz clic para subir</p>
                <input type="file" id="fileInput" multiple accept="image/jpeg,image/png,image/webp" style="display: none;">
                <button onclick="document.getElementById('fileInput').click()" class="btn-add" style="border: none; max-width: 200px;">Seleccionar Archivos</button>
            </div>
            <div id="uploadStatus" style="margin-bottom: 1rem; color: var(--text-muted);"></div>

            <!-- Gallery -->
            <div class="gallery-grid" id="galleryGrid">
                <?php while($img = $imagenes->fetch_assoc()): ?>
                    <div class="img-card" id="img-<?php echo $img['id_imagen']; ?>">
                        <?php if($img['es_principal']): ?>
                            <span class="badge-main">Principal</span>
                        <?php endif; ?>
                        
                        <img src="../uploads/productos/<?php echo $id_producto; ?>/<?php echo $img['nombre_archivo']; ?>" class="img-preview" alt="Producto">
                        
                        <div class="img-actions">
                            <?php if(!$img['es_principal']): ?>
                                <button onclick="setPrincipal(<?php echo $img['id_imagen']; ?>)" style="background:none; border:none; color: var(--text-muted); cursor: pointer;" title="Hacer principal">⭐</button>
                            <?php endif; ?>
                            <button onclick="deleteImage(<?php echo $img['id_imagen']; ?>)" style="background:none; border:none; color: #ef4444; cursor: pointer;" title="Eliminar">🗑️</button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script>
        const idProducto = <?php echo $id_producto; ?>;
        
        // Upload Logic
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        uploadArea.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }

        fileInput.addEventListener('change', function() {
            handleFiles(this.files);
        });

        function handleFiles(files) {
            ([...files]).forEach(uploadFile);
        }

        function uploadFile(file) {
            const url = 'subir_imagen.php';
            const formData = new FormData();
            formData.append('imagen', file);
            formData.append('id_producto', idProducto);

            document.getElementById('uploadStatus').innerText = 'Subiendo...';

            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(() => {
                location.reload(); 
            })
            .catch(() => {
                alert('Error al subir imagen');
            });
        }

        function deleteImage(id) {
            if(!confirm('¿Eliminar imagen?')) return;
            
            const formData = new FormData();
            formData.append('id_imagen', id);
            
            fetch('eliminar_imagen.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) location.reload();
                else alert(data.message);
            });
        }

        function setPrincipal(id) {
            const formData = new FormData();
            formData.append('id_imagen', id);
            formData.append('id_producto', idProducto);
            
            fetch('set_principal.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) location.reload();
                else alert(data.message);
            });
        }
    </script>
</body>
</html>
