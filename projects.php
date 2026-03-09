<?php
// /elearningplatform/projects.php
// Robust projects listing + Upcoming Project Subjects suggestions
// Works when some columns are missing (created_at, thumbnail, demo_link, repo_link, etc.)

ini_set('display_errors', 0); // set to 1 while debugging
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

// -------------------- helpers --------------------
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function is_valid_url($u){
    if (empty($u)) return false;
    $u = filter_var($u, FILTER_SANITIZE_URL);
    return (bool)filter_var($u, FILTER_VALIDATE_URL) && in_array(parse_url($u, PHP_URL_SCHEME), ['http','https']);
}
function guess_stack_from_cat($cat){
    $c = strtolower((string)$cat);
    if (strpos($c,'ml') !== false || strpos($c,'ai') !== false) return 'Python (TensorFlow/PyTorch), Flask API, Jupyter';
    if (strpos($c,'mobile') !== false) return 'Flutter or React Native';
    if (strpos($c,'iot') !== false) return 'ESP32/Arduino, MQTT, Node.js/Flask backend';
    if (strpos($c,'game') !== false) return 'Unity (C#) or Phaser (JS)';
    return 'PHP/MySQL (or Node/Express + MongoDB), HTML/CSS, JavaScript';
}

// -------------------- config & DB --------------------
$configPath = __DIR__ . '/includes/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo "<h2>Missing includes/config.php</h2><p>Create the file and set <code>\$conn = new mysqli(...)</code>.</p>";
    exit;
}
require_once $configPath;
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo "<h2>DB connection error</h2><p>Check includes/config.php — <code>\$conn</code> must be mysqli.</p>";
    exit;
}

// -------------------- detect columns in `projects` table --------------------
$columns = [];
$colRes = $conn->query("SHOW COLUMNS FROM `projects`");
if ($colRes) {
    while ($row = $colRes->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    $colRes->free();
}

// flags for optional columns
$hasCreatedAt = in_array('created_at', $columns, true);
$hasThumbnail = in_array('thumbnail', $columns, true);
$hasRepoLink  = in_array('repo_link', $columns, true);
$hasDemoLink  = in_array('demo_link', $columns, true);
$hasDetails   = in_array('details', $columns, true);
$hasNotes     = in_array('notes', $columns, true);
$hasDifficulty= in_array('difficulty', $columns, true);
$hasEstHours  = in_array('estimated_hours', $columns, true);

// if projects table doesn't exist or not accessible
if (empty($columns)) {
    echo "<h2>DB Error</h2><p>Table <code>projects</code> not found or no permission to access it.</p>";
    exit;
}

// -------------------- Upcoming project subjects (editable) --------------------
// Add or edit topics here — they'll appear for students as upcoming project ideas.
$upcoming_subjects = [
    'Web & Full-Stack' => [
        ['title'=>'Micro LMS (mini learning platform)','difficulty'=>'Medium','hours'=>40,'desc'=>'Students build a small LMS with courses, quizzes, and progress tracking.'],
        ['title'=>'Student Project Hub (catalog & downloads)','difficulty'=>'Medium','hours'=>30,'desc'=>'Catalog of projects with preview, downloads and repo links.'],
        ['title'=>'Job/Internship Portal','difficulty'=>'Medium','hours'=>35,'desc'=>'Companies post openings and students apply with resume upload.']
    ],
    'AI & Data' => [
        ['title'=>'Crop Disease Detection (Image Classification)','difficulty'=>'Medium','hours'=>50,'desc'=>'Upload leaf images and predict disease using a trained model.'],
        ['title'=>'Sentiment Analysis for Feedback','difficulty'=>'Medium','hours'=>30,'desc'=>'Analyze course feedback using NLP and visualize results.'],
        ['title'=>'Handwritten Digit Recognizer (MNIST)','difficulty'=>'Easy','hours'=>12,'desc'=>'Browser canvas input and model inference.']
    ],
    'Mobile & IoT' => [
        ['title'=>'Campus Navigation App (Flutter)','difficulty'=>'Medium','hours'=>45,'desc'=>'Map of campus with offline caching and routing.'],
        ['title'=>'Smart Plant Watering (ESP32)','difficulty'=>'Medium','hours'=>30,'desc'=>'Soil moisture sensor triggers pump; dashboard + alerts.']
    ],
    'Games & Graphics' => [
        ['title'=>'2D Platformer (Unity)','difficulty'=>'Medium','hours'=>40,'desc'=>'Simple platformer with levels and scoring.'],
        ['title'=>'Multiplayer Tic-Tac-Toe (WebSockets)','difficulty'=>'Easy','hours'=>15,'desc'=>'Real-time 2-player game with matchmaking.']
    ],
    'Security & Networking' => [
        ['title'=>'Secure File Share (client-side encryption)','difficulty'=>'Hard','hours'=>60,'desc'=>'Client-side encryption, expiring links and safe server storage.'],
        ['title'=>'Simple Vulnerability Scanner (learning tool)','difficulty'=>'Medium','hours'=>30,'desc'=>'Port scan and banner grab learning tool (ethical use only).']
    ]
];

// -------------------- Handle printable assignment sheet (same as previous behaviour) --------------------
if (isset($_GET['action']) && $_GET['action'] === 'print_sheet' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($id <= 0) { http_response_code(400); exit('Invalid id'); }

    $selectCols = ['id','title','description','category'];
    if ($hasDetails) $selectCols[] = 'details';
    if ($hasRepoLink) $selectCols[] = 'repo_link';
    if ($hasDemoLink) $selectCols[] = 'demo_link';
    if ($hasNotes) $selectCols[] = 'notes';
    if ($hasDifficulty) $selectCols[] = 'difficulty';
    if ($hasEstHours) $selectCols[] = 'estimated_hours';
    if ($hasCreatedAt) $selectCols[] = 'created_at';
    else $selectCols[] = "NOW() AS created_at";

    $sql = "SELECT " . implode(',', $selectCols) . " FROM projects WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $project = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!$project) { http_response_code(404); exit('Project not found'); }

    // Printable assignment HTML
    ?>
    <!doctype html>
    <html lang="en">
    <head>
      <meta charset="utf-8">
      <title>Assignment — <?= e($project['title']) ?></title>
      <meta name="viewport" content="width=device-width,initial-scale=1">
      <style>
        body{font-family:Arial,Helvetica,sans-serif;color:#111;margin:18px;}
        .header{display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #eee;padding-bottom:8px}
        .title{font-size:20px;margin:0}
        .meta{color:#555;font-size:13px}
        .section{margin-top:12px}
        .box{background:#f7f7fb;padding:10px;border-left:5px solid #2bb6c9;border-radius:6px}
        table{width:100%;border-collapse:collapse;margin-top:10px}
        th,td{border:1px solid #ddd;padding:8px;text-align:left}
        th{background:#f2f6f7}
        button{padding:8px 12px;background:#2bb6c9;color:#fff;border:0;border-radius:6px;cursor:pointer}
        @media print{button{display:none}}
      </style>
    </head>
    <body>
      <div class="header">
        <div>
          <h1 class="title"><?= e($project['title']) ?></h1>
          <div class="meta">Category: <?= e($project['category'] ?? 'General') ?> • Posted: <?= e(date('d M Y', strtotime($project['created_at']))) ?></div>
        </div>
        <div><button onclick="window.print()">Print / Save PDF</button></div>
      </div>

      <div class="section"><strong>Objective</strong>
        <div class="box"><?= nl2br(e($project['details'] ?? $project['description'] ?? 'No details provided.')) ?></div>
      </div>

      <div class="section"><strong>Must-have features</strong>
        <div class="box">
          <ul>
            <li>Complete implementation meeting core requirements above.</li>
            <li>README with setup & run instructions.</li>
            <li>Well-structured source code and comments.</li>
            <li>Demo link or demo recording (if applicable).</li>
          </ul>
        </div>
      </div>

      <div class="section"><strong>Suggested tech stack</strong>
        <div class="box"><?= e(guess_stack_from_cat($project['category'] ?? '')) ?></div>
      </div>

      <div class="section"><strong>Deliverables</strong>
        <table><thead><tr><th>Deliverable</th><th>Notes</th></tr></thead>
        <tbody>
          <tr><td>Source code</td><td>ZIP or repo link</td></tr>
          <tr><td>README</td><td>How to install, run, dependencies</td></tr>
          <tr><td>Demo</td><td>Hosted link or short recorded video</td></tr>
          <tr><td>Report</td><td>Design & testing notes (PDF)</td></tr>
        </tbody></table>
      </div>

      <div class="section"><strong>Grading rubric</strong>
        <table><thead><tr><th>Criteria</th><th>Max pts</th></tr></thead><tbody>
          <tr><td>Functionality & Requirements</td><td style="text-align:right">40</td></tr>
          <tr><td>Code Quality & Structure</td><td style="text-align:right">20</td></tr>
          <tr><td>UI / UX</td><td style="text-align:right">10</td></tr>
          <tr><td>Documentation</td><td style="text-align:right">10</td></tr>
          <tr><td>Extra features / Innovation</td><td style="text-align:right">10</td></tr>
          <tr><td>Presentation & Demo</td><td style="text-align:right">10</td></tr>
          <tr><th>Total</th><th style="text-align:right">100</th></tr>
        </tbody></table>
      </div>

      <div class="section" style="margin-top:14px;font-size:13px">
        Repo: <?= $project['repo_link'] ? '<a href="'.e($project['repo_link']).'">'.e($project['repo_link']).'</a>' : 'N/A' ?> —
        Demo: <?= $project['demo_link'] ? '<a href="'.e($project['demo_link']).'">'.e($project['demo_link']).'</a>' : 'N/A' ?>
      </div>

    </body>
    </html>
    <?php
    exit;
}

// -------------------- list page (search + pagination) --------------------
$search = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$categoryFilter = isset($_GET['category']) ? trim((string)$_GET['category']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 9;
$offset = ($page - 1) * $perPage;

// build WHERE clause and params
$where = [];
$params = [];
$types = '';
if ($search !== '') {
    if ($hasDetails) {
        $where[] = "(title LIKE CONCAT('%',?,'%') OR description LIKE CONCAT('%',?,'%') OR details LIKE CONCAT('%',?,'%'))";
        $params[] = $search; $params[] = $search; $params[] = $search;
        $types .= 'sss';
    } else {
        $where[] = "(title LIKE CONCAT('%',?,'%') OR description LIKE CONCAT('%',?,'%'))";
        $params[] = $search; $params[] = $search;
        $types .= 'ss';
    }
}
if ($categoryFilter !== '') {
    $where[] = "category = ?";
    $params[] = $categoryFilter;
    $types .= 's';
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// total count
$count_sql = "SELECT COUNT(*) FROM projects $where_sql";
$stmt = $conn->prepare($count_sql);
if ($stmt === false) { die('DB error: ' . e($conn->error)); }
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->bind_result($total_count);
$stmt->fetch();
$stmt->close();
$total_count = (int)$total_count;
$total_pages = max(1, (int)ceil($total_count / $perPage));

// build select columns dynamically
$selectCols = ['id','title','description','category'];
if ($hasThumbnail) $selectCols[] = 'thumbnail';
if ($hasRepoLink)  $selectCols[] = 'repo_link';
if ($hasDemoLink)  $selectCols[] = 'demo_link';
if ($hasCreatedAt) $selectCols[] = 'created_at';
else $selectCols[] = "NOW() AS created_at";

$sql = "SELECT " . implode(',', $selectCols) . " FROM projects $where_sql ORDER BY " . ($hasCreatedAt ? 'created_at' : 'id') . " DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) { die('DB error: ' . e($conn->error)); }

// bind params + pagination
if ($types === '') {
    $stmt->bind_param('ii', $perPage, $offset);
} else {
    $bind_types = $types . 'ii';
    $bind_params = array_merge($params, [$perPage, $offset]);
    $refs = [];
    $refs[] = & $bind_types;
    foreach ($bind_params as $k => &$v) $refs[] = & $v;
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

$stmt->execute();
$res = $stmt->get_result();
$projects = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// categories for filter
$cats = [];
$resCats = $conn->query("SELECT DISTINCT category FROM projects WHERE category IS NOT NULL AND category <> '' ORDER BY category");
if ($resCats) {
    while ($r = $resCats->fetch_assoc()) $cats[] = $r['category'];
    $resCats->free();
}

// -------------------- Output HTML --------------------
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Project Catalog & Upcoming Subjects</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --bg:#0b1220; --card: rgba(255,255,255,0.03);
      --muted:#9aa7bf; --accent:#06b6d4; --accent2:#7c3aed;
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:Inter,system-ui,Arial,sans-serif;background: linear-gradient(180deg,#071226,#0f1724);color:#edf2f7;min-height:100vh}
    .wrap{max-width:1200px;margin:28px auto;padding:20px}
    header{display:flex;align-items:center;justify-content:space-between;gap:12px}
    h1{margin:0;font-size:1.6rem}
    .controls{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-top:18px}
    .search{display:flex;gap:8px;align-items:center}
    .search input{padding:10px 12px;border-radius:10px;border:0;min-width:260px;background:rgba(255,255,255,0.03);color:var(--muted)}
    .search button{padding:10px 12px;border-radius:10px;border:0;background:var(--accent);color:#012;text-transform:uppercase;font-weight:700;cursor:pointer}
    .filter select{padding:10px 12px;border-radius:10px;border:0;background:rgba(255,255,255,0.03);color:var(--muted)}
    .layout{display:grid;grid-template-columns:1fr 360px;gap:18px;margin-top:20px}
    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px}
    .card{background:var(--card);padding:14px;border-radius:14px;box-shadow:0 8px 20px rgba(2,6,23,0.6);transition:transform .2s ease}
    .card:hover{transform:translateY(-6px);box-shadow:0 18px 30px rgba(2,6,23,0.8)}
    .thumb{width:100%;height:140px;border-radius:8px;object-fit:cover;background:linear-gradient(180deg,rgba(255,255,255,0.02),rgba(0,0,0,0.2));display:block}
    .title{font-weight:600;color:#c9fff6;margin:10px 0 6px}
    .desc{color:var(--muted);font-size:0.95rem;min-height:48px}
    .meta{display:flex;justify-content:space-between;align-items:center;margin-top:12px;gap:8px}
    .meta .cat{font-size:13px;color:var(--muted);padding:6px 10px;border-radius:999px;background:rgba(255,255,255,0.02)}
    .actions{display:flex;gap:8px}
    .btn{padding:8px 12px;border-radius:999px;border:0;text-decoration:none;color:#012;font-weight:700;cursor:pointer}
    .btn.view{background:linear-gradient(90deg,var(--accent),var(--accent2));color:#012}
    .btn.print{background:#334155;color:#fff}
    .btn.link{background:rgba(255,255,255,0.04);color:var(--muted)}
    .side{background:rgba(255,255,255,0.02);padding:14px;border-radius:12px}
    .subject{background:linear-gradient(180deg,rgba(255,255,255,0.02),rgba(0,0,0,0.02));padding:10px;border-radius:8px;margin-bottom:10px}
    .subject h4{margin:0 0 6px}
    .pager{display:flex;justify-content:center;gap:8px;margin:20px 0}
    .pager a{padding:8px 10px;border-radius:8px;background:rgba(255,255,255,0.02);color:var(--muted);text-decoration:none}
    .pager a.active{background:linear-gradient(90deg,var(--accent),var(--accent2));color:#012;font-weight:700}
    footer{margin-top:28px;text-align:center;color:var(--muted);font-size:13px}
    @media (max-width:980px){ .layout{grid-template-columns:1fr} .side{order:2} }
  </style>
</head>
<body>
  <div class="wrap">
    <header>
      <div>
        <h1>Project Catalog</h1>
        <div style="color:var(--muted);font-size:13px;margin-top:6px">Pick a project to generate assignment sheet, view or download</div>
      </div>
      <div style="text-align:right">
        <a href="admin/projects_admin.php" style="color:var(--muted);text-decoration:none;font-size:13px">Admin Upload</a>
      </div>
    </header>

    <form method="get" class="controls" aria-label="Search and filter">
      <div class="search">
        <input type="text" name="q" placeholder="Search by title, description..." value="<?= e($search) ?>">
        <button type="submit">Search</button>
      </div>

      <div class="filter">
        <select name="category" onchange="this.form.submit()">
          <option value="">All categories</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= e($c) ?>" <?= $c === $categoryFilter ? 'selected' : '' ?>><?= e($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>

    <div class="layout">
      <main>
        <div class="grid" role="list">
          <?php if (empty($projects)): ?>
            <div style="grid-column:1/-1;text-align:center;color:var(--muted);padding:36px 12px">No projects found. Add some via the admin panel.</div>
          <?php else: foreach ($projects as $p):
                $thumb = ($hasThumbnail && !empty($p['thumbnail']) && file_exists(__DIR__ . '/' . $p['thumbnail'])) ? $p['thumbnail'] : null;
                $short = mb_strlen($p['description'] ?? '') > 160 ? mb_substr($p['description'],0,157).'...' : ($p['description'] ?? '');
          ?>
            <article class="card" role="listitem">
              <?php if ($thumb): ?>
                <img src="<?= e($thumb) ?>" alt="<?= e($p['title']) ?> thumbnail" class="thumb">
              <?php else: ?>
                <div class="thumb" style="display:flex;align-items:center;justify-content:center;color:var(--muted);font-weight:700"><?= e($p['category'] ?: 'Project') ?></div>
              <?php endif; ?>

              <div class="title"><?= e($p['title']) ?></div>
              <div class="desc"><?= e($short) ?></div>

              <div class="meta">
                <div class="cat"><?= e($p['category'] ?: 'General') ?> • <?= e(date('d M Y', strtotime($p['created_at'] ?: date('Y-m-d')))) ?></div>

                <div class="actions">
                  <a href="view_project.php?id=<?= (int)$p['id'] ?>" class="btn view" target="_blank">Open</a>

                  <form method="get" action="<?= e(basename(__FILE__)) ?>" style="display:inline">
                    <input type="hidden" name="action" value="print_sheet">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="btn print" title="Printable assignment sheet">Print</button>
                  </form>

                  <?php if ($hasDemoLink && is_valid_url($p['demo_link'])): ?>
                    <a href="<?= e($p['demo_link']) ?>" target="_blank" class="btn link">Demo</a>
                  <?php endif; ?>

                  <?php if ($hasRepoLink && is_valid_url($p['repo_link'])): ?>
                    <a href="<?= e($p['repo_link']) ?>" target="_blank" class="btn link">Repo</a>
                  <?php endif; ?>
                </div>
              </div>
            </article>
          <?php endforeach; endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
          <nav class="pager" aria-label="Pages">
            <?php for ($i=1;$i<=$total_pages;$i++):
                $qs = $_GET; $qs['page'] = $i; $link = $_SERVER['PHP_SELF'] . '?' . http_build_query($qs);
            ?>
              <a class="<?= $i === $page ? 'active' : '' ?>" href="<?= e($link) ?>"><?= $i ?></a>
            <?php endfor; ?>
          </nav>
        <?php endif; ?>
      </main>

      <aside class="side" aria-label="Upcoming project subjects">
        <h3 style="margin-top:0">Upcoming Project Subjects</h3>
        <p style="color:var(--muted);font-size:13px;margin-top:6px">These topics will be assigned in the coming days. Click <strong>Admin Upload</strong> to add starter templates.</p>

        <?php foreach ($upcoming_subjects as $domain => $list): ?>
          <div style="margin-top:12px">
            <h4 style="margin:0 0 8px;color:#b6fff8"><?= e($domain) ?></h4>
            <?php foreach ($list as $s): ?>
              <div class="subject">
                <h4 style="margin:0"><?= e($s['title']) ?></h4>
                <div class="small" style="color:var(--muted);margin-top:6px"><?= e($s['desc']) ?></div>
                <div style="margin-top:8px;font-size:13px;color:var(--muted)"><strong>Difficulty:</strong> <?= e($s['difficulty']) ?> &nbsp; | &nbsp; <strong>Est:</strong> <?= e($s['hours']) ?> hrs</div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>

        <div style="margin-top:12px">
          <a href="admin/projects_admin.php" class="btn view" style="display:inline-block;text-decoration:none">Add Starter & Assign</a>
        </div>
      </aside>
    </div>

    <footer>© <?= date('Y') ?> Student Project Hub — Provided by your department</footer>
  </div>

  <script>
    // Open printable sheet in new tab for nicer UX
    document.addEventListener('submit', function(evt){
      var form = evt.target;
      if (form && form.querySelector('input[name="action"][value="print_sheet"]')) {
        evt.preventDefault();
        var id = form.querySelector('input[name="id"]').value;
        var url = window.location.pathname + '?action=print_sheet&id=' + encodeURIComponent(id);
        window.open(url, '_blank', 'noopener');
      }
    });
  </script>
</body>
</html>
