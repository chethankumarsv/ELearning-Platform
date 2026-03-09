<?php
session_start();

/* ---------- CONFIG ---------- */
require_once(__DIR__ . "/../includes/config.php");

/* ---------- ADMIN AUTH ---------- */
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

/* ---------- FETCH ENROLLMENTS ---------- */
$sql = "
    SELECT 
        e.id,
        u.username,
        c.title AS course_title,
        e.enrolled_at
    FROM enrollments e
    INNER JOIN users u ON e.user_id = u.id
    INNER JOIN courses c ON e.course_id = c.id
    ORDER BY e.enrolled_at DESC
";

$result = $conn->query($sql);

if (!$result) {
    die("Database query failed: " . $conn->error);
}

/* ---------- COUNT ENROLLMENTS ---------- */
$countResult = $conn->query("SELECT COUNT(*) AS total FROM enrollments");
$totalEnrollments = $countResult->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin | Enrollments</title>

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
    max-width: 1200px;
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
    margin-bottom: 5px;
}

.subtitle {
    color: #555;
    margin-bottom: 20px;
}

.card {
    background: #ffffff;
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
    color: #ffffff;
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

.badge {
    background: #eef2ff;
    color: #4338ca;
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 13px;
    display: inline-block;
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

<h1>Course Enrollments</h1>
<p class="subtitle">
    Total Enrollments: <strong><?php echo $totalEnrollments; ?></strong>
</p>

<div class="card">

<?php if ($result->num_rows > 0): ?>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Student</th>
            <th>Course</th>
            <th>Enrolled On</th>
        </tr>
    </thead>
    <tbody>

    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td>
                <span class="badge">
                    <?php echo htmlspecialchars($row['username']); ?>
                </span>
            </td>
            <td><?php echo htmlspecialchars($row['course_title']); ?></td>
            <td><?php echo date("d M Y, h:i A", strtotime($row['enrolled_at'])); ?></td>
        </tr>
    <?php endwhile; ?>

    </tbody>
</table>
<?php else: ?>
    <div class="empty">
        <h3>No enrollments found</h3>
        <p>No students have enrolled in any courses yet.</p>
    </div>
<?php endif; ?>

</div>
</div>

</body>
</html>
