<?php
session_start();
require_once __DIR__ . '/includes/config.php';

ini_set('display_errors', 0);
error_reporting(E_ALL);

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "❌ Invalid email address.";
    } else {
        $st = $conn->prepare("SELECT id FROM users WHERE email=?");
        $st->bind_param("s", $email);
        $st->execute();
        $res = $st->get_result();
        $user = $res->fetch_assoc();
        $st->close();

        if (!$user) {
            $msg = "❌ Email not registered.";
        } else {
            // Generate OTP
            $otp = random_int(100000, 999999);
            $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
            $expiry = date("Y-m-d H:i:s", time() + 600); // 10 minutes

            $up = $conn->prepare(
                "UPDATE users SET reset_otp_hash=?, reset_otp_expiry=? WHERE email=?"
            );
            $up->bind_param("sss", $otp_hash, $expiry, $email);
            $up->execute();
            $up->close();

            // Send email
            $subject = "Password Reset OTP";
            $body = "Your OTP for password reset is: $otp\n\nValid for 10 minutes.";
            $headers = "From: noreply@elearning.com";

            mail($email, $subject, $body, $headers);

            $_SESSION['reset_email'] = $email;
            header("Location: reset_password.php");
            exit;
        }
    }
}
?>
<!doctype html>
<html>
<head>
<title>Forgot Password</title>
<style>
body{font-family:Poppins;background:#0f172a;color:#fff;display:flex;justify-content:center;align-items:center;height:100vh}
.box{background:#1e293b;padding:30px;border-radius:12px;width:360px}
input,button{width:100%;padding:12px;margin:10px 0;border-radius:8px;border:0}
button{background:#6366f1;color:#fff;font-weight:700}
.msg{background:#fde68a;color:#000;padding:10px;border-radius:6px}
</style>
</head>
<body>
<div class="box">
<h2>Forgot Password</h2>
<?php if($msg): ?><div class="msg"><?=$msg?></div><?php endif; ?>
<form method="POST">
<input type="email" name="email" placeholder="Registered Email" required>
<button>Send OTP</button>
</form>
</div>
</body>
</html>
