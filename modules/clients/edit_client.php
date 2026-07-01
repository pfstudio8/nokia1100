<?php
// modules/clients/edit_client.php

require_once __DIR__ . '/Controllers/ClientsController.php';

$controller = new ClientsController();
$controller->edit();
?>