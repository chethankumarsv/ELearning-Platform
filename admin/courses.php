<?php
session_start();

/* ---------------- CONFIG INCLUDE ---------------- */
require_once(__DIR__ . "/../includes/config.php");

/* ---------------- ADMIN AUTH ---------------- */
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

/* ---------------- YOUTUBE EMBED HELPER ---------------- */
function getEmbedUrl($url) {
    if (strpos($url, "watch?v=") !== false) {
        $url = str_replace("watch?v=", "embed/", $url);
    } elseif (strpos($url, "youtu.be/") !== false) {
        $id = ltrim(parse_url($url, PHP_URL_PATH), "/");
        $url = "https://www.youtube.com/embed/$id";
    }
    return $url . "?rel=0&modestbranding=1";
}

/* ---------------- SINGLE COURSE ---------------- */
$course = null;

if (isset($_GET['course_id'])) {
    $id = (int)$_GET['course_id'];
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();
}

/* ---------------- FILTERS ---------------- */
$where = [];
if (!empty($_GET['branch'])) {
    $where[] = "branch='" . $conn->real_escape_string($_GET['branch']) . "'";
}
if (!empty($_GET['semester_id'])) {
    $where[] = "semester_id=" . (int)$_GET['semester_id'];
}

$sql = "SELECT * FROM courses";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY id DESC";

$result = $conn->query($sql);
$semesters = $conn->query("SELECT * FROM semesters");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Courses</title>

<style>
body {
    font-family: 'Segoe UI', sans-serif;
    background: #f4f6fb;
    margin: 0;
}
.container {
    max-width: 1200px;
    margin: auto;
    padding: 30px;
}
h1 {
    margin-bottom: 10px;
}
.filters {
    background: #fff;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 25px;
    display: flex;
    gap: 15px;
}
select, button {
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
}
button {
    background: #4f46e5;
    color: #fff;
    cursor: pointer;
    border: none;
}
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}
.card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 10px 20px rgba(0,0,0,.08);
    padding: 15px;
}
.card iframe {
    width: 100%;
    height: 200px;
    border-radius: 10px;
}
.tags span {
    background: #eef2ff;
    color: #4338ca;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 13px;
    margin-right: 5px;
}
.actions a {
    display: inline-block;
    margin-top: 10px;
    margin-right: 5px;
    padding: 8px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
}
.view { background:#22c55e; color:#fff; }
.edit { background:#f59e0b; color:#fff; }
.delete { background:#ef4444; color:#fff; }
.back {
    display:inline-block;
    margin-bottom:15px;
}
</style>
</head>

<body>
<div class="container">

<?php if ($course): ?>
    <a class="back" href="courses.php">⬅ Back</a>
    <h1><?php echo htmlspecialchars($course['title']); ?></h1>

    <?php if ($course['video_url']): ?>
        <iframe src="<?php echo getEmbedUrl($course['video_url']); ?>" allowfullscreen></iframe>
    <?php endif; ?>

    <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>

<?php else: ?>

<h1>Manage Courses</h1>

<form class="filters" method="GET">
    <select name="branch">
        <option value="">All Branches</option>
        <option>CSE</option><option>ECE</option>
        <option>MECH</option><option>CIVIL</option><option>AI-ML</option>
    </select>

    <select name="semester_id">
        <option value="">All Semesters</option>
        <?php while($s=$semesters->fetch_assoc()): ?>
            <option value="<?php echo $s['id']; ?>"><?php echo $s['sem_name']; ?></option>
        <?php endwhile; ?>
    </select>

    <button type="submit">Apply</button>
</form>

<div class="grid">
<?php while($row=$result->fetch_assoc()): ?>
<div class="card">
    <h3><?php echo htmlspecialchars($row['title']); ?></h3>

    <div class="tags">
        <span><?php echo $row['branch']; ?></span>
        <span>Sem <?php echo $row['semester_id']; ?></span>
    </div>

    <?php if ($row['video_url']): ?>
        <iframe src="<?php echo getEmbedUrl($row['video_url']); ?>"></iframe>
    <?php endif; ?>

    <p><?php echo substr(htmlspecialchars($row['description']),0,120); ?>...</p>

    <div class="actions">
        <a class="view" href="?course_id=<?php echo $row['id']; ?>">View</a>
        <a class="edit" href="edit_course.php?id=<?php echo $row['id']; ?>">Edit</a>
        <a class="delete" href="delete_course.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Delete course?')">Delete</a>
    </div>
</div>
<?php endwhile; ?>
</div>

<?php endif; ?>

</div>
</body>
</html>
