<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

/* ---------- AUTH ---------- */
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

/* ---------- SAFE HELPER ---------- */
function e($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

function tableCount($conn, $table) {
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $q = $conn->query("SELECT COUNT(*) AS total FROM `$table`");
    return $q ? (int)$q->fetch_assoc()['total'] : 0;
}

/* ---------- METRICS ---------- */
$total_courses  = tableCount($conn, 'courses');
$total_students = tableCount($conn, 'users');
$total_quizzes  = tableCount($conn, 'quizzes');
$total_notes    = tableCount($conn, 'notes');
$total_certs    = tableCount($conn, 'certificates');
$total_mentors  = tableCount($conn, 'mentors');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard | E-Learning</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
/* ================= ROOT ================= */
:root{
    --bg:#f5f7fb;
    --sidebar:#0f172a;
    --primary:#4f46e5;
    --accent:#22c55e;
    --danger:#ef4444;
    --card:#ffffff;
    --text:#1e293b;
    --muted:#64748b;
    --radius:14px;
    --shadow:0 10px 30px rgba(0,0,0,.08);
}

/* ================= RESET ================= */
*{margin:0;padding:0;box-sizing:border-box}
body{
    font-family:'Segoe UI',system-ui,-apple-system,BlinkMacSystemFont;
    background:var(--bg);
    color:var(--text);
}

/* ================= LAYOUT ================= */
.wrapper{
    display:flex;
    min-height:100vh;
}

/* ================= SIDEBAR ================= */
.sidebar{
    width:260px;
    background:linear-gradient(180deg,#020617,#0f172a);
    color:#fff;
    padding:25px 20px;
}
.brand{
    display:flex;
    align-items:center;
    gap:12px;
    font-size:1.4rem;
    font-weight:800;
    margin-bottom:40px;
}
.menu a{
    display:flex;
    align-items:center;
    gap:12px;
    padding:14px 16px;
    margin-bottom:8px;
    text-decoration:none;
    color:#cbd5f5;
    border-radius:10px;
    transition:.25s;
}
.menu a.active,
.menu a:hover{
    background:rgba(255,255,255,.1);
    color:#fff;
}

/* ================= MAIN ================= */
.main{
    flex:1;
    display:flex;
    flex-direction:column;
}

/* ================= HEADER ================= */
.topbar{
    background:#fff;
    padding:18px 30px;
    box-shadow:0 2px 12px rgba(0,0,0,.05);
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.user{
    display:flex;
    align-items:center;
    gap:12px;
    font-weight:600;
}
.avatar{
    width:42px;height:42px;
    border-radius:50%;
    background:var(--primary);
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
}

/* ================= CONTENT ================= */
.content{
    padding:35px;
}

/* ================= HERO ================= */
.hero{
    background:linear-gradient(135deg,#6366f1,#8b5cf6);
    color:#fff;
    padding:35px;
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    margin-bottom:35px;
}
.hero h1{font-size:2rem;margin-bottom:6px}
.hero p{opacity:.9}

/* ================= STATS ================= */
.stats{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:22px;
}
.card{
    background:var(--card);
    padding:26px;
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    position:relative;
    overflow:hidden;
}
.card::before{
    content:'';
    position:absolute;
    inset:auto 0 0 0;
    height:5px;
    background:var(--primary);
}
.card.green::before{background:var(--accent)}
.card.red::before{background:var(--danger)}
.card i{
    font-size:1.7rem;
    color:var(--primary);
}
.card h2{
    margin:14px 0 6px;
    font-size:2.1rem;
}
.card span{
    color:var(--muted);
    font-weight:600;
}

/* ================= ACTIONS ================= */
.actions{
    margin-top:40px;
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
    gap:20px;
}
.action{
    background:#fff;
    padding:24px;
    border-radius:var(--radius);
    text-decoration:none;
    color:var(--text);
    box-shadow:var(--shadow);
    transition:.3s;
}
.action:hover{
    transform:translateY(-6px);
    box-shadow:0 20px 40px rgba(0,0,0,.15);
}
.action i{
    font-size:1.6rem;
    color:var(--primary);
    margin-bottom:10px;
}

/* ================= RESPONSIVE ================= */
@media(max-width:768px){
    .sidebar{display:none}
    .content{padding:20px}
}
</style>
</head>

<body>
<div class="wrapper">

<!-- ========== SIDEBAR ========== -->
<aside class="sidebar">
    <div class="brand">
        <i class="fas fa-graduation-cap"></i> E-Learning
    </div>
    <nav class="menu">
        <a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
        <a href="students.php"><i class="fas fa-users"></i> Students</a>
        <a href="mentors.php"><i class="fas fa-chalkboard-teacher"></i> Mentors</a>
        <a href="courses.php"><i class="fas fa-book"></i> Courses</a>
        <a href="quizzes.php"><i class="fas fa-question-circle"></i> Quizzes</a>
        <a href="certificates.php"><i class="fas fa-certificate"></i> Certificates</a>
        <a href="enrollments.php"><i class="fas fa-list"></i> Enrollments</a>
        <a href="progress.php"><i class="fas fa-chart-line"></i> Progress</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</aside>

<!-- ========== MAIN ========== -->
<div class="main">

<header class="topbar">
    <h2>Admin Dashboard</h2>
    <div class="user">
        <div class="avatar"><?= strtoupper(substr($_SESSION['admin_name'],0,1)) ?></div>
        <?= e($_SESSION['admin_name']) ?>
    </div>
</header>

<div class="content">

<div class="hero">
    <h1>Welcome, <?= e($_SESSION['admin_name']) ?> 👋</h1>
    <p>Monitor platform activity, students, courses, and performance.</p>
</div>

<div class="stats">
    <div class="card"><i class="fas fa-book"></i><h2><?= $total_courses ?></h2><span>Courses</span></div>
    <div class="card green"><i class="fas fa-users"></i><h2><?= $total_students ?></h2><span>Students</span></div>
    <div class="card"><i class="fas fa-question"></i><h2><?= $total_quizzes ?></h2><span>Quizzes</span></div>
    <div class="card"><i class="fas fa-file"></i><h2><?= $total_notes ?></h2><span>Notes</span></div>
    <div class="card green"><i class="fas fa-certificate"></i><h2><?= $total_certs ?></h2><span>Certificates</span></div>
    <div class="card red"><i class="fas fa-chalkboard-teacher"></i><h2><?= $total_mentors ?></h2><span>Mentors</span></div>
</div>

<div class="actions">
    <a class="action" href="courses.php"><i class="fas fa-book"></i><h4>Manage Courses</h4></a>
    <a class="action" href="students.php"><i class="fas fa-users"></i><h4>Manage Students</h4></a>
    <a class="action" href="progress.php"><i class="fas fa-chart-line"></i><h4>View Progress</h4></a>
    <a class="action" href="upload_notes.php"><i class="fas fa-upload"></i><h4>Upload Notes</h4></a>
</div>

</div>
</div>
</div>
</body>
</html>
