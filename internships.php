<?php
// /elearningplatform/internships.php
session_start();

// =====================
// Define Domain
// =====================
define('DOMAIN', 'http://localhost/elearningplatform');

// =====================
// Config File
// =====================
require_once __DIR__ . '/includes/config.php';

// =====================
// Auto-Check & Add Missing Column
// =====================
$colCheck = $conn->query("SHOW COLUMNS FROM `internships` LIKE 'category'");
if ($colCheck && $colCheck->num_rows === 0) {
    $conn->query("
        ALTER TABLE `internships` 
        ADD COLUMN `category` ENUM('internship','job') NOT NULL DEFAULT 'internship'
    ");
}

// =====================
// Search & Filter Logic
// =====================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'all';

$query = "SELECT id, title, company, apply_link, deadline, category FROM internships WHERE 1";

if ($type !== 'all') {
    $query .= " AND category='" . $conn->real_escape_string($type) . "'";
}
if ($search !== '') {
    $searchEsc = $conn->real_escape_string($search);
    $query .= " AND (title LIKE '%$searchEsc%' OR company LIKE '%$searchEsc%')";
}

$query .= " ORDER BY deadline ASC LIMIT 20";
$internships = $conn->query($query) or die("SQL Error: " . $conn->error);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Internships & Jobs - E-Learning Platform</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --brand:#2563eb; --brand2:#60a5fa; --accent:#ff7e5f;
      --text:#222; --muted:#555; --bg:#f3f4f6; --white:#fff;
    }
    [data-theme="dark"] {
      --bg:#111827; --text:#f9fafb; --muted:#d1d5db; --white:#1f2937;
    }
    body {
      margin:0;
      font-family:'Poppins',sans-serif;
      background:var(--bg);
      color:var(--text);
      transition:all .3s ease;
    }
    header {
      background:linear-gradient(90deg,var(--brand),var(--brand2));
      padding:14px 24px;
      color:#fff;
      display:flex;
      align-items:center;
      justify-content:space-between;
    }
    .brand {font-weight:700;font-size:20px}
    nav ul {list-style:none;display:flex;gap:18px;margin:0;padding:0}
    nav a {color:#fff;text-decoration:none;font-weight:600}
    nav a:hover {text-decoration:underline}

    main {
      max-width:1150px;
      margin:30px auto;
      background:var(--white);
      padding:24px;
      border-radius:16px;
      box-shadow:0 8px 24px rgba(0,0,0,.1);
    }
    h1 {text-align:center;color:var(--accent);margin-bottom:20px}

    .filters {
      display:flex;
      justify-content:space-between;
      flex-wrap:wrap;
      margin-bottom:20px;
      gap:10px;
    }
    .filters input, .filters select {
      padding:10px 12px;
      border:1px solid #ccc;
      border-radius:8px;
      font-size:14px;
      min-width:200px;
    }

    .grid {
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
      gap:20px;
    }

    .card {
      background:#fff;
      border-radius:14px;
      padding:18px;
      box-shadow:0 6px 18px rgba(0,0,0,.08);
      transition:.3s;
      display:flex;
      flex-direction:column;
      justify-content:space-between;
      position:relative;
    }
    .card:hover {transform:translateY(-5px)}
    .badge {
      position:absolute;
      top:15px;
      right:15px;
      background:var(--brand);
      color:#fff;
      font-size:12px;
      padding:4px 10px;
      border-radius:20px;
      font-weight:600;
    }
    .card h3 {margin:0 0 6px;color:var(--brand);font-size:18px}
    .card p {margin:4px 0;color:var(--muted);font-size:14px}
    .deadline {font-size:13px;color:#dc2626;font-weight:600;margin-top:10px}
    .btn {
      display:inline-block;
      margin-top:12px;
      background:var(--accent);
      color:#fff;
      padding:8px 14px;
      border-radius:8px;
      font-weight:600;
      font-size:14px;
      text-align:center;
      text-decoration:none;
    }
    .btn:hover {background:#f87171}
    .empty {text-align:center;margin-top:30px;color:var(--muted);font-style:italic}

    footer {
      margin-top:40px;
      background:#111827;
      color:#9ca3af;
      padding:20px;
      text-align:center;
      border-radius:0 0 16px 16px;
      font-size:13px;
    }
  </style>
</head>
<body>

<header>
  <div class="brand">🚀 Internships & Jobs</div>
  <nav>
    <ul>
      <li><a href="<?= DOMAIN ?>/index.php">Home</a></li>
      <li><a href="<?= DOMAIN ?>/coding.php">Coding</a></li>
      <li><a href="<?= DOMAIN ?>/projects.php">Projects</a></li>
      <li><a href="<?= DOMAIN ?>/aptitude_quiz.php">Aptitude</a></li>
    </ul>
  </nav>
</header>

<main>
  <h1>Explore Internship & Job Opportunities</h1>

  <!-- Search & Filter -->
  <form class="filters" method="GET">
    <input type="text" name="search" placeholder="Search by title or company..." value="<?= htmlspecialchars($search) ?>">
    <select name="type">
      <option value="all" <?= $type=='all'?'selected':'' ?>>All</option>
      <option value="internship" <?= $type=='internship'?'selected':'' ?>>Internships</option>
      <option value="job" <?= $type=='job'?'selected':'' ?>>Jobs</option>
    </select>
    <button class="btn" type="submit">🔍 Search</button>
  </form>

  <div class="grid">
    <?php if ($internships && $internships->num_rows > 0): ?>
      <?php while($i = $internships->fetch_assoc()):
        $daysLeft = ceil((strtotime($i['deadline']) - time()) / 86400);
        $badge = $daysLeft > 0 ? "$daysLeft days left" : "Closed";
      ?>
        <div class="card">
          <span class="badge"><?= $badge ?></span>
          <h3><?= htmlspecialchars($i['title']) ?></h3>
          <p><strong>Company:</strong> <?= htmlspecialchars($i['company']) ?></p>
          <p><strong>Type:</strong> <?= ucfirst($i['category'] ?? 'Internship') ?></p>
          <p class="deadline">Deadline: <?= date("d M Y", strtotime($i['deadline'])) ?></p>
          <a class="btn" href="<?= htmlspecialchars($i['apply_link'] ?? (DOMAIN . '/apply.php?id=' . $i['id'])) ?>" target="_blank">Apply Now</a>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p class="empty">No opportunities found at the moment. Try adjusting filters.</p>
    <?php endif; ?>
  </div>
</main>

<footer>
  © <?= date("Y") ?> E-Learning Platform | Internships & Jobs Section
</footer>

</body>
</html>
