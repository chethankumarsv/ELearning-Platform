<?php // profile_prefs_save.php
session_start(); require __DIR__.'/includes/config.php';
$uid = $_SESSION['user_id'] ?? 0; if(!$uid){ http_response_code(401); exit; }

$branch = $_POST['branch'] ?? 'CSE';
$year   = $_POST['year'] ?? '3';
$goal   = $_POST['goal'] ?? 'placements';
$langs  = $_POST['languages'] ?? 'en';
$topics = $_POST['topics'] ?? 'DSA, System Design';
$avail  = $_POST['availability_json'] ?? '{"days":["Sat","Sun"],"after":"18:00"}';

$sql = "INSERT INTO student_prefs (user_id,branch,year,goal,languages,topics,availability)
        VALUES (?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE branch=VALUES(branch),year=VALUES(year),goal=VALUES(goal),
        languages=VALUES(languages),topics=VALUES(topics),availability=VALUES(availability)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('issssss',$uid,$branch,$year,$goal,$langs,$topics,$avail);
$stmt->execute();

$_SESSION['branch']=$branch; $_SESSION['year']=$year; // for quick personalization
header('Location: mentors.php?saved=1');
