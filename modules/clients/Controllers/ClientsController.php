<?php
// modules/clients/Controllers/ClientsController.php

require_once __DIR__ . '/../../../classes/BaseController.php';
require_once __DIR__ . '/../Models/ClientModel.php';

class ClientsController extends BaseController
{
    private $client_model;

    public function __construct()
    {
        parent::__construct();
        $this->client_model = new ClientModel();
    }

    public function index()
    {
        $this->check_access('clientes');

        $error = '';
        $success = '';

        // Procesar Alta de Cliente
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
            $nombre = trim($_POST['nombre'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if (empty($nombre)) {
                $error = "El nombre del cliente es obligatorio.";
            } else {
                if ($this->client_model->exists($nombre, $telefono)) {
                    $error = "Ya existe un cliente registrado con ese nombre y teléfono.";
                } else {
                    $id_nuevo = $this->client_model->create($nombre, $telefono, $email);
                    if ($id_nuevo) {
                        require_once __DIR__ . '/../../../config/audit.php';
                        audit_log($this->conn, 'CLIENT_CREATE', $_SESSION['user_id'], 'cliente', $id_nuevo, "Registrado nuevo cliente: $nombre (Tel: $telefono)");
                        $success = "Cliente '$nombre' registrado correctamente.";
                    } else {
                        $error = "Error al guardar el cliente en la base de datos.";
                    }
                }
            }
        }

        // Procesar Baja de Cliente
        if (isset($_GET['delete'])) {
            $id_cliente = (int) $_GET['delete'];
            $count = $this->client_model->count_reparaciones($id_cliente);

            if ($count > 0) {
                $error = "No se puede eliminar el cliente porque posee {$count} orden(es) de reparación registradas en el taller.";
            } else {
                $client = $this->client_model->find($id_cliente);
                $c_name = $client ? $client['nombre'] : '';

                if ($this->client_model->delete($id_cliente)) {
                    require_once __DIR__ . '/../../../config/audit.php';
                    audit_log($this->conn, 'CLIENT_DELETE', $_SESSION['user_id'], 'cliente', $id_cliente, "Eliminado cliente: $c_name");
                    $success = "Cliente eliminado con éxito.";
                } else {
                    $error = "Error al eliminar el cliente.";
                }
            }
        }

        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $clientes = $this->client_model->get_all($search);

        $this->render_view(__DIR__ . '/../Views/list.php', [
            'clientes' => $clientes,
            'search' => $search,
            'error' => $error,
            'success' => $success
        ]);
    }

    public function edit()
    {
        $this->check_access('clientes');

        $message = '';
        $message_type = '';
        $id_cliente = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($id_cliente === 0) {
            $this->redirect('clients.php');
        }

        $client = $this->client_model->find($id_cliente);
        if (!$client) {
            $this->redirect('clients.php');
        }

        $nombre = $client['nombre'];
        $telefono = $client['telefono'];
        $email = $client['email'];

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $nombre = trim($_POST['nombre'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if (empty($nombre)) {
                $message = "El nombre del cliente es obligatorio.";
                $message_type = "error";
            } else {
                if ($this->client_model->update($id_cliente, $nombre, $telefono, $email)) {
                    require_once __DIR__ . '/../../../config/audit.php';
                    audit_log($this->conn, 'CLIENT_UPDATE', $_SESSION['user_id'], 'cliente', $id_cliente, "Actualizados datos de cliente: $nombre (Tel: $telefono)");
                    $message = "Cliente actualizado exitosamente.";
                    $message_type = "success";
                } else {
                    $message = "Error al actualizar los datos en la base de datos.";
                    $message_type = "error";
                }
            }
        }

        $this->render_view(__DIR__ . '/../Views/edit.php', [
            'id_cliente' => $id_cliente,
            'nombre' => $nombre,
            'telefono' => $telefono,
            'email' => $email,
            'message' => $message,
            'message_type' => $message_type
        ]);
    }
}
?>
