<?php
// includes/att_helper.php
if(!isset($conn)){
  // require_once 'config.php'; // ensure $conn exists in your project
  // For safety, you can require the main config here
}

function json_response($arr){
  header('Content-Type: application/json');
  echo json_encode($arr);
  exit;
}

function generate_token($len = 40){
  return bin2hex(random_bytes($len/2));
}
?>
