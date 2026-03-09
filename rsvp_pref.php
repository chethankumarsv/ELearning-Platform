<?php
// /elearningplatform/rsvp_pref.php
session_start();
require_once __DIR__ . '/includes/config.php';
header('Content-Type: application/json');

function fail($m,$c=400){ http_response_code($c); echo json_encode(['ok'=>false,'message'=>$m]); exit; }

if (empty($_SESSION['user_id'])) fail("Please login.", 401);
if (empty($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['csrf_rsvp'] ?? '')) fail("Invalid CSRF.");

$eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$opt     = isset($_POST['opt_in']) ? (int)($_POST['opt_in'] ? 1 : 0) : 1;
$userId  = (int)$_SESSION['user_id'];

if ($eventId <= 0) fail("Bad event.");

$stmt = $conn->prepare("UPDATE event_rsvps SET reminder_opt_in=? WHERE event_id=? AND user_id=? AND status='going'");
$stmt->bind_param("iii", $opt, $eventId, $userId);
$stmt->execute();
if ($stmt->affected_rows >= 0) {
  echo json_encode(['ok'=>true, 'opt_in'=>$opt]);
} else {
  fail("Unable to update preference.");
}
$stmt->close();
