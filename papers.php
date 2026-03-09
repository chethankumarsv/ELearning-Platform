<?php
// /elearningplatform/papers.php
session_start();
require_once __DIR__ . '/includes/config.php';

// =====================
// Fetch Question Papers
// =====================
$papers = $conn->query("
  SELECT id, subject, year, file_url 
  FROM question_papers 
  ORDER BY year DESC, subject ASC
") or die("SQL Error (papers): " . $conn->error);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Previous Year Papers - E-Learning Platform</title>
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
    a{text-decoration:none}

    header{background:linear-gradient(90deg,var(--brand),var(--brand2));padding:14px 20px;color:#fff;display:flex;align-items:center;justify-content:space-between}
    .brand{font-weight:700;font-size:20px}
    nav ul{list-style:none;margin:0;padding:0;display:flex;gap:18px}
    nav a{color:#fff;font-weight:600;padding:6px 12px;border-radius:6px}
    nav a:hover{background:rgba(255,255,255,.2)}

    main{max-width:1100px;margin:30px auto;padding:20px;background:var(--white);border-radius:14px;box-shadow:0 8px 24px rgba(0,0,0,.1)}
    h1{text-align:center;color:var(--accent);margin-bottom:20px}
    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px}
    .card{background:#fff;border-radius:12px;padding:18px;box-shadow:0 6px 16px rgba(0,0,0,.08);transition:.25s}
    .card:hover{transform:translateY(-4px)}
    .card h3{margin:0 0 8px;font-size:18px;color:var(--brand)}
    .card p{margin:0 0 12px;font-size:14px;color:var(--muted)}
    .btn{display:inline-block;background:var(--accent);color:#fff;padding:8px 16px;border-radius:6px;font-size:14px;font-weight:600}
    .btn:hover{background:#f87171}

    .empty{text-align:center;color:var(--muted);margin-top:20px;font-style:italic}

    footer{margin-top:40px;background:#111827;color:#9ca3af;padding:20px;text-align:center;font-size:13px;border-radius:0 0 14px 14px}
  </style>
</head>
<body>

<header>
  <div class="brand">📘 Previous Papers</div>
  <nav>
    <ul>
      <li><a href="index.php">Home</a></li>
      <li><a href="materials.php">Materials</a></li>
      <li><a href="dashboard.php">Dashboard</a></li>
    </ul>
  </nav>
</header>

<main>
  <h1>Previous Year Question Papers</h1>
  <div class="grid">
    <?php if($papers && $papers->num_rows > 0): 
      while($p = $papers->fetch_assoc()): ?>
        <div class="card">
          <h3><?= htmlspecialchars($p['subject']) ?> (<?= htmlspecialchars($p['year']) ?>)</h3>
          <p>Download past paper for <?= htmlspecialchars($p['subject']) ?>.</p>
          <a class="btn" href="<?= htmlspecialchars($p['file_url']) ?>" target="_blank">📥 Download</a>
        </div>
    <?php endwhile; else: ?>
      <p class="empty">No previous year papers uploaded yet.</p>
    <?php endif; ?>
  </div>
</main>

<footer>
  © <?= date("Y") ?> E-Learning Platform | Previous Year Papers
</footer>

</body>
</html>
