<?php
declare(strict_types=1);

/*
 |-----------------------------------------------------------
 | PHPMailer Mailer Helper
 |-----------------------------------------------------------
 | - Uses Composer autoload
 | - Gmail SMTP with App Password
 | - No silent failures
 | - Safe error logging
 |-----------------------------------------------------------
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/*
 |-----------------------------------------------------------
 | Load Composer Autoloader
 |-----------------------------------------------------------
 */
require_once __DIR__ . '/../vendor/autoload.php';

/*
 |-----------------------------------------------------------
 | Send Password Reset Email
 |-----------------------------------------------------------
 */
function send_reset_email(string $toEmail, string $toName, string $token): bool
{
    $mail = new PHPMailer(true);

    try {
        /* ================= SMTP CONFIG ================= */
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;

        // 🔴 CHANGE THESE VALUES
        $mail->Username   = 'your_real_gmail@gmail.com';
        $mail->Password   = 'YOUR_GMAIL_APP_PASSWORD';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        /* ================= EMAIL HEADERS ================= */
        $mail->setFrom($mail->Username, 'E-Learning Platform');
        $mail->addAddress($toEmail, $toName);

        /* ================= EMAIL CONTENT ================= */
        $resetLink = "http://localhost/elearningplatform/auth.php?token=" . urlencode($token);

        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';

        $mail->Body = "
            <div style='font-family:Poppins,Arial,sans-serif'>
                <h2>Password Reset</h2>
                <p>Hello <strong>{$toName}</strong>,</p>
                <p>You requested to reset your password.</p>
                <p>
                    <a href='{$resetLink}'
                       style='display:inline-block;
                              padding:12px 20px;
                              background:#6366f1;
                              color:#ffffff;
                              text-decoration:none;
                              border-radius:8px;
                              font-weight:600'>
                        Reset Password
                    </a>
                </p>
                <p>This link expires in <strong>15 minutes</strong>.</p>
                <p style='font-size:12px;color:#777'>
                    If you did not request this, please ignore this email.
                </p>
            </div>
        ";

        $mail->AltBody =
            "Hello {$toName},\n\n" .
            "You requested a password reset.\n\n" .
            "Reset link (valid for 15 minutes):\n" .
            "{$resetLink}\n\n" .
            "If you did not request this, ignore this email.";

        /* ================= SEND ================= */
        $mail->send();
        return true;

    } catch (Exception $e) {
        // Log error safely (no sensitive output to users)
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}
