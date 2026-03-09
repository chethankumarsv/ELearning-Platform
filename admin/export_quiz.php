<?php
// /elearningplatform/export_quiz.php
session_start();
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit;
}

$subject_filter = intval($_POST['subject_id'] ?? 0);
$user_filter    = intval($_POST['user_id'] ?? 0);

$sql = "SELECT qa.*, u.username, s.name AS subject_name 
        FROM quiz_attempts qa
        JOIN users u ON qa.user_id = u.id
        JOIN subjects s ON qa.subject_id = s.id
        WHERE 1=1";
if ($subject_filter > 0) $sql .= " AND qa.subject_id = $subject_filter";
if ($user_filter > 0)    $sql .= " AND qa.user_id = $user_filter";
$sql .= " ORDER BY qa.attempt_date DESC";

$result = $conn->query($sql);
$attempts = [];
while ($row = $result->fetch_assoc()) {
    $attempts[] = $row;
}

// ==================================
// EXPORT TO PDF
// ==================================
if ($_POST['export'] === 'pdf') {
    require __DIR__ . '/vendor/autoload.php';

    // ✅ declare namespace at top-level
   $dompdf = new \Dompdf\Dompdf();

    $html = "<h2 style='text-align:center;'>Quiz Report</h2>";
    $html .= "<table border='1' cellspacing='0' cellpadding='5' width='100%'>
                <tr>
                  <th>Student</th>
                  <th>Subject</th>
                  <th>Score</th>
                  <th>Total</th>
                  <th>Percentage</th>
                  <th>Date</th>
                </tr>";
    foreach ($attempts as $a) {
        $percentage = round(($a['score'] / $a['total']) * 100, 2);
        $html .= "<tr>
                    <td>{$a['username']}</td>
                    <td>{$a['subject_name']}</td>
                    <td>{$a['score']}</td>
                    <td>{$a['total']}</td>
                    <td>{$percentage}%</td>
                    <td>{$a['attempt_date']}</td>
                  </tr>";
    }
    $html .= "</table>";

    $dompdf->loadHtml($html);
    $dompdf->setPaper("A4", "landscape");
    $dompdf->render();
    $dompdf->stream("quiz_report.pdf", ["Attachment" => true]);
    exit;
}
