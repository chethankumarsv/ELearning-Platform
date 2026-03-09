<?php
// ================= SESSION =================
session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

// ================= DB =================
require_once __DIR__ . '/includes/config.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection failed");
}

// ================= HELPERS =================
function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// ================= SECURITY CHECK =================
if (!isset($_SESSION['reset_email'])) {
    header("Location: auth.php");
    exit;
}

$email = $_SESSION['reset_email'];
$message = "";

// ================= HANDLE RESET =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $otp      = trim($_POST['otp'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$otp || !$password) {
        $message = "⚠️ All fields are required.";
    }
    elseif (strlen($password) < 6) {
        $message = "❌ Password must be at least 6 characters.";
    }
    else {
        $st = $conn->prepare(
            "SELECT reset_otp_hash, reset_otp_expiry 
             FROM users 
             WHERE email=? LIMIT 1"
        );
        $st->bind_param("s", $email);
        $st->execute();
        $res = $st->get_result();
        $user = $res->fetch_assoc();
        $st->close();

        if (!$user) {
            $message = "❌ Invalid request.";
        }
        elseif (!$user['reset_otp_hash'] || !$user['reset_otp_expiry']) {
            $message = "❌ No reset request found.";
        }
        elseif (strtotime($user['reset_otp_expiry']) < time()) {
            $message = "❌ OTP expired. Please request again.";
        }
        elseif (!password_verify($otp, $user['reset_otp_hash'])) {
            $message = "❌ Invalid OTP.";
        }
        else {
            // ✅ Update password
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $up = $conn->prepare(
                "UPDATE users 
                 SET password=?, reset_otp_hash=NULL, reset_otp_expiry=NULL 
                 WHERE email=?"
            );
            $up->bind_param("ss", $hash, $email);
            $up->execute();
            $up->close();

            // cleanup session
            unset($_SESSION['reset_email']);

            header("Location: auth.php?reset=success");
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password</title>
<meta name="viewport" content="width=device-width,initial-scale=1">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

<style>
body{
    font-family:Poppins,sans-serif;
    background:linear-gradient(-45deg,#6a11cb,#2575fc,#ff6a88,#7b2cfb);
    background-size:400% 400%;
    animation:bg 16s ease infinite;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    color:#fff;
}
@keyframes bg{
    0%{background-position:0% 50%}
    50%{background-position:100% 50%}
    100%{background-position:0% 50%}
}
.card{
    background:rgba(0,0,0,.55);
    padding:30px;
    border-radius:18px;
    width:360px;
    box-shadow:0 18px 40px rgba(0,0,0,.5);
}
h2{text-align:center;margin-top:0}
input,button{
    width:100%;
    padding:14px;
    margin:10px 0;
    border-radius:12px;
    border:none;
}
input{
    background:rgba(255,255,255,.1);
    color:#fff;
}
button{
    background:linear-gradient(90deg,#00c6ff,#0072ff);
    font-weight:700;
    cursor:pointer;
}
.message{
    background:rgba(255,193,7,.2);
    padding:12px;
    border-radius:8px;
    margin-bottom:12px;
    text-align:center;
}
.small{
    text-align:center;
    font-size:13px;
    opacity:.85;
}
</style>
</head>

<body>

<div class="card">
<h2>🔐 Reset Password</h2>

<?php if ($message): ?>
    <div class="message"><?= e($message) ?></div>
<?php endif; ?>

<form method="POST">
    <input type="text" name="otp" placeholder="Enter OTP" required>
    <input type="password" name="password" placeholder="New Password" required>
    <button>Reset Password</button>
</form>

<div class="small">
    OTP valid for 10 minutes only
</div>
</div>

</body>
</html>
