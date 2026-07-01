<?php
// modules/workshop/index.php

require_once __DIR__ . '/Controllers/WorkshopController.php';

$controller = new WorkshopController();
$controller->index();
?>
