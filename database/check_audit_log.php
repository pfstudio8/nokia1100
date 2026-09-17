<?php
require_once __DIR__ . '/../config/db.php';
$result = $conn->query("SHOW COLUMNS FROM audit_log");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
} else {
    echo "Error: " . $conn->error;
}
?>
