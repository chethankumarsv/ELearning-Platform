<?php
session_start();
require_once "includes/config.php";

$user_id   = $_SESSION['user_id'];
$video_id  = intval($_POST['video_id']);
$course_id = intval($_POST['course_id']);

$stmt = $conn->prepare("
INSERT INTO user_video_progress (user_id, course_id, video_id, watched, watched_at)
VALUES (?, ?, ?, 1, NOW())
ON DUPLICATE KEY UPDATE watched = 1, watched_at = NOW()
");
$stmt->bind_param("iii", $user_id, $course_id, $video_id);
$stmt->execute();

header("Location: course_videos.php?course_id=".$course_id);
exit;
