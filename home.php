<?php
session_start();

// ✅ If already logged in, go to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// ✅ If not logged in, go to login/signup page
header("Location: auth.php");
exit();
?>
