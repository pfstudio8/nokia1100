<?php
// modules/clients/Models/ClientModel.php

require_once __DIR__ . '/../../../classes/BaseModel.php';

class ClientModel extends BaseModel
{
    public function get_all($search = '')
    {
        $where = "";
        if ($search !== "") {
            $search_escaped = $this->conn->real_escape_string($search);
            $where = " WHERE nombre LIKE '%$search_escaped%' OR telefono LIKE '%$search_escaped%' OR email LIKE '%$search_escaped%'";
        }
        $sql = "SELECT id_cliente, nombre, telefono, email, created_at FROM cliente $where ORDER BY nombre ASC";
        $result = $this->conn->query($sql);
        $clientes = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $clientes[] = $row;
            }
        }
        return $clientes;
    }

    public function find($id_cliente)
    {
        $stmt = $this->conn->prepare("SELECT nombre, telefono, email FROM cliente WHERE id_cliente = ?");
        $stmt->bind_param("i", $id_cliente);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res;
    }

    public function exists($nombre, $telefono)
    {
        $stmt = $this->conn->prepare("SELECT id_cliente FROM cliente WHERE nombre = ? AND telefono = ?");
        $stmt->bind_param("ss", $nombre, $telefono);
        $stmt->execute();
        $stmt->store_result();
        $count = $stmt->num_rows;
        $stmt->close();
        return $count > 0;
    }

    public function create($nombre, $telefono, $email)
    {
        $stmt = $this->conn->prepare("INSERT INTO cliente (nombre, telefono, email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nombre, $telefono, $email);
        $id = 0;
        if ($stmt->execute()) {
            $id = $this->conn->insert_id;
        }
        $stmt->close();
        return $id;
    }

    public function update($id_cliente, $nombre, $telefono, $email)
    {
        $stmt = $this->conn->prepare("UPDATE cliente SET nombre = ?, telefono = ?, email = ? WHERE id_cliente = ?");
        $stmt->bind_param("sssi", $nombre, $telefono, $email, $id_cliente);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function count_reparaciones($id_cliente)
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM reparacion WHERE id_cliente = ?");
        $stmt->bind_param("i", $id_cliente);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
        return $count;
    }

    public function delete($id_cliente)
    {
        $stmt = $this->conn->prepare("DELETE FROM cliente WHERE id_cliente = ?");
        $stmt->bind_param("i", $id_cliente);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}
?>
