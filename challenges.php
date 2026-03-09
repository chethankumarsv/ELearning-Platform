<?php
// /elearningplatform/challenges.php
session_start();
require_once __DIR__ . '/includes/config.php';

// Redirect if not logged in
if (empty($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

$userId = $_SESSION['user_id'];

// =====================
// Weekly Challenges
// =====================
$challenges = [
    "💻 Coding: Write a program to check if a number is prime without using loops.",
    "🌐 Web Dev: Build a simple landing page with HTML & CSS for a college fest.",
    "📊 Data Science: Create a Python script to plot student scores using Matplotlib.",
    "🤖 AI/ML: Train a model to predict house prices using any dataset.",
    "📱 Mobile: Design a simple calculator app UI in Figma or Android Studio.",
    "🕹️ Game Dev: Create a 2D bouncing ball game using Unity or Pygame.",
    "🔐 Security: Research about hashing algorithms and implement SHA-256 in code.",
    "🛰️ IoT: Build a prototype to monitor room temperature using Arduino sensors.",
    "☁️ Cloud: Deploy a static website on AWS S3 or GitHub Pages.",
    "📚 Research: Summarize a recent IEEE paper in 5 bullet points."
];

$currentWeek = date("W");
$totalWeeks = 10; // number of challenges available
$bonusPoints = 50;

// Handle completion
if (isset($_POST['complete']) && isset($_POST['week'])) {
    $week = intval($_POST['week']);

    // Check if already completed
    $check = $conn->prepare("SELECT 1 FROM weekly_challenges WHERE user_id=? AND week_number=?");
    $check->bind_param("ii", $userId, $week);
    $check->execute();
    $already = $check->get_result()->num_rows > 0;

    if (!$already) {
        $stmt = $conn->prepare("INSERT INTO weekly_challenges (user_id, week_number) VALUES (?, ?)");
        $stmt->bind_param("ii", $userId, $week);
        $stmt->execute();

        $conn->query("UPDATE users SET points = points + $bonusPoints WHERE id = $userId");

        $msg = "✅ Challenge for Week $week marked as completed! +$bonusPoints points earned 🎉";
    } else {
        $msg = "🎯 You already completed Week $week challenge!";
    }
}

// Fetch completed challenges
$completed = [];
$res = $conn->query("SELECT week_number FROM weekly_challenges WHERE user_id=$userId");
while ($row = $res->fetch_assoc()) {
    $completed[] = $row['week_number'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Weekly Challenges - E-Learning</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {font-family:Poppins, sans-serif; background:#f0f2f5; margin:0; padding:20px;}
    h1 {text-align:center; color:#2563eb;}
    .container {max-width:900px; margin:auto;}
    .card {background:#fff; padding:20px; margin:15px 0; border-radius:12px;
           box-shadow:0 6px 18px rgba(0,0,0,.1); position:relative; overflow:hidden;}
    .card h3 {margin:0 0 10px; color:#ff7e5f;}
    .card p {margin:0 0 10px; color:#333;}
    .btn {padding:8px 14px; border:none; border-radius:6px; background:#2563eb;
          color:#fff; font-weight:600; cursor:pointer; transition:.3s;}
    .btn:hover {background:#1d4ed8;}
    .completed {background:#22c55e; cursor:default;}
    .msg {text-align:center; margin-bottom:20px; font-weight:600; color:#16a34a;}
  </style>
</head>
<body>
  <div class="container">
    <h1>🔥 Weekly Challenges</h1>
    <?php if(!empty($msg)): ?><p class="msg"><?= $msg ?></p><?php endif; ?>

    <?php for($w=1; $w<=$totalWeeks; $w++): 
      $challenge = $challenges[($w-1) % count($challenges)];
      $isCompleted = in_array($w, $completed);
    ?>
      <div class="card">
        <h3>Week <?= $w ?> <?= $w==$currentWeek ? "(Current)" : "" ?></h3>
        <p><?= $challenge ?></p>
        <?php if($isCompleted): ?>
          <button class="btn completed">✔ Completed</button>
        <?php elseif($w <= $currentWeek): ?>
          <form method="post">
            <input type="hidden" name="week" value="<?= $w ?>">
            <button type="submit" name="complete" class="btn">Mark as Completed</button>
          </form>
        <?php else: ?>
          <button class="btn" disabled>🔒 Locked</button>
        <?php endif; ?>
      </div>
    <?php endfor; ?>
  </div>
</body>
</html>
