<?php
session_start();
require_once "includes/config.php";

if (!isset($_SESSION['user_id'])) {
    exit(json_encode([]));
}

$user_id = $_SESSION['user_id'];
$partner_id = intval($_GET['partner_id']);

$query = "
SELECT * FROM messages 
WHERE (sender_id = $user_id AND receiver_id = $partner_id)
   OR (sender_id = $partner_id AND receiver_id = $user_id)
ORDER BY created_at ASC";

$res = $conn->query($query);
$messages = [];

while ($row = $res->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode($messages);
?>
