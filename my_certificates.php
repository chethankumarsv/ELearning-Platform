<?php
session_start();
require_once("includes/config.php");

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ✅ Fetch all certificates for the logged-in user
$query = $conn->prepare("
    SELECT c.id AS cert_id, c.file_path, c.issued_at, 
           cr.title AS course_name 
    FROM certificates c
    JOIN courses cr ON c.course_id = cr.id
    WHERE c.user_id = ?
    ORDER BY c.issued_at DESC
");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Certificates</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f6fa;
            margin: 0;
            padding: 20px;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }
        .cert-container {
            max-width: 900px;
            margin: auto;
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background: #2c3e50;
            color: #fff;
        }
        tr:nth-child(even) {
            background: #f2f2f2;
        }
        a.download-btn {
            background: #27ae60;
            color: #fff;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }
        a.download-btn:hover {
            background: #219150;
        }
    </style>
</head>
<body>
    <h1>My Certificates</h1>
    <div class="cert-container">
        <?php if ($result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>Course</th>
                    <th>Issued On</th>
                    <th>Download</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                        <td><?php echo date("d M Y", strtotime($row['issued_at'])); ?></td>
                        <td>
                            <a class="download-btn" href="<?php echo $row['file_path']; ?>" target="_blank">Download</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>No certificates earned yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>
