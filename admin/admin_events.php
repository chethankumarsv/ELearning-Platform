<?php
// /elearningplatform/admin_events.php
session_start();
require_once __DIR__ . '/includes/config.php';

if (function_exists('date_default_timezone_set')) {
  date_default_timezone_set('Asia/Kolkata');
}

// ---------- Auth gate (admin only) ----------
if (empty($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'admin')) {
  http_response_code(403);
  echo "<p style='font-family:system-ui;max-width:720px;margin:40px auto;text-align:center'>
          <b>403 • Forbidden</b><br>You must be an <b>admin</b> to access this page.<br>
          <a href='auth.php'>Login</a>
        </p>";
  exit;
}

// ---------- Helpers ----------
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function csrf_token(){
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
  return $_SESSION['csrf'];
}
function check_csrf(){
  if (empty($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['csrf'] ?? '')) {
    http_response_code(400);
    echo "Bad Request (CSRF).";
    exit;
  }
}
// Convert HTML5 datetime-local to MySQL DATETIME
function to_mysql_dt(?string $v): ?string {
  if (!$v) return null;
  // expected "YYYY-MM-DDTHH:MM"
  $v = str_replace('T',' ', $v);
  return preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}$/', $v) ? ($v.":00") : $v;
}

function ensure_upload_dir($dir){
  if (!is_dir($dir)) @mkdir($dir, 0775, true);
  return is_dir($dir) && is_writable($dir);
}

function upload_banner(?array $file, ?string $oldPath=null){
  if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return $oldPath;
  if ($file['error'] !== UPLOAD_ERR_OK) return $oldPath;

  $allowed = ['jpg','jpeg','png','webp'];
  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, $allowed, true)) return $oldPath;

  if (!ensure_upload_dir(__DIR__.'/uploads/banners')) return $oldPath;

  $name = 'uploads/banners/'.date('Ymd_His').'_'.bin2hex(random_bytes(4)).'.'.$ext;
  $dest = __DIR__ . '/' . $name;
  if (move_uploaded_file($file['tmp_name'], $dest)) {
    // Remove old local file (safety: only under uploads/banners)
    if ($oldPath && str_starts_with($oldPath, 'uploads/banners/') && file_exists(__DIR__.'/'.$oldPath)) {
      @unlink(__DIR__.'/'.$oldPath);
    }
    return $name;
  }
  return $oldPath;
}

// ---------- Actions ----------
$action = $_GET['action'] ?? 'list';
$msg = $_GET['msg'] ?? null;

// Create / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  $id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $title= trim($_POST['title'] ?? '');
  $dt   = to_mysql_dt($_POST['event_date'] ?? '');
  $desc = trim($_POST['description'] ?? '');
  $loc  = trim($_POST['location'] ?? '');
  $reg  = trim($_POST['registration_url'] ?? '');
  $oldBanner = trim($_POST['old_banner'] ?? '');

  if ($title === '' || !$dt) {
    header("Location: admin_events.php?action=".($id?'edit&id='.$id:'create')."&msg=".urlencode("Please fill Title and Date/Time."));
    exit;
  }

  // Upload banner if provided
  $banner = upload_banner($_FILES['banner'] ?? null, $oldBanner);
  $remove_banner = isset($_POST['remove_banner']);
  if ($remove_banner && $oldBanner && str_starts_with($oldBanner, 'uploads/banners/') && file_exists(__DIR__.'/'.$oldBanner)) {
    @unlink(__DIR__.'/'.$oldBanner);
    $banner = '';
  }

  if ($id > 0) {
    // Update
    $stmt = $conn->prepare("UPDATE events
      SET title=?, event_date=?, description=?, location=?, registration_url=?, banner=?, updated_at=NOW()
      WHERE id=? LIMIT 1");
    $stmt->bind_param("ssssssi", $title, $dt, $desc, $loc, $reg, $banner, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_events.php?msg=".urlencode("Event updated."));
    exit;
  } else {
    // Create
    $stmt = $conn->prepare("INSERT INTO events (title, event_date, description, location, registration_url, banner, created_at, updated_at)
                            VALUES (?,?,?,?,?,?,NOW(),NOW())");
    $stmt->bind_param("ssssss", $title, $dt, $desc, $loc, $reg, $banner);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_events.php?msg=".urlencode("Event created."));
    exit;
  }
}

// Delete
if ($action === 'delete' && isset($_GET['id'], $_GET['csrf'])) {
  if ($_GET['csrf'] !== ($_SESSION['csrf'] ?? '')) {
    http_response_code(400); echo "Bad Request (CSRF)."; exit;
  }
  $id = (int)$_GET['id'];
  // fetch banner to remove file
  $res = $conn->prepare("SELECT banner FROM events WHERE id=? LIMIT 1");
  $res->bind_param("i", $id);
  $res->execute(); $row = $res->get_result()->fetch_assoc(); $res->close();
  if ($row && $row['banner'] && str_starts_with($row['banner'], 'uploads/banners/') && file_exists(__DIR__.'/'.$row['banner'])) {
    @unlink(__DIR__.'/'.$row['banner']);
  }
  $stmt = $conn->prepare("DELETE FROM events WHERE id=? LIMIT 1");
  $stmt->bind_param("i",$id);
  $stmt->execute();
  $stmt->close();
  header("Location: admin_events.php?msg=".urlencode("Event deleted."));
  exit;
}

// Fetch for edit
$editEvent = null;
if ($action === 'edit' && isset($_GET['id'])) {
  $id = (int)$_GET['id'];
  $stmt = $conn->prepare("SELECT * FROM events WHERE id=? LIMIT 1");
  $stmt->bind_param("i",$id);
  $stmt->execute(); $editEvent = $stmt->get_result()->fetch_assoc(); $stmt->close();
}

// List data
$q = trim($_GET['q'] ?? '');
$month = trim($_GET['month'] ?? '');
$sql = "SELECT id, title, event_date, location, banner, registration_url FROM events WHERE 1=1";
$params=[]; $types='';

if ($q !== '') {
  $like = "%$q%";
  $sql .= " AND (title LIKE ? OR location LIKE ?)";
  $types .= "ss"; $params[]=$like; $params[]=$like;
}
if ($month !== '' && preg_match('/^\d{4}-\d{2}$/', $month)) {
  $sql .= " AND DATE_FORMAT(event_date, '%Y-%m') = ?";
  $types .= "s"; $params[]=$month;
}
$sql .= " ORDER BY event_date DESC LIMIT 100";
$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$list = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en" data-theme="">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Admin • Events — E-Learning</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --brand:#2563eb; --brand2:#60a5fa; --accent:#ff7e5f; --accent2:#fbbf24; --success:#10b981;
      --text:#111827; --muted:#6b7280; --bg:#f3f4f6; --card:#ffffff; --shadow:rgba(0,0,0,.12);
      --danger:#ef4444;
    }
    [data-theme="dark"]{
      --bg:#0f172a; --text:#f9fafb; --muted:#cbd5e1; --card:#0b1220; --shadow:rgba(0,0,0,.35);
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:Poppins,system-ui,-apple-system,Segoe UI,Roboto;background:var(--bg);color:var(--text);}
    a{text-decoration:none}

    header{position:sticky;top:0;z-index:50;background:linear-gradient(90deg,var(--brand),var(--brand2));color:#fff;
      box-shadow:0 6px 20px rgba(0,0,0,.15)}
    .nav{max-width:1100px;margin:0 auto;padding:14px 18px;display:flex;align-items:center;justify-content:space-between}
    .brand{font-weight:800;letter-spacing:.3px}
    .nav a{color:#fff;font-weight:600;margin-left:14px;padding:6px 12px;border-radius:10px}
    .nav a:hover{background:rgba(255,255,255,.18)}
    .toggle{background:#fff;color:#0f172a;border:none;border-radius:10px;padding:6px 10px;cursor:pointer;font-weight:700}

    .wrap{max-width:1100px;margin:24px auto;background:rgba(255,255,255,.95);backdrop-filter:blur(10px);
      border-radius:24px;box-shadow:0 16px 40px var(--shadow);padding:24px}
    [data-theme="dark"] .wrap{background:rgba(11,18,32,.9)}

    .toolbar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;margin-bottom:14px}
    .toolbar .left{display:flex;gap:10px;align-items:center}
    .btn{appearance:none;border:none;border-radius:12px;padding:10px 16px;font-weight:800;cursor:pointer}
    .btn-primary{background:var(--accent);color:#fff}
    .btn-ghost{background:transparent;border:2px solid #d1d5db;color:#111827}
    [data-theme="dark"] .btn-ghost{border-color:#334155;color:#e5e7eb}
    .btn-danger{background:var(--danger);color:#fff}
    .search{display:flex;gap:8px}
    .search input,.search select{padding:10px 12px;border-radius:12px;border:1px solid #d1d5db;outline:none;font-size:14px}
    .search input:focus,.search select:focus{box-shadow:0 0 0 4px rgba(37,99,235,.18);border-color:#93c5fd}
    [data-theme="dark"] .search input,[data-theme="dark"] .search select{background:#0b1220;color:#f9fafb;border-color:#1f2937}

    .notice{background:#ecfccb;color:#365314;border-left:6px solid #84cc16;padding:10px 12px;border-radius:12px;margin-bottom:12px}
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px}
    .card{background:linear-gradient(135deg,var(--card),#f9fafb);border-radius:18px;box-shadow:0 10px 26px var(--shadow);
      overflow:hidden;display:flex;flex-direction:column}
    .banner{height:140px;width:100%;object-fit:cover;background:#e5e7eb}
    .body{padding:14px}
    .title{font-weight:800;margin:0 0 6px;color:var(--accent)}
    .meta{font-size:13px;color:var(--muted);margin-bottom:10px}
    .actions{display:flex;gap:8px;padding:12px}

    /* Form */
    .form{display:grid;grid-template-columns:1fr;gap:12px;margin-top:6px}
    @media(min-width:900px){ .form{grid-template-columns:1fr 1fr;} }
    .f{display:flex;flex-direction:column;gap:6px}
    .f label{font-weight:700;font-size:14px}
    .f input,.f textarea{padding:12px;border-radius:12px;border:1px solid #d1d5db;font-size:14px;outline:none}
    .f textarea{min-height:120px;resize:vertical}
    [data-theme="dark"] .f input,[data-theme="dark"] .f textarea{background:#0b1220;border-color:#1f2937;color:#f9fafb}
    .two{grid-column:1/-1}
    .preview{border-radius:12px;overflow:hidden;background:#0b1220}
    .muted{color:var(--muted);font-size:12px}

    footer{background:#0f172a;color:#e5e7eb;margin-top:24px;padding:22px 16px;text-align:center;font-size:14px}
  </style>
</head>
<body>
<header>
  <div class="nav">
    <div class="brand">🎓 E-Learning • Admin</div>
    <div>
      <a href="index.php">Home</a>
      <a href="event.php">View Events</a>
      <button class="toggle" id="themeBtn" onclick="toggleTheme()">🌙</button>
    </div>
  </div>
</header>

<div class="wrap">
  <?php if ($msg): ?>
    <div class="notice"><?= h($msg) ?></div>
  <?php endif; ?>

  <?php if ($action === 'create' || $action === 'edit'): ?>
    <?php
      $isEdit = ($action === 'edit' && $editEvent);
      $val = fn($k,$d='') => $isEdit ? ($editEvent[$k] ?? $d) : $d;
      $dtLocal = $isEdit && !empty($editEvent['event_date'])
        ? date('Y-m-d\TH:i', strtotime($editEvent['event_date']))
        : '';
    ?>
    <h2 style="margin:6px 0 10px"><?= $isEdit ? 'Edit Event' : 'Create Event' ?></h2>
    <form class="form" method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <?php if($isEdit): ?><input type="hidden" name="id" value="<?= (int)$editEvent['id'] ?>"><?php endif; ?>
      <div class="f">
        <label>Title *</label>
        <input type="text" name="title" required value="<?= h($val('title')) ?>" placeholder="e.g., Hackathon 2025 Kickoff">
      </div>
      <div class="f">
        <label>Date & Time *</label>
        <input type="datetime-local" name="event_date" required value="<?= h($dtLocal) ?>">
      </div>
      <div class="f">
        <label>Location</label>
        <input type="text" name="location" value="<?= h($val('location')) ?>" placeholder="Auditorium A / Online">
      </div>
      <div class="f">
        <label>Registration URL</label>
        <input type="url" name="registration_url" value="<?= h($val('registration_url')) ?>" placeholder="https://...">
      </div>
      <div class="f two">
        <label>Description</label>
        <textarea name="description" placeholder="Describe the event agenda, speakers, etc."><?= h($val('description')) ?></textarea>
      </div>
      <div class="f">
        <label>Banner (JPG/PNG/WebP)</label>
        <input type="file" name="banner" accept=".jpg,.jpeg,.png,.webp">
        <div class="muted">Recommended 1200×600px. Large files are auto-kept as-is.</div>
      </div>
      <div class="f">
        <label>&nbsp;</label>
        <?php if($isEdit && $val('banner')): ?>
          <div class="preview">
            <img src="<?= h($val('banner')) ?>" alt="" style="width:100%;max-height:140px;object-fit:cover">
          </div>
          <input type="hidden" name="old_banner" value="<?= h($val('banner')) ?>">
          <label style="display:flex;gap:8px;align-items:center;margin-top:6px">
            <input type="checkbox" name="remove_banner" value="1"> <span class="muted">Remove current banner</span>
          </label>
        <?php else: ?>
          <input type="hidden" name="old_banner" value="">
        <?php endif; ?>
      </div>

      <div class="two" style="display:flex;gap:10px;flex-wrap:wrap">
        <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Save Changes' : 'Create Event' ?></button>
        <a class="btn btn-ghost" href="admin_events.php">Cancel</a>
      </div>
    </form>

  <?php else: ?>
    <div class="toolbar">
      <div class="left">
        <a class="btn btn-primary" href="admin_events.php?action=create">➕ New Event</a>
      </div>
      <form class="search" method="get" action="admin_events.php">
        <input type="text" name="q" placeholder="Search by title or location..." value="<?= h($q) ?>">
        <select name="month" title="Filter by month">
          <option value="">All months</option>
          <?php
            $now = new DateTime('first day of this month');
            for ($i=-2; $i<=8; $i++) {
              $m = clone $now; $m->modify(($i>=0?"+$i":"$i")." month");
              $val = $m->format('Y-m'); $label = $m->format('M Y');
              $sel = ($month === $val) ? 'selected' : '';
              echo "<option value=\"".h($val)."\" $sel>$label</option>";
            }
          ?>
        </select>
        <button class="btn btn-ghost" type="submit">Filter</button>
        <a class="btn btn-ghost" href="admin_events.php">Reset</a>
      </form>
    </div>

    <?php if ($list && $list->num_rows): ?>
      <div class="grid">
        <?php while($e = $list->fetch_assoc()): ?>
          <div class="card">
            <img class="banner" src="<?= h($e['banner'] ?: 'assets/img/event-placeholder.jpg') ?>" alt="">
            <div class="body">
              <h3 class="title"><?= h($e['title']) ?></h3>
              <div class="meta">
                📅 <?= date('D, d M Y • h:i A', strtotime($e['event_date'])) ?>
                <?php if (!empty($e['location'])): ?> &nbsp; • &nbsp; 📍 <?= h($e['location']) ?><?php endif; ?>
              </div>
            </div>
            <div class="actions">
              <a class="btn btn-ghost" href="event.php?id=<?= (int)$e['id'] ?>" target="_blank">View</a>
              <a class="btn btn-primary" href="admin_events.php?action=edit&id=<?= (int)$e['id'] ?>">Edit</a>
              <a class="btn btn-danger" href="admin_events.php?action=delete&id=<?= (int)$e['id'] ?>&csrf=<?= h(csrf_token()) ?>"
                 onclick="return confirm('Delete this event permanently?');">Delete</a>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <p style="text-align:center;color:var(--muted)">No events found.</p>
    <?php endif; ?>
  <?php endif; ?>
</div>

<footer>© <?= date('Y') ?> E-Learning Platform • Admin</footer>

<script>
  // Theme toggle (match index)
  const themeBtn = document.getElementById('themeBtn');
  function setBtnIcon(){ themeBtn.textContent = document.body.getAttribute('data-theme')==='dark' ? '☀️' : '🌙'; }
  function toggleTheme(){
    const b=document.body;
    if(b.getAttribute("data-theme")==="dark"){ b.removeAttribute("data-theme"); localStorage.removeItem("theme"); }
    else { b.setAttribute("data-theme","dark"); localStorage.setItem("theme","dark"); }
    setBtnIcon();
  }
  if(localStorage.getItem("theme")==="dark"){ document.body.setAttribute("data-theme","dark"); }
  setBtnIcon();
</script>
</body>
</html>
