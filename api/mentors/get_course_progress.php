<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false]);
  exit;
}

$user_id = $_SESSION['user_id'];
$course_id = (int)($_GET['course_id'] ?? 0);

$sql = "
SELECT
  CASE
    WHEN COUNT(cl.id) = 0 THEN 0
    ELSE ROUND(COUNT(lp.id) / COUNT(cl.id) * 100, 2)
  END AS progress
FROM course_lessons cl
LEFT JOIN lesson_progress lp
  ON cl.id = lp.lesson_id
 AND lp.user_id = ?
WHERE cl.course_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();

$res = $stmt->get_result()->fetch_assoc();
echo json_encode(['success' => true, 'progress' => $res['progress'] ?? 0]);
