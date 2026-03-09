<?php
require_once "../includes/config.php";

if (!isset($_GET['course_id']) || !isset($_GET['student_id'])) {
    die("Missing parameters.");
}

$course_id = intval($_GET['course_id']);
$student_id = intval($_GET['student_id']);

$sql = $conn->prepare("
    SELECT users.name AS student_name, courses.title AS course_title
    FROM enrollments
    JOIN users ON users.id = enrollments.user_id
    JOIN courses ON courses.id = enrollments.course_id
    WHERE users.id = ? AND courses.id = ?
");
$sql->bind_param("ii", $student_id, $course_id);
$sql->execute();
$data = $sql->get_result()->fetch_assoc();

if (!$data) {
    die("Student not enrolled in this course.");
}

$student = $data['student_name'];
$course  = $data['course_title'];

header("Content-Type: text/plain");

echo "Certificate of Completion\n";
echo "-------------------------\n";
echo "This is to certify that:\n\n";
echo "$student\n\n";
echo "has successfully completed the course:\n";
echo "$course\n\n";
echo "Issued on: " . date("d-m-Y") . "\n";

?>
