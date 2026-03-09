<?php
// mark_attendance.php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/att_helper.php';

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$method = 'student_self';
$lat = isset($_REQUEST['lat']) ? floatval($_REQUEST['lat']) : null;
$lng = isset($_REQUEST['lng']) ? floatval($_REQUEST['lng']) : null;
$token = $_GET['token'] ?? $_POST['token'] ?? null;
$session_id = isset($_POST['session_id']) ? intval($_POST['session_id']) : null;

// logged in user required
$user_id = $_SESSION['user_id'] ?? null;
if(!$user_id){
    // if token present and you want anonymous flow, you can accept reg_no mapping here
    json_response(['ok'=>false,'error'=>'Not logged in. Please login to mark attendance.']);
}

// if token provided, resolve session
if($token){
    $stmt = $conn->prepare("SELECT id, session_date, start_time FROM attendance_sessions WHERE qr_token = ? LIMIT 1");
    $stmt->bind_param("s", $token); $stmt->execute(); $res = $stmt->get_result();
    if($row = $res->fetch_assoc()){
        $session_id = intval($row['id']);
        $method = 'qr';
    } else {
        json_response(['ok'=>false,'error'=>'Invalid token']);
    }
}

if(!$session_id){
    json_response(['ok'=>false,'error'=>'No session specified']);
}

// prevent duplicate
$chk = $conn->prepare("SELECT id FROM attendance_records WHERE session_id = ? AND user_id = ? LIMIT 1");
$chk->bind_param("ii", $session_id, $user_id); $chk->execute(); $chk->store_result();
if($chk->num_rows > 0){
    json_response(['ok'=>false,'message'=>'Already marked']);
}

// Optional: check time window and set remark if outside
$remarks = null;
$si = $conn->prepare("SELECT session_date, start_time FROM attendance_sessions WHERE id = ? LIMIT 1");
$si->bind_param("i", $session_id); $si->execute(); $sres = $si->get_result();
if($sinfo = $sres->fetch_assoc()){
    $startDT = new DateTime($sinfo['session_date'] . ' ' . ($sinfo['start_time'] ?: '00:00:00'));
    $now = new DateTime('now');
    $diff = $now->getTimestamp() - $startDT->getTimestamp();
    // allow -600 .. +1800 seconds by policy — change if needed
    if($diff < -600 || $diff > 1800){
        $remarks = 'checked_out_of_window';
    }
}

// insert record
$ins = $conn->prepare("INSERT INTO attendance_records (session_id, user_id, method, ip_address, latitude, longitude, remarks) VALUES (?,?,?,?,?,?,?)");
$ins->bind_param("iissdds", $session_id, $user_id, $method, $ip, $lat, $lng, $remarks);
if($ins->execute()){
    json_response(['ok'=>true,'message'=>'Attendance marked']);
} else {
    json_response(['ok'=>false,'error'=>'DB error: '.$conn->error]);
}
