<?php
// materials.php — student view for uploaded notes (improved & styled)
require_once __DIR__ . '/includes/config.php';

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

$semester = isset($_GET['semester']) ? intval($_GET['semester']) : 0;
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

/* Build query with safe parameter binding */
$where = [];
$params = [];
$types = '';

if ($semester >= 1 && $semester <= 8) {
    $where[] = "semester = ?";
    $types .= 'i';
    $params[] = $semester;
}
if ($q !== '') {
    $where[] = "(subject LIKE ? OR original_filename LIKE ?)";
    $types .= 'ss';
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
}

$sql = "SELECT id, semester, subject, original_filename, file_path, file_type, file_size, version, uploaded_on FROM notes";
if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY semester, subject, uploaded_on DESC";

$notes = [];
if ($stmt = $conn->prepare($sql)) {
    if (!empty($params)) {
        // dynamic bind
        $bind_names = [];
        $bind_names[] = $types;
        for ($i = 0; $i < count($params); $i++) {
            // PHP requires references
            $bind_names[] = & $params[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) $notes = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    // fallback to direct query
    $res = $conn->query($sql);
    if ($res) $notes = $res->fetch_all(MYSQLI_ASSOC);
}

/* Resolve paths helper */
function resolve_paths_from_db($file_path) {
    $file_path = trim((string)$file_path);
    $web_normalized = preg_replace('#^(\.+/)+#', '', $file_path); // remove ../ or ../../
    $web_normalized = ltrim($web_normalized, '/\\');
    $web_path = $web_normalized;
    $project_root = realpath(__DIR__);
    $server_path = $project_root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $web_normalized);
    $real = realpath($server_path);
    if ($real && strpos($real, $project_root) === 0) {
        return [
            'web' => $web_path,
            'server' => $real,
            'exists' => true
        ];
    }
    return [
        'web' => $web_path,
        'server' => $server_path,
        'exists' => file_exists($server_path)
    ];
}

/* Human-readable filesize */
function hr_filesize($bytes) {
    if ($bytes <= 0) return '-';
    $units = ['B','KB','MB','GB','TB'];
    $e = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $e), 1) . ' ' . $units[$e];
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Study Materials</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    :root{
      --bg: #f6f9fb;
      --card: #ffffff;
      --muted: #64748b;
      --accent: linear-gradient(90deg,#06b6d4,#3b82f6);
      --accent-color: #0ea5e9;
      --success: #10b981;
      --danger: #ef4444;
      --glass: rgba(255,255,255,0.6);
    }

    *{box-sizing:border-box}
    body{margin:0;font-family:Inter,ui-sans-serif,system-ui,Segoe UI,Roboto,"Helvetica Neue",Arial;color:#0f172a;background:var(--bg);-webkit-font-smoothing:antialiased}
    .wrap{max-width:1100px;margin:28px auto;padding:18px}
    .topbar{display:flex;gap:12px;align-items:center;justify-content:space-between;margin-bottom:20px}
    .title { display:flex;flex-direction:column; }
    h1{font-size:1.6rem;margin:0;color:#0b1220}
    .subtitle{color:var(--muted);font-size:0.95rem;margin-top:6px}

    /* search / filters */
    .filters { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; margin-bottom:18px; }
    .filter-item { display:flex; flex-direction:column; gap:6px; }
    select, input[type="text"] { padding:10px 12px; border-radius:10px; border:1px solid #e6eef8; background:#fff; min-width:180px; outline:none; font-size:0.95rem; }
    input[type="text"] { min-width:260px; }
    .btn { padding:10px 14px; border-radius:10px; background:var(--accent-color); color:#fff; border:0; cursor:pointer; font-weight:700; box-shadow: 0 6px 18px rgba(14,165,233,0.12); }
    .btn.ghost { background:transparent;color:var(--accent-color); border:1px solid rgba(14,165,233,0.12); box-shadow:none; font-weight:600; }
    .pill { padding:8px 10px; border-radius:999px; background:var(--card); border:1px solid #eef2f7; color:var(--muted); font-weight:600; }

    /* grid */
    .grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:16px; }
    .card { background:var(--card); border-radius:12px; padding:14px; box-shadow: 0 12px 30px rgba(9,30,66,0.06); border:1px solid rgba(15,23,42,0.04); display:flex; gap:12px; align-items:flex-start; transition:transform .16s ease,box-shadow .16s ease; }
    .card:hover { transform:translateY(-6px); box-shadow: 0 18px 40px rgba(9,30,66,0.08); }
    .thumb { width:110px; height:110px; border-radius:8px; flex-shrink:0; display:flex; align-items:center; justify-content:center; background:linear-gradient(180deg,#f8fafc,#eef2ff); border:1px solid #f0f6ff; color:#0b1220; font-weight:700; font-size:13px; text-align:center; padding:8px;}
    .meta-block { flex:1; display:flex; flex-direction:column; gap:8px; }
    .meta-top { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; }
    .file-title { font-weight:700; color:#071135; margin:0; font-size:1.02rem; }
    .sub { color:var(--muted); font-size:0.92rem; margin-top:4px; }
    .badges { display:flex; gap:8px; align-items:center; margin-top:6px; flex-wrap:wrap; }
    .badge { background: linear-gradient(180deg,#f1faff,#fff); border:1px solid #e6f2ff; color:#0b1220; padding:6px 8px; border-radius:8px; font-weight:700; font-size:0.82rem; }
    .badge.small { padding:5px 7px; font-weight:600; color:var(--muted); background:transparent; border:1px dashed #f0f4f8; }

    .actions { display:flex; flex-direction:column; gap:8px; align-items:center; min-width:120px; }
    .actions a { display:inline-block; padding:10px 12px; border-radius:8px; text-decoration:none; font-weight:700; font-size:0.92rem; }
    .actions .download { background:linear-gradient(90deg,#3742fa,#0ea5e9); color:#fff; }
    .actions .open { background:transparent; color:#1f2937; border:1px solid #eef2f7; }
    .actions .missing { background:transparent; color:var(--danger); border:1px solid rgba(239,68,68,0.12); padding:8px 10px; border-radius:8px; font-weight:700; }

    .preview { margin-top:10px; border-radius:8px; overflow:hidden; border:1px solid #f1f5f9; }
    .preview iframe { width:100%; height:180px; display:block; border:0; background:#fff; }
    .preview img { width:100%; height:auto; display:block; }

    .empty { text-align:center; padding:48px; background:var(--card); border-radius:12px; box-shadow:0 8px 24px rgba(9,30,66,0.04); color:var(--muted); font-weight:600; }

    footer.info { margin-top:18px; color:var(--muted); font-size:0.95rem; text-align:center; }

    @media (max-width:720px){
      .topbar{flex-direction:column;align-items:flex-start;gap:10px}
      .actions{flex-direction:row;min-width:unset}
      .thumb{width:92px;height:92px}
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div class="title">
        <h1>Study Materials</h1>
        <div class="subtitle">Semester-wise notes, PDFs and images. Preview or download the originals.</div>
      </div>

      <div style="display:flex;gap:8px;align-items:center">
        <span class="pill">Total: <?php echo count($notes); ?></span>
        <a class="btn ghost" href="index.php">Back to Home</a>
      </div>
    </div>

    <form class="filters" method="get" action="">
      <div class="filter-item">
        <label class="sub" style="font-weight:700">Semester</label>
        <select name="semester" onchange="this.form.submit()">
          <option value="0">All semesters</option>
          <?php for($i=1;$i<=8;$i++): ?>
            <option value="<?php echo $i?>" <?php if($semester==$i) echo 'selected'; ?>><?php echo $i?></option>
          <?php endfor; ?>
        </select>
      </div>

      <div class="filter-item" style="flex:1">
        <label class="sub" style="font-weight:700">Search subject / filename</label>
        <input type="text" name="q" placeholder="Enter subject or filename..." value="<?php echo h($q); ?>">
      </div>

      <div style="display:flex;flex-direction:column;gap:8px;justify-content:flex-end">
        <button class="btn" type="submit">Filter</button>
        <a class="btn ghost" href="materials.php" style="text-align:center">Reset</a>
      </div>
    </form>

    <?php if (empty($notes)): ?>
      <div class="empty">No study materials found.</div>
    <?php else: ?>
      <div class="grid">
        <?php foreach($notes as $n):
            $subject = $n['subject'] ?? '';
            $origName = $n['original_filename'] ?? basename($n['file_path'] ?? '');
            $uploadedOn = $n['uploaded_on'] ?? '';
            $version = $n['version'] ?? 1;
            $paths = resolve_paths_from_db($n['file_path'] ?? '');
            $webPath = $paths['web'];
            $serverPath = $paths['server'];
            $exists = $paths['exists'];
            $ext = strtolower(pathinfo($webPath, PATHINFO_EXTENSION));
            $previewable = in_array($ext, ['pdf','jpg','jpeg','png']);
            $sizeText = isset($n['file_size']) && $n['file_size'] ? hr_filesize(intval($n['file_size'])) : '-';
        ?>
          <article class="card" aria-labelledby="file-<?php echo h($n['id']); ?>">
            <div class="thumb" aria-hidden="true">
              <?php if (in_array($ext,['jpg','jpeg','png'])): ?>
                <img src="<?php echo h($webPath); ?>" alt="<?php echo h($origName); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:8px">
              <?php else: ?>
                <div style="text-align:center;padding:6px">
                  <div style="font-size:22px;color:#073b4c">📄</div>
                  <div style="font-size:11px;margin-top:8px;color:var(--muted);font-weight:700">DOCUMENT</div>
                </div>
              <?php endif; ?>
            </div>

            <div class="meta-block">
              <div class="meta-top">
                <div>
                  <h2 id="file-<?php echo h($n['id']); ?>" class="file-title"><?php echo 'SEM '.h($n['semester']).' — '.h($subject); ?></h2>
                  <div class="sub"><?php echo h($origName); ?></div>
                </div>
                <div class="badges">
                  <div class="badge">v<?php echo h($version); ?></div>
                  <div class="badge small"><?php echo h($sizeText); ?></div>
                  <div class="badge small"><?php echo strtoupper(h($ext ?: 'file')); ?></div>
                </div>
              </div>

              <?php if ($previewable && $exists): ?>
                <div class="preview" role="region" aria-label="Preview: <?php echo h($origName); ?>">
                  <?php if ($ext === 'pdf'): ?>
                    <iframe src="<?php echo h($webPath); ?>" title="<?php echo h($origName); ?>"></iframe>
                  <?php else: ?>
                    <img src="<?php echo h($webPath); ?>" alt="<?php echo h($origName); ?>">
                  <?php endif; ?>
                </div>
              <?php elseif (!$exists): ?>
                <div style="margin-top:8px;color:var(--danger);font-weight:700">File missing on server</div>
              <?php else: ?>
                <div style="margin-top:8px;color:var(--muted);font-weight:600">Preview not available</div>
              <?php endif; ?>

              <div style="margin-top:10px;color:var(--muted);font-size:0.9rem">Uploaded: <?php echo h($uploadedOn); ?></div>
            </div>

            <div class="actions" aria-hidden="false">
              <?php if ($exists): ?>
                <a class="download" href="<?php echo h($webPath); ?>" download>⬇ Download</a>
                <a class="open" href="<?php echo h($webPath); ?>" target="_blank" rel="noopener">🔍 Open</a>
              <?php else: ?>
                <div class="missing">Missing</div>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <footer class="info">Tip: Use the filter to narrow by semester. Click “Open” to view in a new tab, or “Download” to save the original file.</footer>
  </div>
</body>
</html>
