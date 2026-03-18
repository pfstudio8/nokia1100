<?php
require_once '../config/bd.php';

$sql = "SELECT id_usuario, email, token_verificacion FROM usuario ORDER BY id_usuario DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "Last User: " . $row['email'] . "\n";
    echo "Token: " . $row['token_verificacion'] . "\n";
    
    // Construct link
    $base_url = "http://localhost/nokia1100"; // Assuming standard XAMPP setup
    echo "Link: " . $base_url . "/verificar.php?token=" . $row['token_verificacion'] . "\n";
} else {
    echo "No users found.";
}
?>
