<?php
// classes/BaseController.php

class BaseController
{
    protected $conn;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        global $conn;
        if (!isset($conn)) {
            require_once __DIR__ . '/../config/db.php';
        }
        $this->conn = $conn;
    }

    protected function check_auth()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "/index.php");
            exit();
        }

        // Evitar sesiones múltiples (excepto para la cuenta guest/invitado)
        if ($_SESSION['user_id'] !== 'guest') {
            $stmt = $this->conn->prepare("SELECT session_token FROM usuario WHERE id_usuario = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $db_token = $res['session_token'] ?? null;
            $session_token = $_SESSION['session_token'] ?? null;

            if ($db_token !== $session_token) {
                // Discrepancia detectada: se inició sesión en otro dispositivo.
                session_unset();
                session_destroy();
                header("Location: " . BASE_URL . "/index.php?error=" . urlencode("Se ha iniciado sesión en otro dispositivo. Tu sesión actual ha sido cerrada."));
                exit();
            }
        }
    }

    protected function check_access($module_id)
    {
        $this->check_auth();
        require_once __DIR__ . '/Layout.php';
        Layout::checkAccess($module_id);
    }

    protected function render_view($view_path, $data = [])
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        require_once __DIR__ . '/Layout.php';
        //Extraer las variables para que sean accesibles directamente en la vista
        extract($data);
        require $view_path;
    }

    protected function redirect($url)
    {
        header("Location: " . $url);
        exit();
    }

    protected function validate_password($password)
    {
        if (strlen($password) < 8) {
            return "La contraseña debe tener al menos 8 caracteres.";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return "La contraseña debe contener al menos una letra mayúscula.";
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            return "La contraseña debe contener al menos un carácter especial (ej. @, $, !, %, *, ?, &).";
        }
        return true;
    }
}
?>