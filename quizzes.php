<?php
session_start();
require_once("includes/config.php");

/* ================= AUTH ================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

$user_id   = (int)$_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if ($course_id <= 0) {
    die("<p style='color:red;text-align:center'>Invalid course.</p>");
}

/* ================= COURSE ================= */
$courseStmt = $conn->prepare("SELECT title FROM courses WHERE id=?");
$courseStmt->bind_param("i", $course_id);
$courseStmt->execute();
$course = $courseStmt->get_result()->fetch_assoc();

if (!$course) {
    die("<p style='color:red;text-align:center'>Course not found.</p>");
}

/* ================= QUESTIONS ================= */
$qStmt = $conn->prepare("SELECT * FROM quizzes WHERE course_id=?");
$qStmt->bind_param("i", $course_id);
$qStmt->execute();
$qRes = $qStmt->get_result();

$questions = [];
while ($q = $qRes->fetch_assoc()) {

    $options = [
        'A' => $q['option_a'],
        'B' => $q['option_b'],
        'C' => $q['option_c'],
        'D' => $q['option_d']
    ];

    $keys = array_keys($options);
    shuffle($keys);
    $shuffled = [];
    foreach ($keys as $k) $shuffled[$k] = $options[$k];

    $q['shuffled_options'] = $shuffled;
    $questions[] = $q;
}

shuffle($questions);
$total = count($questions);

/* ================= SUBMISSION ================= */
$score = 0;
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($questions as $q) {
        $qid = $q['id'];
        $sel = $_POST['answer'][$qid] ?? '';
        $cor = $q['correct_option'];

        $ok = ($sel === $cor) ? 1 : 0;
        if ($ok) $score++;

        $stmt = $conn->prepare(
            "INSERT INTO quiz_results (user_id, quiz_id, selected_option, is_correct)
             VALUES (?,?,?,?)"
        );
        $stmt->bind_param("iisi", $user_id, $qid, $sel, $ok);
        $stmt->execute();

        $results[] = [
            'question' => $q['question'],
            'options' => [
                'A'=>$q['option_a'],
                'B'=>$q['option_b'],
                'C'=>$q['option_c'],
                'D'=>$q['option_d']
            ],
            'selected'=>$sel,
            'correct'=>$cor,
            'is_correct'=>$ok
        ];
    }
}

$isMobile = preg_match('/Android|iPhone|iPad|iPod/i', $_SERVER['HTTP_USER_AGENT']);
?>
<!DOCTYPE html>
<html>
<head>
<title>Quiz – <?php echo htmlspecialchars($course['title']); ?></title>

<style>
body{
    font-family:'Poppins',sans-serif;
    background:linear-gradient(135deg,#e0eafc,#cfdef3);
    margin:0;padding:20px
}
.quiz-box{
    max-width:960px;margin:auto;background:#fff;
    padding:35px;border-radius:16px;
    box-shadow:0 15px 35px rgba(0,0,0,.15);
    user-select:none
}
h1{text-align:center}
.question{margin-bottom:22px}
label{
    display:block;background:#f4f6f7;
    padding:12px 14px;border-radius:10px;
    margin:6px 0;cursor:pointer
}
button{
    background:#2ecc71;border:none;color:#fff;
    padding:14px 26px;border-radius:10px;
    font-size:16px;cursor:pointer
}
button:hover{background:#27ae60}
.correct{color:green;font-weight:bold}
.wrong{color:red;font-weight:bold}
.correct-bg{background:#d4efdf;padding:6px;border-radius:8px}
.wrong-bg{background:#f5b7b1;padding:6px;border-radius:8px}

#camera{
    width:220px;border-radius:12px;
    border:2px solid #ccc
}

@media(max-width:768px){
    button{width:100%;font-size:18px}
    #camera{width:180px}
}
</style>

<script>
const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
let violations = 0;
const MAX_VIOLATIONS = isMobile ? 999 : 2;
let cameraStream = null;

/* ========= START CAMERA ========= */
async function startCamera(){
    try{
        cameraStream = await navigator.mediaDevices.getUserMedia({
            video:{facingMode:"user"}, audio:false
        });
        document.getElementById("camera").srcObject = cameraStream;
    }catch(e){
        violation("Camera permission denied");
    }
}

/* ========= SNAPSHOT ========= */
function captureSnapshot(reason){
    if(!cameraStream) return;

    const video = document.getElementById("camera");
    const canvas = document.createElement("canvas");
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    canvas.getContext("2d").drawImage(video,0,0);

    canvas.toBlob(blob=>{
        const fd = new FormData();
        fd.append("image", blob);
        fd.append("reason", reason);
        fd.append("course_id", <?php echo $course_id; ?>);
        fetch("backend/upload_evidence.php",{method:"POST",body:fd});
    },"image/jpeg",0.8);
}

/* ========= VIOLATION ========= */
function violation(reason){
    violations++;
    captureSnapshot(reason);

    if(isMobile){
        console.warn(reason);
        return;
    }

    alert("Warning: "+reason);
    if(violations >= MAX_VIOLATIONS){
        alert("Quiz auto-submitted due to violations");
        document.getElementById("quizForm").submit();
    }
}

/* ========= COPY BLOCK ========= */
document.addEventListener("contextmenu",e=>e.preventDefault());
document.addEventListener("keydown",e=>{
    if((e.ctrlKey && ['c','v','x','a','p'].includes(e.key.toLowerCase())) || e.key==="PrintScreen"){
        e.preventDefault();
        violation("Copy attempt detected");
    }
});

/* ========= DESKTOP MONITOR ========= */
if(!isMobile){
    document.addEventListener("visibilitychange",()=>{
        if(document.hidden) violation("Tab switch detected");
    });

    window.addEventListener("blur",()=>{
        violation("Window blur detected");
    });

    window.onload=()=>{
        document.documentElement.requestFullscreen?.();
        startCamera();
    };

    document.addEventListener("fullscreenchange",()=>{
        if(!document.fullscreenElement){
            violation("Fullscreen exit detected");
        }
    });
}else{
    window.onload=startCamera;
}

/* ========= CAMERA CHECK ========= */
setInterval(()=>{
    if(cameraStream){
        const tracks = cameraStream.getVideoTracks();
        if(tracks.length===0 || tracks[0].readyState!=="live"){
            violation("Camera disabled");
        }
    }
},5000);
</script>
</head>

<body>
<div class="quiz-box">

<h1><?php echo htmlspecialchars($course['title']); ?></h1>

<div style="text-align:center;margin-bottom:15px">
    <video id="camera" autoplay muted playsinline></video>
    <p style="font-size:14px;color:#555">
        Webcam monitoring is enabled during this quiz
    </p>
</div>

<?php if($isMobile){ ?>
<div style="background:#fff3cd;padding:12px;border-radius:8px;margin-bottom:15px">
⚠️ Mobile device detected. Switching apps may affect your attempt.
</div>
<?php } ?>

<?php if($_SERVER['REQUEST_METHOD'] !== 'POST'){ ?>

<form method="POST" id="quizForm">
<?php foreach($questions as $q){ ?>
<div class="question">
<p><?php echo htmlspecialchars($q['question']); ?></p>
<?php foreach($q['shuffled_options'] as $k=>$v){ ?>
<label>
<input type="radio" name="answer[<?php echo $q['id']; ?>]" value="<?php echo $k; ?>" required>
<?php echo htmlspecialchars($v); ?>
</label>
<?php } ?>
</div>
<?php } ?>
<button type="submit">Submit Quiz</button>
</form>

<?php } else { ?>

<h2>Your Results</h2>
<?php foreach($results as $r){ ?>
<p><b><?php echo htmlspecialchars($r['question']); ?></b></p>
<?php foreach($r['options'] as $k=>$v){
$cls="";
if($k===$r['correct']) $cls="correct-bg";
if($k===$r['selected'] && !$r['is_correct']) $cls="wrong-bg";
?>
<div class="<?php echo $cls; ?>">
(<?php echo $k; ?>) <?php echo htmlspecialchars($v); ?>
</div>
<?php } ?><hr>
<?php } ?>

<h3>Score: <?php echo $score; ?> / <?php echo $total; ?></h3>

<?php if($score >= ceil($total*0.5)){ ?>
<p class="correct">Passed</p>
<?php } else { ?>
<p class="wrong">Failed</p>
<a href="quizzes.php?course_id=<?php echo $course_id; ?>">Retry</a>
<?php } ?>

<?php } ?>
</div>
</body>
</html>
