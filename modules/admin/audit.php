<?php
// modules/admin/audit.php

require_once __DIR__ . '/Controllers/AdminController.php';

$controller = new AdminController();
$controller->audit();
