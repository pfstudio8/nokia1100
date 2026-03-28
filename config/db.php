<?php
define('BASE_URL', '/www/nokia1100');
require_once __DIR__ . '/../classes/Database.php';

// Instantiate the new OOP Database class
$db = new Database();

// Expose legacy $conn variable to prevent breaking existing scripts
$conn = $db->getConnection();
?>
