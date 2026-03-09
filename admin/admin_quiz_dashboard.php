<?php
// /elearningplatform/admin_quiz_dashboard.php
session_start();
require_once __DIR__ . '/includes/config.php';

// Ensure admin login (replace with your admin session logic)
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit;
}

// =============================
// Filters
// =============================
$subject_filter = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;
$user_filter    = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Get all subjects
$subjects = $conn->query("SELECT * FROM subjects ORDER BY name ASC");

// Get all users
$users = $conn->query("SELECT id, username, email FROM users ORDER BY username ASC");

// Build query
$sql = "SELECT qa.*, u.username, s.name AS subject_name 
        FROM quiz_attempts qa
        JOIN users u ON qa.user_id = u.id
        JOIN subjects s ON qa.subject_id = s.id
        WHERE 1=1";

if ($subject_filter > 0) {
    $sql .= " AND qa.subject_id = $subject_filter";
}
if ($user_filter > 0) {
    $sql .= " AND qa.user_id = $user_filter";
}

$sql .= " ORDER BY qa.attempt_date DESC";

$result = $conn->query($sql);
$attempts = [];
while ($row = $result->fetch_assoc()) {
    $attempts[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Quiz Dashboard</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, sans-serif;
      background: linear-gradient(135deg, #232526, #414345);
      margin: 0;
      padding: 0;
      color: #fff;
      display: flex;
      flex-direction: column;
      align-items: center;
      min-height: 100vh;
    }
    header {
      width: 100%;
      text-align: center;
      padding: 20px;
      background: rgba(0,0,0,0.5);
      font-size: 1.8rem;
      font-weight: bold;
    }
    .container {
      margin: 20px;
      width: 95%;
      max-width: 1100px;
      background: rgba(255,255,255,0.1);
      backdrop-filter: blur(12px);
      border-radius: 16px;
      padding: 25px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.4);
    }
    form {
      display: flex;
      gap: 10px;
      margin-bottom: 15px;
      flex-wrap: wrap;
    }
    select, button {
      padding: 10px;
      border-radius: 8px;
      border: none;
      font-size: 1rem;
    }
    button {
      background: linear-gradient(45deg, #00c6ff, #0072ff);
      color: #fff;
      cursor: pointer;
      transition: 0.3s;
    }
    button:hover { transform: scale(1.05); }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    th, td {
      padding: 12px;
      text-align: center;
      border-bottom: 1px solid rgba(255,255,255,0.2);
    }
    th {
      background: rgba(0,0,0,0.4);
      font-size: 1.1rem;
    }
    tr:hover { background: rgba(255,255,255,0.1); }
    .score-pass { color: #00e676; font-weight: bold; }
    .score-fail { color: #ff5252; font-weight: bold; }
    canvas {
      margin-top: 20px;
      background: #fff;
      border-radius: 10px;
      padding: 10px;
    }
    @media(max-width: 700px) {
      th, td { font-size: 0.8rem; padding: 8px; }
      form { flex-direction: column; }
    }
  </style>
</head>
<body>
  <header>Admin Quiz Dashboard</header>
  <div class="container">
    <!-- Filter Form -->
    <form method="GET">
      <select name="subject_id">
        <option value="0">-- All Subjects --</option>
        <?php while ($s = $subjects->fetch_assoc()): ?>
          <option value="<?= $s['id'] ?>" <?= $subject_filter==$s['id']?'selected':'' ?>>
            <?= htmlspecialchars($s['name']) ?>
          </option>
        <?php endwhile; ?>
      </select>

      <select name="user_id">
        <option value="0">-- All Students --</option>
        <?php while ($u = $users->fetch_assoc()): ?>
          <option value="<?= $u['id'] ?>" <?= $user_filter==$u['id']?'selected':'' ?>>
            <?= htmlspecialchars($u['username']) ?> (<?= $u['email'] ?>)
          </option>
        <?php endwhile; ?>
      </select>

      <button type="submit">Filter</button>
    </form>

    <!-- Export Buttons -->
    <form method="POST" action="export_quiz.php" style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;">
      <input type="hidden" name="subject_id" value="<?= $subject_filter ?>">
      <input type="hidden" name="user_id" value="<?= $user_filter ?>">
      <button type="submit" name="export" value="excel">Export to Excel</button>
      <button type="submit" name="export" value="pdf">Export to PDF</button>
    </form>

    <?php if (count($attempts) > 0): ?>
      <table>
        <tr>
          <th>Student</th>
          <th>Subject</th>
          <th>Score</th>
          <th>Total</th>
          <th>Percentage</th>
          <th>Date</th>
        </tr>
        <?php foreach ($attempts as $a): 
          $percentage = round(($a['score'] / $a['total']) * 100, 2);
        ?>
          <tr>
            <td><?= htmlspecialchars($a['username']) ?></td>
            <td><?= htmlspecialchars($a['subject_name']) ?></td>
            <td class="<?= $percentage >= 40 ? 'score-pass' : 'score-fail' ?>">
              <?= $a['score'] ?>
            </td>
            <td><?= $a['total'] ?></td>
            <td><?= $percentage ?>%</td>
            <td><?= date("d M Y, h:i A", strtotime($a['attempt_date'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>

      <canvas id="summaryChart" width="400" height="200"></canvas>
    <?php else: ?>
      <p>No quiz attempts found.</p>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    <?php if (count($attempts) > 0): ?>
    const ctx = document.getElementById('summaryChart').getContext('2d');
    const summaryChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: <?= json_encode(array_column($attempts, 'username')) ?>,
        datasets: [{
          label: 'Scores',
          data: <?= json_encode(array_column($attempts, 'score')) ?>,
          backgroundColor: '#00c6ff'
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { labels: { color: "#000" } } },
        scales: {
          x: { ticks: { color: "#000" } },
          y: { ticks: { color: "#000" }, beginAtZero: true }
        }
      }
    });
    <?php endif; ?>
  </script>
</body>
</html>
