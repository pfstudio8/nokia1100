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
}
?>