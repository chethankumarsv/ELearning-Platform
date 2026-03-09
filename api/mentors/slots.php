<?php
session_start(); require __DIR__.'/../../includes/config.php';
$mid = (int)($_GET['mentor_id'] ?? 0);
$stmt = $conn->prepare("SELECT id,start_at,end_at FROM mentor_slots WHERE mentor_id=? AND booked_by IS NULL AND start_at >= NOW() ORDER BY start_at ASC LIMIT 50");
$stmt->bind_param('i',$mid); $stmt->execute();
$res = $stmt->get_result(); $out=[]; while($r=$res->fetch_assoc()) $out[]=$r;
header('Content-Type: application/json'); echo json_encode($out);
