<?php
// verificar.php
session_start();
require_once '../config/db.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $now = date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare("SELECT id_usuario, nombre_usuario, rol FROM usuario WHERE token_verificacion = ? AND token_expira > ? AND verificado = 0");
    $stmt->bind_param("ss", $token, $now);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $id_usuario = $user['id_usuario'];
        $username = $user['nombre_usuario'];
        $stmt->close();

        // Activar cuenta
        $update_stmt = $conn->prepare("UPDATE usuario SET verificado = 1, token_verificacion = NULL, token_expira = NULL WHERE id_usuario = ?");
        $update_stmt->bind_param("i", $id_usuario);
        
        if ($update_stmt->execute()) {
            require_once '../config/audit.php';
            audit_log($conn, 'LOGIN_OK', $id_usuario, 'usuario', $id_usuario, "Cuenta verificada desde correo electrónico: $username");
            
            ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuenta Verificada - NOKIA1100</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f1115; color: #f8fafc; }
        .font-display { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="bg-[#1a1d24] border border-[#2a2f3a] rounded-2xl p-8 max-w-md w-full text-center shadow-2xl">
        <div class="w-20 h-20 bg-emerald-500/20 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6">
            <span class="material-symbols-outlined text-4xl">check_circle</span>
        </div>
        <h1 class="font-display font-bold text-2xl mb-3 text-white">¡Cuenta Verificada!</h1>
        <p class="text-gray-400 mb-8">Tu correo electrónico ha sido confirmado con éxito. Ya puedes cerrar esta pestaña y volver al inicio de sesión.</p>
        <button onclick="window.close()" class="bg-emerald-500 hover:bg-emerald-600 text-white font-medium py-3 px-6 rounded-lg transition-colors w-full">
            Cerrar esta pestaña
        </button>
    </div>
</body>
</html>
<?php
            exit();
        } else {
            $error_msg = "Error al activar la cuenta en la base de datos.";
            ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error de Verificación - NOKIA1100</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f1115; color: #f8fafc; }
        .font-display { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="bg-[#1a1d24] border border-[#2a2f3a] rounded-2xl p-8 max-w-md w-full text-center shadow-2xl">
        <div class="w-20 h-20 bg-red-500/20 text-red-500 rounded-full flex items-center justify-center mx-auto mb-6">
            <span class="material-symbols-outlined text-4xl">error</span>
        </div>
        <h1 class="font-display font-bold text-2xl mb-3 text-white">Error de Verificación</h1>
        <p class="text-gray-400 mb-8"><?php echo $error_msg; ?></p>
        <button onclick="window.close()" class="bg-[#2a2f3a] hover:bg-[#323844] text-white font-medium py-3 px-6 rounded-lg transition-colors w-full">
            Cerrar esta pestaña
        </button>
    </div>
</body>
</html>
<?php
            exit();
        }
        $update_stmt->close();

    } else {
        $error_msg = "El enlace de verificación es inválido, ha expirado o tu cuenta ya fue activada.";
        ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error de Verificación - NOKIA1100</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f1115; color: #f8fafc; }
        .font-display { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="bg-[#1a1d24] border border-[#2a2f3a] rounded-2xl p-8 max-w-md w-full text-center shadow-2xl">
        <div class="w-20 h-20 bg-red-500/20 text-red-500 rounded-full flex items-center justify-center mx-auto mb-6">
            <span class="material-symbols-outlined text-4xl">error</span>
        </div>
        <h1 class="font-display font-bold text-2xl mb-3 text-white">Error de Verificación</h1>
        <p class="text-gray-400 mb-8"><?php echo $error_msg; ?></p>
        <button onclick="window.close()" class="bg-[#2a2f3a] hover:bg-[#323844] text-white font-medium py-3 px-6 rounded-lg transition-colors w-full">
            Cerrar esta pestaña
        </button>
    </div>
</body>
</html>
<?php
        exit();
    }
} else {
    $error_msg = "Enlace incorrecto.";
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error de Verificación - NOKIA1100</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f1115; color: #f8fafc; }
        .font-display { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="bg-[#1a1d24] border border-[#2a2f3a] rounded-2xl p-8 max-w-md w-full text-center shadow-2xl">
        <div class="w-20 h-20 bg-red-500/20 text-red-500 rounded-full flex items-center justify-center mx-auto mb-6">
            <span class="material-symbols-outlined text-4xl">error</span>
        </div>
        <h1 class="font-display font-bold text-2xl mb-3 text-white">Error de Verificación</h1>
        <p class="text-gray-400 mb-8"><?php echo $error_msg; ?></p>
        <button onclick="window.close()" class="bg-[#2a2f3a] hover:bg-[#323844] text-white font-medium py-3 px-6 rounded-lg transition-colors w-full">
            Cerrar esta pestaña
        </button>
    </div>
</body>
</html>
<?php
    exit();
}
?>