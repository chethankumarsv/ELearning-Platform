<?php
// view_notes.php  (save in project root: /elearningplatform/view_notes.php)
require_once __DIR__ . '/includes/config.php';

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// read filters
$selectedSem = isset($_GET['semester']) ? intval($_GET['semester']) : 0;
$selectedSub = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;
$search      = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

// detect whether notes table has subject_id column
$hasSubjectId = false;
$colCheck = $conn->query("SHOW COLUMNS FROM notes LIKE 'subject_id'");
if ($colCheck && $colCheck->num_rows > 0) $hasSubjectId = true;

// fetch subjects only if subjects table exists
$subjects = [];
$subjectsExist = false;
$subCheck = $conn->query("SHOW TABLES LIKE 'subjects'");
if ($subCheck && $subCheck->num_rows > 0) {
    $subjectsExist = true;
    $subStmt = $conn->prepare("SELECT id, semester, name FROM subjects WHERE (? = 0 OR semester = ?) ORDER BY semester, name");
    if ($subStmt) {
        $subStmt->bind_param('ii', $selectedSem, $selectedSem);
        $subStmt->execute();
        $subRes = $subStmt->get_result();
        if ($subRes) $subjects = $subRes->fetch_all(MYSQLI_ASSOC);
        $subStmt->close();
    } else {
        // fallback - simple query
        $q = "SELECT id, semester, name FROM subjects ORDER BY semester, name";
        $res = $conn->query($q);
        if ($res) $subjects = $res->fetch_all(MYSQLI_ASSOC);
    }
}

// Build notes query dynamically and safely
$where = [];
$params = [];
$types = '';

if ($selectedSem >= 1 && $selectedSem <= 8) {
    $where[] = "semester = ?";
    $types .= 'i';
    $params[] = $selectedSem;
}

if ($hasSubjectId) {
    if ($selectedSub > 0) {
        $where[] = "subject_id = ?";
        $types .= 'i';
        $params[] = $selectedSub;
    }
    if ($search !== '') {
        // text fallback search
        $where[] = "subject LIKE ?";
        $types .= 's';
        $params[] = "%$search%";
    }
} else {
    // notes table stores subject as text column
    if ($search !== '') {
        $where[] = "subject LIKE ?";
        $types .= 's';
        $params[] = "%$search%";
    }
    if ($selectedSub > 0 && $subjectsExist) {
        // map selected subject id to its name and filter by name (best-effort)
        $foundName = '';
        foreach ($subjects as $s) {
            if (intval($s['id']) === $selectedSub) { $foundName = $s['name']; break; }
        }
        if ($foundName !== '') {
            $where[] = "subject = ?";
            $types .= 's';
            $params[] = $foundName;
        }
    }
}

// build SQL
$sql = "SELECT id, semester, ";
if ($hasSubjectId) {
    // prefer to show subject text if exists in notes
    $sql .= "COALESCE(subject, '') AS subject, subject_id, original_filename, file_path, file_type, file_size, version, uploaded_on FROM notes";
} else {
    $sql .= "subject, original_filename, file_path, file_type, file_size, version, uploaded_on FROM notes";
}

if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY semester, subject, uploaded_on DESC";

$notes = [];
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        // bind params dynamically (initialize the binder array explicitly)
        $bind_names = [];
        $bind_names[] = $types;
        for ($i=0; $i<count($params); $i++) {
            $bind_name = 'bind' . $i;
            $$bind_name = $params[$i];
            $bind_names[] = &$$bind_name;
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) $notes = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        // fallback to query without params (shouldn't happen)
        $res = $conn->query($sql);
        if ($res) $notes = $res->fetch_all(MYSQLI_ASSOC);
    }
} else {
    // no params - simpler path
    $res = $conn->query($sql);
    if ($res) $notes = $res->fetch_all(MYSQLI_ASSOC);
}

// If we have subject_id values and subjects table exists, build an id->name map
$subjectMap = [];
if ($subjectsExist && !empty($subjects)) {
    foreach ($subjects as $s) $subjectMap[intval($s['id'])] = $s['name'];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Notes - E-Learning</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#f8fafc;color:#111;margin:0;padding:28px}
    .wrap{max-width:1100px;margin:0 auto}
    h1{margin:0 0 14px}
    form.filters{display:flex;gap:8px;margin-bottom:14px;align-items:center;flex-wrap:wrap}
    input[type="text"], select{padding:8px;border:1px solid #ddd;border-radius:6px}
    button{padding:8px 12px;background:#0ea5e9;color:#fff;border:0;border-radius:6px;cursor:pointer}
    table{width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 8px 24px rgba(11,14,29,0.06)}
    th,td{padding:12px;border-bottom:1px solid #f1f5f9;text-align:left}
    th{background:#f1f5f9}
    a.download{color:#2563eb;text-decoration:none;font-weight:600}
    .meta{font-size:13px;color:#6b7280}
    .empty{padding:24px;color:#6b7280}
    .controls { display:flex; gap:12px; align-items:flex-end; }
    @media(max-width:820px){ form.filters{flex-direction:column;align-items:stretch} .controls{flex-direction:column;align-items:stretch} }
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Notes & Materials</h1>

    <form class="filters" method="get" action="">
      <div>
        <label>Semester</label><br>
        <select name="semester" onchange="this.form.submit()">
          <option value="0">All</option>
          <?php for($i=1;$i<=8;$i++): ?>
            <option value="<?php echo $i?>" <?php if($selectedSem===$i) echo 'selected'; ?>><?php echo $i?></option>
          <?php endfor; ?>
        </select>
      </div>

      <?php if ($subjectsExist): ?>
      <div>
        <label>Subject</label><br>
        <select name="subject_id" onchange="this.form.submit()">
          <option value="0">All Subjects</option>
          <?php foreach($subjects as $s): ?>
            <option value="<?php echo intval($s['id']); ?>" <?php echo $selectedSub == intval($s['id']) ? 'selected':''; ?>>
              <?php echo h($s['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <div style="flex:1">
        <label>Search</label><br>
        <input type="text" name="q" placeholder="Search subject or filename" value="<?php echo h($search); ?>" style="width:100%;">
      </div>

      <div>
        <button type="submit">Filter</button>
        <a href="view_notes.php" style="display:inline-block;margin-left:8px;padding:8px 10px;background:#e6eef8;color:#063; border-radius:6px;text-decoration:none">Reset</a>
      </div>

      <div style="margin-left:auto;">
        <a href="admin/login.php" style="color:#374151;text-decoration:underline">Admin? Login</a>
      </div>
    </form>

    <?php if (empty($notes)): ?>
      <div class="empty">No notes found.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Semester</th>
            <th>Subject</th>
            <th>File</th>
            <th>Size</th>
            <th>Version</th>
            <th>Uploaded On</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($notes as $n): ?>
            <tr>
              <td><?php echo h($n['id']); ?></td>
              <td><?php echo h($n['semester']); ?></td>
              <td>
                <?php
                  $subjectLabel = '';
                  if ($hasSubjectId && !empty($n['subject_id']) && isset($subjectMap[intval($n['subject_id'])])) {
                      $subjectLabel = $subjectMap[intval($n['subject_id'])];
                  } else {
                      $subjectLabel = $n['subject'] ?? '';
                  }
                ?>
                <?php echo h($subjectLabel); ?>
              </td>
              <td>
                <?php
                  $path = ltrim($n['file_path'], '/');
                  $name = $n['original_filename'] ?? basename($path);
                ?>
                <a class="download" href="<?php echo h($path); ?>" target="_blank" rel="noopener noreferrer"><?php echo h($name); ?></a>
              </td>
              <td class="meta"><?php echo isset($n['file_size']) ? (round($n['file_size']/1024,1) . ' KB') : '-' ?></td>
              <td class="meta"><?php echo h($n['version'] ?? 1); ?></td>
              <td class="meta"><?php echo h($n['uploaded_on'] ?? ''); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

  </div>
</body>
</html>
