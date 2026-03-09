<?php
require_once __DIR__ . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = intval($_POST['internship_id']);
  $name = $conn->real_escape_string($_POST['name']);
  $email = $conn->real_escape_string($_POST['email']);
  $message = $conn->real_escape_string($_POST['message']);
  
  // Handle resume upload
  $resume = null;
  if (!empty($_FILES['resume']['name'])) {
    $targetDir = __DIR__ . "/uploads/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    $resume = basename($_FILES['resume']['name']);
    $targetFile = $targetDir . $resume;
    move_uploaded_file($_FILES['resume']['tmp_name'], $targetFile);
  }

  // Save application
  $sql = "INSERT INTO internship_applications (internship_id, name, email, message, resume, created_at)
          VALUES ($id, '$name', '$email', '$message', '$resume', NOW())";
  if ($conn->query($sql)) {
    echo "<script>alert('Your application has been submitted successfully!');window.location.href='internships.php';</script>";
  } else {
    echo "Error: " . $conn->error;
  }
}
?>
