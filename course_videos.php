<?php
session_start();
require_once "includes/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$course_id = intval($_GET['course_id'] ?? 0);
$user_id   = $_SESSION['user_id'];

$sql = "
SELECT v.*,
       IF(p.watched = 1, 1, 0) AS watched
FROM course_videos v
LEFT JOIN user_video_progress p 
       ON p.video_id = v.id AND p.user_id = ?
WHERE v.course_id = ?
ORDER BY v.video_order ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
<title>Course Videos</title>
</head>
<body>

<h2>Course Videos</h2>

<?php while ($v = $result->fetch_assoc()): ?>
    <div style="margin-bottom:20px;">
        <h4><?= htmlspecialchars($v['title']) ?></h4>

        <iframe width="560" height="315"
            src="<?= str_replace("watch?v=", "embed/", $v['video_url']) ?>"
            allowfullscreen></iframe>

        <?php if (!$v['watched']): ?>
            <form method="post" action="mark_video_complete.php">
                <input type="hidden" name="video_id" value="<?= $v['id'] ?>">
                <input type="hidden" name="course_id" value="<?= $course_id ?>">
                <button type="submit">Mark as Completed</button>
            </form>
        <?php else: ?>
            ✅ Completed
        <?php endif; ?>
    </div>
<?php endwhile; ?>

<a href="check_quiz_unlock.php?course_id=<?= $course_id ?>">Go to Quiz</a>

</body>
</html>
