<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) exit;

$user_id = $_SESSION['user_id'];
$lesson_id = (int)($_POST['lesson_id'] ?? 0);

$stmt = $conn->prepare("
INSERT INTO lesson_progress (user_id, lesson_id, completed, completed_at)
VALUES (?, ?, 1, NOW())
ON DUPLICATE KEY UPDATE
  completed = 1,
  completed_at = NOW()
");
$stmt->bind_param("ii", $user_id, $lesson_id);
$stmt->execute();

echo json_encode(['success'=>true]);
