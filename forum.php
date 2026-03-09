<?php
// /elearningplatform/forum.php
session_start();
require_once __DIR__ . '/includes/config.php';

// =====================
// Insert New Question
// =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['user_id'])) {
    $question = trim($_POST['question']);
    if (!empty($question)) {
        $stmt = $conn->prepare("INSERT INTO forum_questions (user_id, question) VALUES (?, ?)");
        $stmt->bind_param("is", $_SESSION['user_id'], $question);
        $stmt->execute();
        $stmt->close();
        header("Location: forum.php?success=1");
        exit;
    }
}

// =====================
// Fetch Recent Questions
// =====================
$questions = $conn->query("
  SELECT fq.id, fq.question, fq.created_at, u.name 
  FROM forum_questions fq
  LEFT JOIN users u ON fq.user_id = u.id
  ORDER BY fq.created_at DESC
  LIMIT 15
") or die("SQL Error (forum): " . $conn->error);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Forum - E-Learning Platform</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --brand:#2563eb; --brand2:#60a5fa; --accent:#ff7e5f;
      --text:#222; --muted:#555; --bg:#f0f2f5; --white:#fff;
    }
    [data-theme="dark"] {
      --bg:#1f2937; --text:#f9fafb; --muted:#d1d5db; --white:#111827;
    }
    body {
      margin:0;
      font-family:Poppins,system-ui,-apple-system,Segoe UI,Roboto;
      background:var(--bg);
      color:var(--text);
      transition:background .3s,color .3s;
    }
    header{background:linear-gradient(90deg,var(--brand),var(--brand2));padding:14px 20px;color:#fff;display:flex;align-items:center;justify-content:space-between}
    .brand{font-weight:700;font-size:20px}
    nav ul{list-style:none;margin:0;padding:0;display:flex;gap:18px}
    nav a{color:#fff;font-weight:600;padding:6px 12px;border-radius:6px}
    nav a:hover{background:rgba(255,255,255,.2)}

    main{max-width:1000px;margin:30px auto;padding:20px;background:var(--white);border-radius:14px;box-shadow:0 8px 24px rgba(0,0,0,.1)}
    h1{text-align:center;color:var(--accent);margin-bottom:20px}

    .ask-box{margin-bottom:30px}
    textarea{width:100%;padding:12px;border-radius:8px;border:1px solid #ccc;resize:vertical;font-size:14px}
    .btn{background:var(--accent);color:#fff;padding:10px 20px;border:none;border-radius:6px;font-weight:600;cursor:pointer;margin-top:10px}
    .btn:hover{background:#f87171}
    .msg{margin:10px 0;color:green;font-size:14px;font-weight:600}

    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px}
    .card{background:#fff;border-radius:12px;padding:18px;box-shadow:0 6px 16px rgba(0,0,0,.08);transition:.25s}
    .card:hover{transform:translateY(-3px)}
    .card h3{margin:0 0 8px;font-size:16px;color:var(--brand)}
    .card p{margin:0 0 6px;font-size:14px;color:var(--muted)}
    .meta{font-size:12px;color:#6b7280}

    .empty{text-align:center;color:var(--muted);margin-top:20px;font-style:italic}

    footer{margin-top:40px;background:#111827;color:#9ca3af;padding:20px;text-align:center;font-size:13px;border-radius:0 0 14px 14px}
  </style>
</head>
<body>

<header>
  <div class="brand">👨‍🏫 Student Forum</div>
  <nav>
    <ul>
      <li><a href="index.php">Home</a></li>
      <li><a href="projects.php">Projects</a></li>
      <li><a href="research.php">Research</a></li>
    </ul>
  </nav>
</header>

<main>
  <h1>Ask & Discuss Questions</h1>

  <!-- Ask a Question -->
  <?php if(!empty($_SESSION['user_id'])): ?>
    <div class="ask-box">
      <?php if(isset($_GET['success'])): ?>
        <div class="msg">✅ Your question has been posted!</div>
      <?php endif; ?>
      <form method="post">
        <textarea name="question" rows="4" placeholder="Type your question here..." required></textarea>
        <button type="submit" class="btn">Ask Question</button>
      </form>
    </div>
  <?php else: ?>
    <p style="text-align:center;color:var(--muted)">🔒 Please <a href="auth.php">login</a> to ask a question.</p>
  <?php endif; ?>

  <!-- Recent Questions -->
  <div class="grid">
    <?php if($questions && $questions->num_rows > 0): 
      while($q = $questions->fetch_assoc()): ?>
        <div class="card">
          <h3><?= htmlspecialchars($q['question']) ?></h3>
          <p class="meta">Asked by <?= htmlspecialchars($q['name'] ?? 'Anonymous') ?> on <?= date("d M Y", strtotime($q['created_at'])) ?></p>
        </div>
    <?php endwhile; else: ?>
      <p class="empty">No questions have been asked yet.</p>
    <?php endif; ?>
  </div>
</main>

<footer>
  © <?= date("Y") ?> E-Learning Platform | Student Forum
</footer>

</body>
</html>
