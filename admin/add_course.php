<?php
session_start();
require_once(__DIR__ . "/../includes/config.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $branch = $_POST['branch'];
    $semester_id = $_POST['semester_id'];
    $video_url = $_POST['video_url'];

    $stmt = $conn->prepare(
        "INSERT INTO courses (title, description, branch, semester_id, video_url)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sssds", $title, $description, $branch, $semester_id, $video_url);
    $stmt->execute();

    header("Location: courses.php?success=1");
    exit;
}
?>

<form method="POST">
    <input type="text" name="title" placeholder="Course Title" required>
    <textarea name="description" placeholder="Course Description"></textarea>
    <input type="text" name="branch" placeholder="Branch">
    <input type="number" name="semester_id" placeholder="Semester">
    <input type="url" name="video_url" placeholder="YouTube URL">
    <button type="submit">Upload Course</button>
</form>
