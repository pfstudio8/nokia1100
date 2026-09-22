<?php
require 'config/db.php';
$db = new Database();
$res = $db->conn->query('SELECT * FROM audit_log LIMIT 1');
print_r($res->fetch_assoc());
