<?php
// modules/workshop/print_receipt.php

require_once __DIR__ . '/Controllers/WorkshopController.php';

$controller = new WorkshopController();
$controller->print_receipt();
?>
