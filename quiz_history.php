<?php
session_start();
require_once __DIR__ . '/includes/config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ✅ Try normal query first, fallback if column not found
$sql = "
    SELECT qa.*, c.title AS course_name 
    FROM quiz_attempts qa
    JOIN courses c ON qa.course_id = c.id
    WHERE qa.user_id = ?
    ORDER BY qa.attempt_date DESC
";

$stmt = $conn->prepare($sql);

// If 'course_id' doesn't exist, fallback to simple query (no JOIN)
if (!$stmt) {
    $sql = "
        SELECT qa.*, 'General Quiz' AS course_name
        FROM quiz_attempts qa
        WHERE qa.user_id = ?
        ORDER BY qa.attempt_date DESC
    ";
    $stmt = $conn->prepare($sql);
}

if (!$stmt) {
    die("Database prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

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
  <title>Quiz History</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0; padding: 0; box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }
    body {
      background: linear-gradient(135deg, #1e1e2f, #2b5876);
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
      font-size: 1.8rem;
      font-weight: 600;
      background: rgba(0,0,0,0.7);
      box-shadow: 0 4px 15px rgba(0,0,0,0.4);
      color: #00d4ff;
    }
    .container {
      margin-top: 40px;
      width: 95%;
      max-width: 900px;
      background: rgba(255,255,255,0.1);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 30px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.5);
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
      color: #fff;
    }
    th, td {
      padding: 12px;
      text-align: center;
      border-bottom: 1px solid rgba(255,255,255,0.2);
    }
    th {
      background: rgba(0,0,0,0.5);
      font-size: 1rem;
      color: #00d4ff;
    }
    tr:hover {
      background: rgba(255,255,255,0.1);
      transition: 0.3s;
    }
    .score-pass { color: #00e676; font-weight: bold; }
    .score-fail { color: #ff5252; font-weight: bold; }
    .btn-back {
      display: inline-block;
      margin-top: 25px;
      padding: 10px 20px;
      border-radius: 8px;
      text-decoration: none;
      color: #fff;
      background: #00b894;
      transition: 0.3s;
    }
    .btn-back:hover { background: #019875; transform: translateY(-2px); }
    canvas {
      margin-top: 25px;
      background: #fff;
      border-radius: 12px;
      padding: 15px;
    }
    footer {
      margin-top: 40px;
      text-align: center;
      font-size: 0.9rem;
      color: #ccc;
      padding: 15px;
      background: rgba(0,0,0,0.5);
      width: 100%;
    }
    @media(max-width: 600px) {
      th, td { font-size: 0.85rem; padding: 8px; }
    }
  </style>
</head>
<body>
  <header>Quiz History</header>

  <div class="container">
    <?php if (count($attempts) > 0): ?>
      <table>
        <tr>
          <th>Course</th>
          <th>Score</th>
          <th>Total</th>
          <th>Percentage</th>
          <th>Date</th>
        </tr>
        <?php foreach ($attempts as $a): 
          $percentage = $a['total'] > 0 ? round(($a['score'] / $a['total']) * 100, 2) : 0;
        ?>
          <tr>
            <td><?= htmlspecialchars($a['course_name']) ?></td>
            <td class="<?= $percentage >= 40 ? 'score-pass' : 'score-fail' ?>">
              <?= $a['score'] ?>
            </td>
            <td><?= $a['total'] ?></td>
            <td><?= $percentage ?>%</td>
            <td><?= date("d M Y, h:i A", strtotime($a['attempt_date'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>

      <canvas id="quizChart" width="400" height="200"></canvas>
      <div style="text-align:center;">
        <a href="index.php" class="btn-back">⬅ Back to Dashboard</a>
      </div>

    <?php else: ?>
      <p style="text-align:center;">No quiz attempts yet. Take your first quiz today!</p>
      <div style="text-align:center;">
        <a href="index.php" class="btn-back">⬅ Back to Dashboard</a>
      </div>
    <?php endif; ?>
  </div>

  <footer>© 2025 E-Learning Platform | Designed by Chethan SV</footer>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    <?php if (count($attempts) > 0): ?>
    const ctx = document.getElementById('quizChart').getContext('2d');
    const quizChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: <?= json_encode(array_column($attempts, 'course_name')) ?>,
        datasets: [{
          label: 'Quiz Scores',
          data: <?= json_encode(array_column($attempts, 'score')) ?>,
          backgroundColor: 'rgba(0, 212, 255, 0.8)',
          borderColor: '#00d4ff',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { labels: { color: "#000" } }
        },
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
