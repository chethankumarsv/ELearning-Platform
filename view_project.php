<?php
// /elearningplatform/view_project.php
// Student-facing project code viewer (safe, robust).
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();

// DB config (expects $conn mysqli in includes/config.php)
$config = __DIR__ . '/includes/config.php';
if (!file_exists($config)) { http_response_code(500); echo "Missing includes/config.php"; exit; }
require_once $config;
if (!isset($conn) || !($conn instanceof mysqli)) { http_response_code(500); echo "DB connection error"; exit; }

// small helpers
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function is_text_file($path){
    if (!is_file($path)) return false;
    $f = @fopen($path, 'rb');
    if (!$f) return false;
    $chunk = fread($f, 512);
    fclose($f);
    if ($chunk === false) return false;
    return strpos($chunk, "\0") === false;
}
function bytes_readable($n){ if ($n<1024) return $n.' B'; if ($n<1048576) return round($n/1024,1).' KB'; return round($n/1048576,2).' MB'; }

// validate id
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { http_response_code(400); echo "Invalid project id"; exit; }
$id = (int)$_GET['id'];
if ($id <= 0) { http_response_code(400); echo "Invalid project id"; exit; }

// discover which columns exist to avoid unknown column errors
$cols = [];
$cres = $conn->query("SHOW COLUMNS FROM `projects`");
if ($cres) {
    while ($r = $cres->fetch_assoc()) $cols[] = $r['Field'];
    $cres->free();
}

// build select fields dynamically
$select = ['id','title','description','category'];
foreach (['code','storage_path','zip_path','repo_link','demo_link','thumbnail','difficulty','estimated_hours','notes','created_at'] as $c) {
    if (in_array($c, $cols, true)) $select[] = $c;
}
$sql = "SELECT ". implode(',', $select) ." FROM projects WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) { http_response_code(500); echo "DB prepare error"; exit; }
$stmt->bind_param('i',$id);
$stmt->execute();
$res = $stmt->get_result();
$project = $res ? $res->fetch_assoc() : null;
$stmt->close();
if (!$project) { http_response_code(404); echo "Project not found"; exit; }

// decide where code comes from:
// 1) DB 'code' column (if exists and not empty)
// 2) Extracted folder in storage_path (web relative) -> filesystem path -> list files and view chosen file
// 3) zip_path available to download
$code_from_db = isset($project['code']) && trim($project['code']) !== '' ? $project['code'] : null;
$storageWeb = $project['storage_path'] ?? null;
$zipWeb = $project['zip_path'] ?? null;
$repo = $project['repo_link'] ?? null;
$demo = $project['demo_link'] ?? null;
$thumb = $project['thumbnail'] ?? null;

// compute filesystem path if storageWeb present and allowed
$storageFS = null;
if ($storageWeb) {
    // normalize: remove leading slash if any, then resolve
    $rel = ltrim($storageWeb, '/\\');
    $candidate = realpath(__DIR__ . '/' . $rel);
    // ensure candidate exists and is inside project dir (prevents pointing to other places)
    if ($candidate && is_dir($candidate) && strpos($candidate, realpath(__DIR__)) === 0) {
        $storageFS = $candidate;
    } else {
        // try relative to project root (if storageWeb already contains uploads path)
        $candidate2 = realpath(__DIR__ . '/' . $storageWeb);
        if ($candidate2 && is_dir($candidate2) && strpos($candidate2, realpath(__DIR__)) === 0) $storageFS = $candidate2;
    }
}

// handle requested file path (safe)
// `path` is a web-style relative path under storageWeb folder
$viewRel = isset($_GET['path']) ? trim((string)$_GET['path']) : '';
$viewFileFS = null;
$dirList = [];
if ($storageFS) {
    $requested = $storageFS . '/' . ltrim($viewRel, '/\\');
    $real = @realpath($requested);
    if ($real && strpos($real, $storageFS) === 0) {
        if (is_dir($real)) {
            // list directory
            $entries = scandir($real);
            foreach ($entries as $e) {
                if ($e === '.' || $e === '..') continue;
                $full = $real . DIRECTORY_SEPARATOR . $e;
                $dirList[] = [
                    'name' => $e,
                    'is_dir' => is_dir($full),
                    'size' => is_file($full) ? filesize($full) : 0,
                    'rel' => ltrim(($viewRel !== '' ? $viewRel . '/' : '') . $e, '/\\')
                ];
            }
            usort($dirList, function($a,$b){
                if ($a['is_dir'] && !$b['is_dir']) return -1;
                if (!$a['is_dir'] && $b['is_dir']) return 1;
                return strcasecmp($a['name'],$b['name']);
            });
        } elseif (is_file($real)) {
            $viewFileFS = $real;
        }
    }
}

// if no explicit view file chosen, try to find default file in storageFS
if ($storageFS && !$viewFileFS) {
    $candidates = ['README.md','README.txt','README','index.php','index.html','main.py','app.py'];
    foreach ($candidates as $cand) {
        $p = $storageFS . DIRECTORY_SEPARATOR . $cand;
        if (file_exists($p) && is_file($p)) { $viewFileFS = $p; break; }
    }
    if (!$viewFileFS) {
        // pick first small textual file at root
        $it = new DirectoryIterator($storageFS);
        foreach ($it as $f) {
            if ($f->isDot() || !$f->isFile()) continue;
            $ext = strtolower($f->getExtension());
            if (in_array($ext, ['php','html','js','css','py','java','c','cpp','md','txt','json','sql','xml','json','csv'])) {
                $viewFileFS = $f->getPathname(); break;
            }
        }
    }
}

// handle file download request (download file from storageFS)
if (isset($_GET['download']) && $viewFileFS && is_file($viewFileFS)) {
    $basename = basename($viewFileFS);
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.rawurlencode($basename).'"');
    header('Content-Length: ' . filesize($viewFileFS));
    readfile($viewFileFS);
    exit;
}

// prepare code text to show
$code_text = null;
$code_filename = null;
$code_is_text = false;
if ($code_from_db !== null) {
    $code_text = $code_from_db;
    $code_filename = 'code_from_database.txt';
    $code_is_text = true;
} elseif ($viewFileFS && is_file($viewFileFS)) {
    if (is_text_file($viewFileFS)) {
        $code_text = file_get_contents($viewFileFS);
        $code_filename = basename($viewFileFS);
        $code_is_text = true;
    } else {
        // binary file — won't display, instruct to download
        $code_text = null;
        $code_filename = basename($viewFileFS);
        $code_is_text = false;
    }
}

// helper for link building
$self = basename(__FILE__);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= e($project['title']) ?> — Project Code</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <!-- highlight.js for syntax highlighting -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/atom-one-dark.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>
  <style>
    :root{
      --bg1:#0f172a; --card:#111827; --accent:#06b6d4; --accent2:#8b5cf6; --muted:#9aa7bf; --text:#e6eef6;
    }
    body{margin:0;font-family:Poppins,system-ui,Arial,sans-serif;background:linear-gradient(180deg,#071226,#0f1724);color:var(--text)}
    header{background:linear-gradient(90deg,var(--accent),var(--accent2));padding:14px 20px;color:#012;font-weight:700;border-bottom-left-radius:14px;border-bottom-right-radius:14px}
    .wrap{max-width:1000px;margin:28px auto;padding:18px}
    .card{background:rgba(255,255,255,0.03);padding:18px;border-radius:12px;border:1px solid rgba(255,255,255,0.03);box-shadow:0 10px 30px rgba(2,6,23,0.7)}
    .title{font-size:1.6rem;margin:0;color:#7ff1f1}
    .subtitle{color:var(--muted);margin-top:6px}
    .top{display:flex;gap:18px;align-items:center;justify-content:space-between}
    .meta small{color:var(--muted)}
    .actions{display:flex;gap:8px;flex-wrap:wrap}
    .btn{padding:8px 12px;border-radius:10px;border:0;cursor:pointer;font-weight:700}
    .btn.primary{background:linear-gradient(90deg,var(--accent),var(--accent2));color:#012}
    .btn.ghost{background:rgba(255,255,255,0.03);color:var(--muted)}
    .layout{display:grid;grid-template-columns:1fr 320px;gap:18px;margin-top:14px}
    .file-list{background:rgba(255,255,255,0.02);padding:10px;border-radius:8px;max-height:520px;overflow:auto}
    .file-item{display:flex;justify-content:space-between;align-items:center;padding:8px;border-radius:6px}
    .code-area{background:#0a0f1c;border-radius:10px;padding:12px;position:relative;overflow:auto}
    pre.hljs{margin:0;padding:12px;border-radius:8px;background:transparent}
    .copy-btn{position:absolute;right:12px;top:12px;padding:8px 12px;border-radius:8px;border:0;background:linear-gradient(90deg,var(--accent),var(--accent2));color:#012;font-weight:700;cursor:pointer}
    .note{color:var(--muted);font-size:13px}
    a.back{display:inline-block;margin-top:12px;color:var(--muted);text-decoration:none}
    footer{margin-top:18px;color:var(--muted);text-align:center;font-size:13px}
    @media (max-width:980px){ .layout{grid-template-columns:1fr} .file-list{max-height:220px} }
  </style>
</head>
<body>
<header>📘 Project Code Viewer</header>

<div class="wrap">
  <div class="card">
    <div class="top">
      <div>
        <div class="title"><?= e($project['title']) ?></div>
        <div class="subtitle"><?= e($project['description'] ?? '') ?></div>
        <div class="meta" style="margin-top:8px">
          <small>Category: <?= e($project['category'] ?? 'General') ?> •
            Difficulty: <?= e($project['difficulty'] ?? '—') ?> •
            Posted: <?= e(isset($project['created_at']) ? date('d M Y', strtotime($project['created_at'])) : date('d M Y')) ?></small>
        </div>
      </div>

      <div class="actions">
        <?php if (!empty($demo)): ?>
          <a class="btn primary" href="<?= e($demo) ?>" target="_blank" rel="noopener">Open Demo</a>
        <?php endif; ?>
        <?php if (!empty($repo)): ?>
          <a class="btn ghost" href="<?= e($repo) ?>" target="_blank" rel="noopener">View Repo</a>
        <?php endif; ?>
        <?php if ($zipWeb ?? false): ?>
          <a class="btn ghost" href="<?= e($zipWeb) ?>" target="_blank" rel="noopener">Download ZIP</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="layout">
      <main>
        <h3 style="margin:12px 0 8px;color:#bff7f7">Code</h3>

        <div class="code-area">
          <?php if ($code_text !== null && $code_is_text): ?>
            <button class="copy-btn" id="copyBtn">Copy Code</button>
            <pre id="codeBlock" class="hljs"><?= e($code_text) ?></pre>
            <div style="margin-top:8px" class="note">Showing: <?= e($code_filename) ?></div>
          <?php elseif ($viewFileFS && !$code_is_text): ?>
            <div style="padding:18px;color:var(--muted)">This file (<?= e($code_filename) ?>) is not a text file and cannot be previewed. Use the Download button.</div>
            <div style="margin-top:8px"><a class="btn ghost" href="<?= e($self) . '?id=' . $id . '&path=' . urlencode($viewRel) . '&download=1' ?>">Download File</a></div>
          <?php else: ?>
            <div style="padding:18px;color:#7ee0e0;font-family:monospace">// No code available for this project yet.
// Please check the repository link or contact the instructor to upload the source code.</div>
          <?php endif; ?>
        </div>

        <a class="back" href="projects.php">← Back to Projects</a>
      </main>

      <aside>
        <div style="margin-bottom:12px" class="note"><strong>Files (if uploaded)</strong></div>

        <?php if (!$storageFS): ?>
          <div class="note">No extracted files found for this project.</div>
        <?php else: ?>
          <div class="file-list" role="list">
            <?php
              // breadcrumb
              $crumb = $viewRel ? explode('/', $viewRel) : [];
            ?>
            <div style="padding:6px 8px;color:<?= $viewRel ? '#cfeef6' : 'var(--muted)' ?>;">Path: /<?= e($viewRel) ?></div>
            <?php if ($viewRel): ?>
              <div style="padding:6px 8px"><a href="<?= e($self) ?>?id=<?= $id ?>">[.. root]</a></div>
            <?php endif; ?>

            <?php if (empty($dirList)): ?>
              <div style="padding:8px;color:var(--muted)">This folder is empty.</div>
            <?php else: foreach ($dirList as $fi): ?>
              <div class="file-item">
                <div>
                  <?php if ($fi['is_dir']): ?>
                    <a href="<?= e($self) ?>?id=<?= $id ?>&path=<?= urlencode($fi['rel']) ?>">📁 <?= e($fi['name']) ?></a>
                    <div class="note"><?= e($fi['rel']) ?></div>
                  <?php else: ?>
                    <a href="<?= e($self) ?>?id=<?= $id ?>&path=<?= urlencode($fi['rel']) ?>"><?= e($fi['name']) ?></a>
                    <div class="note"><?= e(bytes_readable($fi['size'])) ?></div>
                  <?php endif; ?>
                </div>
                <div>
                  <?php if (!$fi['is_dir']): ?>
                    <a class="btn ghost" href="<?= e($self) ?>?id=<?= $id ?>&path=<?= urlencode($fi['rel']) ?>&download=1">Download</a>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; endif; ?>
          </div>
        <?php endif; ?>

        <div style="margin-top:12px" class="note">
          <?php if ($project['notes'] ?? false): ?>
            <strong>Instructor notes:</strong><br><?= nl2br(e($project['notes'])) ?>
          <?php else: ?>
            No instructor notes.
          <?php endif; ?>
        </div>
      </aside>
    </div>
  </div>

  <footer>© <?= date('Y') ?> E-Learning Platform | Designed by Chethan SV</footer>
</div>

<script>hljs.highlightAll();</script>
<script>
  (function(){
    const copyBtn = document.getElementById('copyBtn');
    if (!copyBtn) return;
    copyBtn.addEventListener('click', async function(){
      const codeEl = document.getElementById('codeBlock');
      try {
        await navigator.clipboard.writeText(codeEl.innerText);
        copyBtn.textContent = 'Copied ✓';
        setTimeout(()=> copyBtn.textContent = 'Copy Code', 1600);
      } catch (err) {
        alert('Copy failed — please select and copy manually.');
      }
    });
  })();
</script>
</body>
</html>
