<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success'=>false,'msg'=>'Login required']);
  exit;
}

$user_id = $_SESSION['user_id'];
$course_id = (int)($_POST['course_id'] ?? 0);

$stmt = $conn->prepare(
  "INSERT IGNORE INTO course_enrollments (user_id, course_id) VALUES (?, ?)"
);
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();

echo json_encode(['success'=>true]);
