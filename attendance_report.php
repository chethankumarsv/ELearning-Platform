<?php
// attendance_report.php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/att_helper.php';

if(!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['teacher','admin'])){
    http_response_code(403); echo "Forbidden"; exit;
}

$course_id = intval($_GET['course_id'] ?? 0);
$date_from = $_GET['from'] ?? null;
$date_to = $_GET['to'] ?? null;
$export = ($_GET['export'] ?? '') === 'csv';

if(!$course_id || !$date_from || !$date_to){
    json_response(['ok'=>false,'error'=>'missing params (course_id, from, to)']);
}

// total sessions in range
$stmt = $conn->prepare("SELECT COUNT(*) AS total_sessions FROM attendance_sessions WHERE course_id = ? AND session_date BETWEEN ? AND ?");
$stmt->bind_param("iss", $course_id, $date_from, $date_to);
$stmt->execute(); $total_sessions = intval($stmt->get_result()->fetch_assoc()['total_sessions']);

// per-student present count — list students from users table (adapt name & columns)
$q = "SELECT u.id AS user_id, u.name AS student_name, COUNT(ar.id) AS present_count
      FROM users u
      LEFT JOIN attendance_records ar ON ar.user_id = u.id
      LEFT JOIN attendance_sessions s ON ar.session_id = s.id
      WHERE s.course_id = ? AND s.session_date BETWEEN ? AND ?
      GROUP BY u.id";
$stmt2 = $conn->prepare($q);
$stmt2->bind_param("iss", $course_id, $date_from, $date_to);
$stmt2->execute();
$res = $stmt2->get_result();

$rows = [];
while($r = $res->fetch_assoc()){
    $percent = $total_sessions > 0 ? round(($r['present_count'] / $total_sessions) * 100, 2) : 0;
    $rows[] = [
        'user_id' => $r['user_id'],
        'student_name' => $r['student_name'],
        'present_count' => intval($r['present_count']),
        'total_sessions' => $total_sessions,
        'percent' => $percent
    ];
}

if($export){
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_report.csv"');
    $out = fopen('php://output','w');
    fputcsv($out, ['user_id','student_name','present_count','total_sessions','percent']);
    foreach($rows as $row) fputcsv($out, $row);
    fclose($out);
    exit;
}

json_response(['ok'=>true,'data'=>$rows]);
