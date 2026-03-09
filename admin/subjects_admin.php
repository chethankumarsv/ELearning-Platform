<?php
// admin/subjects_admin.php
session_start();
require_once __DIR__ . '/../includes/config.php';
if (!isset($_SESSION['admin_id'])) { header("Location: index.php"); exit; }

$msg = '';
// Add subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $semester = intval($_POST['semester']);
    $name = trim($_POST['name']);
    if ($semester < 1 || $semester > 8 || $name === '') {
        $msg = "Invalid input.";
    } else {
        $stmt = $conn->prepare("INSERT INTO subjects (semester, name) VALUES (?, ?)");
        $stmt->bind_param('is', $semester, $name);
        if ($stmt->execute()) $msg = "Subject added.";
        else $msg = "DB error: " . $conn->error;
    }
}

// Delete subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delId = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
    $stmt->bind_param('i', $delId);
    if ($stmt->execute()) $msg = "Subject deleted.";
    else $msg = "Delete failed: " . $conn->error;
}

// Edit subject handling (inline)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $editId = intval($_POST['edit_id']);
    $semester = intval($_POST['edit_semester']);
    $name = trim($_POST['edit_name']);
    if ($name === '' || $semester < 1 || $semester > 8) {
        $msg = "Invalid input for edit.";
    } else {
        $stmt = $conn->prepare("UPDATE subjects SET semester = ?, name = ? WHERE id = ?");
        $stmt->bind_param('isi', $semester, $name, $editId);
        if ($stmt->execute()) $msg = "Subject updated.";
        else $msg = "Update failed: " . $conn->error;
    }
}

// Fetch subjects
$res = $conn->query("SELECT * FROM subjects ORDER BY semester, name");
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Manage Subjects</title>
<style>
body{font-family:Arial;padding:18px;background:#f6f8fb}
.card{background:#fff;padding:16px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,0.06);max-width:900px;margin:0 auto}
.form-row{display:flex;gap:8px;margin-bottom:8px}
input,select{padding:8px;border:1px solid #ddd;border-radius:6px}
.btn{padding:8px 10px;border-radius:6px;background:#007BFF;color:#fff;border:0;cursor:pointer}
.table{width:100%;border-collapse:collapse;margin-top:12px}
.table th, .table td{padding:8px;border-bottom:1px solid #eee;text-align:left}
.small{font-size:13px;color:#666}
.msg{background:#eef7ff;padding:8px;border-left:4px solid #2b6cb0;margin-bottom:10px}
</style>
</head>
<body>
<div class="card">
  <h2>Subjects — Manage (per semester)</h2>
  <?php if($msg): ?><div class="msg"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

  <form method="post" style="margin-bottom:12px;">
    <div class="form-row">
      <select name="semester" required>
        <option value="">Select Semester</option>
        <?php for($i=1;$i<=8;$i++): ?><option value="<?=$i?>"><?=$i?></option><?php endfor; ?>
      </select>
      <input type="text" name="name" placeholder="Subject name (e.g. Data Structures)" required>
      <button class="btn" name="add_subject" type="submit">Add Subject</button>
    </div>
    <div class="small">Add standard subjects so admin can reuse them when uploading notes.</div>
  </form>

  <table class="table">
    <thead><tr><th>#</th><th>Semester</th><th>Subject</th><th>Actions</th></tr></thead>
    <tbody>
      <?php while($row = $res->fetch_assoc()): ?>
      <tr>
        <td><?php echo $row['id']; ?></td>
        <td><?php echo $row['semester']; ?></td>
        <td>
          <form method="post" style="display:flex;gap:8px;align-items:center;">
            <input type="hidden" name="edit_id" value="<?php echo $row['id']; ?>">
            <select name="edit_semester" style="width:80px">
              <?php for($i=1;$i<=8;$i++): ?>
                <option value="<?=$i?>" <?= $i==$row['semester'] ? 'selected':'' ?>><?=$i?></option>
              <?php endfor; ?>
            </select>
            <input type="text" name="edit_name" value="<?php echo htmlspecialchars($row['name']); ?>" required>
            <button class="btn" type="submit">Save</button>
          </form>
        </td>
        <td>
          <form method="post" onsubmit="return confirm('Delete this subject? This will not delete existing notes');" style="display:inline">
            <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
            <button class="btn" style="background:#e53e3e">Delete</button>
          </form>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <p class="small" style="margin-top:12px;">Tip: Deleting a subject will not delete notes; existing notes keep their text subject.</p>
</div>
</body>
</html>
