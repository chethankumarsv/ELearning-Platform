<?php
session_start();
require_once "includes/config.php";

if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['error' => 'Not logged in']));
}

$sender_id = $_SESSION['user_id'];
$receiver_id = intval($_POST['receiver_id']);
$message = trim($_POST['message']);

if ($message != '') {
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $sender_id, $receiver_id, $message);
    $stmt->execute();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Empty message']);
}
?>
