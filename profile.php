<?php
session_start();
if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    header("Location: auth.php?type=login");
    exit;
}

require_once __DIR__ . '/includes/config.php';

$id = $_SESSION['student_id'];
$stmt = $conn->prepare("SELECT username, usn, email FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html>
<head>
<title>My Profile</title>
<style>
body{
  font-family:Poppins,sans-serif;
  background:#0f172a;
  color:#fff;
  padding:40px;
}
.card{
  max-width:500px;
  margin:auto;
  background:#1e293b;
  padding:30px;
  border-radius:16px;
}
h2{margin-bottom:20px}
.field{margin-bottom:15px}
label{color:#94a3b8;font-size:14px}
.value{font-weight:600}
a{color:#7c3aed}
</style>
</head>
<body>
  <div class="card">
    <h2>👤 My Profile</h2>
    <div class="field"><label>Username</label><div class="value"><?php echo h($user['username']); ?></div></div>
    <div class="field"><label>USN</label><div class="value"><?php echo h($user['usn']); ?></div></div>
    <div class="field"><label>Email</label><div class="value"><?php echo h($user['email']); ?></div></div>
    <br>
    <a href="index.php">← Back to Home</a>
  </div>
</body>
</html>
