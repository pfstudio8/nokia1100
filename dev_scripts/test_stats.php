<?php
require_once 'database/database.php';
require_once 'modules/admin/Models/AdminModel.php';

$model = new AdminModel();
$stats = $model->get_financial_stats();
echo "DATA:\n";
print_r($stats);
echo "JSON:\n";
echo json_encode($stats);
?>
