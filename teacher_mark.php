<?php
// teacher_mark.php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/att_helper.php';

if(!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'teacher'){
    http_response_code(403); json_response(['ok'=>false,'error'=>'forbidden']); exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    json_response(['ok'=>false,'error'=>'POST only']);
}

$session_id = intval($_POST['session_id'] ?? 0);
$student_id = intval($_POST['user_id'] ?? 0);
$status = $_POST['status'] ?? 'present';
$remarks = $_POST['remarks'] ?? null;

if(!$session_id || !$student_id){
    json_response(['ok'=>false,'error'=>'missing params']);
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$method = 'manual';

// Upsert (insert or update)
$q = "INSERT INTO attendance_records (session_id,user_id,method,ip_address,status,remarks)
      VALUES (?,?,?,?,?,?)
      ON DUPLICATE KEY UPDATE status=VALUES(status), remarks=VALUES(remarks), method=VALUES(method), marked_at=CURRENT_TIMESTAMP";

$ins = $conn->prepare($q);
$ins->bind_param("iissss", $session_id, $student_id, $method, $ip, $status, $remarks);
if($ins->execute()){
    json_response(['ok'=>true]);
} else {
    json_response(['ok'=>false,'error'=>'db error: '.$conn->error]);
}
