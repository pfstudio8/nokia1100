<?php
// classes/BaseModel.php

class BaseModel
{
    protected $conn;

    public function __construct()
    {
        global $conn;
        if (!isset($conn)) {
            require_once __DIR__ . '/../config/db.php';
        }
        $this->conn = $conn;
    }
}
?>
