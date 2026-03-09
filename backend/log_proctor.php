<?php
session_start();
require_once("includes/config.php");

if(!isset($_SESSION['user_id'])) exit;

$data = json_decode(file_get_contents("php://input"), true);
$event = $data['event'] ?? '';

$stmt = $conn->prepare(
    "INSERT INTO proctoring_logs (user_id, course_id, event_type)
     VALUES (?,?,?)"
);
$stmt->bind_param("iis", $_SESSION['user_id'], $data['course_id'], $event);
$stmt->execute();
