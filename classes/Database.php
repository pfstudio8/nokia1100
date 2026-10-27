<?php
class Database {
    private $host;
    private $user;
    private $password;
    private $db_name;
    public $conn;

    public function __construct() {
        require_once __DIR__ . '/../config/bootstrap.php';

        $this->host = $_ENV['DB_HOST'];
        $this->user = $_ENV['DB_USER'];
        $this->password = $_ENV['DB_PASS'];
        $this->db_name = $_ENV['DB_NAME'];
        
        mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);
        try {
            $this->conn = new mysqli($this->host, $this->user, $this->password, $this->db_name);
            $this->conn->set_charset("utf8mb4");
            $this->conn->query("SET time_zone = '-03:00'");
        } catch (Exception $e) {
            error_log("Connection failed: " . $e->getMessage());
            die("Error interno de conexión a la base de datos.");
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function query($sql) {
        return $this->conn->query($sql);
    }

    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }

    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }

    public function insert_id() {
        return $this->conn->insert_id;
    }

    public function close() {
        $this->conn->close();
    }
}
