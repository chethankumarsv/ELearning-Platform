<?php
// admin/upload_notes.php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit;
}

/* small output-escape helper used in this page */
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

$uploadDir    = __DIR__ . '/../uploads/notes/';
$webUploadDir = 'uploads/notes/';

// ensure upload dir exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// allowed MIME types -> extension
$allowed = [
    'application/pdf' => 'pdf',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
    'application/vnd.ms-powerpoint' => 'ppt',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'image/jpeg' => 'jpg',
    'image/png'  => 'png'
];

$maxFileSize = 30 * 1024 * 1024; // 30MB
$messages = [];

// === Handle upload ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    $semester = intval($_POST['semester'] ?? 0);
    $subject  = trim((string)($_POST['subject'] ?? ''));

    if ($semester < 1 || $semester > 8) $messages[] = 'Invalid semester.';
    if ($subject === '') $messages[] = 'Subject name required.';
    if (!isset($_FILES['notes_file']) || $_FILES['notes_file']['error'] !== UPLOAD_ERR_OK) {
        $messages[] = 'Please select a file to upload.';
    }

    if (empty($messages)) {
        $file = $_FILES['notes_file'];

        if ($file['size'] > $maxFileSize) {
            $messages[] = 'File too large (max 30MB).';
        } else {
            // Determine MIME type securely
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']) ?: mime_content_type($file['tmp_name']);

            if (!array_key_exists($mime, $allowed)) {
                $messages[] = 'Unsupported file type: ' . h($mime);
            } else {
                // Determine previous version for same semester+subject (if notes table exists)
                $prevVer = 0;
                $stmt = $conn->prepare("SELECT MAX(`version`) AS v FROM notes WHERE semester = ? AND subject = ?");
                if ($stmt) {
                    $stmt->bind_param('is', $semester, $subject);
                    $stmt->execute();
                    if (method_exists($stmt, 'get_result')) {
                        $res = $stmt->get_result();
                        $row = $res ? $res->fetch_assoc() : null;
                        $prevVer = intval($row['v'] ?? 0);
                    } else {
                        $stmt->store_result();
                        // fallback: unable to fetch num rows in this environment — keep prevVer as 0
                    }
                    $stmt->close();
                }

                $newVer = $prevVer + 1;

                // generate safe filename
                $ext = $allowed[$mime];
                $safeName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $targetPath = $uploadDir . $safeName;
                $webPath = $webUploadDir . $safeName;

                // move uploaded file
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $origName = $conn->real_escape_string($file['name']);
                    $fileSize = (int)filesize($targetPath);
                    $fileType = $conn->real_escape_string($mime);

                    // insert record
                    $ins = $conn->prepare(
                        "INSERT INTO notes (semester, subject, original_filename, file_path, uploader, file_type, file_size, `version`)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    if ($ins) {
                        $uploader = 'Admin';
                        // types: i (semester) + s*5 (subject, orig, path, uploader, file_type) + i (file_size) + i (version)
                        // => 'isssssii' (1 i, 5 s, 2 i) -> total 8 params
                        $ins->bind_param('isssssii', $semester, $subject, $origName, $webPath, $uploader, $fileType, $fileSize, $newVer);
                        if ($ins->execute()) {
                            $messages[] = 'Upload successful.';
                        } else {
                            $messages[] = 'DB insert failed: ' . h($ins->error ?: $conn->error);
                            @unlink($targetPath);
                        }
                        $ins->close();
                    } else {
                        $messages[] = 'DB prepare failed: ' . h($conn->error);
                        @unlink($targetPath);
                    }
                } else {
                    $messages[] = 'Failed to move uploaded file.';
                }
            }
        }
    }
}

// === Handle delete ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delId = intval($_POST['delete_id']);
    $s = $conn->prepare("SELECT file_path FROM notes WHERE id = ?");
    if ($s) {
        $s->bind_param('i', $delId);
        $s->execute();
        $res = method_exists($s, 'get_result') ? $s->get_result() : null;
        $row = $res ? $res->fetch_assoc() : null;
        $s->close();

        if ($row) {
            $path = __DIR__ . '/../' . $row['file_path']; // path relative to project root
            $del = $conn->prepare("DELETE FROM notes WHERE id = ?");
            if ($del) {
                $del->bind_param('i', $delId);
                if ($del->execute()) {
                    if (file_exists($path)) @unlink($path);
                    $messages[] = 'Note deleted.';
                } else {
                    $messages[] = 'Delete failed: ' . h($del->error ?: $conn->error);
                }
                $del->close();
            } else {
                $messages[] = 'Delete prepare failed: ' . h($conn->error);
            }
        } else {
            $messages[] = 'Note not found.';
        }
    } else {
        $messages[] = 'Select prepare failed: ' . h($conn->error);
    }
}

// === Fetch notes ===
$notesRes = $conn->query("SELECT * FROM notes ORDER BY semester, subject, uploaded_on DESC");
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Admin - Upload Notes</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{font-family:Arial,Helvetica,sans-serif;background:#f4f6fb;padding:20px}
.container{max-width:1100px;margin:20px auto}
.card{background:#fff;padding:18px;border-radius:10px;box-shadow:0 6px 20px rgba(24,39,75,.06);margin-bottom:18px}
.grid{display:flex;gap:18px;flex-wrap:wrap}
.formcol{flex:1 1 340px}
.listcol{flex:2 1 640px}
label{display:block;margin-top:8px}
select,input[type=file],input[type=text]{width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;margin-top:6px}
table{width:100%;border-collapse:collapse}
th,td{padding:8px;border-bottom:1px solid #eee;text-align:left}
.actions button{margin-right:6px}
a.btn{display:inline-block;padding:8px 12px;background:#2b6cb0;color:#fff;border-radius:6px;text-decoration:none}
.msg{padding:8px;margin-bottom:10px;background:#eef7ff;border-left:4px solid #2b6cb0}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.small{font-size:13px;color:#666}
@media(max-width:820px){ .grid{flex-direction:column} .formcol,.listcol{flex:unset} }
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <h2>Admin — Manage Notes</h2>
    <div>
      <a class="btn" href="../view_notes.php" target="_blank">Open Student View</a>
      <a class="btn" href="index.php" style="background:#c53030">Logout</a>
    </div>
  </div>

  <?php foreach($messages as $m): ?>
    <div class="msg"><?= h($m) ?></div>
  <?php endforeach; ?>

  <div class="card grid">
    <div class="formcol">
      <h3>Upload Notes</h3>
      <form method="post" enctype="multipart/form-data" novalidate>
        <label>Semester</label>
        <select name="semester" required>
          <option value="">Select Semester</option>
          <?php for($i=1;$i<=8;$i++): ?>
            <option value="<?= $i ?>"><?= $i ?></option>
          <?php endfor; ?>
        </select>

        <label>Subject</label>
        <input type="text" name="subject" required placeholder="e.g. Data Structures">

        <label>File (PDF / PPT / DOC / JPG / PNG)</label>
        <input type="file" name="notes_file" required>

        <div style="margin-top:10px;">
          <button type="submit" name="upload" style="padding:10px 14px;background:#2b6cb0;color:#fff;border:0;border-radius:6px;cursor:pointer">Upload</button>
        </div>
      </form>
      <p class="small">Max file size 30MB. Supported: PDF, PPT/PPTX, DOC/DOCX, JPG, PNG.</p>
    </div>

    <div class="listcol">
      <h3>All Notes</h3>
      <table>
        <thead><tr><th>#</th><th>Sem</th><th>Subject</th><th>File</th><th>Ver</th><th>Uploaded On</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if ($notesRes && $notesRes->num_rows): ?>
          <?php while($row = $notesRes->fetch_assoc()): ?>
            <tr>
              <td><?= h($row['id']) ?></td>
              <td><?= h($row['semester']) ?></td>
              <td><?= h($row['subject']) ?></td>
              <td><a href="<?= h('../' . ltrim($row['file_path'], '/')) ?>" target="_blank"><?= h($row['original_filename']) ?></a></td>
              <td><?= h($row['version']) ?></td>
              <td><?= h($row['uploaded_on']) ?></td>
              <td class="actions">
                <form method="post" style="display:inline" onsubmit="return confirm('Delete this note?');">
                  <input type="hidden" name="delete_id" value="<?= h($row['id']) ?>">
                  <button type="submit" style="background:#e53e3e;color:#fff;padding:6px 8px;border:0;border-radius:6px;cursor:pointer">Delete</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="7" style="color:#666;padding:12px">No notes found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
