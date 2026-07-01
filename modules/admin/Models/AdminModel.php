<?php
// modules/admin/Models/AdminModel.php

require_once __DIR__ . '/../../../classes/BaseModel.php';

class AdminModel extends BaseModel
{
    // --- Dashboard Metrics ---

    public function get_total_sales()
    {
        $res = $this->conn->query("SELECT SUM(total) as total_ventas FROM venta");
        return ($res && $res->num_rows > 0) ? (float) $res->fetch_assoc()['total_ventas'] : 0.0;
    }

    public function get_total_transactions()
    {
        $res = $this->conn->query("SELECT COUNT(*) as total_transacciones FROM venta");
        return ($res && $res->num_rows > 0) ? (int) $res->fetch_assoc()['total_transacciones'] : 0;
    }

    public function get_total_users()
    {
        $res = $this->conn->query("SELECT COUNT(*) as total_usuarios FROM usuario");
        return ($res && $res->num_rows > 0) ? (int) $res->fetch_assoc()['total_usuarios'] : 0;
    }

    public function get_low_stock_count()
    {
        $res = $this->conn->query("SELECT COUNT(*) as bajo_stock FROM inventario i INNER JOIN producto p ON i.id_producto = p.id_producto WHERE i.cantidad <= 5 AND p.is_active = 1");
        return ($res && $res->num_rows > 0) ? (int) $res->fetch_assoc()['bajo_stock'] : 0;
    }

    public function get_recent_sales($limit = 5)
    {
        $limit = (int) $limit;
        $result = $this->conn->query("SELECT id_venta, fecha, total, metodo_de_pago FROM venta ORDER BY fecha DESC LIMIT $limit");
        $sales = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sales[] = $row;
            }
        }
        return $sales;
    }

    // --- User Management ---

    public function get_all_users($filter = 'activos')
    {
        $where = match($filter) {
            'inactivos' => "WHERE u.is_active = 0",
            'todos'     => "",
            default     => "WHERE u.is_active = 1",
        };

        $sql = "SELECT u.id_usuario, u.id_persona, u.nombre_usuario, u.rol, u.is_active, u.fecha_baja, u.modulos_permitidos,
                       p.nombre, p.apellido, p.dni, p.telefono, p.email, p.direccion
                FROM usuario u
                INNER JOIN persona p ON u.id_persona = p.id_persona
                $where
                ORDER BY u.is_active DESC, p.nombre ASC";

        $result = $this->conn->query($sql);
        $users = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        return $users;
    }

    public function find_user_by_id($id_usuario)
    {
        $stmt = $this->conn->prepare("
            SELECT u.id_usuario, u.id_persona, u.nombre_usuario, u.rol, u.is_active,
                   p.nombre, p.apellido, p.dni, p.telefono, p.email, p.direccion
            FROM usuario u
            INNER JOIN persona p ON u.id_persona = p.id_persona
            WHERE u.id_usuario = ?
        ");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res;
    }

    public function check_username_exists($username, $exclude_id = null)
    {
        if ($exclude_id) {
            $stmt = $this->conn->prepare("SELECT id_usuario FROM usuario WHERE nombre_usuario = ? AND id_usuario != ?");
            $stmt->bind_param("si", $username, $exclude_id);
        } else {
            $stmt = $this->conn->prepare("SELECT id_usuario FROM usuario WHERE nombre_usuario = ?");
            $stmt->bind_param("s", $username);
        }
        $stmt->execute();
        $stmt->store_result();
        $count = $stmt->num_rows;
        $stmt->close();
        return $count > 0;
    }

    public function check_email_exists($email, $exclude_id_persona = null)
    {
        if ($exclude_id_persona) {
            $stmt = $this->conn->prepare("SELECT id_persona FROM persona WHERE email = ? AND id_persona != ?");
            $stmt->bind_param("si", $email, $exclude_id_persona);
        } else {
            $stmt = $this->conn->prepare("SELECT id_persona FROM persona WHERE email = ?");
            $stmt->bind_param("s", $email);
        }
        $stmt->execute();
        $stmt->store_result();
        $count = $stmt->num_rows;
        $stmt->close();
        return $count > 0;
    }

    public function check_dni_exists($dni, $exclude_id_persona = null)
    {
        if ($exclude_id_persona) {
            $stmt = $this->conn->prepare("SELECT id_persona FROM persona WHERE dni = ? AND id_persona != ?");
            $stmt->bind_param("si", $dni, $exclude_id_persona);
        } else {
            $stmt = $this->conn->prepare("SELECT id_persona FROM persona WHERE dni = ?");
            $stmt->bind_param("s", $dni);
        }
        $stmt->execute();
        $stmt->store_result();
        $count = $stmt->num_rows;
        $stmt->close();
        return $count > 0;
    }

    public function create_user_transaction($persona_data, $user_data)
    {
        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare("INSERT INTO persona (nombre, apellido, dni, telefono, email, direccion) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "ssssss",
                $persona_data['nombre'],
                $persona_data['apellido'],
                $persona_data['dni'],
                $persona_data['telefono'],
                $persona_data['email'],
                $persona_data['direccion']
            );
            if (!$stmt->execute()) {
                throw new Exception("Error al registrar los datos personales de la persona");
            }
            $id_persona = $this->conn->insert_id;
            $stmt->close();

            $stmt = $this->conn->prepare(
                "INSERT INTO usuario (id_persona, nombre_usuario, contrasena, rol, email, verificado, is_active, token_verificacion, token_expira) 
                 VALUES (?, ?, ?, ?, ?, 1, 1, NULL, NULL)"
            );
            $stmt->bind_param(
                "issss",
                $id_persona,
                $user_data['username'],
                $user_data['hashed_password'],
                $user_data['rol'],
                $user_data['email']
            );
            if (!$stmt->execute()) {
                throw new Exception("Error al registrar la cuenta de usuario");
            }
            $id_nuevo_usuario = $this->conn->insert_id;
            $stmt->close();

            $this->conn->commit();
            return $id_nuevo_usuario;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function update_user_transaction($id_usuario, $id_persona, $persona_data, $user_data)
    {
        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare("UPDATE persona SET nombre = ?, apellido = ?, dni = ?, email = ?, telefono = ?, direccion = ? WHERE id_persona = ?");
            $stmt->bind_param(
                "ssssssi",
                $persona_data['nombre'],
                $persona_data['apellido'],
                $persona_data['dni'],
                $persona_data['email'],
                $persona_data['telefono'],
                $persona_data['direccion'],
                $id_persona
            );
            if (!$stmt->execute()) {
                throw new Exception("Error al actualizar los datos personales de la persona");
            }
            $stmt->close();

            if (!empty($user_data['hashed_password'])) {
                $stmt = $this->conn->prepare("UPDATE usuario SET nombre_usuario = ?, email = ?, rol = ?, contrasena = ? WHERE id_usuario = ?");
                $stmt->bind_param("ssssi", $user_data['username'], $user_data['email'], $user_data['rol'], $user_data['hashed_password'], $id_usuario);
            } else {
                $stmt = $this->conn->prepare("UPDATE usuario SET nombre_usuario = ?, email = ?, rol = ? WHERE id_usuario = ?");
                $stmt->bind_param("sssi", $user_data['username'], $user_data['email'], $user_data['rol'], $id_usuario);
            }

            if (!$stmt->execute()) {
                throw new Exception("Error al actualizar la cuenta de usuario");
            }
            $stmt->close();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function toggle_user_status($id_usuario)
    {
        // Get current status
        $stmt = $this->conn->prepare("SELECT is_active FROM usuario WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $is_active = $stmt->get_result()->fetch_assoc()['is_active'];
        $stmt->close();

        $new_status = $is_active ? 0 : 1;
        $fecha_baja = $new_status ? null : date('Y-m-d H:i:s');

        $stmt = $this->conn->prepare("UPDATE usuario SET is_active = ?, fecha_baja = ? WHERE id_usuario = ?");
        $stmt->bind_param("isi", $new_status, $fecha_baja, $id_usuario);
        $success = $stmt->execute();
        $stmt->close();

        return [
            'success' => $success,
            'new_status' => $new_status
        ];
    }

    public function update_user_role($id_usuario, $rol)
    {
        $stmt = $this->conn->prepare("UPDATE usuario SET rol = ? WHERE id_usuario = ?");
        $stmt->bind_param("si", $rol, $id_usuario);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function update_user_modules($id_usuario, $modules_str)
    {
        $stmt = $this->conn->prepare("UPDATE usuario SET modulos_permitidos = ? WHERE id_usuario = ?");
        $stmt->bind_param("si", $modules_str, $id_usuario);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    // --- Profile Management ---

    public function get_user_profile($id_usuario)
    {
        $stmt = $this->conn->prepare("
            SELECT u.id_usuario, u.id_persona, u.nombre_usuario, u.rol, u.email as user_email,
                   p.nombre, p.apellido, p.dni, p.telefono, p.email as persona_email, p.direccion
            FROM usuario u
            INNER JOIN persona p ON u.id_persona = p.id_persona
            WHERE u.id_usuario = ?
        ");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res;
    }

    public function update_profile_transaction($id_usuario, $id_persona, $persona_data, $password_data = null)
    {
        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare("UPDATE persona SET nombre = ?, apellido = ?, dni = ?, email = ?, telefono = ?, direccion = ? WHERE id_persona = ?");
            $stmt->bind_param(
                "ssssssi",
                $persona_data['nombre'],
                $persona_data['apellido'],
                $persona_data['dni'],
                $persona_data['email'],
                $persona_data['telefono'],
                $persona_data['direccion'],
                $id_persona
            );
            if (!$stmt->execute()) {
                throw new Exception("Error al actualizar datos personales");
            }
            $stmt->close();

            if ($password_data) {
                $stmt = $this->conn->prepare("UPDATE usuario SET contrasena = ?, email = ? WHERE id_usuario = ?");
                $stmt->bind_param("ssi", $password_data['hashed_password'], $persona_data['email'], $id_usuario);
            } else {
                $stmt = $this->conn->prepare("UPDATE usuario SET email = ? WHERE id_usuario = ?");
                $stmt->bind_param("si", $persona_data['email'], $id_usuario);
            }

            if (!$stmt->execute()) {
                throw new Exception("Error al actualizar cuenta");
            }
            $stmt->close();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}
?>
