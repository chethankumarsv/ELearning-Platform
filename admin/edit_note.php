<?php
// admin/edit_note.php
session_start();
require_once __DIR__ . '/../includes/config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: upload_notes.php'); exit; }

// fetch note
$stmt = $conn->prepare("SELECT * FROM notes WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$note = $stmt->get_result()->fetch_assoc();
if (!$note) { header('Location: upload_notes.php'); exit; }

// fetch subjects
$subRes = $conn->query("SELECT * FROM subjects ORDER BY semester, name");
$subjects = [];
while($s = $subRes->fetch_assoc()) $subjects[] = $s;

$msg = '';

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $semester = intval($_POST['semester']);
    $subject_id = !empty($_POST['subject_id']) ? intval($_POST['subject_id']) : null;
    $subject_text = trim($_POST['subject_text'] ?? '');

    if ($semester < 1 || $semester > 8) $msg = 'Invalid semester.';
    if (!$subject_id && $subject_text === '') $msg = 'Select or enter subject.';

    if ($msg === '') {
        $finalSubject = $subject_text;
        if ($subject_id) {
            $sstmt = $conn->prepare("SELECT name FROM subjects WHERE id=?");
            $sstmt->bind_param('i', $subject_id);
            $sstmt->execute();
            $sr = $sstmt->get_result()->fetch_assoc();
            if ($sr) $finalSubject = $sr['name'];
        }

        // optional file replace
        if (!empty($_FILES['notes_file']) && $_FILES['notes_file']['error'] === UPLOAD_ERR_OK) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['notes_file']['tmp_name']);
            $allowed = ['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png','application/msword'=>'doc','application/vnd.openxmlformats-officedocument.wordprocessingml.document'=>'docx','application/vnd.openxmlformats-officedocument.presentationml.presentation'=>'pptx','application/vnd.ms-powerpoint'=>'ppt'];
            if (!isset($allowed[$mime])) $msg = 'Unsupported file type.';
            else {
                $ext = $allowed[$mime];
                $newName = time().'_'.bin2hex(random_bytes(6)).'.'.$ext;
                $uploadDir = __DIR__ . '/../uploads/notes/';
                if (!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
                $dest = $uploadDir . $newName;
                if (!move_uploaded_file($_FILES['notes_file']['tmp_name'], $dest)) $msg = 'Failed to move uploaded file.';
                else {
                    // delete old file
                    $old = __DIR__ . '/../' . $note['file_path'];
                    if (file_exists($old)) @unlink($old);
                    $webPath = 'uploads/notes/' . $newName;
                    $origName = $conn->real_escape_string($_FILES['notes_file']['name']);
                    $fileSize = filesize($dest);
                    $fileType = $conn->real_escape_string($mime);
                    // update query includes file change
                    $upd = $conn->prepare("UPDATE notes SET semester=?, subject=?, subject_id=?, original_filename=?, file_path=?, file_type=?, file_size=? WHERE id=?");
                    $upd->bind_param('isisssii', $semester, $finalSubject, $subject_id, $origName, $webPath, $fileType, $fileSize, $id);
                    if ($upd->execute()) { $msg = 'Note updated with new file.'; }
                    else $msg = 'DB update failed: '.$conn->error;
                }
            }
        } else {
            // only metadata update
            $upd = $conn->prepare("UPDATE notes SET semester=?, subject=?, subject_id=? WHERE id=?");
            $upd->bind_param('isii', $semester, $finalSubject, $subject_id, $id);
            if ($upd->execute()) $msg = 'Note updated.';
            else $msg = 'DB update failed: '.$conn->error;
        }
        // Refresh note
        $stmt = $conn->prepare("SELECT * FROM notes WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $note = $stmt->get_result()->fetch_assoc();
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8"><title>Edit Note</title>
<style>
body{font-family:Arial;background:#f6f8fb;padding:18px}
.card{max-width:820px;margin:0 auto;background:#fff;padding:18px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,.06)}
input,select{padding:8px;border:1px solid #ddd;border-radius:6px;width:100%}
label{display:block;margin-top:8px}
.btn{padding:8px 10px;background:#007BFF;color:#fff;border:0;border-radius:6px;cursor:pointer;margin-top:10px}
.msg{padding:8px;background:#eef7ff;border-left:4px solid #2b6cb0;margin-bottom:10px}
.small{font-size:13px;color:#666}
</style>
</head>
<body>
<div class="card">
  <h2>Edit Note #<?php echo $note['id']; ?></h2>
  <?php if($msg): ?><div class="msg"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <label>Semester</label>
    <select name="semester" required>
      <?php for($i=1;$i<=8;$i++): ?>
        <option value="<?=$i?>" <?= $note['semester']==$i ? 'selected':'' ?>><?=$i?></option>
      <?php endfor; ?>
    </select>

    <label>Choose existing subject</label>
    <select name="subject_id">
      <option value="">-- Use manual / keep current --</option>
      <?php foreach($subjects as $s): ?>
        <option value="<?php echo $s['id']; ?>" <?= $note['subject_id'] == $s['id'] ? 'selected':'' ?>>
          <?php echo htmlspecialchars('Sem '.$s['semester'].' — '.$s['name']); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label>Or subject (manual)</label>
    <input type="text" name="subject_text" value="<?php echo htmlspecialchars($note['subject']); ?>">

    <label>Replace file (optional)</label>
    <input type="file" name="notes_file">
    <div class="small">Current file: <a href="../<?php echo htmlspecialchars($note['file_path']); ?>" target="_blank"><?php echo htmlspecialchars($note['original_filename']); ?></a></div>

    <button class="btn" name="save" type="submit">Save Changes</button>
    <a href="upload_notes.php" style="margin-left:8px;">Back to Notes</a>
  </form>
</div>
</body>
</html>
