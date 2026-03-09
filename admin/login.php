<?php
declare(strict_types=1);

session_start();

/* ================= LOAD DATABASE CONFIG (CORRECT PATH) ================= */
$configPath = __DIR__ . '/../config/database.php';

if (!file_exists($configPath)) {
    die('Configuration file not found. Please check if config/database.php exists.');
}

require_once $configPath;

/* ================= ADMIN AUTH ================= */
define('ADMIN_NAME', 'admin');
define('ADMIN_PASS', 'Admin@123');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['admin_name'] ?? '';
    $pass = $_POST['admin_password'] ?? '';

    if ($name === ADMIN_NAME && $pass === ADMIN_PASS) {
        $_SESSION['admin'] = true;
        header('Location: index.php');
        exit;
    }

    $error = 'Invalid admin credentials';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login</title>
<style>
body{
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:#0f2027;
    font-family:Arial, sans-serif;
}
.box{
    background:#fff;
    padding:30px;
    width:350px;
    border-radius:10px;
}
input,button{
    width:100%;
    padding:10px;
    margin-top:10px;
}
button{
    background:#0072ff;
    color:#fff;
    border:none;
    cursor:pointer;
}
.error{color:red;text-align:center}
</style>
</head>
<body>

<div class="box">
<h2>Admin Login</h2>

<?php if($error): ?>
<p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="post">
    <input type="text" name="admin_name" placeholder="Admin Name" required>
    <input type="password" name="admin_password" placeholder="Password" required>
    <button type="submit">Login</button>
</form>
</div>

</body>
</html>
