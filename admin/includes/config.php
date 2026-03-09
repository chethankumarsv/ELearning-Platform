<?php
/**
 * -------------------------------------------------
 * Global Configuration File
 * Used by BOTH admin and user pages
 * -------------------------------------------------
 */

/* ---------- Database Credentials ---------- */
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "elearningplatform";

/* ---------- Database Connection ---------- */
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

/* ---------- Character Set ---------- */
$conn->set_charset("utf8mb4");

/* ---------- Timezone ---------- */
date_default_timezone_set("Asia/Kolkata");

/* ---------- Optional Helper Include ---------- */
$helper = __DIR__ . "/att_helper.php";
if (file_exists($helper)) {
    require_once $helper;
}

/* ---------- Development Errors (optional) ---------- */
/*
error_reporting(E_ALL);
ini_set('display_errors', 1);
*/
