<?php
// viewer.php - safer viewer for local uploads or remote resources
session_start();
require_once __DIR__ . '/includes/config.php';

$file = trim($_GET['file'] ?? '');
if (!$file) { http_response_code(400); echo "Missing file parameter"; exit; }

$UPLOAD_DIR = realpath(__DIR__ . '/uploads');

// Reject obviously unsafe requests
if (strpos($file, "\0") !== false) { http_response_code(400); echo "Invalid file"; exit; }

$is_local = false;
$srcUrl = '';

// If the parameter is a remote URL
if (preg_match('#^https?://#i', $file)) {
    $srcUrl = $file;
} else {
    // treat as uploads/<basename>
    $candidate = realpath(__DIR__ . '/' . ltrim($file, '/'));
    if ($candidate && $UPLOAD_DIR && strpos($candidate, $UPLOAD_DIR) === 0) {
        $is_local = true;
        // produce web-accessible relative path (uploads/filename)
        $srcUrl = 'uploads/' . basename($candidate);
    } else {
        http_response_code(403);
        echo "Access denied or invalid file.";
        exit;
    }
}

// infer type by extension
$ext = strtolower(pathinfo($srcUrl, PATHINFO_EXTENSION));
$is_pdf = in_array($ext, ['pdf']);
$is_video = in_array($ext, ['mp4','webm','ogg','mov']);

// Security headers
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
header("Permissions-Policy: microphone=(), camera=()");
header("Content-Security-Policy: default-src 'self' https:; frame-src 'self' https:; object-src 'none';");

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Viewer</title>
<style>body{margin:0;font-family:system-ui,Arial;background:#071227;color:#fff} .wrap{max-width:1100px;margin:20px auto;padding:12px}</style>
</head>
<body>
<div class="wrap">
  <a href="materials.php" style="color:#9fc5ff;display:inline-block;margin-bottom:12px">&larr; Back to materials</a>

  <?php if ($is_pdf): ?>
    <h2 style="margin:8px 0">PDF Viewer</h2>
    <div style="height:76vh;border-radius:8px;overflow:hidden">
      <iframe src="<?= htmlspecialchars($srcUrl) ?>" style="width:100%;height:100%;border:0"></iframe>
    </div>

  <?php elseif ($is_video): ?>
    <h2 style="margin:8px 0">Video Player</h2>
    <video controls style="width:100%;max-height:76vh;border-radius:8px">
      <source src="<?= htmlspecialchars($srcUrl) ?>">
      Your browser doesn't support the video element. <a href="<?= htmlspecialchars($srcUrl) ?>" target="_blank">Open it</a>.
    </video>

  <?php else: ?>
    <h2 style="margin:8px 0">Open Resource</h2>
    <p><a href="<?= htmlspecialchars($srcUrl) ?>" target="_blank" rel="noopener noreferrer" style="color:#9fc5ff">Open in new tab</a></p>
  <?php endif; ?>
</div>
</body>
</html>
