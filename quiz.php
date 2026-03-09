<?php
// /elearningplatform/quiz.php
session_start();
require_once __DIR__ . '/includes/config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ===============================
// Handle Submission
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = intval($_POST['subject_id']);
    $answers = $_POST['answers'] ?? [];
    $score = 0;

    foreach ($answers as $qid => $selected) {
        $res = $conn->query("SELECT answer FROM questions WHERE id=$qid");
        if ($res && $row = $res->fetch_assoc()) {
            if ($row['answer'] === $selected) {
                $score++;
            }
        }
    }

    $total = count($answers);

    // Save attempt
    $stmt = $conn->prepare("INSERT INTO quiz_attempts (user_id, subject_id, score, total) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiii", $user_id, $subject_id, $score, $total);
    $stmt->execute();

    $message = "You scored $score out of $total.";
}

// ===============================
// Fetch Questions
// ===============================
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 1;
$sql = "SELECT * FROM questions WHERE subject_id = $subject_id ORDER BY RAND() LIMIT 5";
$result = $conn->query($sql);
$questions = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Daily Quiz</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, sans-serif;
      background: linear-gradient(135deg, #1a73e8, #0f2027);
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
      background: rgba(0,0,0,0.4);
      backdrop-filter: blur(6px);
      font-size: 1.8rem;
      font-weight: bold;
    }
    .quiz-container {
      background: rgba(255,255,255,0.1);
      backdrop-filter: blur(12px);
      border-radius: 16px;
      padding: 25px;
      margin: 20px;
      width: 95%;
      max-width: 700px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.4);
    }
    .question { font-size: 1.2rem; margin-bottom: 15px; }
    .options { display: flex; flex-direction: column; gap: 10px; }
    label {
      padding: 12px;
      border-radius: 10px;
      background: rgba(255,255,255,0.15);
      display: block;
      cursor: pointer;
      transition: 0.3s;
    }
    input[type="radio"] { display: none; }
    input[type="radio"]:checked + label {
      background: rgba(0,230,118,0.3);
      border: 2px solid #00e676;
    }
    button {
      margin-top: 20px;
      padding: 12px;
      width: 100%;
      border: none;
      border-radius: 12px;
      background: linear-gradient(45deg, #00c6ff, #0072ff);
      color: #fff;
      font-size: 1.1rem;
      font-weight: bold;
      cursor: pointer;
      transition: 0.3s;
    }
    button:hover { transform: scale(1.03); }
    .message {
      margin-top: 20px;
      padding: 15px;
      background: rgba(0,0,0,0.5);
      border-radius: 12px;
      text-align: center;
    }
  </style>
</head>
<body>
  <header>Daily Quiz</header>
  <div class="quiz-container">
    <?php if (!empty($message)): ?>
      <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (count($questions) > 0): ?>
      <form method="POST">
        <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
        <?php foreach ($questions as $index => $q): ?>
          <div class="question-block">
            <div class="question">Q<?= $index+1 ?>: <?= htmlspecialchars($q['question']) ?></div>
            <div class="options">
              <?php foreach (['option1','option2','option3','option4'] as $opt): ?>
                <input type="radio" id="q<?= $q['id'].'_'.$opt ?>" name="answers[<?= $q['id'] ?>]" value="<?= htmlspecialchars($q[$opt]) ?>">
                <label for="q<?= $q['id'].'_'.$opt ?>"><?= htmlspecialchars($q[$opt]) ?></label>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
        <button type="submit">Submit Quiz</button>
      </form>
    <?php else: ?>
      <p>No quiz available for this subject today.</p>
    <?php endif; ?>
  </div>
</body>
</html>
