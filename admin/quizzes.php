<?php
session_start();

/* ---------------- CONFIG ---------------- */
require_once(__DIR__ . "/../includes/config.php");

/* ---------------- ADMIN AUTH ---------------- */
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

/* ---------------- ADD QUIZ ---------------- */
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = (int)$_POST['course_id'];
    $question  = trim($_POST['question']);
    $a = trim($_POST['option_a']);
    $b = trim($_POST['option_b']);
    $c = trim($_POST['option_c']);
    $d = trim($_POST['option_d']);
    $correct = $_POST['correct_option'];

    if ($course_id && $question && $a && $b && $c && $d && $correct) {
        $stmt = $conn->prepare(
            "INSERT INTO quizzes 
            (course_id, question, option_a, option_b, option_c, option_d, correct_option)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "issssss",
            $course_id, $question, $a, $b, $c, $d, $correct
        );
        $stmt->execute();
        $msg = "Quiz added successfully!";
    } else {
        $msg = "Please fill all fields.";
    }
}

/* ---------------- FETCH COURSES ---------------- */
$courses = $conn->query("SELECT id, title FROM courses ORDER BY title");

/* ---------------- FETCH QUIZZES ---------------- */
$quizzes = $conn->query("
    SELECT q.id, q.question, q.correct_option, c.title AS course_title
    FROM quizzes q
    JOIN courses c ON q.course_id = c.id
    ORDER BY q.id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin | Quizzes</title>

<style>
* { box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body { margin:0; background:#f4f6fb; }
.container { max-width:1200px; margin:auto; padding:30px; }

h1 { margin-bottom:10px; }
.subtitle { color:#555; margin-bottom:20px; }

.card {
    background:#fff;
    border-radius:16px;
    padding:20px;
    box-shadow:0 15px 30px rgba(0,0,0,0.08);
    margin-bottom:30px;
}

label { font-weight:600; display:block; margin-top:15px; }
input, textarea, select {
    width:100%;
    padding:10px;
    border-radius:8px;
    border:1px solid #ccc;
    margin-top:5px;
}
textarea { resize:vertical; }

button {
    margin-top:20px;
    padding:12px 20px;
    background:#4f46e5;
    color:#fff;
    border:none;
    border-radius:10px;
    cursor:pointer;
    font-size:15px;
}
button:hover { background:#4338ca; }

.msg {
    padding:10px;
    margin-bottom:15px;
    border-radius:8px;
    background:#ecfeff;
    color:#0369a1;
}

table { width:100%; border-collapse:collapse; }
thead { background:#4f46e5; color:#fff; }
th, td { padding:14px; text-align:left; }
tbody tr { border-bottom:1px solid #eee; }
tbody tr:hover { background:#f1f5ff; }

.badge {
    background:#eef2ff;
    color:#4338ca;
    padding:4px 12px;
    border-radius:20px;
    font-size:13px;
}

.back {
    display:inline-block;
    margin-bottom:15px;
    text-decoration:none;
    color:#4f46e5;
    font-weight:500;
}
</style>
</head>

<body>
<div class="container">

<a class="back" href="dashboard.php">← Back to Dashboard</a>

<h1>Manage Quizzes</h1>
<p class="subtitle">Add and manage course quizzes</p>

<div class="card">
<h3>Add New Quiz</h3>

<?php if ($msg): ?>
<div class="msg"><?php echo $msg; ?></div>
<?php endif; ?>

<form method="POST">
    <label>Course</label>
    <select name="course_id" required>
        <option value="">Select Course</option>
        <?php while ($c = $courses->fetch_assoc()): ?>
            <option value="<?php echo $c['id']; ?>">
                <?php echo htmlspecialchars($c['title']); ?>
            </option>
        <?php endwhile; ?>
    </select>

    <label>Question</label>
    <textarea name="question" rows="3" required></textarea>

    <label>Option A</label>
    <input type="text" name="option_a" required>

    <label>Option B</label>
    <input type="text" name="option_b" required>

    <label>Option C</label>
    <input type="text" name="option_c" required>

    <label>Option D</label>
    <input type="text" name="option_d" required>

    <label>Correct Option</label>
    <select name="correct_option" required>
        <option value="">Select</option>
        <option value="A">A</option>
        <option value="B">B</option>
        <option value="C">C</option>
        <option value="D">D</option>
    </select>

    <button type="submit">Add Quiz</button>
</form>
</div>

<div class="card">
<h3>All Quizzes</h3>

<table>
<thead>
<tr>
    <th>ID</th>
    <th>Course</th>
    <th>Question</th>
    <th>Correct</th>
</tr>
</thead>
<tbody>
<?php while ($q = $quizzes->fetch_assoc()): ?>
<tr>
    <td><?php echo $q['id']; ?></td>
    <td><span class="badge"><?php echo htmlspecialchars($q['course_title']); ?></span></td>
    <td><?php echo htmlspecialchars(substr($q['question'],0,80)); ?>...</td>
    <td><?php echo $q['correct_option']; ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

</div>
</div>
</body>
</html>
