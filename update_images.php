<?php
require_once 'config/bd.php';

try {
    // iPhones
    $conn->query("UPDATE producto_detalle SET imagen_url = 'https://images.unsplash.com/photo-1510557880182-3d4d3cba35a5?auto=format&fit=crop&q=80&w=400' WHERE modelo LIKE '%iPhone%' OR marca = 'Apple'");
    
    // Samsung
    $conn->query("UPDATE producto_detalle SET imagen_url = 'https://images.unsplash.com/photo-1610945415295-d9bbf067e59c?auto=format&fit=crop&q=80&w=400' WHERE modelo LIKE '%Samsung%' OR marca = 'Samsung'");
    
    // Motorola (Generic Smartphone)
    $conn->query("UPDATE producto_detalle SET imagen_url = 'https://images.unsplash.com/photo-1598327105666-5b89351aff23?auto=format&fit=crop&q=80&w=400' WHERE modelo LIKE '%Motorola%' OR marca = 'Motorola'");
    
    // Accessories - Charger
    $conn->query("UPDATE producto_detalle SET imagen_url = 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?auto=format&fit=crop&q=80&w=400' WHERE tipo_repuesto LIKE '%Cargador%' OR modelo LIKE '%Cargador%'");
    
    // Accessories - Case/Funda
    $conn->query("UPDATE producto_detalle SET imagen_url = 'https://images.unsplash.com/photo-1586105251261-72a756497a11?auto=format&fit=crop&q=80&w=400' WHERE tipo_repuesto LIKE '%Funda%' OR modelo LIKE '%Funda%'");

    // Generic fallback for others
    $conn->query("UPDATE producto_detalle SET imagen_url = 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&q=80&w=400' WHERE imagen_url IS NULL OR imagen_url LIKE '%via.placeholder%'");

    echo "Imágenes actualizadas correctamente.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
