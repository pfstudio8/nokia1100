<?php
// tienda/auth/logout.php
session_start();

// Unset only customer-related session variables
unset($_SESSION['cliente_id']);
unset($_SESSION['cliente_email']);

header("Location: ../login.php");
exit();
?>
