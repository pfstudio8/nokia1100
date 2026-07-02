<?php
// includes/filtros.php
// Variables esperadas: $brands, $categories, $min_price, $max_price, $cat, $marca, $search

// Ayuda a mantener los parámetros de búsqueda en la URL
function get_query_url($new_params = []) {
    $params = $_GET;
    // Combina los nuevos parámetros
    foreach ($new_params as $key => $value) {
        if ($value === null) {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }
    return '?' . http_build_query($params);
}
?>

<form action="" method="GET" id="filterForm">
    <?php // Mantiene la búsqueda actual ?>
    <?php if(!empty($search)): ?><input type="hidden" name="q" value="<?php echo htmlspecialchars($search); ?>"><?php endif; ?>
    
    <?php // Filtro por categoría ?>
    <div class="filter-group">
        <div class="filter-title">Categorías</div>
        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <a href="<?php echo get_query_url(['categoria' => null, 'page' => 1]); ?>" 
               style="color: <?php echo !$cat ? 'var(--primary)' : 'var(--text-muted)'; ?>; font-weight: <?php echo !$cat ? '600' : '400'; ?>">
               Todas
            </a>
            <?php 
            if(isset($categories) && $categories->num_rows > 0) {
                $categories->data_seek(0); // Reinicia el puntero
                while($c = $categories->fetch_assoc()): 
                    $is_active = ($cat == $c['categoria']);
            ?>
                <a href="<?php echo get_query_url(['categoria' => $c['categoria'], 'page' => 1]); ?>" 
                   style="color: <?php echo $is_active ? 'var(--primary)' : 'var(--text-muted)'; ?>; font-weight: <?php echo $is_active ? '600' : '400'; ?>">
                   <?php echo htmlspecialchars($c['categoria']); ?>
                </a>
            <?php 
                endwhile; 
            }
            ?>
        </div>
    </div>

    <?php // Filtro por marca ?>
    <div class="filter-group">
        <div class="filter-title">Marcas</div>
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            <?php 
            if(isset($brands) && $brands->num_rows > 0) {
                $brands->data_seek(0);
                while($b = $brands->fetch_assoc()): 
                    $isChecked = in_array($b['marca'], $marca);
            ?>
                <label class="checkbox-label" style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0; color: <?php echo $isChecked ? 'var(--primary)' : 'var(--text-muted)'; ?>;">
                    <input type="checkbox" name="marca[]" value="<?php echo htmlspecialchars($b['marca']); ?>"
                           <?php echo $isChecked ? 'checked' : ''; ?>
                           style="width: 16px; height: 16px; accent-color: var(--primary);">
                    <span style="font-size: 0.95rem; font-weight: <?php echo $isChecked ? '600' : '400'; ?>;"><?php echo htmlspecialchars($b['marca']); ?></span>
                </label>
            <?php 
                endwhile; 
            }
            ?>
        </div>
    </div>

    <?php // Filtro por precio ?>
    <div class="filter-group">
        <div class="filter-title">Precio</div>
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <input type="number" name="min_price" class="form-control" placeholder="Mín" 
                       value="<?php echo isset($_GET['min_price']) ? $_GET['min_price'] : ''; ?>" 
                       style="font-size: 0.85rem; padding: 0.5rem; background: rgba(0,0,0,0.2);">
                <span style="color: var(--text-dim);">–</span>
                <input type="number" name="max_price" class="form-control" placeholder="Máx" 
                       value="<?php echo isset($_GET['max_price']) ? $_GET['max_price'] : ''; ?>" 
                       style="font-size: 0.85rem; padding: 0.5rem; background: rgba(0,0,0,0.2);">
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="width: 100%; letter-spacing: 0.05em;">APLICAR PRECIO</button>
        </div>
    </div>
    
    <?php if($cat || !empty($marca) || isset($_GET['min_price']) || isset($_GET['max_price'])): ?>
        <a href="catalogo.php" class="btn btn-outline btn-sm" style="width: 100%; text-align: center; justify-content: center; margin-top: 0.5rem;">Limpiar Filtros</a>
    <?php endif; ?>
</form>

<script src="<?php echo BASE_URL; ?>/assets/js/filtros.js?v=<?php echo time(); ?>"></script>
