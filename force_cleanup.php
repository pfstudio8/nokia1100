<?php
// force_cleanup.php
if (file_exists(__DIR__ . '/modules/sales/sales.php')) {
    unlink(__DIR__ . '/modules/sales/sales.php');
    echo "Deleted old sales.php<br>";
} else {
    echo "sales.php does not exist<br>";
}

if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "Opcache reset done.<br>";
}

echo '<a href="modules/sales/index.php">Ir a Ventas</a>';
