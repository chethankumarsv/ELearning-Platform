<?php
// my_courses.php
session_start();
require_once __DIR__ . '/includes/config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Ensure enrollments table exists (safe auto-create)
$conn->query("
CREATE TABLE IF NOT EXISTS `enrollments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `course_id` INT NOT NULL,
  `enrolled_on` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_enrollment` (`user_id`, `course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Fetch enrolled courses with join
$sql = "
SELECT c.id, c.title, c.category, c.image, e.enrolled_on
FROM enrollments e
JOIN courses c ON e.course_id = c.id
WHERE e.user_id = ?
ORDER BY e.enrolled_on DESC
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$courses = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Courses | E-Learning Platform</title>
<style>
    body {
        font-family: 'Poppins', sans-serif;
        margin: 0;
        background: linear-gradient(135deg, #eef2f3, #8e9eab);
        color: #333;
    }
    header {
        background: #222;
        color: white;
        text-align: center;
        padding: 20px;
        font-size: 24px;
        letter-spacing: 1px;
    }
    .container {
        max-width: 1100px;
        margin: 40px auto;
        padding: 20px;
    }
    .course-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 25px;
    }
    .course-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .course-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    .course-card img {
        width: 100%;
        height: 180px;
        object-fit: cover;
    }
    .course-card-content {
        padding: 20px;
        text-align: center;
    }
    .course-card h3 {
        margin: 10px 0;
        font-size: 20px;
        color: #333;
    }
    .category {
        font-size: 14px;
        color: #777;
        margin-bottom: 10px;
    }
    .enrolled-date {
        font-size: 13px;
        color: #555;
    }
    .btn-group {
        margin-top: 15px;
        display: flex;
        justify-content: center;
        gap: 10px;
    }
    .btn {
        padding: 10px 18px;
        border-radius: 8px;
        text-decoration: none;
        color: white;
        font-size: 14px;
        transition: background 0.3s ease;
    }
    .btn-primary { background: #007bff; }
    .btn-primary:hover { background: #0056b3; }
    .btn-danger { background: #dc3545; }
    .btn-danger:hover { background: #b52a35; }
    .no-courses {
        text-align: center;
        margin-top: 100px;
        color: #444;
        font-size: 18px;
    }
</style>
</head>
<body>
<header>My Enrolled Courses</header>

<div class="container">
    <?php if (empty($courses)): ?>
        <div class="no-courses">
            <p>You haven't enrolled in any courses yet.</p>
            <a href="courses.php" class="btn btn-primary">Browse Courses</a>
        </div>
    <?php else: ?>
        <div class="course-grid">
            <?php foreach ($courses as $course): ?>
                <div class="course-card">
                    <img src="uploads/<?php echo htmlspecialchars($course['image']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                    <div class="course-card-content">
                        <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                        <p class="category"><?php echo htmlspecialchars($course['category']); ?></p>
                        <p class="enrolled-date">Enrolled on: <?php echo date('d M Y', strtotime($course['enrolled_on'])); ?></p>
                        <div class="btn-group">
                            <a href="course_content.php?id=<?php echo $course['id']; ?>" class="btn btn-primary">Start Learning</a>
                            <a href="unenroll.php?course_id=<?php echo $course['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to unenroll from this course?');">Unenroll</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
