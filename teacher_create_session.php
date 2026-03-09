<?php
// teacher_create_session.php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/att_helper.php';

// Ensure teacher logged in
if(!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'teacher'){
    http_response_code(403); echo "Forbidden - teacher only"; exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $course_id = intval($_POST['course_id'] ?? 0);
    $session_date = $_POST['session_date'] ?? null; // yyyy-mm-dd
    $start_time = $_POST['start_time'] ?? null;
    $end_time = $_POST['end_time'] ?? null;

    if(!$course_id || !$session_date){
        $error = "Course and date required.";
    } else {
        $teacher_id = intval($_SESSION['user_id']);
        $qr_token = generate_token(40);

        $stmt = $conn->prepare("INSERT INTO attendance_sessions (course_id, teacher_id, session_date, start_time, end_time, qr_token) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("iissss", $course_id, $teacher_id, $session_date, $start_time, $end_time, $qr_token);
        if($stmt->execute()){
            $session_id = $stmt->insert_id;
            // generate QR url (use https on production)
            $host = $_SERVER['HTTP_HOST'];
            $base = rtrim(dirname($_SERVER['REQUEST_URI']), '/\\');
            $qr_url = "https://{$host}{$base}/mark_attendance.php?token=" . urlencode($qr_token);
            $qr_img = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . urlencode($qr_url);
            $success = true;
        } else {
            $error = "DB error: " . $conn->error;
        }
    }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Create Attendance Session</title></head>
<body>
<h2>Create Attendance Session</h2>
<?php if(!empty($error)) echo "<div style='color:red;'>".htmlspecialchars($error)."</div>"; ?>
<?php if(!empty($success)): ?>
    <div style="background:#efe;padding:10px;border:1px solid #cfc;">
        <p>Session created. ID: <?=htmlspecialchars($session_id)?> </p>
        <p>QR Check-in URL: <a href="<?=htmlspecialchars($qr_url)?>" target="_blank"><?=htmlspecialchars($qr_url)?></a></p>
        <img src="<?=htmlspecialchars($qr_img)?>" alt="QR"><br>
        <small>Show this QR to students (projector/print).</small>
    </div>
<?php endif; ?>

<form method="post">
    <label>Course ID: <input name="course_id" required></label><br><br>
    <label>Date: <input name="session_date" type="date" required></label><br><br>
    <label>Start time: <input name="start_time" type="time"></label><br><br>
    <label>End time: <input name="end_time" type="time"></label><br><br>
    <button type="submit">Create & Generate QR</button>
</form>
</body>
</html>
