<?php
// /elearningplatform/comment_add.php
session_start();
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

function fail($m,$c=400){ http_response_code($c); echo json_encode(['ok'=>false,'message'=>$m]); exit; }
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if (function_exists('date_default_timezone_set')) date_default_timezone_set('Asia/Kolkata');

if (empty($_SESSION['user_id'])) fail("Please login to comment.", 401);
$userId = (int)$_SESSION['user_id'];
$userName = $_SESSION['name'] ?? 'Student';
$userRole = $_SESSION['role'] ?? 'student';

// CSRF
if (empty($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['csrf_cmt'] ?? '')) fail("Invalid CSRF token.");

// Inputs
$eventId  = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$parentId = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;
$content  = trim($_POST['content'] ?? '');

// Validate
if ($eventId <= 0) fail("Bad event.");
if ($content === '' || mb_strlen($content) < 2) fail("Comment is too short.");
if (mb_strlen($content) > 2000) fail("Comment too long (max 2000 chars).");

// Ensure event exists
$es = $conn->prepare("SELECT id FROM events WHERE id=? LIMIT 1");
$es->bind_param("i",$eventId); $es->execute();
if (!$es->get_result()->num_rows) fail("Event not found.", 404);
$es->close();

// Optional: ensure parent belongs to same event
if ($parentId) {
  $ps = $conn->prepare("SELECT id FROM event_comments WHERE id=? AND event_id=? LIMIT 1");
  $ps->bind_param("ii",$parentId,$eventId); $ps->execute();
  if (!$ps->get_result()->num_rows) fail("Invalid reply target.");
  $ps->close();
}

// Simple rate-limit: one comment every 10 seconds per user
if (!empty($_SESSION['last_comment_at']) && (time() - (int)$_SESSION['last_comment_at'] < 10)) {
  fail("You're commenting too fast. Please wait a moment.");
}
$_SESSION['last_comment_at'] = time();

// Auto-approve for admins; pending for others (change if you prefer)
$AUTO_APPROVE = ($userRole === 'admin'); // set to true to auto-approve everyone
$status = $AUTO_APPROVE ? 'visible' : 'pending';

$stmt = $conn->prepare("INSERT INTO event_comments (event_id, user_id, parent_id, content, status) VALUES (?,?,?,?,?)");
$stmt->bind_param("iiiss", $eventId, $userId, $parentId, $content, $status);
$stmt->execute();
$newId = $stmt->insert_id;
$stmt->close();

if ($status === 'visible') {
  // Return minimal HTML snippet so the client can inject it immediately
  $html  = '<div class="cmt" data-id="'.(int)$newId.'">';
  $html .=   '<div class="cmtHead"><b>'.h($userName).'</b> <span class="meta">just now</span></div>';
  $html .=   '<div class="cmtBody">'.nl2br(h($content)).'</div>';
  $html .=   '<div class="cmtActions"><button class="btn-light replyBtn" data-id="'.(int)$newId.'">Reply</button></div>';
  $html .= '</div>';
  echo json_encode(['ok'=>true,'status'=>'visible','html'=>$html,'id'=>$newId]);
} else {
  echo json_encode(['ok'=>true,'status'=>'pending','message'=>'Your comment is awaiting moderation.']);
}
