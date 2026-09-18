<?php
require_once __DIR__ . '/config/db.php';
$result = $conn->query("SHOW COLUMNS FROM audit_log");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
