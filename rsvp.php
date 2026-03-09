<?php
// /elearningplatform/rsvp.php
session_start();
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

function fail($msg, $code=400){ http_response_code($code); echo json_encode(['ok'=>false,'message'=>$msg]); exit; }

if (empty($_SESSION['user_id'])) fail("Please login to RSVP.", 401);
$userId = (int)$_SESSION['user_id'];

// CSRF check
if (empty($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['csrf_rsvp'] ?? '')) fail("Invalid CSRF token.");

// Validate inputs
$action  = $_POST['action'] ?? '';
$eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
if (!$eventId) fail("Invalid event.");

$eventStmt = $conn->prepare("SELECT id, title, event_date FROM events WHERE id=? LIMIT 1");
$eventStmt->bind_param("i", $eventId);
$eventStmt->execute();
$event = $eventStmt->get_result()->fetch_assoc();
$eventStmt->close();
if (!$event) fail("Event not found.", 404);

// Past event protection
if (strtotime($event['event_date']) < time() && $action === 'register') fail("This event has already happened.", 400);

if ($action === 'register') {
  // Insert or update to 'going'
  $stmt = $conn->prepare("INSERT INTO event_rsvps (event_id, user_id, status) VALUES (?,?, 'going')
                          ON DUPLICATE KEY UPDATE status='going', updated_at=NOW()");
  $stmt->bind_param("ii", $eventId, $userId);
  $stmt->execute();
  $stmt->close();
  echo json_encode(['ok'=>true, 'message'=>"Registered"]);
  exit;
}

if ($action === 'cancel') {
  $stmt = $conn->prepare("UPDATE event_rsvps SET status='cancelled', updated_at=NOW()
                          WHERE event_id=? AND user_id=?");
  $stmt->bind_param("ii", $eventId, $userId);
  $stmt->execute();
  $stmt->close();
  echo json_encode(['ok'=>true, 'message'=>"Registration cancelled"]);
  exit;
}

fail("Unknown action.");
