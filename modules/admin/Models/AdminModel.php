<?php
// modules/admin/Models/AdminModel.php

require_once __DIR__ . '/../../../classes/BaseModel.php';
require_once __DIR__ . '/../../../classes/ServiceProduct.php';

class AdminModel extends BaseModel
{
    // --- Métricas del Panel de Control ---

    public function get_total_sales()
    {
        $res = $this->conn->query("SELECT SUM(total) as total_ventas FROM venta WHERE estado != 'anulada'");
        return ($res && $res->num_rows > 0) ? (float) $res->fetch_assoc()['total_ventas'] : 0.0;
    }

    public function get_total_transactions()
    {
        $res = $this->conn->query("SELECT COUNT(*) as total_transacciones FROM venta WHERE estado != 'anulada'");
        return ($res && $res->num_rows > 0) ? (int) $res->fetch_assoc()['total_transacciones'] : 0;
    }

    public function get_total_users()
    {
        $res = $this->conn->query("SELECT COUNT(*) as total_usuarios FROM usuario");
        return ($res && $res->num_rows > 0) ? (int) $res->fetch_assoc()['total_usuarios'] : 0;
    }

    public function get_low_stock_count()
    {
        $excludeServices = ServiceProduct::excludeSql('p', 'd');
        $res = $this->conn->query("SELECT COUNT(*) as bajo_stock FROM inventario i INNER JOIN producto p ON i.id_producto = p.id_producto LEFT JOIN producto_detalle d ON p.id_producto = d.id_producto WHERE i.cantidad <= COALESCE(d.stock_minimo, 5) AND p.is_active = 1 AND $excludeServices");
        return ($res && $res->num_rows > 0) ? (int) $res->fetch_assoc()['bajo_stock'] : 0;
    }

    public function get_recent_sales($limit = 5)
    {
        $limit = (int) $limit;
        $stmt = $this->conn->prepare("SELECT v.id_venta, v.fecha, v.total, COALESCE(NULLIF(TRIM(v.metodo_de_pago), ''), m.metodo) AS metodo_de_pago FROM venta v LEFT JOIN metodos_pago m ON v.id_metodo_pago = m.id_metodo_pago WHERE v.estado != 'anulada' ORDER BY v.fecha DESC, v.id_venta DESC LIMIT ?");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $sales = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sales[] = $row;
            }
        }
        $stmt->close();
        return $sales;
    }

    // --- Gestión de Usuarios ---

    public function count_active_admins($exclude_id = null)
    {
        if ($exclude_id) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM usuario WHERE rol = 'admin' AND is_active = 1 AND id_usuario != ?");
            $stmt->bind_param("i", $exclude_id);
        } else {
            $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM usuario WHERE rol = 'admin' AND is_active = 1");
        }
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
        return (int)$total;
    }

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

            $modulos_permitidos = ($user_data['rol'] === 'admin') ? null : 'dashboard,venta,inventario,clientes';
            $stmt = $this->conn->prepare(
                "INSERT INTO usuario (id_persona, nombre_usuario, contrasena, rol, email, verificado, is_active, token_verificacion, token_expira, modulos_permitidos) 
                 VALUES (?, ?, ?, ?, ?, 1, 1, NULL, NULL, ?)"
            );
            $stmt->bind_param(
                "isssss",
                $id_persona,
                $user_data['username'],
                $user_data['hashed_password'],
                $user_data['rol'],
                $user_data['email'],
                $modulos_permitidos
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

            $default_modulos = 'dashboard,venta,inventario,clientes';
            if (!empty($user_data['hashed_password'])) {
                if ($user_data['rol'] === 'admin') {
                    $stmt = $this->conn->prepare("UPDATE usuario SET nombre_usuario = ?, email = ?, rol = ?, contrasena = ?, modulos_permitidos = NULL WHERE id_usuario = ?");
                    $stmt->bind_param("ssssi", $user_data['username'], $user_data['email'], $user_data['rol'], $user_data['hashed_password'], $id_usuario);
                } else {
                    $stmt = $this->conn->prepare("UPDATE usuario SET nombre_usuario = ?, email = ?, rol = ?, contrasena = ?, modulos_permitidos = COALESCE(modulos_permitidos, ?) WHERE id_usuario = ?");
                    $stmt->bind_param("sssssi", $user_data['username'], $user_data['email'], $user_data['rol'], $user_data['hashed_password'], $default_modulos, $id_usuario);
                }
            } else {
                if ($user_data['rol'] === 'admin') {
                    $stmt = $this->conn->prepare("UPDATE usuario SET nombre_usuario = ?, email = ?, rol = ?, modulos_permitidos = NULL WHERE id_usuario = ?");
                    $stmt->bind_param("sssi", $user_data['username'], $user_data['email'], $user_data['rol'], $id_usuario);
                } else {
                    $stmt = $this->conn->prepare("UPDATE usuario SET nombre_usuario = ?, email = ?, rol = ?, modulos_permitidos = COALESCE(modulos_permitidos, ?) WHERE id_usuario = ?");
                    $stmt->bind_param("ssssi", $user_data['username'], $user_data['email'], $user_data['rol'], $default_modulos, $id_usuario);
                }
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
        // Al pasar a admin se limpian las restricciones de módulos (acceso total).
        // Al pasar a empleado se asignan módulos estándar para evitar fuga de privilegios si tenía NULL.
        if ($rol === 'admin') {
            $stmt = $this->conn->prepare("UPDATE usuario SET rol = ?, modulos_permitidos = NULL WHERE id_usuario = ?");
            $stmt->bind_param("si", $rol, $id_usuario);
        } else {
            $default_modules = 'dashboard,venta,inventario,clientes';
            $stmt = $this->conn->prepare("UPDATE usuario SET rol = ?, modulos_permitidos = COALESCE(modulos_permitidos, ?) WHERE id_usuario = ?");
            $stmt->bind_param("ssi", $rol, $default_modules, $id_usuario);
        }
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function update_user_modules($id_usuario, $modules_str)
    {
        // Los permisos de un administrador no pueden modificarse
        $stmt = $this->conn->prepare("UPDATE usuario SET modulos_permitidos = ? WHERE id_usuario = ? AND rol <> 'admin'");
        $stmt->bind_param("si", $modules_str, $id_usuario);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    // --- Gestión de Perfil ---

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

    public function update_profile_transaction($id_usuario, $id_persona, $persona_data, $password_data = null, $new_username = null)
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

            $sql_user = "UPDATE usuario SET email = ?";
            $types = "s";
            $params = [$persona_data['email']];

            if (!empty($new_username)) {
                $sql_user .= ", nombre_usuario = ?";
                $types .= "s";
                $params[] = $new_username;
            }

            if ($password_data) {
                $sql_user .= ", contrasena = ?";
                $types .= "s";
                $params[] = $password_data['hashed_password'];
            }

            $sql_user .= " WHERE id_usuario = ?";
            $types .= "i";
            $params[] = (int)$id_usuario;

            $stmt = $this->conn->prepare($sql_user);
            $stmt->bind_param($types, ...$params);

            if (!$stmt->execute()) {
                throw new Exception("Error al actualizar cuenta de usuario");
            }
            $stmt->close();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    // --- Auditoría ---
    public function get_audit_logs($limit = 100, $modulo = '')
    {
        $limit = (int) $limit;
        // La auditoría del sistema está dedicada exclusivamente al inventario
        $sql = "SELECT a.*, u.nombre_usuario 
                FROM audit_log a 
                LEFT JOIN usuario u ON a.id_usuario = u.id_usuario 
                WHERE (LOWER(a.tabla_afectada) IN ('inventario', 'producto') OR a.accion LIKE 'INVENTORY_%')
                ORDER BY a.fecha DESC LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $logs = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
        }
        $stmt->close();
        return $logs;
    }

    public function get_financial_stats()
    {
        $sql = "
            SELECT 
                m.mes,
                COALESCE(v.total_ventas, 0) AS ingresos,
                COALESCE(c.total_compras, 0) AS gastos
            FROM (
                SELECT DATE_FORMAT(DATE_SUB(NOW(), INTERVAL i MONTH), '%Y-%m') AS mes
                FROM (SELECT 0 as i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) temp
            ) m
            LEFT JOIN (
                SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes, SUM(total) AS total_ventas
                FROM venta
                WHERE estado != 'anulada'
                GROUP BY DATE_FORMAT(fecha, '%Y-%m')
            ) v ON m.mes = v.mes
            LEFT JOIN (
                SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes, SUM(total) AS total_compras
                FROM compra
                WHERE estado = 'Recibido'
                GROUP BY DATE_FORMAT(fecha, '%Y-%m')
            ) c ON m.mes = c.mes
            ORDER BY m.mes ASC;
        ";
        $result = $this->conn->query($sql);
        $labels = [];
        $ingresos = [];
        $gastos = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $labels[] = $row['mes'];
                $ingresos[] = $row['ingresos'];
                $gastos[] = $row['gastos'];
            }
        }
        return ['labels' => $labels, 'ingresos' => $ingresos, 'gastos' => $gastos];
    }
}
