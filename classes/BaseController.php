<?php
// classes/BaseController.php

class BaseController
{
    protected ?mysqli $conn = null;

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

    protected function check_auth(bool $json = false): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            if ($json) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => 'No autenticado o sesión expirada']);
                exit();
            }
            header("Location: " . BASE_URL . "/index.php");
            exit();
        }

        // Evitar sesiones múltiples y verificar que la cuenta siga activa (excepto para la cuenta guest/invitado)
        if ($_SESSION['user_id'] !== 'guest' && is_numeric($_SESSION['user_id'])) {
            $stmt = $this->conn->prepare("SELECT session_token, is_active, rol FROM usuario WHERE id_usuario = ?");
            $userIdInt = (int)$_SESSION['user_id'];
            $stmt->bind_param("i", $userIdInt);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$res || (int)($res['is_active'] ?? 0) !== 1) {
                // Cuenta desactivada o eliminada
                session_unset();
                session_destroy();
                if ($json) {
                    http_response_code(401);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'error' => 'Tu cuenta ha sido desactivada']);
                    exit();
                }
                header("Location: " . BASE_URL . "/index.php?error=" . urlencode("Tu cuenta ha sido desactivada. Comunícate con el administrador."));
                exit();
            }

            $db_token = $res['session_token'] ?? null;
            $session_token = $_SESSION['session_token'] ?? null;

            if ($db_token !== $session_token) {
                // Discrepancia detectada: se inició sesión en otro dispositivo.
                session_unset();
                session_destroy();
                if ($json) {
                    http_response_code(401);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'error' => 'Se ha iniciado sesión en otro dispositivo']);
                    exit();
                }
                header("Location: " . BASE_URL . "/index.php?error=" . urlencode("Se ha iniciado sesión en otro dispositivo. Tu sesión actual ha sido cerrada."));
                exit();
            }

            // Sincronizar el rol en la sesión por si fue actualizado en la base de datos
            if (!empty($res['rol'])) {
                $_SESSION['role'] = $res['rol'];
            }
        }
    }

    protected function check_access(string $module_id, bool $json = false): void
    {
        $this->check_auth($json);
        require_once __DIR__ . '/Layout.php';
        if ($json) {
            if (!Layout::hasAccess($module_id)) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => 'No tiene permiso para acceder a este recurso']);
                exit();
            }
            return;
        }
        Layout::checkAccess($module_id);
    }

    // Permite el acceso si el usuario tiene AL MENOS uno de los módulos indicados.
    // Con $json = true responde 403 en JSON (para endpoints AJAX) en vez de redirigir a HTML.
    protected function check_any_access(array $module_ids, bool $json = false): void
    {
        $this->check_auth($json);
        require_once __DIR__ . '/Layout.php';
        foreach ($module_ids as $module_id) {
            if (Layout::hasAccess($module_id)) {
                return;
            }
        }
        if ($json) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No tiene permiso para realizar esta acción.']);
            exit();
        }
        $this->redirect(BASE_URL . "/modules/employee/index.php?error=" . urlencode("No tiene permiso para acceder a ese módulo"));
    }

    protected function render_view(string $view_path, array $data = []): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        require_once __DIR__ . '/Layout.php';
        // Extraer las variables para que sean accesibles directamente en la vista
        extract($data);
        require $view_path;
    }

    protected function redirect(string $url): void
    {
        header("Location: " . $url);
        exit();
    }

    protected function validate_password(string $password): true|string
    {
        if (strlen($password) < 8) {
            return "La contraseña debe tener al menos 8 caracteres.";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return "La contraseña debe contener al menos una letra mayúscula.";
        }
        if (!preg_match('/[a-z]/', $password)) {
            return "La contraseña debe contener al menos una letra minúscula.";
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            return "La contraseña debe contener al menos un carácter especial (ej. @, $, !, %, *, ?, &).";
        }
        return true;
    }

    protected function verify_csrf(?string $token): bool
    {
        $session_token = $_SESSION['session_token'] ?? '';
        return !empty($session_token) && !empty($token) && hash_equals($session_token, (string)$token);
    }
}