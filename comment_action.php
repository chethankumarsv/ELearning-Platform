<?php
// /elearningplatform/comment_action.php
session_start();
require_once __DIR__ . '/includes/config.php';
header('Content-Type: application/json');

function fail($m,$c=400){ http_response_code($c); echo json_encode(['ok'=>false,'message'=>$m]); exit; }

if (empty($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'admin')) fail("Forbidden", 403);

if (empty($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['csrf_cmt_admin'] ?? '')) fail("Invalid CSRF.");

$action = $_POST['action'] ?? '';
$cid    = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($cid <= 0) fail("Bad id.");

if ($action === 'approve') {
  $s = $conn->prepare("UPDATE event_comments SET status='visible' WHERE id=? LIMIT 1");
  $s->bind_param("i",$cid); $s->execute(); $s->close();
  echo json_encode(['ok'=>true]); exit;
}
if ($action === 'hide') {
  $s = $conn->prepare("UPDATE event_comments SET status='hidden' WHERE id=? LIMIT 1");
  $s->bind_param("i",$cid); $s->execute(); $s->close();
  echo json_encode(['ok'=>true]); exit;
}
if ($action === 'delete') {
  $s = $conn->prepare("DELETE FROM event_comments WHERE id=? LIMIT 1");
  $s->bind_param("i",$cid); $s->execute(); $s->close();
  echo json_encode(['ok'=>true]); exit;
}

fail("Unknown action.");
