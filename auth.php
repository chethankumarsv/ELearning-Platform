<?php
session_start();

/* ================= DATABASE ================= */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli("127.0.0.1", "root", "", "elearningplatform", 3306);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Database connection failed. Start MySQL in XAMPP.");
}

/* ================= HELPERS ================= */
function clean($v) {
    return trim(htmlspecialchars($v));
}

function validUsername($u) {
    return preg_match('/^[A-Za-z0-9_]{4,20}$/', $u);
}

function validUSN($u) {
    return preg_match('/^[A-Za-z0-9]{4,20}$/', $u);
}

function validEmail($e) {
    return filter_var($e, FILTER_VALIDATE_EMAIL);
}

function validPassword($p) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/', $p);
}

/* ================= STUDENT LOGIN ================= */
if (isset($_POST['student_login'])) {

    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $student_error = "All fields are required";
    } else {

        $stmt = $conn->prepare(
            "SELECT id, username, password FROM users WHERE username = ? LIMIT 1"
        );
        if (!$stmt) die($conn->error);

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $row = $res->fetch_assoc();

            if (password_verify($password, $row['password'])) {

                $_SESSION['student_id'] = $row['id'];
                $_SESSION['student_username'] = $row['username'];
                $_SESSION['role'] = 'student';

                /* ✅ auto-scroll trigger */
                $_SESSION['just_logged_in'] = true;

                header("Location: index.php");
                exit;
            }
        }

        $student_error = "Invalid username or password";
    }
}

/* ================= STUDENT SIGNUP ================= */
if (isset($_POST['student_signup'])) {

    $username = clean($_POST['username'] ?? '');
    $usn      = clean($_POST['usn'] ?? '');
    $email    = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!validUsername($username)) {
        $student_error = "Invalid username format";
    } elseif (!validUSN($usn)) {
        $student_error = "Invalid USN format";
    } elseif (!validEmail($email)) {
        $student_error = "Invalid email address";
    } elseif (!validPassword($password)) {
        $student_error = "Password must be strong";
    } else {

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO users (username, usn, email, password) VALUES (?, ?, ?, ?)"
        );
        if (!$stmt) die($conn->error);

        $stmt->bind_param("ssss", $username, $usn, $email, $hash);

        if ($stmt->execute()) {
            $student_success = "Signup successful. Please login.";
        } else {
            $student_error = "Username / USN / Email already exists";
        }
    }
}

/* ================= ADMIN LOGIN (HARDCODED) ================= */
if (isset($_POST['admin_login'])) {

    $admin_user = clean($_POST['admin_username'] ?? '');
    $admin_pass = $_POST['password'] ?? '';

    if ($admin_user === 'admin' && $admin_pass === 'Admin@123') {

        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_name'] = 'admin';
        $_SESSION['role'] = 'admin';

        header("Location: admin/dashboard.php");
        exit;

    } else {
        $admin_error = "Invalid admin username or password";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Authentication</title>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins,sans-serif}
body{
    min-height:100vh;
    background:linear-gradient(135deg,#2b6cff,#5a4dff);
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
}
.auth-container{display:flex;gap:40px}
.auth-card{
    width:380px;
    padding:30px;
    border-radius:20px;
    background:#241c5c;
    box-shadow:0 30px 80px rgba(0,0,0,.45);
    color:#fff;
}
.tabs{display:flex;background:#3a3278;border-radius:14px;padding:6px;margin-bottom:20px}
.tabs button{flex:1;border:none;background:none;color:#fff;padding:12px;border-radius:12px;cursor:pointer}
.tabs .active{background:linear-gradient(135deg,#ff4d8d,#8f3bff)}
input{width:100%;padding:15px;border-radius:14px;border:none;background:#3a3278;color:#fff;margin-bottom:14px}
.btn{width:100%;padding:15px;border-radius:16px;border:none;font-weight:600;cursor:pointer}
.student-btn{background:linear-gradient(135deg,#00c6ff,#0072ff)}
.admin-btn{background:linear-gradient(135deg,#ff4d8d,#8f3bff)}
.error{color:#ff7a7a;font-size:13px;margin-bottom:10px}
.success{color:#4cd964;font-size:13px;margin-bottom:10px}
@media(max-width:900px){.auth-container{flex-direction:column;width:100%;padding:20px}.auth-card{width:100%}}
</style>
</head>

<body>

<div class="auth-container">

<div class="auth-card">
<h2>🎓 Student Access</h2>

<?php if(isset($student_error)) echo "<div class='error'>$student_error</div>"; ?>
<?php if(isset($student_success)) echo "<div class='success'>$student_success</div>"; ?>

<div class="tabs">
<button class="active" onclick="showLogin()">Login</button>
<button onclick="showSignup()">Signup</button>
</div>

<form method="POST" id="loginForm">
<input name="username" placeholder="Username" required>
<input type="password" name="password" placeholder="Password" required>
<button class="btn student-btn" name="student_login">Login</button>
</form>

<form method="POST" id="signupForm" style="display:none">
<input name="username" placeholder="Username" required>
<input name="usn" placeholder="USN" required>
<input name="email" placeholder="Email" required>
<input type="password" name="password" placeholder="Password" required>
<button class="btn student-btn" name="student_signup">Signup</button>
</form>
</div>

<div class="auth-card">
<h2>🛡️ Admin Access</h2>
<?php if(isset($admin_error)) echo "<div class='error'>$admin_error</div>"; ?>
<form method="POST">
<input name="admin_username" placeholder="Admin Username" required>
<input type="password" name="password" placeholder="Password" required>
<button class="btn admin-btn" name="admin_login">Login</button>
</form>
</div>

</div>

<script>
function showLogin(){
    loginForm.style.display="block";
    signupForm.style.display="none";
}
function showSignup(){
    loginForm.style.display="none";
    signupForm.style.display="block";
}
</script>

</body>
</html>
