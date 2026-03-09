<?php
// admin/header.php
// Reusable admin header / nav. Safe checks for session & DB connection.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ensure DB connection is available
if (!isset($conn) || !$conn) {
    require_once __DIR__ . '/../includes/config.php';
}

// Admin check - be permissive to either admin_id or is_admin flag
$adminAuthenticated = false;
if (!empty($_SESSION['admin_id'])) {
    $adminAuthenticated = true;
} elseif (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    $adminAuthenticated = true;
}

if (!$adminAuthenticated) {
    // prefer a server-side header redirect when possible
    if (!headers_sent()) {
        header('Location: login.php');
        exit;
    } else {
        // fallback to client-side redirect when headers already sent
        echo '<script>location.href="login.php"</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=login.php"></noscript>';
        exit;
    }
}

// Safe admin display name (try admin_name, then username, else Admin)
if (!empty($_SESSION['admin_name']) && is_string($_SESSION['admin_name'])) {
    $adminName = htmlspecialchars($_SESSION['admin_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
} elseif (!empty($_SESSION['username']) && is_string($_SESSION['username'])) {
    $adminName = htmlspecialchars($_SESSION['username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
} else {
    $adminName = 'Admin';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin - E-Learning</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
/* Minimal header styling - will not conflict much with existing styles */
.admin-header {
  background: linear-gradient(90deg,#007BFF,#00C6FF);
  color:#fff;padding:12px 20px;display:flex;align-items:center;justify-content:space-between;
  box-shadow:0 3px 10px rgba(0,0,0,0.12);
}
.admin-brand {font-weight:700;font-size:1.15rem}
.admin-nav a {color:#fff;text-decoration:none;margin-left:16px;font-weight:600}
.admin-nav a:hover {opacity:0.92;text-decoration:underline}
.admin-quick {font-size:0.95rem;color:#e8f9ff}
@media(max-width:700px){ .admin-nav{display:none} }
</style>
</head>
<body>
<header class="admin-header">
  <div>
    <span class="admin-brand">E-Learning Admin</span>
    <span class="admin-quick" style="margin-left:14px;">Welcome, <?php echo $adminName; ?></span>
  </div>
  <nav class="admin-nav">
    <a href="dashboard.php">Dashboard</a>
    <a href="courses.php">Courses</a>
    <a href="users.php">Students</a>
    <a href="upload_notes.php">Notes</a>
    <a href="subjects_admin.php">Subjects</a>
    <a href="logout.php">Logout</a>
  </nav>
</header>
<main style="padding:18px;">
