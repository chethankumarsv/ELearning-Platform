<?php
// /elearningplatform/send_reminders.php
// Run via cron: */5 * * * * /usr/bin/php /path/to/elearningplatform/send_reminders.php >/dev/null 2>&1
// Or hit via browser with a secret token: send_reminders.php?key=YOUR_SECRET

session_write_close(); // no need for session
require_once __DIR__ . '/includes/config.php'; // should define $conn (mysqli)

if (function_exists('date_default_timezone_set')) {
  date_default_timezone_set('Asia/Kolkata');
}

/** Optional simple guard if accessed via web */
$CLI = (php_sapi_name() === 'cli');
$EXPECTED_KEY = 'CHANGE_ME_LONG_RANDOM_KEY';
if (!$CLI) {
  if (!isset($_GET['key']) || $_GET['key'] !== $EXPECTED_KEY) {
    http_response_code(403);
    echo "Forbidden";
    exit;
  }
}

/** Try PHPMailer if available */
function send_mail_smart($toEmail, $toName, $subject, $html, $text = '') {
  $text = $text ?: strip_tags($html);
  if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
      // ---- SMTP config (edit to match your SMTP) ----
      // $mail->isSMTP();
      // $mail->Host = 'smtp.example.com';
      // $mail->SMTPAuth = true;
      // $mail->Username = 'user@example.com';
      // $mail->Password = 'password';
      // $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
      // $mail->Port = 587;

      $mail->setFrom('no-reply@elearning.local', 'E-Learning Events');
      $mail->addAddress($toEmail, $toName ?: $toEmail);
      $mail->isHTML(true);
      $mail->Subject = $subject;
      $mail->Body    = $html;
      $mail->AltBody = $text;
      $mail->send();
      return true;
    } catch (\Throwable $e) {
      // Fallback to mail()
    }
  }
  // Fallback simple mail()
  $headers  = "MIME-Version: 1.0\r\n";
  $headers .= "Content-type: text/html; charset=UTF-8\r\n";
  $headers .= "From: E-Learning Events <no-reply@elearning.local>\r\n";
  return @mail($toEmail, $subject, $html, $headers);
}

function remind_window(mysqli $conn, int $minutesLead, string $label) {
  // 15-minute window: now+lead .. now+lead+15min
  $sqlEvents = "
    SELECT id, title, event_date, COALESCE(location,'') AS location
    FROM events
    WHERE event_date BETWEEN (NOW() + INTERVAL ? MINUTE)
                         AND (NOW() + INTERVAL (? + 15) MINUTE)
    ORDER BY event_date ASC
  ";
  $stmt = $conn->prepare($sqlEvents);
  $stmt->bind_param("ii", $minutesLead, $minutesLead);
  $stmt->execute();
  $events = $stmt->get_result();

  while ($ev = $events->fetch_assoc()) {
    $eventId = (int)$ev['id'];

    // Pull GOING + opt-in users who haven't been sent this reminder_type
    $sqlUsers = "
      SELECT u.id AS uid, u.name, u.email
      FROM event_rsvps r
      JOIN users u ON u.id = r.user_id
      WHERE r.event_id = ?
        AND r.status = 'going'
        AND r.reminder_opt_in = 1
        AND u.email IS NOT NULL AND u.email <> ''
        AND NOT EXISTS (
          SELECT 1 FROM event_reminder_logs l
          WHERE l.event_id = r.event_id AND l.user_id = r.user_id AND l.reminder_type = ?
        )
    ";
    $stmtU = $conn->prepare($sqlUsers);
    $stmtU->bind_param("is", $eventId, $label);
    $stmtU->execute();
    $users = $stmtU->get_result();

    if (!$users->num_rows) continue;

    $dateStr = date('D, d M Y • h:i A', strtotime($ev['event_date']));
    $subject = ($label === '24h' ? "Reminder: {$ev['title']} is tomorrow" : "Starting soon: {$ev['title']}");
    $eventLink = sprintf('https://your-domain.example/elearningplatform/event.php?id=%d', $eventId);
    $icsLink   = sprintf('https://your-domain.example/elearningplatform/event.php?ics=%d', $eventId);

    while ($u = $users->fetch_assoc()) {
      $toName  = $u['name'] ?: 'Student';
      $toEmail = $u['email'];

      $html = "
        <div style='font-family:system-ui,Segoe UI,Roboto;'>
          <h2 style='margin:0 0 8px'>".htmlspecialchars($ev['title'])."</h2>
          <p style='margin:4px 0;color:#374151'>📅 {$dateStr}".($ev['location']? " • 📍 ".htmlspecialchars($ev['location']) : "")."</p>
          <p style='margin:12px 0'>Hi ".htmlspecialchars($toName).",<br/>
             This is your ".htmlspecialchars($label)." reminder for the event.</p>
          <p style='margin:12px 0'>
            <a href='{$eventLink}' style='background:#ff7e5f;color:#fff;padding:10px 16px;border-radius:999px;text-decoration:none;font-weight:700'>View Event</a>
            &nbsp; &nbsp;
            <a href='{$icsLink}' style='color:#111827'>Add to Calendar (.ics)</a>
          </p>
          <p style='margin-top:16px;color:#6b7280;font-size:12px'>You can turn off reminders on the event page or in My RSVPs.</p>
        </div>";

      if (send_mail_smart($toEmail, $toName, $subject, $html)) {
        // log it
        $ins = $conn->prepare("INSERT IGNORE INTO event_reminder_logs (event_id, user_id, reminder_type) VALUES (?,?,?)");
        $ins->bind_param("iis", $eventId, $u['uid'], $label);
        $ins->execute();
        $ins->close();
      }
    }
    $stmtU->close();
  }
  $stmt->close();
}

// Run both windows
remind_window($conn, 1440, '24h'); // ~24 hours
remind_window($conn, 60,   '1h');  // ~1 hour

echo $CLI ? "" : "OK";
