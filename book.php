<?php
session_start(); require __DIR__.'/../../includes/config.php';
$uid = $_SESSION['user_id'] ?? 0; if(!$uid){ http_response_code(401); echo json_encode(['error'=>'Login required']); exit; }
$slot = (int)($_POST['slot_id'] ?? 0);
$stmt = $conn->prepare("UPDATE mentor_slots SET booked_by=? WHERE id=? AND booked_by IS NULL");
$stmt->bind_param('ii',$uid,$slot);
$stmt->execute();
if($stmt->affected_rows===1){ echo json_encode(['ok'=>true]); }
else { http_response_code(409); echo json_encode(['ok'=>false,'error'=>'Slot already booked or invalid']); }
