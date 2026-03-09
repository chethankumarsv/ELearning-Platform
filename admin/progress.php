<?php
session_start();

/* ---------------- CONFIG ---------------- */
require_once(__DIR__ . "/../includes/config.php");

/* ---------------- ADMIN AUTH ---------------- */
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

/* ---------------- FETCH STUDENT PROGRESS ---------------- */
$sql = "
    SELECT 
        u.id AS student_id,
        u.username,
        c.title AS course_title,
        e.progress,
        e.enrolled_at,
        (
            SELECT COUNT(*) 
            FROM attendance a 
            WHERE a.user_id = u.id
        ) AS total_logins
    FROM enrollments e
    JOIN users u ON e.user_id = u.id
    JOIN courses c ON e.course_id = c.id
    WHERE u.role = 'student'
    ORDER BY e.enrolled_at DESC
";

$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin | Student Progress</title>

<style>
* {
    box-sizing: border-box;
    font-family: 'Segoe UI', sans-serif;
}

body {
    margin: 0;
    background: #f4f6fb;
}

.container {
    max-width: 1300px;
    margin: auto;
    padding: 30px;
}

.back {
    display: inline-block;
    margin-bottom: 15px;
    text-decoration: none;
    color: #4f46e5;
    font-weight: 500;
}

h1 {
    margin-bottom: 6px;
}

.subtitle {
    color: #555;
    margin-bottom: 20px;
}

.card {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 15px 30px rgba(0,0,0,0.08);
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: #4f46e5;
    color: #fff;
}

th, td {
    padding: 14px;
    text-align: left;
}

tbody tr {
    border-bottom: 1px solid #eee;
}

tbody tr:hover {
    background: #f1f5ff;
}

.progress-bar {
    background: #e5e7eb;
    border-radius: 20px;
    overflow: hidden;
    height: 14px;
    width: 120px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #22c55e, #16a34a);
}

.badge {
    background: #eef2ff;
    color: #4338ca;
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 13px;
}

.login-count {
    font-weight: 600;
    color: #2563eb;
}

.empty {
    text-align: center;
    padding: 40px;
    color: #777;
}
</style>
</head>

<body>

<div class="container">

<a class="back" href="dashboard.php">← Back to Dashboard</a>

<h1>Student Progress & Attendance</h1>
<p class="subtitle">
    Monitor learning progress, activity, and attendance
</p>

<div class="card">

<?php if ($result->num_rows > 0): ?>
<table>
    <thead>
        <tr>
            <th>Student</th>
            <th>Course</th>
            <th>Progress</th>
            <th>Attendance</th>
            <th>Enrolled On</th>
        </tr>
    </thead>
    <tbody>

    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td>
                <span class="badge">
                    <?php echo htmlspecialchars($row['username']); ?>
                </span>
            </td>
            <td><?php echo htmlspecialchars($row['course_title']); ?></td>

            <td>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo (int)$row['progress']; ?>%;"></div>
                </div>
                <small><?php echo (int)$row['progress']; ?>%</small>
            </td>

            <td class="login-count">
                <?php echo (int)$row['total_logins']; ?> logins
            </td>

            <td>
                <?php echo date("d M Y", strtotime($row['enrolled_at'])); ?>
            </td>
        </tr>
    <?php endwhile; ?>

    </tbody>
</table>
<?php else: ?>
    <div class="empty">
        <h3>No progress data found</h3>
        <p>No students have enrolled or logged in yet.</p>
    </div>
<?php endif; ?>

</div>
</div>

</body>
</html>
