<?php
// scripts/notify_low_attendance.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/att_helper.php';

// settings
$course_id = 1; // or loop all courses
$from = date('Y-01-01');
$to = date('Y-m-d');
$threshold = 60; // percent

// reuse attendance_report.php logic inline for one course
$stmt = $conn->prepare("SELECT COUNT(*) AS total_sessions FROM attendance_sessions WHERE course_id = ? AND session_date BETWEEN ? AND ?");
$stmt->bind_param("iss", $course_id, $from, $to); $stmt->execute(); $total_sessions = intval($stmt->get_result()->fetch_assoc()['total_sessions']);
if($total_sessions == 0) exit;

$q = "SELECT u.id AS user_id, u.name AS student_name, u.email, COUNT(ar.id) AS present_count
      FROM users u
      LEFT JOIN attendance_records ar ON ar.user_id = u.id
      LEFT JOIN attendance_sessions s ON ar.session_id = s.id
      WHERE s.course_id = ? AND s.session_date BETWEEN ? AND ?
      GROUP BY u.id";
$stmt2 = $conn->prepare($q);
$stmt2->bind_param("iss", $course_id, $from, $to);
$stmt2->execute();
$res = $stmt2->get_result();

while($r = $res->fetch_assoc()){
    $percent = round(($r['present_count'] / $total_sessions) * 100,2);
    if($percent < $threshold){
        // send email - use mail() or PHPMailer - simple mail example:
        $to = $r['email'];
        $subject = "Low attendance alert";
        $message = "Hi {$r['student_name']}, your attendance for course {$course_id} is {$percent}%. Please improve.";
        // @mail($to, $subject, $message); // uncomment after testing mail setup
        echo "Would email {$to} — attendance {$percent}%\n";
    }
}
