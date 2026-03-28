<?php
// admin/auth/logout.php
session_start();

// Unset only admin-related session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_role']);

header("Location: ../login.php");
exit();
?>
