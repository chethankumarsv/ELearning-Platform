<?php
// includes/config.php

// Your actual database configuration - UPDATED WITH YOUR DB NAME
$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = ""; // Default XAMPP password (empty)
$dbname = "elearningplatform"; // Your actual database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // If database doesn't exist, try to create it
    if ($conn->connect_errno == 1049) { // Unknown database
        // Create connection without database
        $temp_conn = new mysqli($servername, $username, $password);
        if ($temp_conn->connect_error) {
            die("Connection failed: " . $temp_conn->connect_error);
        }
        
        // Create database
        $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
        if ($temp_conn->query($sql) === TRUE) {
            // Reconnect with database
            $conn = new mysqli($servername, $username, $password, $dbname);
            if ($conn->connect_error) {
                die("Connection failed after creating database: " . $conn->connect_error);
            }
        } else {
            die("Error creating database: " . $temp_conn->error);
        }
        $temp_conn->close();
    } else {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Optional - remove this line if you don't have an OpenAI API key yet


// Set character set
$conn->set_charset("utf8mb4");
require_once __DIR__ . '/att_helper.php';
// at top of includes/config.php
date_default_timezone_set('Asia/Kolkata');
// Optional: Enable error reporting for development
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
?>