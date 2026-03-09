<?php
session_start();
require_once("../includes/config.php");

/* ========= ADMIN AUTH ========= */
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

/* ========= FILTERS ========= */
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$user_id   = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

$where = [];
$params = [];
$types  = "";

/* Build conditions safely */
if ($course_id > 0) {
    $where[] = "e.course_id = ?";
    $params[] = $course_id;
    $types .= "i";
}
if ($user_id > 0) {
    $where[] = "e.user_id = ?";
    $params[] = $user_id;
    $types .= "i";
}

$whereSql = "";
if (!empty($where)) {
    $whereSql = "WHERE " . implode(" AND ", $where);
}

/* ========= SQL (FIXED COLUMN NAMES) ========= */
$sql = "
SELECT 
    e.id,
    e.violation_type,
    e.image_path,
    e.created_at,
    u.username AS student_name,
    c.title AS course_title
FROM proctoring_evidence e
JOIN users u ON u.id = e.user_id
JOIN courses c ON c.id = e.course_id
$whereSql
ORDER BY e.created_at DESC
";

/* ========= PREPARE ========= */
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("SQL Prepare Failed: " . $conn->error);
}

/* ========= BIND ========= */
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

/* ========= EXECUTE ========= */
$stmt->execute();

/* ========= FETCH ========= */
$result = $stmt->get_result();
if ($result === false) {
    die("get_result() failed. Ensure mysqlnd is enabled.");
}

/* ========= DROPDOWNS (FIXED COLUMN) ========= */
$courses = $conn->query("SELECT id, title FROM courses");
$users   = $conn->query("SELECT id, username FROM users");
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin – Proctoring Evidence</title>

<style>
body{
    font-family:Poppins,sans-serif;
    background:#f4f6f9;
    margin:0;padding:20px
}
.container{max-width:1200px;margin:auto}
h1{text-align:center;margin-bottom:20px}
.filters{
    background:#fff;
    padding:15px;
    border-radius:12px;
    margin-bottom:20px;
    display:flex;
    gap:10px;
    flex-wrap:wrap
}
select,button{
    padding:10px 14px;
    border-radius:8px;
    border:1px solid #ccc
}
button{
    background:#3498db;
    color:#fff;
    border:none;
    cursor:pointer
}
.card{
    background:#fff;
    padding:15px;
    border-radius:12px;
    margin-bottom:15px;
    display:flex;
    gap:20px;
    align-items:center;
    box-shadow:0 6px 15px rgba(0,0,0,.08)
}
.card img{
    max-width:200px;
    border-radius:10px;
    border:1px solid #ddd
}
.badge{
    display:inline-block;
    padding:4px 10px;
    background:#e74c3c;
    color:#fff;
    border-radius:20px;
    font-size:12px
}
.empty{
    text-align:center;
    color:#888;
    padding:40px
}
</style>
</head>

<body>
<div class="container">

<h1>🛡️ Proctoring Evidence Dashboard</h1>

<form class="filters" method="GET">
    <select name="course_id">
        <option value="">All Courses</option>
        <?php while($c = $courses->fetch_assoc()){ ?>
            <option value="<?php echo $c['id']; ?>" <?php if($course_id==$c['id']) echo "selected"; ?>>
                <?php echo htmlspecialchars($c['title']); ?>
            </option>
        <?php } ?>
    </select>

    <select name="user_id">
        <option value="">All Students</option>
        <?php while($u = $users->fetch_assoc()){ ?>
            <option value="<?php echo $u['id']; ?>" <?php if($user_id==$u['id']) echo "selected"; ?>>
                <?php echo htmlspecialchars($u['username']); ?>
            </option>
        <?php } ?>
    </select>

    <button type="submit">Filter</button>
</form>

<?php if ($result->num_rows === 0) { ?>
    <div class="empty">No proctoring evidence found.</div>
<?php } ?>

<?php while($row = $result->fetch_assoc()){ ?>
<div class="card">
    <img src="../uploads/proctoring/<?php echo htmlspecialchars($row['image_path']); ?>">

    <div>
        <p><b>Student:</b> <?php echo htmlspecialchars($row['student_name']); ?></p>
        <p><b>Course:</b> <?php echo htmlspecialchars($row['course_title']); ?></p>
        <p><b>Violation:</b>
            <span class="badge"><?php echo htmlspecialchars($row['violation_type']); ?></span>
        </p>
        <p><b>Time:</b> <?php echo $row['created_at']; ?></p>
    </div>
</div>
<?php } ?>

</div>
</body>
</html>
