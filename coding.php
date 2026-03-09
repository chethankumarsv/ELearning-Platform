<?php
// /elearningplatform/coding.php
session_start();
require_once __DIR__ . '/includes/config.php';

// =====================
// Fetch Coding Problems
// =====================
$problems = $conn->query("
  SELECT id, title, description, difficulty, created_at
  FROM coding_problems
  ORDER BY created_at DESC
  LIMIT 10
") or die("SQL Error (coding): " . $conn->error);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Coding Challenges - E-Learning Platform</title>
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

    main{max-width:1100px;margin:30px auto;padding:20px;background:var(--white);border-radius:14px;box-shadow:0 8px 24px rgba(0,0,0,.1)}
    h1{text-align:center;color:var(--accent);margin-bottom:20px}

    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px}
    .card{background:#fff;border-radius:12px;padding:18px;box-shadow:0 6px 16px rgba(0,0,0,.08);transition:.25s;display:flex;flex-direction:column;justify-content:space-between}
    .card:hover{transform:translateY(-4px)}
    .card h3{margin:0 0 8px;font-size:18px;color:var(--brand)}
    .card p{margin:0 0 12px;font-size:14px;color:var(--muted)}
    .difficulty{display:inline-block;padding:4px 10px;border-radius:12px;font-size:12px;font-weight:600;color:#fff;margin-bottom:10px}
    .easy{background:#16a34a}
    .medium{background:#f59e0b}
    .hard{background:#dc2626}
    .btn{display:inline-block;background:var(--accent);color:#fff;padding:8px 16px;border-radius:6px;font-size:14px;font-weight:600;cursor:pointer;text-align:center}
    .btn:hover{background:#f87171}

    .empty{text-align:center;color:var(--muted);margin-top:20px;font-style:italic}

    footer{margin-top:40px;background:#111827;color:#9ca3af;padding:20px;text-align:center;font-size:13px;border-radius:0 0 14px 14px}
  </style>
</head>
<body>

<header>
  <div class="brand">💻 Coding Challenges</div>
  <nav>
    <ul>
      <li><a href="index.php">Home</a></li>
      <li><a href="projects.php">Projects</a></li>
      <li><a href="tools.php">Tools</a></li>
    </ul>
  </nav>
</header>

<main>
  <h1>Daily Coding Challenges</h1>
  <div class="grid">
    <?php if($problems && $problems->num_rows > 0): 
      while($p = $problems->fetch_assoc()): 
        $diffClass = strtolower($p['difficulty']); ?>
        <div class="card">
          <span class="difficulty <?= $diffClass ?>"><?= ucfirst($p['difficulty']) ?></span>
          <h3><?= htmlspecialchars($p['title']) ?></h3>
          <p><?= nl2br(htmlspecialchars(mb_strimwidth($p['description'],0,120,'…'))) ?></p>
          <a class="btn" href="problem.php?id=<?= (int)$p['id'] ?>">Solve</a>
        </div>
    <?php endwhile; else: ?>
      <p class="empty">No coding challenges available yet.</p>
    <?php endif; ?>
  </div>
</main>

<footer>
  © <?= date("Y") ?> E-Learning Platform | Coding Challenges
</footer>

</body>
</html>
