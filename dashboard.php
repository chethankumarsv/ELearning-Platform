<?php
// dashboard.php — CORRECT & STABLE

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// User must be logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: auth.php");
    exit;
}

// Read data from session (NO DB QUERY NEEDED)
$name  = $_SESSION['name']  ?? 'Student';
$usn   = $_SESSION['usn']   ?? '';
$email = $_SESSION['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

<style>
body{
    margin:0;
    min-height:100vh;
    font-family:Poppins,Arial,sans-serif;
    background:linear-gradient(135deg,#1f2933,#111827);
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff;
}
.dashboard{
    width:420px;
    padding:32px;
    border-radius:22px;
    background:rgba(255,255,255,0.08);
    backdrop-filter:blur(18px);
    box-shadow:0 20px 40px rgba(0,0,0,0.45);
    text-align:center;
}
.dashboard h2{
    margin:0 0 8px;
    font-size:1.6rem;
}
.dashboard p{
    margin:6px 0;
    color:#cbd5f5;
}
nav{
    margin-top:24px;
}
nav a{
    display:block;
    padding:14px;
    margin:12px 0;
    border-radius:14px;
    background:rgba(255,255,255,0.12);
    color:#fff;
    font-weight:600;
    text-decoration:none;
    transition:all .25s ease;
}
nav a:hover{
    background:rgba(255,255,255,0.25);
    transform:translateY(-2px);
}
.logout{
    background:linear-gradient(90deg,#ef4444,#f97316);
}
.logout:hover{
    background:linear-gradient(90deg,#dc2626,#ea580c);
}
</style>
</head>

<body>

<div class="dashboard">
    <h2>Welcome, <?= htmlspecialchars($name) ?> 👋</h2>
    <p><strong>USN:</strong> <?= htmlspecialchars($usn) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>

    <nav>
        <a href="courses.php">📚 My Courses</a>
        <a href="quizzes.php">📝 Quizzes</a>
        <a href="my_certificates.php">📜 Certificates</a>
        <a class="logout" href="auth.php?action=logout">🚪 Logout</a>
    </nav>
</div>

</body>
</html>
