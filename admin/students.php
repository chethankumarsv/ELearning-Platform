<?php
session_start();

/* ---------------- CONFIG ---------------- */
require_once(__DIR__ . "/../includes/config.php");

/* ---------------- ADMIN AUTH ---------------- */
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

/* ---------------- FETCH STUDENTS ---------------- */
$sql = "SELECT id, username, email, role, created_at
        FROM users
        WHERE role = 'student'
        ORDER BY created_at DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Database query failed: " . $conn->error);
}

/* ---------------- COUNT STUDENTS ---------------- */
$countSql = "SELECT COUNT(*) AS total FROM users WHERE role = 'student'";
$countResult = $conn->query($countSql);
$totalStudents = $countResult->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin | Registered Students</title>

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
    text-transform: capitalize;
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

<h1>Registered Students</h1>
<p class="subtitle">
    Total Registered Students: <strong><?php echo $totalStudents; ?></strong>
</p>

<div class="card">

<?php if ($result->num_rows > 0): ?>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Registered On</th>
        </tr>
    </thead>
    <tbody>

    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['username']); ?></td>
            <td>
                <?php
                    echo (!empty($row['email']))
                        ? htmlspecialchars($row['email'])
                        : '<span style="color:#999;">Not Provided</span>';
                ?>
            </td>
            <td>
                <span class="badge"><?php echo htmlspecialchars($row['role']); ?></span>
            </td>
            <td>
                <?php echo date("d M Y, h:i A", strtotime($row['created_at'])); ?>
            </td>
        </tr>
    <?php endwhile; ?>

    </tbody>
</table>
<?php else: ?>
    <div class="empty">
        <h3>No students found</h3>
        <p>No student accounts are registered yet.</p>
    </div>
<?php endif; ?>

</div>
</div>

</body>
</html>
