<?php
// modules/auth/Models/AuthModel.php

require_once __DIR__ . '/../../../classes/BaseModel.php';

class AuthModel extends BaseModel
{
    public function find_by_username($username)
    {
        $stmt = $this->conn->prepare(
            "SELECT id_usuario, nombre_usuario, contrasena, rol, verificado, is_active
             FROM usuario
             WHERE nombre_usuario = ?"
        );
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res;
    }

    public function username_exists($username)
    {
        $stmt = $this->conn->prepare("SELECT id_usuario FROM usuario WHERE nombre_usuario = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $count = $stmt->num_rows;
        $stmt->close();
        return $count > 0;
    }

    public function dni_exists($dni)
    {
        $stmt = $this->conn->prepare("SELECT id_persona FROM persona WHERE dni = ?");
        $stmt->bind_param("s", $dni);
        $stmt->execute();
        $stmt->store_result();
        $count = $stmt->num_rows;
        $stmt->close();
        return $count > 0;
    }

    public function email_exists($email)
    {
        $stmt = $this->conn->prepare("SELECT id_persona FROM persona WHERE email = ?");
        $stmt->bind_param("s", $email);
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
            // Insertar datos de persona
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
                throw new Exception("Error al registrar persona");
            }
            $id_persona = $this->conn->insert_id;
            $stmt->close();

            // Obtener total de usuarios para asignar el rol de admin al primero
            $total_usuarios = 0;
            $count_query = $this->conn->query("SELECT COUNT(*) as total FROM usuario");
            if ($count_query) {
                $row_count = $count_query->fetch_assoc();
                $total_usuarios = (int) $row_count['total'];
            }
            $rol = ($total_usuarios === 0) ? 'admin' : 'empleado';
            
            // El primer usuario (admin) se verifica automáticamente; el resto empieza sin verificar (0)
            $verificado = ($rol === 'admin') ? 1 : 0;
            $token = isset($user_data['token_verificacion']) ? $user_data['token_verificacion'] : null;
            $expira = isset($user_data['token_expira']) ? $user_data['token_expira'] : null;

            // Insertar datos de usuario
            $stmt = $this->conn->prepare(
                "INSERT INTO usuario (id_persona, nombre_usuario, contrasena, rol, email, verificado, is_active, token_verificacion, token_expira)
                 VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)"
            );
            $stmt->bind_param(
                "issssiss",
                $id_persona,
                $user_data['username'],
                $user_data['hashed_password'],
                $rol,
                $user_data['email'],
                $verificado,
                $token,
                $expira
            );
            if (!$stmt->execute()) {
                throw new Exception("Error al registrar usuario");
            }
            $id_usuario = $this->conn->insert_id;
            $stmt->close();

            $this->conn->commit();
            return [
                'id_usuario' => $id_usuario,
                'rol' => $rol,
                'username' => $user_data['username'],
                'nombre' => $persona_data['nombre'],
                'apellido' => $persona_data['apellido']
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function find_by_email($email)
    {
        $stmt = $this->conn->prepare("
            SELECT u.id_usuario, p.nombre 
            FROM usuario u 
            JOIN persona p ON u.id_persona = p.id_persona 
            WHERE p.email = ?
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res;
    }

    public function set_reset_token($id_usuario, $token, $expira)
    {
        $stmt = $this->conn->prepare("UPDATE usuario SET token_verificacion = ?, token_expira = ? WHERE id_usuario = ?");
        $stmt->bind_param("ssi", $token, $expira, $id_usuario);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function find_by_valid_token($token)
    {
        $stmt = $this->conn->prepare("SELECT id_usuario FROM usuario WHERE token_verificacion = ? AND token_expira > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res;
    }

    public function update_password($id_usuario, $hashed_password)
    {
        $stmt = $this->conn->prepare("UPDATE usuario SET contrasena = ?, token_verificacion = NULL, token_expira = NULL WHERE id_usuario = ?");
        $stmt->bind_param("si", $hashed_password, $id_usuario);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function get_last_user_token()
    {
        $sql = "SELECT id_usuario, email, token_verificacion FROM usuario ORDER BY id_usuario DESC LIMIT 1";
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
}
?>
