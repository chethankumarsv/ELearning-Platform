<?php
session_start();
require_once __DIR__ . '/config.php';

// Fetch category
$category = isset($_GET['category']) ? $_GET['category'] : 'Quantitative';

// Fetch random questions from DB
$query = $conn->prepare("SELECT * FROM aptitude_questions WHERE category=? ORDER BY RAND() LIMIT 10");
$query->bind_param("s", $category);
$query->execute();
$result = $query->get_result();
$questions = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Aptitude Quiz - E-Learning Platform</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
  background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
  color: #fff;
  font-family: 'Poppins', sans-serif;
  padding: 20px;
}
.container {
  background: rgba(255,255,255,0.1);
  border-radius: 20px;
  padding: 30px;
  box-shadow: 0 0 25px rgba(0,0,0,0.3);
  backdrop-filter: blur(10px);
}
.btn-custom {
  background-color: #00bcd4;
  color: white;
  border: none;
}
.btn-custom:hover {
  background-color: #0097a7;
}
.timer {
  font-size: 18px;
  font-weight: bold;
  color: #ffeb3b;
}
.explanation {
  background: rgba(255,255,255,0.1);
  border-radius: 10px;
  padding: 10px;
  margin-top: 10px;
}
</style>
</head>
<body>

<div class="container mt-4">
  <h2 class="text-center mb-3">Aptitude Quiz for Engineering Students</h2>
  
  <div class="text-center mb-3">
    <form method="get" action="">
      <select name="category" class="form-select w-auto d-inline">
        <option value="Quantitative" <?= $category=='Quantitative'?'selected':'' ?>>Quantitative</option>
        <option value="Logical" <?= $category=='Logical'?'selected':'' ?>>Logical</option>
        <option value="Verbal" <?= $category=='Verbal'?'selected':'' ?>>Verbal</option>
      </select>
      <button type="submit" class="btn btn-custom ms-2">Start Quiz</button>
    </form>
  </div>

  <div class="timer text-center mb-3">⏳ Time Left: <span id="time">05:00</span></div>

  <form id="quizForm">
    <?php foreach ($questions as $index => $q): ?>
      <div class="mb-4">
        <h5>Q<?= $index + 1 ?>. <?= htmlspecialchars($q['question']) ?></h5>
        <?php foreach (['A','B','C','D'] as $opt): ?>
          <div>
            <label>
              <input type="radio" name="q<?= $q['id'] ?>" value="<?= $opt ?>"> 
              <?= htmlspecialchars($q['option_'.strtolower($opt)]) ?>
            </label>
          </div>
        <?php endforeach; ?>
        <div class="explanation d-none" id="exp<?= $q['id'] ?>">
          <strong>Explanation:</strong> <?= htmlspecialchars($q['explanation']) ?>
        </div>
        <hr>
      </div>
    <?php endforeach; ?>
    <div class="text-center">
      <button type="button" class="btn btn-success" onclick="submitQuiz()">Submit Quiz</button>
    </div>
  </form>

  <div id="result" class="text-center mt-4 d-none"></div>
</div>

<script>
let time = 300; // 5 minutes
const timerElement = document.getElementById('time');

function updateTimer() {
  const minutes = Math.floor(time / 60);
  const seconds = time % 60;
  timerElement.textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
  if (time <= 0) {
    submitQuiz();
  } else {
    time--;
    setTimeout(updateTimer, 1000);
  }
}
updateTimer();

function submitQuiz() {
  let score = 0, total = <?= count($questions) ?>;
  const explanations = document.querySelectorAll('.explanation');

  <?php foreach ($questions as $q): ?>
    const correct = '<?= $q['correct_option'] ?>';
    const chosen = document.querySelector('input[name="q<?= $q['id'] ?>"]:checked');
    if (chosen && chosen.value === correct) score++;
    document.getElementById('exp<?= $q['id'] ?>').classList.remove('d-none');
  <?php endforeach; ?>

  document.getElementById('result').innerHTML = `
    <h4>Your Score: ${score} / ${total}</h4>
    <p>${score >= total*0.7 ? '🎉 Great job!' : '📘 Keep practicing!'}</p>
    <a href="?category=<?= $category ?>" class="btn btn-custom mt-2">Retry Quiz</a>
  `;
  document.getElementById('result').classList.remove('d-none');
}
</script>

</body>
</html>
