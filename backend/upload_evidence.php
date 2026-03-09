<?php
session_start();
require_once("../includes/config.php");

/* ========= AUTH CHECK ========= */
if (!isset($_SESSION['user_id'])) {
    exit();
}

$user_id   = (int)$_SESSION['user_id'];
$course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
$reason    = isset($_POST['reason']) ? trim($_POST['reason']) : 'unknown';

/* ========= BASIC VALIDATION ========= */
if ($course_id <= 0 || !isset($_FILES['image'])) {
    exit();
}

/* ========= UPLOAD DIRECTORY ========= */
$dir = "../uploads/proctoring/";
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

/* ========= FILE NAME ========= */
$filename = uniqid("proof_") . ".jpg";
$path = $dir . $filename;

/* ========= MOVE FILE ========= */
if (move_uploaded_file($_FILES['image']['tmp_name'], $path)) {

    $stmt = $conn->prepare(
        "INSERT INTO proctoring_evidence 
            (user_id, course_id, violation_type, image_path)
         VALUES (?,?,?,?)"
    );

    if ($stmt) {
        $stmt->bind_param("iiss", $user_id, $course_id, $reason, $filename);
        $stmt->execute();
        $stmt->close();
    }
}
