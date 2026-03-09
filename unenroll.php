<?php
session_start();
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['course_id'])) {
    die("Invalid request.");
}

$user_id = intval($_SESSION['user_id']);
$course_id = intval($_GET['course_id']);

$stmt = $conn->prepare("DELETE FROM enrollments WHERE user_id = ? AND course_id = ?");
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
$stmt->close();

header("Location: my_courses.php");
exit;
?>
