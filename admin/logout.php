<?php
// admin/logout.php
session_start();

// remove ONLY admin session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
unset($_SESSION['is_admin']);

// Or destroy all sessions if admin panel is separate
// session_destroy();

// Redirect to ADMIN login page
header("Location: login.php");
exit;
