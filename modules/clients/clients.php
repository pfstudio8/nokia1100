<?php
// modules/clients/clients.php

require_once __DIR__ . '/Controllers/ClientsController.php';

$controller = new ClientsController();
$controller->index();
?>