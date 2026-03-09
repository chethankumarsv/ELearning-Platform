<?php
session_start();
require_once "includes/config.php";
require_once "vendor/autoload.php";

use Dompdf\Dompdf;

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

$user_id   = (int)$_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$status    = $_GET['status'] ?? '';

if ($status !== 'passed') {
    echo "<h2>⚠️ Sorry, you did not pass. Certificate cannot be generated.</h2>";
    exit();
}

// ---- Fetch course title
$stmt = $conn->prepare("SELECT title FROM courses WHERE id=? LIMIT 1");
if (!$stmt) die("SQL error in courses query: " . htmlspecialchars($conn->error));
$stmt->bind_param("i", $course_id);
$stmt->execute();

$course = null;
if (method_exists($stmt, 'get_result')) {
    $res = $stmt->get_result();
    $course = $res ? $res->fetch_assoc() : null;
} else {
    $stmt->store_result();
    $stmt->bind_result($title);
    if ($stmt->num_rows === 1 && $stmt->fetch()) $course = ['title' => $title];
}
$stmt->close();

if (!$course) die("Invalid course or course not found.");

// ---- Get student name (prefer session; otherwise query)
$student_name = $_SESSION['user_name'] ?? null;
if (!$student_name) {
    $stmt = $conn->prepare("SELECT username FROM users WHERE id=? LIMIT 1");
    if (!$stmt) die("SQL error in users query: " . htmlspecialchars($conn->error));
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $user = null;
    if (method_exists($stmt, 'get_result')) {
        $res = $stmt->get_result();
        $user = $res ? $res->fetch_assoc() : null;
    } else {
        $stmt->store_result();
        $stmt->bind_result($uname);
        if ($stmt->num_rows === 1 && $stmt->fetch()) $user = ['username' => $uname];
    }
    $stmt->close();

    if (!$user) die("Invalid user or user not found.");
    $student_name = $user['username'];
}

// ---- Build certificate
$course_name = $course['title'];
$date        = date("d M Y");

$dompdf = new Dompdf();
$html = "
<div style='border:10px solid #2c3e50; padding:40px; text-align:center; font-family:Arial;'>
    <h1 style='color:#2c3e50;'>Certificate of Completion</h1>
    <p style='font-size:18px;'>This is proudly presented to</p>
    <h2 style='color:#27ae60;'>" . htmlspecialchars($student_name) . "</h2>
    <p style='font-size:18px;'>for successfully completing the course</p>
    <h3 style='color:#2980b9;'>" . htmlspecialchars($course_name) . "</h3>
    <p style='font-size:16px;'>Issued on " . htmlspecialchars($date) . "</p>
    <br><br>
    <p>_________________________<br>Authorized Signature</p>
</div>
";
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// ---- Ensure /certificates exists and save
$certificateDir = __DIR__ . "/certificates/";
if (!is_dir($certificateDir)) {
    mkdir($certificateDir, 0777, true);
}
$filename_safe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $student_name . "_" . $course_name);
$filePath = "certificates/" . $filename_safe . ".pdf"; // relative for DB
$fullPath = __DIR__ . "/" . $filePath;
file_put_contents($fullPath, $dompdf->output());

// ---- Upsert into certificates table
$check = $conn->prepare("SELECT id FROM certificates WHERE user_id=? AND course_id=?");
if (!$check) die("SQL error in check query: " . htmlspecialchars($conn->error));
$check->bind_param("ii", $user_id, $course_id);
$check->execute();
$has = method_exists($check,'get_result') ? $check->get_result()->num_rows > 0 : ($check->store_result() || true) && $check->num_rows > 0;
$check->close();

if ($has) {
    $upd = $conn->prepare("UPDATE certificates SET file_path=?, issued_at=NOW() WHERE user_id=? AND course_id=?");
    if (!$upd) die("SQL error in update query: " . htmlspecialchars($conn->error));
    $upd->bind_param("sii", $filePath, $user_id, $course_id);
    $upd->execute();
    $upd->close();
} else {
    $ins = $conn->prepare("INSERT INTO certificates (user_id, course_id, file_path, issued_at) VALUES (?,?,?,NOW())");
    if (!$ins) die("SQL error in insert query: " . htmlspecialchars($conn->error));
    $ins->bind_param("iis", $user_id, $course_id, $filePath);
    $ins->execute();
    $ins->close();
}

// ---- Download
$dompdf->stream($filename_safe . ".pdf", ["Attachment" => true]);
