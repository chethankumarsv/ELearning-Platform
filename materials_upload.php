<?php
// materials_upload.php - secure uploader (admin-only, CSRF, mime checks, prepared statements)
session_start();
require_once __DIR__ . '/includes/config.php';

// ---------- CONFIG ----------
$MAX_FILE_BYTES = 50 * 1024 * 1024; // 50 MB
$UPLOAD_DIR = __DIR__ . '/uploads';
$ALLOWED_EXT = [
    'pdf'  => 'application/pdf',
    'mp4'  => 'video/mp4',
    'webm' => 'video/webm',
    'ogg'  => 'video/ogg',
    'mp3'  => 'audio/mpeg',
    'wav'  => 'audio/x-wav',
    'zip'  => 'application/zip',
    'txt'  => 'text/plain'
];
// ----------------------------

// Basic admin check - replace with your own auth system
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    http_response_code(403);
    echo "Forbidden - admin only";
    exit;
}

// Ensure upload dir exists and writable
if (!is_dir($UPLOAD_DIR)) {
    if (!mkdir($UPLOAD_DIR, 0755, true)) {
        die("Failed to create upload directory.");
    }
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$csrf_token = $_SESSION['csrf_token'];

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $posted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf_token, $posted_token)) {
        $errors[] = "Invalid CSRF token. Please reload and try again.";
    }

    // Basic inputs
    $subject = trim($_POST['subject'] ?? '');
    $title   = trim($_POST['title'] ?? '');
    $provided_type = trim($_POST['type'] ?? '');
    $remote_url = trim($_POST['file_url'] ?? '');

    if ($subject === '' || $title === '') {
        $errors[] = "Subject and Title are required.";
    }

    $final_file_url = '';
    $final_type = '';

    // If a file uploaded use it otherwise use remote URL
    if (!empty($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $f = $_FILES['file'];

        if ($f['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Upload error (code " . intval($f['error']) . ").";
        } else {
            if ($f['size'] > $MAX_FILE_BYTES) {
                $errors[] = "File exceeds maximum allowed size of 50 MB.";
            } else {
                // Determine MIME type using finfo
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($f['tmp_name']) ?: mime_content_type($f['tmp_name'] ?? '');
                // sanitize original name
                $origName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($f['name']));
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

                // validate extension + mime
                if (!array_key_exists($ext, $ALLOWED_EXT)) {
                    $errors[] = "File extension .$ext is not allowed.";
                } else {
                    $expectedMime = $ALLOWED_EXT[$ext];
                    // some video mimes may differ; allow general startsWith check for video/audio
                    $mimeOk = false;
                    if ($mime === $expectedMime) $mimeOk = true;
                    elseif (strpos($expectedMime, 'video/') === 0 && strpos($mime, 'video/') === 0) $mimeOk = true;
                    elseif (strpos($expectedMime, 'audio/') === 0 && strpos($mime, 'audio/') === 0) $mimeOk = true;
                    elseif ($ext === 'pdf' && $mime === 'application/pdf') $mimeOk = true;

                    if (!$mimeOk) {
                        $errors[] = "Uploaded file mime-type ($mime) does not match expected ($expectedMime).";
                    } else {
                        // generate safe unique name
                        $basename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                        $targetPath = $UPLOAD_DIR . '/' . $basename;

                        if (!move_uploaded_file($f['tmp_name'], $targetPath)) {
                            $errors[] = "Failed to move uploaded file.";
                        } else {
                            // set safe permissions
                            @chmod($targetPath, 0644);
                            // store web-accessible relative URL
                            $final_file_url = 'uploads/' . $basename;
                            $final_type = $provided_type ?: $ext;
                        }
                    }
                }
            }
        }
    } else {
        // no uploaded file, require remote URL
        if ($remote_url === '') {
            $errors[] = "Provide either an upload or a remote file URL.";
        } else {
            // validate remote URL pattern
            if (!preg_match('#^https?://#i', $remote_url)) {
                $errors[] = "Remote URL must start with http:// or https://";
            } else {
                // infer extension/type from URL
                $pathExt = strtolower(pathinfo(parse_url($remote_url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                if ($pathExt && array_key_exists($pathExt, $ALLOWED_EXT)) {
                    $final_type = $provided_type ?: $pathExt;
                } else {
                    // treat as link if extension unknown
                    $final_type = $provided_type ?: 'link';
                }
                $final_file_url = $remote_url;
            }
        }
    }

    // If no errors, insert into DB (use prepared statements). Handle DB with/without 'type' column.
    if (empty($errors)) {
        // Check if 'type' column exists
        $has_type = false;
        $res = $conn->query("SHOW COLUMNS FROM materials LIKE 'type'");
        if ($res && $res->num_rows > 0) $has_type = true;

        if ($has_type) {
            $stmt = $conn->prepare("INSERT INTO materials (subject, title, file_url, `type`, created_at) VALUES (?, ?, ?, ?, NOW())");
            if (!$stmt) { $errors[] = "DB prepare failed: " . $conn->error; }
            else {
                $stmt->bind_param('ssss', $subject, $title, $final_file_url, $final_type);
                if (!$stmt->execute()) $errors[] = "DB insert failed: " . $stmt->error;
                else $success = "Material uploaded successfully.";
                $stmt->close();
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO materials (subject, title, file_url, created_at) VALUES (?, ?, ?, NOW())");
            if (!$stmt) { $errors[] = "DB prepare failed: " . $conn->error; }
            else {
                $stmt->bind_param('sss', $subject, $title, $final_file_url);
                if (!$stmt->execute()) $errors[] = "DB insert failed: " . $stmt->error;
                else $success = "Material uploaded successfully.";
                $stmt->close();
            }
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Upload Material (Admin)</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body{font-family:Poppins,system-ui;margin:22px;background:#f7fafc;color:#0b1220}
    .wrap{max-width:820px;margin:0 auto;background:#fff;padding:20px;border-radius:10px;box-shadow:0 8px 28px rgba(2,6,23,.06)}
    label{display:block;margin-top:10px;font-weight:600}
    input[type="text"], input[type="url"], select{width:100%;padding:10px;border-radius:8px;border:1px solid #e6edf3}
    input[type="file"]{margin-top:6px}
    button{margin-top:12px;padding:10px 16px;border-radius:10px;background:linear-gradient(90deg,#ff8a3d,#ff5c7c);color:#fff;border:0;font-weight:700}
    .err{color:#b91c1c;background:#fff0f0;padding:10px;border-radius:8px;margin-top:12px}
    .ok{color:#065f46;background:#ecfdf5;padding:10px;border-radius:8px;margin-top:12px}
  </style>
</head>
<body>
  <div class="wrap">
    <h2>Upload Study Material (Admin)</h2>

    <?php if (!empty($errors)): ?>
      <div class="err"><strong>Errors:</strong><ul><?php foreach($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="ok"><?=htmlspecialchars($success)?> <a href="materials.php">View materials</a></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

      <label>Subject
        <input type="text" name="subject" required value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>">
      </label>

      <label>Title
        <input type="text" name="title" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
      </label>

      <label>Upload file (allowed: pdf, mp4, webm, ogg, mp3, wav, zip, txt)
        <input type="file" name="file" accept=".pdf,.mp4,.webm,.ogg,.mp3,.wav,.zip,.txt">
      </label>

      <label>or Remote URL (http/https)
        <input type="url" name="file_url" placeholder="https://example.com/file.pdf" value="<?= htmlspecialchars($_POST['file_url'] ?? '') ?>">
      </label>

      <label>Type (optional - leave empty to auto-detect)
        <select name="type">
          <option value="">(auto)</option>
          <option value="pdf">pdf</option>
          <option value="video">video</option>
          <option value="audio">audio</option>
          <option value="link">link</option>
          <option value="archive">archive</option>
          <option value="file">file</option>
        </select>
      </label>

      <button type="submit">Upload</button>
    </form>
  </div>
</body>
</html>
