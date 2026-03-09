<?php
// admin/materials_admin.php
session_start();
require_once __DIR__ . '/../includes/config.php';
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: /elearningplatform/login.php?redirect=admin/materials_admin.php');
    exit;
}

// helpers
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function slugify($s){ return preg_replace('/[^a-z0-9\-]+/','-', strtolower(trim($s))); }

$uploadDir = realpath(__DIR__ . '/../') . '/uploads/materials/';
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

// allowed extensions & max size (10 MB by default)
$allowed = ['pdf','doc','docx','ppt','pptx','zip','rar','txt','md'];
$maxBytes = 10 * 1024 * 1024; // 10 MB

// CSRF
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

$errors = [];
$success = '';

// handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) { $errors[] = 'Invalid CSRF'; }
    else {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) $errors[] = 'Invalid id';
        else {
            $st = $conn->prepare("SELECT filename FROM materials WHERE id = ? LIMIT 1");
            $st->bind_param('i', $id); $st->execute(); $res = $st->get_result(); $row = $res ? $res->fetch_assoc() : null; $st->close();
            if ($row) {
                // delete file
                $path = __DIR__ . '/../' . $row['filename'];
                if (file_exists($path)) @unlink($path);
                // delete db
                $d = $conn->prepare("DELETE FROM materials WHERE id = ?");
                $d->bind_param('i',$id);
                if ($d->execute()) $success = 'Material deleted';
                else $errors[] = 'DB delete failed: '.$d->error;
                $d->close();
            } else $errors[] = 'Material not found';
        }
    }
}

// handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) { $errors[] = 'Invalid CSRF'; }
    else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $semester = trim($_POST['semester'] ?? '');
        $type = trim($_POST['type'] ?? 'Lecture Notes');

        if ($title === '') $errors[] = 'Title required';
        if (empty($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) $errors[] = 'File required';

        if (empty($errors)) {
            $file = $_FILES['file'];
            if ($file['error'] !== UPLOAD_ERR_OK) $errors[] = 'Upload error code: '.$file['error'];
            else {
                $orig = $file['name'];
                $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) $errors[] = 'Type not allowed: '.$ext;
                elseif ($file['size'] > $maxBytes) $errors[] = 'File too large (max 10 MB)';
                else {
                    // safe filename
                    $base = time() . '-' . bin2hex(random_bytes(6)) . '-' . slugify(pathinfo($orig, PATHINFO_FILENAME));
                    $filename = 'uploads/materials/' . $base . '.' . $ext;
                    $dest = __DIR__ . '/../' . $filename;
                    if (!move_uploaded_file($file['tmp_name'], $dest)) $errors[] = 'Failed to save file';
                    else {
                        $uploader = $_SESSION['username'] ?? 'admin';
                        $st = $conn->prepare("INSERT INTO materials (title, description, course, semester, type, filename, original_name, filesize, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $st->bind_param('ssssssiss', $title, $description, $course, $semester, $type, $filename, $orig, $file['size'], $uploader);
                        if ($st->execute()) $success = 'Uploaded successfully';
                        else { $errors[] = 'DB insert failed: '.$st->error; @unlink($dest); }
                        $st->close();
                    }
                }
            }
        }
    }
}

// fetch existing materials
$matRes = $conn->query("SELECT * FROM materials ORDER BY created_at DESC");
$materials = $matRes ? $matRes->fetch_all(MYSQLI_ASSOC) : [];

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin — Course Materials</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#f5f7fb;color:#111;margin:0;padding:18px}
    .wrap{max-width:1100px;margin:0 auto}
    h1{margin:0 0 14px}
    .grid{display:grid;grid-template-columns:1fr 420px;gap:18px}
    .card{background:#fff;padding:14px;border-radius:10px;box-shadow:0 6px 18px rgba(0,0,0,0.06)}
    input[type=text], textarea, select{width:100%;padding:8px;border:1px solid #d7dde6;border-radius:6px;margin-top:6px}
    textarea{min-height:90px}
    .btn{padding:10px 12px;border-radius:8px;background:#0ea5a4;color:#012;border:0;cursor:pointer;font-weight:700;margin-top:10px}
    .list{margin-top:12px}
    .item{padding:10px;border-bottom:1px solid #eef2f6;display:flex;justify-content:space-between;align-items:center}
    .item .left{max-width:70%}
    .small{font-size:13px;color:#666}
    .msg{padding:10px;border-radius:6px;margin-bottom:12px}
    .success{background:#ecffef;color:#064;border:1px solid #bfeecf}
    .error{background:#fff0f0;color:#700;border:1px solid #f8c8c8}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Course Materials — Admin</h1>
    <?php if ($success): ?><div class="msg success"><?= e($success) ?></div><?php endif; ?>
    <?php if (!empty($errors)): ?><div class="msg error"><ul><?php foreach ($errors as $er) echo '<li>'.e($er).'</li>'; ?></ul></div><?php endif; ?>

    <div class="grid">
      <div class="card">
        <h3>Upload Material</h3>
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="action" value="upload">
          <label>Title</label>
          <input type="text" name="title" required>

          <label>Course / Subject</label>
          <input type="text" name="course" placeholder="e.g. Data Structures">

          <label>Semester / Batch</label>
          <input type="text" name="semester" placeholder="e.g. Sem 4">

          <label>Type</label>
          <select name="type">
            <option>Lecture Notes</option>
            <option>Question Paper</option>
            <option>Slides</option>
            <option>Assignment</option>
            <option>Reference</option>
          </select>

          <label>Description</label>
          <textarea name="description"></textarea>

          <label>File (pdf/docx/pptx/zip - max 10MB)</label>
          <input type="file" name="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.rar,.txt">

          <div style="margin-top:10px">
            <button class="btn" type="submit">Upload</button>
          </div>
        </form>
      </div>

      <div class="card">
        <h3>Existing Materials</h3>
        <div class="list">
          <?php if (empty($materials)): ?>
            <div class="small">No materials uploaded.</div>
          <?php else: foreach ($materials as $m): ?>
            <div class="item">
              <div class="left">
                <strong><?= e($m['title']) ?></strong><br>
                <span class="small"><?= e($m['course'] ?? 'General') ?> • <?= e($m['type']) ?> • <?= e(date('d M Y', strtotime($m['created_at']))) ?></span>
              </div>
              <div style="text-align:right">
                <a href="../<?= e($m['filename']) ?>" target="_blank" class="small">Open</a>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete this material?');">
                  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                  <button type="submit" class="btn" style="background:#ef4444;color:white;margin-left:8px">Delete</button>
                </form>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
