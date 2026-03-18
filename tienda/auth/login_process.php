<?php
// tienda/auth/login_process.php
session_start();
require_once dirname(dirname(__DIR__)) . '/config/bd.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        header("Location: ../login.php?error=Por favor complete todos los campos");
        exit();
    }

    $stmt = $conn->prepare("SELECT id, email, password, verificado FROM usuarios_tienda WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            if ($user['verificado'] == 0) {
                header("Location: ../login.php?error=Tu cuenta no ha sido verificada. Revisa tu correo.");
                exit();
            }

            // Session isolation: only set cliente_id
            $_SESSION['cliente_id'] = $user['id'];
            $_SESSION['cliente_email'] = $user['email'];
            
            $redirect = !empty($_POST['redirect']) ? $_POST['redirect'] : '../index.php';
            header("Location: $redirect");
            exit();
        } else {
            header("Location: ../login.php?error=Contraseña incorrecta");
            exit();
        }
    } else {
        header("Location: ../login.php?error=Usuario no encontrado");
        exit();
    }
} else {
    header("Location: ../login.php");
    exit();
}
?>
