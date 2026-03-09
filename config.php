<?php
// includes/config.php — Database connection for E-learning Platform

// Database settings
$host   = "localhost";          // XAMPP default
$user   = "root";               // Default MySQL user
$pass   = "";                   // Usually empty in XAMPP
$dbname = "elearningplatform";  // Make sure this DB exists

// Create connection (object-oriented MySQLi)
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_errno) {
    die("Database connection failed: " . $conn->connect_error);
}

// UTF-8 support (very important for notes, filenames, user names)
if (!$conn->set_charset("utf8mb4")) {
    // fallback (rare)
    $conn->set_charset("utf8");
}

// Optional — stronger error mode for debugging
// Comment/remove when project goes live
// mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?>
