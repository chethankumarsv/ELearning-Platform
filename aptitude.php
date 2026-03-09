<?php
session_start();
include_once('./includes/config.php');

// Example user session
$_SESSION['username'] = isset($_SESSION['username']) ? $_SESSION['username'] : 'Engineering Student';

// Progress simulation (can later be dynamic from DB)
$progress = [
  'quantitative' => 60,
  'logical' => 45,
  'verbal' => 50,
  'technical' => 35,
  'data' => 25
];

// Tip of the day
$tips = [
  "Revise basic formulas daily to boost your problem-solving speed.",
  "Try solving 10 mixed aptitude problems every day.",
  "Focus on accuracy — it’s more important than attempting all questions.",
  "For technical aptitude, review fundamental engineering concepts regularly.",
  "Time management is key — practice under a timer."
];
$dailyTip = $tips[array_rand($tips)];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Aptitude Learning Hub | Engineering eLearning Platform</title>
  <link rel="stylesheet" href="./assets/css/style.css">
  <style>
    body {
      margin: 0;
      font-family: "Poppins", sans-serif;
      background: #0b0d17;
      color: #fff;
    }

    header {
      background: linear-gradient(90deg, #007bff, #00d1ff);
      padding: 20px 0;
      text-align: center;
      font-size: 1.8rem;
      font-weight: 600;
      color: #fff;
    }

    .dashboard {
      width: 90%;
      max-width: 1200px;
      margin: 40px auto;
      background: rgba(255,255,255,0.05);
      border-radius: 15px;
      padding: 20px 30px;
      box-shadow: 0 0 15px rgba(0,209,255,0.2);
    }

    .welcome {
      font-size: 1.2rem;
      margin-bottom: 15px;
    }

    .tip-box {
      background: rgba(0,209,255,0.1);
      border-left: 4px solid #00d1ff;
      padding: 15px 20px;
      margin-bottom: 25px;
      border-radius: 8px;
      font-style: italic;
      color: #bdeeff;
    }

    h2.section-title {
      color: #00d1ff;
      text-transform: uppercase;
      font-size: 1.6rem;
      margin-top: 30px;
      text-align: center;
    }

    .topic-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
      margin-top: 25px;
    }

    .topic-card {
      background: rgba(255,255,255,0.08);
      border-radius: 15px;
      padding: 20px;
      text-align: center;
      transition: 0.3s;
      position: relative;
    }

    .topic-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 0 15px rgba(0,209,255,0.4);
    }

    .topic-card h3 {
      color: #00d1ff;
      margin-bottom: 8px;
    }

    .progress-bar {
      width: 100%;
      background: rgba(255,255,255,0.1);
      border-radius: 50px;
      height: 10px;
      margin: 10px 0;
      overflow: hidden;
    }

    .progress {
      height: 10px;
      background: #00d1ff;
      border-radius: 50px;
      transition: width 0.5s;
    }

    .quiz-btn {
      background: #00d1ff;
      border: none;
      color: #000;
      padding: 10px 25px;
      border-radius: 25px;
      font-weight: 600;
      margin-top: 10px;
      cursor: pointer;
      transition: 0.3s;
    }

    .quiz-btn:hover {
      background: #00a8cc;
    }

    .question-bank {
      margin-top: 40px;
      background: rgba(255,255,255,0.08);
      padding: 25px;
      border-radius: 12px;
    }

    .question {
      background: rgba(0,0,0,0.2);
      padding: 15px;
      margin: 10px 0;
      border-radius: 10px;
      text-align: left;
      line-height: 1.6;
    }

    footer {
      text-align: center;
      padding: 20px;
      background: #101820;
      color: #aaa;
      margin-top: 40px;
    }

    @media (max-width: 600px) {
      header { font-size: 1.4rem; }
    }
  </style>
</head>
<body>

  <header>
    Engineering Aptitude Learning Hub
  </header>

  <div class="dashboard">
    <div class="welcome">👋 Welcome, <b><?php echo $_SESSION['username']; ?></b>!</div>
    <div class="tip-box">💡 Tip of the Day: <?php echo $dailyTip; ?></div>

    <h2 class="section-title">Your Progress</h2>
    <div class="topic-grid">
      <?php
      $topics = [
        "Quantitative Aptitude" => "quantitative",
        "Logical Reasoning" => "logical",
        "Verbal Ability" => "verbal",
        "Technical Aptitude" => "technical",
        "Data Interpretation" => "data"
      ];

      foreach ($topics as $title => $key) {
        $percent = isset($progress[$key]) ? $progress[$key] : 0;
        echo "
        <div class='topic-card'>
          <h3>$title</h3>
          <p>Progress: $percent%</p>
          <div class='progress-bar'>
            <div class='progress' style='width: {$percent}%;'></div>
          </div>
          <button class='quiz-btn' onclick=\"location.href='aptitude_quiz.php?topic=$key'\">Start Practice</button>
        </div>
        ";
      }
      ?>
    </div>

    <div class="question-bank">
      <h2 class="section-title">Engineering-Oriented Practice Questions</h2>

      <div class="question">🔹 <b>Quantitative:</b> A pump can fill a tank in 3 hours and a leak can empty it in 5 hours. If both are open, how long will it take to fill the tank?</div>
      <div class="question">🔹 <b>Logical:</b> If every engineer is creative and some creative people are not engineers, which of the following is true?</div>
      <div class="question">🔹 <b>Verbal:</b> Choose the correct sentence: <i>“Neither the teacher nor the students ___ ready.”</i></div>
      <div class="question">🔹 <b>Technical (Mechanical):</b> What is the efficiency of a Carnot engine working between 500 K and 300 K?</div>
      <div class="question">🔹 <b>Technical (Electrical):</b> What is the phase difference between voltage and current in a purely inductive circuit?</div>
      <div class="question">🔹 <b>Technical (CS):</b> What is the time complexity of binary search?</div>
      <div class="question">🔹 <b>Data Interpretation:</b> In a bar chart showing department-wise placement data, if CSE has 80%, ECE 70%, ME 60%, what is the average placement percentage?</div>

      <button class="quiz-btn" onclick="location.href='aptitude_quiz.php'">Take Complete Test</button>
    </div>
  </div>

  <footer>
    © <?php echo date("Y"); ?> eLearning Platform | Empowering Engineers with Aptitude & Intelligence
  </footer>

</body>
</html>
