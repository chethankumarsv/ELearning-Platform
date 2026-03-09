<?php
session_start();
include('../includes/config.php');

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

/* ---------- COUNT FUNCTION ---------- */
function getCount($conn, $table) {
    $sql = "SELECT COUNT(*) AS total FROM $table";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        die("SQL Error in $table: " . mysqli_error($conn));
    }
    return mysqli_fetch_assoc($result)['total'];
}

/* ---------- COUNTS ---------- */
$total_courses      = getCount($conn, "courses");
$total_students     = getCount($conn, "users");
$total_quizzes      = getCount($conn, "quizzes");
$total_certificates = getCount($conn, "certificates");
$total_notes        = getCount($conn, "notes");
$total_mentors      = getCount($conn, "mentors");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box}

body{
    font-family:'Segoe UI',sans-serif;
    background:#f8fafc;
    color:#334155;
}

.admin-container{display:flex;min-height:100vh}

/* ---------- SIDEBAR ---------- */
.sidebar{
    width:260px;
    background:linear-gradient(180deg,#1e293b,#0f172a);
    color:#fff;
}

.sidebar-header{
    padding:25px;
    text-align:center;
    border-bottom:1px solid #334155;
}

.sidebar-menu{list-style:none;padding:20px 0}

.sidebar-menu a{
    color:#cbd5e1;
    text-decoration:none;
    padding:14px 25px;
    display:flex;
    align-items:center;
    gap:12px;
    transition:.3s;
}

.sidebar-menu a:hover,
.sidebar-menu a.active{
    background:rgba(255,255,255,.1);
    color:#fff;
    border-left:4px solid #3b82f6;
}

/* ---------- MAIN ---------- */
.main-content{flex:1;display:flex;flex-direction:column}

.header{
    background:#fff;
    padding:18px 30px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 2px 10px rgba(0,0,0,.08);
}

.content{padding:30px}

/* ---------- DASHBOARD ---------- */
.dashboard-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
    gap:25px;
    margin-bottom:30px;
}

.stat-card{
    background:#fff;
    padding:25px;
    border-radius:12px;
    box-shadow:0 4px 15px rgba(0,0,0,.08);
    border-left:5px solid;
}

.stat-card i{font-size:26px;margin-bottom:12px}
.stat-number{font-size:2.2rem;font-weight:800}
.stat-label{color:#64748b;font-weight:600}

.courses{border-color:#3b82f6}
.students{border-color:#10b981}
.mentors{border-color:#06b6d4}
.quizzes{border-color:#f59e0b}
.certificates{border-color:#ef4444}
.notes{border-color:#8b5cf6}

/* ---------- QUICK ACTIONS ---------- */
.quick-actions{
    background:#fff;
    padding:25px;
    border-radius:12px;
    box-shadow:0 4px 15px rgba(0,0,0,.08);
}

.action-buttons{
    display:flex;
    gap:15px;
    flex-wrap:wrap;
}

.action-btn{
    flex:1;
    min-width:200px;
    padding:14px 24px;
    border-radius:8px;
    border:2px solid #e2e8f0;
    text-decoration:none;
    color:#475569;
    font-weight:600;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    transition:.3s;
}

.action-btn:hover{
    border-color:#3b82f6;
    color:#3b82f6;
    transform:translateY(-2px);
}

/* 🔐 Proctoring Evidence Button */
.evidence-btn{
    border-color:#ef4444;
    color:#ef4444;
}
.evidence-btn:hover{
    background:#ef4444;
    color:#fff;
}

/* ---------- FOOTER ---------- */
.admin-footer{
    margin-top:auto;
    background:#1e293b;
    color:#cbd5e1;
    text-align:center;
    padding:20px;
}
</style>
</head>

<body>
<div class="admin-container">

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-graduation-cap"></i> Admin Panel</h2>
        <p>E-Learning System</p>
    </div>
    <ul class="sidebar-menu">
        <li><a class="active" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="courses.php"><i class="fas fa-book"></i> Courses</a></li>
        <li><a href="users.php"><i class="fas fa-users"></i> Students</a></li>
        <li><a href="mentors.php"><i class="fas fa-chalkboard-teacher"></i> Mentors</a></li>
        <li><a href="quizzes.php"><i class="fas fa-question-circle"></i> Quizzes</a></li>
        <li><a href="proctor_evidence.php"><i class="fas fa-user-shield"></i> Proctoring Evidence</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- MAIN -->
<div class="main-content">

<div class="header">
    <h1>Admin Dashboard</h1>
    <strong>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></strong>
</div>

<div class="content">

<!-- STATS -->
<div class="dashboard-grid">
    <div class="stat-card courses"><i class="fas fa-book"></i><div class="stat-number"><?php echo $total_courses; ?></div><div class="stat-label">Courses</div></div>
    <div class="stat-card students"><i class="fas fa-users"></i><div class="stat-number"><?php echo $total_students; ?></div><div class="stat-label">Students</div></div>
    <div class="stat-card mentors"><i class="fas fa-chalkboard-teacher"></i><div class="stat-number"><?php echo $total_mentors; ?></div><div class="stat-label">Mentors</div></div>
    <div class="stat-card quizzes"><i class="fas fa-question-circle"></i><div class="stat-number"><?php echo $total_quizzes; ?></div><div class="stat-label">Quizzes</div></div>
    <div class="stat-card certificates"><i class="fas fa-certificate"></i><div class="stat-number"><?php echo $total_certificates; ?></div><div class="stat-label">Certificates</div></div>
    <div class="stat-card notes"><i class="fas fa-file-alt"></i><div class="stat-number"><?php echo $total_notes; ?></div><div class="stat-label">Notes</div></div>
</div>

<!-- QUICK ACTIONS -->
<div class="quick-actions">
<h3><i class="fas fa-bolt"></i> Quick Actions</h3>
<div class="action-buttons">

<a href="courses.php" class="action-btn"><i class="fas fa-plus"></i> Add Course</a>
<a href="users.php" class="action-btn"><i class="fas fa-users"></i> Manage Students</a>
<a href="mentors.php" class="action-btn"><i class="fas fa-chalkboard-teacher"></i> Manage Mentors</a>
<a href="quizzes.php" class="action-btn"><i class="fas fa-question-circle"></i> Create Quiz</a>

<!-- 🔐 PROCTORING EVIDENCE BUTTON -->
<a href="proctor_evidence.php" class="action-btn evidence-btn">
<i class="fas fa-user-shield"></i> Proctoring Evidence
</a>

</div>
</div>

</div>

<footer class="admin-footer">
&copy; <?php echo date("Y"); ?> E-Learning Platform
</footer>

</div>
</div>
</body>
</html>
