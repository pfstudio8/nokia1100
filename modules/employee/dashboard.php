<?php
// modules/employee/dashboard.php

require_once __DIR__ . '/Controllers/EmployeeController.php';

$controller = new EmployeeController();
$controller->dashboard();
?>