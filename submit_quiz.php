session_start();
require_once 'includes/config.php';

$user_id = $_SESSION['user_id'];
$quiz_id = $_POST['quiz_id'];
$score   = $_POST['score'];

$sql = "INSERT INTO quiz_attempts (user_id, quiz_id, score, attempted_at)
        VALUES ('$user_id', '$quiz_id', '$score', NOW())";
$conn->query($sql);
