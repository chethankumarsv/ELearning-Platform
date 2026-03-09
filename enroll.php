<?php
// enroll.php
session_start();
require_once __DIR__ . '/includes/config.php';

// ===== Helper for clean error display =====
function showMessage($title, $message, $color = "red") {
    echo "<!doctype html><html><head><meta charset='utf-8'>
    <title>$title</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f0f0f0, #dfe9f3);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            width: 400px;
        }
        h2 {
            color: $color;
            margin-bottom: 10px;
        }
        p { color: #333; }
        a {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            background: #007bff;
            color: white;
            transition: 0.3s;
        }
        a:hover { background: #0056b3; }
    </style>
    </head><body>
    <div class='card'>
        <h2>$title</h2>
        <p>$message</p>
        <a href='courses.php'>Back to Courses</a>
    </div>
    </body></html>";
    exit;
}

// ===== 1) Check Login =====
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = intval($_SESSION['user_id']);

// ===== 2) Validate Course ID =====
if (!isset($_GET['course_id'])) {
    showMessage("Error", "Missing course ID parameter.");
}
$course_id = intval($_GET['course_id']);
if ($course_id <= 0) {
    showMessage("Error", "Invalid course ID.");
}

// ===== 3) Ensure enrollments table exists =====
$createEnrollmentsTable = "
CREATE TABLE IF NOT EXISTS `enrollments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `course_id` INT NOT NULL,
  `enrolled_on` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_enrollment` (`user_id`, `course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
if (!$conn->query($createEnrollmentsTable)) {
    showMessage("Error", "Failed to ensure enrollments table: " . $conn->error);
}

// ===== 4) Check if Course Exists =====
$courseStmt = $conn->prepare("SELECT id, title FROM courses WHERE id = ?");
if (!$courseStmt) {
    showMessage("Error", "Database prepare failed: " . $conn->error);
}
$courseStmt->bind_param("i", $course_id);
$courseStmt->execute();
$result = $courseStmt->get_result();
if ($result->num_rows === 0) {
    showMessage("Error", "Course not found (ID: $course_id).");
}
$course = $result->fetch_assoc();
$courseStmt->close();

// ===== 5) Check if Already Enrolled =====
$checkStmt = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
if (!$checkStmt) {
    showMessage("Error", "Database prepare failed: " . $conn->error);
}
$checkStmt->bind_param("ii", $user_id, $course_id);
$checkStmt->execute();
$checkStmt->store_result();
if ($checkStmt->num_rows > 0) {
    showMessage("Already Enrolled", "You are already enrolled in <strong>" . htmlspecialchars($course['title']) . "</strong>.", "green");
}
$checkStmt->close();

// ===== 6) Insert Enrollment =====
$insertStmt = $conn->prepare("INSERT INTO enrollments (user_id, course_id, enrolled_on) VALUES (?, ?, NOW())");
if (!$insertStmt) {
    showMessage("Error", "Database prepare failed (insert): " . $conn->error);
}
$insertStmt->bind_param("ii", $user_id, $course_id);
if (!$insertStmt->execute()) {
    if ($conn->errno == 1062) {
        showMessage("Notice", "You are already enrolled in this course.", "green");
    } else {
        showMessage("Error", "Failed to enroll: " . $insertStmt->error);
    }
}
$insertStmt->close();

// ===== 7) Success Message =====
showMessage("Enrollment Successful", "You are now enrolled in <strong>" . htmlspecialchars($course['title']) . "</strong>!<br>
<a href='my_courses.php' style='background:#28a745;'>Go to My Courses</a>", "green");
?>
