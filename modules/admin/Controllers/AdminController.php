<?php
// modules/admin/Controllers/AdminController.php

require_once __DIR__ . '/../../../classes/BaseController.php';
require_once __DIR__ . '/../Models/AdminModel.php';

class AdminController extends BaseController
{
    private $admin_model;

    public function __construct()
    {
        parent::__construct();
        $this->admin_model = new AdminModel();
    }

    public function dashboard()
    {
        $this->check_access('dashboard');

        $total_ventas = $this->admin_model->get_total_sales();
        $total_trans = $this->admin_model->get_total_transactions();
        $total_usuarios = $this->admin_model->get_total_users();
        $bajo_stock = $this->admin_model->get_low_stock_count();
        $ventas_recientes = $this->admin_model->get_recent_sales(5);

        $this->render_view(__DIR__ . '/../Views/dashboard.php', [
            'totalVentas' => $total_ventas,
            'totalTrans' => $total_trans,
            'totalUsuarios' => $total_usuarios,
            'bajoStock' => $bajo_stock,
            'ventasRecientes' => $ventas_recientes
        ]);
    }

    public function users()
    {
        $this->check_access('usuarios');

        $filtro = $_GET['filtro'] ?? 'activos';
        $users = $this->admin_model->get_all_users($filtro);

        $this->render_view(__DIR__ . '/../Views/users.php', [
            'clientes' => $users, // Se usa 'clientes' por coherencia con la vista
            'filtro' => $filtro
        ]);
    }

    public function add_user()
    {
        $this->check_access('usuarios');

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->redirect("users.php");
        }

        $nombre           = trim($_POST['nombre'] ?? '');
        $apellido         = trim($_POST['apellido'] ?? '');
        $dni              = trim($_POST['dni'] ?? '');
        $email            = trim($_POST['email'] ?? '');
        $telefono         = trim($_POST['telefono'] ?? '');
        $direccion        = trim($_POST['direccion'] ?? '');
        $username         = trim($_POST['username'] ?? '');
        $password         = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $rol              = trim($_POST['rol'] ?? 'empleado');

        if (empty($nombre) || empty($apellido) || empty($dni) || empty($email) || empty($username) || empty($password) || empty($password_confirm) || empty($rol)) {
            $this->redirect("users.php?error=" . urlencode("Complete todos los campos requeridos"));
        }

        if ($password !== $password_confirm) {
            $this->redirect("users.php?error=" . urlencode("Las contraseñas no coinciden"));
        }

        if (strlen($password) < 6) {
            $this->redirect("users.php?error=" . urlencode("La contraseña debe tener al menos 6 caracteres"));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirect("users.php?error=" . urlencode("El formato del email no es válido"));
        }

        if (!preg_match('/^[0-9]{7,10}$/', $dni)) {
            $this->redirect("users.php?error=" . urlencode("El DNI debe tener entre 7 y 10 dígitos"));
        }

        if ($rol !== 'admin' && $rol !== 'empleado') {
            $this->redirect("users.php?error=" . urlencode("El rol seleccionado no es válido"));
        }

        if ($this->admin_model->check_username_exists($username)) {
            $this->redirect("users.php?error=" . urlencode("El nombre de usuario ya está en uso"));
        }

        if ($this->admin_model->check_email_exists($email)) {
            $this->redirect("users.php?error=" . urlencode("El email ingresado ya está registrado"));
        }

        if ($this->admin_model->check_dni_exists($dni)) {
            $this->redirect("users.php?error=" . urlencode("El DNI ingresado ya está registrado"));
        }

        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $persona_data = [
                'nombre' => $nombre,
                'apellido' => $apellido,
                'dni' => $dni,
                'email' => $email,
                'telefono' => $telefono,
                'direccion' => $direccion
            ];
            $user_data = [
                'username' => $username,
                'hashed_password' => $hashed,
                'rol' => $rol,
                'email' => $email
            ];

            $id_nuevo = $this->admin_model->create_user_transaction($persona_data, $user_data);

            require_once __DIR__ . '/../../../config/audit.php';
            audit_log($this->conn, 'USER_CREATE', $_SESSION['user_id'], 'usuario', $id_nuevo, "Administrador creó usuario directamente: $username ($nombre $apellido) con rol $rol");

            $this->redirect("users.php?success=" . urlencode("Usuario '$username' agregado correctamente y listo para operar"));
        } catch (Exception $e) {
            $this->redirect("users.php?error=" . urlencode($e->getMessage()));
        }
    }

    public function edit_user()
    {
        $this->check_access('usuarios');

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->redirect("users.php");
        }

        $id_usuario = (int)($_POST['id_usuario'] ?? 0);
        $id_persona = (int)($_POST['id_persona'] ?? 0);
        $nombre     = trim($_POST['nombre'] ?? '');
        $apellido   = trim($_POST['apellido'] ?? '');
        $dni        = trim($_POST['dni'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $telefono   = trim($_POST['telefono'] ?? '');
        $direccion  = trim($_POST['direccion'] ?? '');
        $username   = trim($_POST['username'] ?? '');
        $password   = $_POST['password'] ?? '';
        $rol        = trim($_POST['rol'] ?? 'empleado');

        if ($id_usuario === 0 || $id_persona === 0 || empty($nombre) || empty($apellido) || empty($dni) || empty($email) || empty($username) || empty($rol)) {
            $this->redirect("users.php?error=" . urlencode("Complete todos los campos requeridos"));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirect("users.php?error=" . urlencode("El formato del email no es válido"));
        }

        if (!preg_match('/^[0-9]{7,10}$/', $dni)) {
            $this->redirect("users.php?error=" . urlencode("El DNI debe tener entre 7 y 10 dígitos"));
        }

        if ($this->admin_model->check_username_exists($username, $id_usuario)) {
            $this->redirect("users.php?error=" . urlencode("El nombre de usuario ya está en uso"));
        }

        if ($this->admin_model->check_email_exists($email, $id_persona)) {
            $this->redirect("users.php?error=" . urlencode("El email ingresado ya está registrado"));
        }

        if ($this->admin_model->check_dni_exists($dni, $id_persona)) {
            $this->redirect("users.php?error=" . urlencode("El DNI ingresado ya está registrado"));
        }

        try {
            $hashed_password = '';
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    $this->redirect("users.php?error=" . urlencode("La contraseña debe tener al menos 6 caracteres"));
                }
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            }

            $persona_data = [
                'nombre' => $nombre,
                'apellido' => $apellido,
                'dni' => $dni,
                'email' => $email,
                'telefono' => $telefono,
                'direccion' => $direccion
            ];
            $user_data = [
                'username' => $username,
                'hashed_password' => $hashed_password,
                'rol' => $rol,
                'email' => $email
            ];

            $this->admin_model->update_user_transaction($id_usuario, $id_persona, $persona_data, $user_data);

            require_once __DIR__ . '/../../../config/audit.php';
            audit_log($this->conn, 'USER_UPDATE', $_SESSION['user_id'], 'usuario', $id_usuario, "Administrador editó datos del usuario: $username ($nombre $apellido) con rol $rol");

            $this->redirect("users.php?success=" . urlencode("Usuario '$username' actualizado correctamente"));
        } catch (Exception $e) {
            $this->redirect("users.php?error=" . urlencode($e->getMessage()));
        }
    }

    public function delete_user()
    {
        $this->check_access('usuarios');

        $id_usuario = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id_usuario === 0) {
            $this->redirect("users.php");
        }

        // Evita la autodesactivación
        if ($id_usuario === (int)$_SESSION['user_id']) {
            $this->redirect("users.php?error=" . urlencode("No puedes desactivarte a ti mismo"));
        }

        $res = $this->admin_model->toggle_user_status($id_usuario);

        if ($res['success']) {
            $user = $this->admin_model->find_user_by_id($id_usuario);
            $username = $user ? $user['nombre_usuario'] : '';
            $action_str = $res['new_status'] ? 're-activó' : 'desactivó';
            $audit_action = $res['new_status'] ? 'USER_REACTIVATE' : 'USER_DEACTIVATE';

            require_once __DIR__ . '/../../../config/audit.php';
            audit_log($this->conn, $audit_action, $_SESSION['user_id'], 'usuario', $id_usuario, "Administrador $action_str al usuario: $username");

            $msg = $res['new_status'] ? "Usuario reactivado con éxito" : "Usuario desactivado correctamente";
            $this->redirect("users.php?success=" . urlencode($msg));
        } else {
            $this->redirect("users.php?error=" . urlencode("Error al cambiar estado del usuario"));
        }
    }

    public function save_user_modules()
    {
        $this->check_access('usuarios');

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->redirect("users.php");
        }

        $id_usuario = (int)($_POST['id_usuario'] ?? 0);
        $modulos = $_POST['modulos'] ?? [];

        if ($id_usuario === 0) {
            $this->redirect("users.php?error=" . urlencode("Usuario no válido"));
        }

        $modules_str = implode(',', $modulos);

        if ($this->admin_model->update_user_modules($id_usuario, $modules_str)) {
            $user = $this->admin_model->find_user_by_id($id_usuario);
            $username = $user ? $user['nombre_usuario'] : '';

            require_once __DIR__ . '/../../../config/audit.php';
            audit_log($this->conn, 'USER_PERMISSIONS_UPDATE', $_SESSION['user_id'], 'usuario', $id_usuario, "Modificados permisos de módulos para: $username. Nuevos: $modules_str");

            $this->redirect("users.php?success=" . urlencode("Permisos actualizados correctamente"));
        } else {
            $this->redirect("users.php?error=" . urlencode("Error al guardar permisos"));
        }
    }

    public function update_user_role()
    {
        $this->check_access('usuarios');

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->redirect("users.php");
        }

        $id_usuario = (int)($_POST['id_usuario'] ?? 0);
        $rol = trim($_POST['rol'] ?? '');

        if ($id_usuario === 0 || ($rol !== 'admin' && $rol !== 'empleado')) {
            $this->redirect("users.php?error=" . urlencode("Datos no válidos"));
        }

        if ($id_usuario === (int)$_SESSION['user_id']) {
            $this->redirect("users.php?error=" . urlencode("No puedes cambiar tu propio rol"));
        }

        if ($this->admin_model->update_user_role($id_usuario, $rol)) {
            $user = $this->admin_model->find_user_by_id($id_usuario);
            $username = $user ? $user['nombre_usuario'] : '';

            require_once __DIR__ . '/../../../config/audit.php';
            audit_log($this->conn, 'USER_ROLE_UPDATE', $_SESSION['user_id'], 'usuario', $id_usuario, "Actualizado rol de usuario: $username a $rol");

            $this->redirect("users.php?success=" . urlencode("Rol de usuario actualizado correctamente"));
        } else {
            $this->redirect("users.php?error=" . urlencode("Error al actualizar rol"));
        }
    }

    public function profile()
    {
        $this->check_access('perfil');

        $id_user = $_SESSION['user_id'];
        $success_msg = "";
        $error_msg = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre'] ?? '');
            $apellido = trim($_POST['apellido'] ?? '');
            $username = trim($_POST['username'] ?? '');

            if (empty($nombre) || empty($apellido) || empty($username)) {
                $error_msg = "Complete todos los campos obligatorios.";
            } else {
                try {
                    // Obtiene el ID de la persona actual
                    $current = $this->admin_model->get_user_profile($id_user);
                    $id_persona = $current['id_persona'];

                    $persona_data = [
                        'nombre' => $nombre,
                        'apellido' => $apellido,
                        'email' => $current['persona_email'],
                        'dni' => $current['dni'],
                        'telefono' => $current['telefono'],
                        'direccion' => $current['direccion']
                    ];

                    // Verifica disponibilidad del nombre de usuario
                    if ($this->admin_model->check_username_exists($username, $id_user)) {
                        throw new Exception("El nombre de usuario ya está en uso.");
                    }

                    $this->admin_model->update_profile_transaction($id_user, $id_persona, $persona_data, null);
                    
                    // Actualiza el nombre de usuario en la base de datos
                    $updateUser = $this->conn->prepare("UPDATE usuario SET nombre_usuario = ? WHERE id_usuario = ?");
                    $updateUser->bind_param("si", $username, $id_user);
                    $updateUser->execute();
                    $updateUser->close();

                    $_SESSION['username'] = $username;
                    $success_msg = "Perfil actualizado correctamente.";
                } catch (Exception $e) {
                    $error_msg = $e->getMessage();
                }
            }
        }

        $current_data = $this->admin_model->get_user_profile($id_user);
        // Mapea las claves para coincidir con la vista
        $current_data['nombre_usuario'] = $current_data['nombre_usuario'];
        $current_data['email'] = $current_data['persona_email'];

        $this->render_view(__DIR__ . '/../Views/profile.php', [
            'current_data' => $current_data,
            'success_msg' => $success_msg,
            'error_msg' => $error_msg
        ]);
    }
}
?>
