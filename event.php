<?php
// /elearningplatform/event.php
session_start();
require_once __DIR__ . '/includes/config.php';

if (function_exists('date_default_timezone_set')) {
  date_default_timezone_set('Asia/Kolkata');
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ---------- DB helpers (prepared) ----------
function db_prepare(mysqli $conn, string $sql, string $types = '', array $params = []) {
  $stmt = $conn->prepare($sql);
  if (!$stmt) return false;
  if ($types && $params) { $stmt->bind_param($types, ...$params); }
  if (!$stmt->execute()) return false;
  return $stmt;
}

function get_event_by_id_or_title(mysqli $conn, $idOrTitle) {
  if ($idOrTitle === null || $idOrTitle === '') return null;

  // If numeric, try by ID first
  if (ctype_digit((string)$idOrTitle)) {
    $stmt = db_prepare($conn,
      "SELECT id, title, event_date, COALESCE(description,'') AS description,
              COALESCE(location,'') AS location, '' AS banner,
              COALESCE(registration_url,'') AS registration_url
       FROM events WHERE id = ? LIMIT 1", "i", [ (int)$idOrTitle ]);
    if ($stmt) {
      $res = $stmt->get_result();
      if ($res && $res->num_rows) return $res->fetch_assoc();
    }
  }

  // Fallback: treat as title (exact match first, then LIKE)
  $decoded = urldecode((string)$idOrTitle);

  $stmt = db_prepare($conn,
    "SELECT id, title, event_date, COALESCE(description,'') AS description,
            COALESCE(location,'') AS location, '' AS banner,
            COALESCE(registration_url,'') AS registration_url
     FROM events WHERE title = ? LIMIT 1", "s", [ $decoded ]);
  if ($stmt) {
    $res = $stmt->get_result();
    if ($res && $res->num_rows) return $res->fetch_assoc();
  }

  $like = "%".$decoded."%";
  $stmt = db_prepare($conn,
    "SELECT id, title, event_date, COALESCE(description,'') AS description,
            COALESCE(location,'') AS location, '' AS banner,
            COALESCE(registration_url,'') AS registration_url
     FROM events WHERE title LIKE ? ORDER BY event_date ASC LIMIT 1", "s", [ $like ]);
  if ($stmt) {
    $res = $stmt->get_result();
    if ($res && $res->num_rows) return $res->fetch_assoc();
  }
  return null;
}

function list_upcoming_events(mysqli $conn, ?string $q = null, ?string $month = null) {
  $base = "SELECT id, title, event_date, COALESCE(description,'') AS description,
                  COALESCE(location,'') AS location, '' AS banner,
                  COALESCE(registration_url,'') AS registration_url
           FROM events WHERE event_date >= CURDATE()";
  $types = '';
  $params = [];

  if ($q) {
    $base .= " AND (title LIKE ? OR description LIKE ? OR location LIKE ?)";
    $types .= "sss";
    $like = "%".$q."%";
    array_push($params, $like, $like, $like);
  }
  if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
    // Filter by month (YYYY-MM)
    $base .= " AND DATE_FORMAT(event_date, '%Y-%m') = ?";
    $types .= "s";
    $params[] = $month;
  }
  $base .= " ORDER BY event_date ASC LIMIT 30";
  $stmt = db_prepare($conn, $base, $types, $params);
  if (!$stmt) return [];
  $res = $stmt->get_result();
  $rows = [];
  while ($row = $res->fetch_assoc()) $rows[] = $row;
  return $rows;
}

/* ================================
   RSVP helpers
================================ */
if (empty($_SESSION['csrf_rsvp'])) $_SESSION['csrf_rsvp'] = bin2hex(random_bytes(16));

function get_attendee_count(mysqli $conn, int $eventId): int {
  $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM event_rsvps WHERE event_id=? AND status='going'");
  $stmt->bind_param("i", $eventId);
  $stmt->execute();
  $c = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
  $stmt->close();
  return $c;
}
function get_user_rsvp_status(mysqli $conn, int $eventId, int $userId): ?string {
  $stmt = $conn->prepare("SELECT status FROM event_rsvps WHERE event_id=? AND user_id=? LIMIT 1");
  $stmt->bind_param("ii", $eventId, $userId);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  return $row['status'] ?? null;
}

/* ================================
   Comments helpers + CSRF
================================ */
if (empty($_SESSION['csrf_cmt'])) $_SESSION['csrf_cmt'] = bin2hex(random_bytes(16));
if (empty($_SESSION['csrf_cmt_admin'])) $_SESSION['csrf_cmt_admin'] = bin2hex(random_bytes(16));

function fetch_event_comments(mysqli $conn, int $eventId, bool $includePendingForUser=false, int $userId=0): array {
  $sql = "SELECT c.id, c.parent_id, c.content, c.status, c.created_at, u.name
          FROM event_comments c
          JOIN users u ON u.id = c.user_id
          WHERE c.event_id = ? AND (c.status='visible'".($includePendingForUser?" OR (c.status='pending' AND c.user_id=?)":"").")
          ORDER BY c.created_at ASC";
  if ($includePendingForUser) { $stmt = $conn->prepare($sql); $stmt->bind_param("ii",$eventId, $userId); }
  else { $stmt = $conn->prepare($sql); $stmt->bind_param("i",$eventId); }
  $stmt->execute(); $res = $stmt->get_result();
  $rows = [];
  while ($r = $res->fetch_assoc()) $rows[] = $r;
  $stmt->close();

  // Build 1-level thread
  $byId = []; $tree = [];
  foreach($rows as $r){ $r['children']=[]; $byId[$r['id']]=$r; }
  foreach($byId as $id => &$node){
    if (!empty($node['parent_id']) && isset($byId[$node['parent_id']])) {
      $byId[$node['parent_id']]['children'][] = &$node;
    } else {
      $tree[] = &$node;
    }
  }
  return $tree;
}

// ---------- ICS download ----------
if (isset($_GET['ics'])) {
  $event = get_event_by_id_or_title($conn, $_GET['ics']);
  if ($event) {
    $title = $event['title'];
    $start = strtotime($event['event_date']);
    $end   = $start ? $start + 2*60*60 : time() + 2*60*60; // 2 hours
    $uid   = bin2hex(random_bytes(8)) . "@elearning.local";
    $loc   = $event['location'] ?: 'TBD';
    $desc  = strip_tags($event['description'] ?? '');

    $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//E-Learning//Events//EN\r\n";
    $ics .= "BEGIN:VEVENT\r\n";
    $ics .= "UID:$uid\r\n";
    $ics .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
    $ics .= "DTSTART:" . gmdate('Ymd\THis\Z', $start ?: time()) . "\r\n";
    $ics .= "DTEND:"   . gmdate('Ymd\THis\Z', $end) . "\r\n";
    $ics .= "SUMMARY:" . str_replace(["\r","\n"]," ",$title) . "\r\n";
    $ics .= "LOCATION:" . str_replace(["\r","\n"]," ",$loc) . "\r\n";
    if ($desc) $ics .= "DESCRIPTION:" . str_replace(["\r","\n"]," ",$desc) . "\r\n";
    $ics .= "END:VEVENT\r\nEND:VCALENDAR\r\n";

    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="event.ics"');
    echo $ics;
    exit;
  } else {
    http_response_code(404);
    echo "Event not found.";
    exit;
  }
}

// ---------- Routing ----------
$eventId = $_GET['id'] ?? null;
$searchQ = isset($_GET['q']) ? trim($_GET['q']) : null;
$monthQ  = isset($_GET['month']) ? trim($_GET['month']) : null;

$event    = $eventId ? get_event_by_id_or_title($conn, $eventId) : null;
$upcoming = !$event ? list_upcoming_events($conn, $searchQ, $monthQ) : [];
?>
<!DOCTYPE html>
<html lang="en" data-theme="">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= $event ? 'Event: '.h($event['title']) : 'Upcoming Events' ?> — E-Learning</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --brand:#2563eb; --brand2:#60a5fa; --accent:#ff7e5f; --accent2:#fbbf24; --success:#22c55e;
      --text:#0f172a; --muted:#64748b; --bg:#eef2ff; --card:rgba(255,255,255,.9); --shadow:0 10px 30px rgba(2,6,23,.12);
      --ring:rgba(37,99,235,.2); --danger:#ef4444;
      --grad-hero:linear-gradient(135deg,#6366f1 0%, #22d3ee 100%);
      --grad-card:linear-gradient(180deg, rgba(255,255,255,.85), rgba(255,255,255,.75));
    }
    [data-theme="dark"]{
      --bg:#0b1220; --text:#e5edff; --muted:#a3b2d3; --card:rgba(17,24,39,.72); --shadow:0 12px 34px rgba(0,0,0,.45);
      --ring:rgba(99,102,241,.28);
      --grad-hero:linear-gradient(135deg,#1e293b 0%, #0ea5e9 100%);
      --grad-card:linear-gradient(180deg, rgba(17,24,39,.92), rgba(17,24,39,.86));
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:Poppins,system-ui,-apple-system,Segoe UI,Roboto;background:var(--bg);color:var(--text)}

    /* ---------- NAVBAR ---------- */
    header{position:sticky;top:0;z-index:50;backdrop-filter:saturate(140%) blur(8px);}
    .nav{
      max-width:1100px;margin:10px auto;background:var(--card);border:1px solid rgba(99,102,241,.12);
      box-shadow:var(--shadow);border-radius:18px;padding:12px 16px;display:flex;align-items:center;justify-content:space-between
    }
    .brand{display:flex;align-items:center;gap:10px;font-weight:800}
    .brand i{display:inline-grid;place-items:center;width:34px;height:34px;border-radius:10px;
      background:linear-gradient(135deg,#3b82f6,#22d3ee);color:#fff}
    .nav a{color:var(--text);text-decoration:none;font-weight:700;padding:8px 12px;border-radius:10px;opacity:.9}
    .nav a:hover{background:rgba(99,102,241,.12)}
    .toggle{background:linear-gradient(135deg,#22d3ee,#3b82f6);color:#fff;border:none;border-radius:12px;padding:8px 12px;
      cursor:pointer;font-weight:800;box-shadow:0 8px 18px rgba(59,130,246,.25)}
    .toggle:hover{filter:brightness(1.05)}

    /* ---------- HERO ---------- */
    .hero{
      background:var(--grad-hero); color:#fff; text-align:center; padding:90px 16px 110px;
      position:relative; overflow:hidden; border-bottom-left-radius:40% 10%; border-bottom-right-radius:40% 10%;
      box-shadow:inset 0 -20px 40px rgba(0,0,0,.15)
    }
    .hero::after,.hero::before{
      content:""; position:absolute; width:420px; height:420px; background:radial-gradient(closest-side, rgba(255,255,255,.25), rgba(255,255,255,0));
      filter:blur(14px); border-radius:50%; pointer-events:none; mix-blend:screen
    }
    .hero::before{ top:-120px; left:-120px }
    .hero::after{ bottom:-120px; right:-120px }
    .hero h1{margin:0 0 10px;font-size:46px;letter-spacing:.4px;text-shadow:0 10px 30px rgba(0,0,0,.25)}
    .hero p{margin:0;opacity:.95}

    /* ---------- CONTAINER ---------- */
    .wrap{
      max-width:1100px;margin:-60px auto 60px;padding:26px;border-radius:22px;
      background:var(--card); box-shadow:var(--shadow); border:1px solid rgba(99,102,241,.12);
      backdrop-filter:blur(10px)
    }

    /* ---------- FILTER BAR ---------- */
    .toolbar{display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;margin-bottom:18px}
    .search{flex:1;display:flex;gap:10px}
    .search input,.search select{
      flex:1;padding:12px 14px;border-radius:14px;border:1px solid rgba(99,102,241,.2);background:var(--grad-card);
      outline:none;font-size:14px;color:var(--text)
    }
    .search input:focus,.search select:focus{box-shadow:0 0 0 6px var(--ring);border-color:#93c5fd}
    .btn{appearance:none;border:none;border-radius:14px;padding:10px 16px;font-weight:800;cursor:pointer;text-decoration:none;text-align:center}
    .btn-primary{background:linear-gradient(135deg,#ff7e5f,#fbbf24);color:#16161a;box-shadow:0 10px 20px rgba(251,191,36,.28)}
    .btn-primary:hover{filter:brightness(1.03)}
    .btn-ghost{background:transparent;border:2px solid rgba(99,102,241,.25);color:var(--text)}
    .btn-ghost:hover{background:rgba(99,102,241,.08)}
    .btn-danger{background:linear-gradient(135deg,#ef4444,#f97316);color:#fff;box-shadow:0 10px 20px rgba(239,68,68,.25)}

    /* ---------- GRID & CARDS ---------- */
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:18px}
    .card{
      background:var(--grad-card);border:1px solid rgba(99,102,241,.15);border-radius:20px;box-shadow:var(--shadow);
      overflow:hidden;display:flex;flex-direction:column;transform:translateY(0);transition:transform .18s ease, box-shadow .18s ease
    }
    .card:hover{transform:translateY(-6px);box-shadow:0 16px 40px rgba(2,6,23,.18)}
    .banner{height:160px;width:100%;object-fit:cover;background:#e2e8f0}
    .body{padding:16px}
    .title{font-weight:900;margin:0 0 6px;background:linear-gradient(135deg,#2563eb,#06b6d4);-webkit-background-clip:text;color:transparent}
    .meta{font-size:13px;color:var(--muted);margin-bottom:10px}
    .desc{font-size:14px;color:var(--text);opacity:.85;line-height:1.55}

    /* ---------- DETAIL LAYOUT ---------- */
    .detail{display:grid;grid-template-columns:1fr;gap:20px}
    @media(min-width:900px){ .detail{grid-template-columns:1.4fr 1fr;} }
    .detail .bigimg{width:100%;height:360px;object-fit:cover;border-radius:18px;background:#e5e7eb;box-shadow:var(--shadow)}
    .panel{background:var(--grad-card);border:1px solid rgba(99,102,241,.15);border-radius:20px;box-shadow:var(--shadow);padding:18px}
    .row{display:flex;align-items:center;gap:12px;margin:10px 0}
    .row b{min-width:120px}
    .chip{display:inline-block;background:linear-gradient(135deg,#10b981,#34d399);color:#fff;border-radius:999px;padding:6px 12px;font-size:12px;font-weight:900}

    /* ---------- COMMENTS ---------- */
    .cWrap{margin-top:24px}
    .cTitle{font-weight:900;margin:0 0 8px;background:linear-gradient(135deg,#ff7e5f,#fbbf24);-webkit-background-clip:text;color:transparent}
    .cMeta{color:var(--muted);font-size:13px;margin-bottom:10px}
    .cForm{display:flex;gap:10px;align-items:flex-start;margin:10px 0 16px}
    .cForm textarea{
      flex:1;padding:12px;border-radius:14px;border:1px solid rgba(99,102,241,.2);font-size:14px;min-height:100px;resize:vertical;background:var(--grad-card);color:var(--text)
    }
    .btn-light{background:transparent;border:2px solid rgba(99,102,241,.25);color:var(--text);border-radius:14px;padding:10px 16px;font-weight:800;cursor:pointer}
    .cList{display:flex;flex-direction:column;gap:12px}
    .cmt{border:1px solid rgba(99,102,241,.18);border-radius:16px;padding:12px;background:var(--grad-card)}
    .cmt .cmtHead{display:flex;gap:10px;align-items:center;margin-bottom:6px}
    .cmt .cmtHead b{font-weight:900}
    .cmt .meta{color:var(--muted);font-size:12px}
    .cmt .cmtBody{white-space:pre-wrap;line-height:1.55}
    .cmt .cmtActions{display:flex;gap:8px;margin-top:8px}
    .replies{margin-left:18px;display:flex;flex-direction:column;gap:10px}
    .badge-wait{display:inline-block;background:#f59e0b;color:#fff;border-radius:999px;padding:2px 8px;font-size:11px;font-weight:900;margin-left:6px}
    .adminTools{display:flex;gap:6px;margin-left:auto}

    /* ---------- FOOTER ---------- */
    footer{color:#cbd5e1;margin-top:40px;padding:40px 16px 24px;text-align:center;font-size:14px}
    .smallmuted{color:#94a3b8;font-size:12px;margin-top:8px}

    /* ---------- Motion pref ---------- */
    @media (prefers-reduced-motion: reduce) {
      .card:hover{ transform:none; }
      .toggle,.btn,.btn-primary{ transition:none; }
    }
  </style>
</head>
<body>

<header>
  <div class="nav">
    <div class="brand"><i>🎓</i>E-Learning</div>
    <div>
      <a href="index.php">Home</a>
      <a href="dashboard.php">Dashboard</a>
      <?php if(!empty($_SESSION['user_id'])): ?>
        <a href="my_rsvps.php">My RSVPs</a>
      <?php endif; ?>
      <button class="toggle" id="themeBtn" onclick="toggleTheme()">🌙</button>
    </div>
  </div>
</header>

<div class="hero">
  <h1><?= $event ? 'Event Details' : 'Upcoming Events' ?></h1>
  <p><?= $event ? 'Everything you need to attend.' : 'Don’t miss what’s coming next!' ?></p>
</div>

<div class="wrap">
<?php if ($event): ?>
  <!-- EVENT DETAIL -->
  <div class="detail">
    <img class="bigimg" src="<?= h($event['banner'] ?: 'assets/img/event-placeholder.jpg') ?>" alt="">
    <div class="panel">
      <h2 class="title"><?= h($event['title']) ?></h2>
      <div class="meta">
        📅 <?= date('D, d M Y • h:i A', strtotime($event['event_date'])) ?>
        <?php if (!empty($event['location'])): ?> &nbsp; &bull; &nbsp; 📍 <?= h($event['location']) ?><?php endif; ?>
      </div>
      <p class="desc" style="margin:10px 0 16px"><?= nl2br(h($event['description'])) ?></p>

      <div class="row">
        <b>Status</b>
        <?php $isPast = strtotime($event['event_date']) < time(); ?>
        <?= $isPast ? '<span class="chip" style="background:linear-gradient(135deg,#ef4444,#f97316)">Past</span>' : '<span class="chip">Upcoming</span>' ?>
      </div>

      <?php
        // attendee count + current user RSVP
        $attendees  = get_attendee_count($conn, (int)$event['id']);
        $userStatus = null;
        if (!empty($_SESSION['user_id'])) {
          $userStatus = get_user_rsvp_status($conn, (int)$event['id'], (int)$_SESSION['user_id']);
        }
      ?>
      <div class="row">
        <b>Attendees</b>
        <span class="chip" id="attChip">👥 <?= (int)$attendees ?></span>
      </div>

      <div class="actions" style="padding-left:0">
        <?php if (!empty($event['registration_url'])): ?>
          <a class="btn btn-primary" href="<?= h($event['registration_url']) ?>" target="_blank" rel="noopener">Official Register</a>
        <?php endif; ?>
        <a class="btn btn-ghost" href="?ics=<?= urlencode((string)$event['id']) ?>">Add to Calendar (.ics)</a>
        <a class="btn btn-ghost" href="event.php">Back to Events</a>

        <?php if ($isPast): ?>
          <span class="btn btn-ghost" style="opacity:.6;cursor:not-allowed">Event Finished</span>
        <?php else: ?>
          <?php if (empty($_SESSION['user_id'])): ?>
            <a class="btn btn-primary" href="auth.php">Login to RSVP</a>
          <?php else: ?>
            <?php if ($userStatus === 'going'): ?>
              <button class="btn btn-danger" id="rsvpCancelBtn" data-event="<?= (int)$event['id'] ?>">Cancel RSVP</button>
            <?php else: ?>
              <button class="btn btn-primary" id="rsvpBtn" data-event="<?= (int)$event['id'] ?>">RSVP: I’m Going</button>
            <?php endif; ?>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <?php if (!empty($_SESSION['user_id']) && !$isPast && $userStatus === 'going'): ?>
        <div class="row">
          <b>Email Reminder</b>
          <?php
            $optQ = $conn->prepare("SELECT reminder_opt_in FROM event_rsvps WHERE event_id=? AND user_id=? LIMIT 1");
            $eid  = (int)$event['id']; $uid = (int)$_SESSION['user_id'];
            $optQ->bind_param("ii", $eid, $uid);
            $optQ->execute(); $optRow = $optQ->get_result()->fetch_assoc(); $optQ->close();
            $optIn = (int)($optRow['reminder_opt_in'] ?? 1);
          ?>
          <label style="display:flex;align-items:center;gap:10px">
            <input id="remToggle" type="checkbox" <?= $optIn ? 'checked' : '' ?> >
            <span class="chip" id="remState"><?= $optIn ? 'On' : 'Off' ?></span>
          </label>
        </div>
      <?php endif; ?>

      <?php
        // COMMENTS
        $includeMine   = !empty($_SESSION['user_id']);
        $commentsTree  = fetch_event_comments($conn, (int)$event['id'], $includeMine, (int)($_SESSION['user_id'] ?? 0));
      ?>
      <div class="cWrap">
        <h3 class="cTitle">💬 Comments & Q&A</h3>
        <div class="cMeta">Be respectful and keep it on-topic. Replies are supported.</div>

        <?php if (!empty($_SESSION['user_id'])): ?>
          <form id="cForm" class="cForm" onsubmit="return false">
            <textarea id="cContent" placeholder="Ask a question or share a thought..."></textarea>
            <input type="hidden" id="cParent" value="">
            <button class="btn btn-primary" id="cSubmit">Post</button>
          </form>
        <?php else: ?>
          <p class="cMeta">Please <a href="auth.php">log in</a> to comment.</p>
        <?php endif; ?>

        <div id="cList" class="cList">
          <?php
            function render_comment_node($n, $isAdmin){
              $pending = ($n['status']==='pending');
              echo '<div class="cmt" data-id="'.(int)$n['id'].'">';
                echo '<div class="cmtHead"><b>'.h($n['name']).'</b>';
                echo ' <span class="meta">'.date('d M Y, h:i A', strtotime($n['created_at'] ?? 'now')).'</span>';
                if ($pending) echo ' <span class="badge-wait">Pending</span>';
                if ($isAdmin) {
                  echo '<div class="adminTools">';
                  echo '<button class="btn-light adm" data-act="approve" data-id="'.(int)$n['id'].'">Approve</button>';
                  echo '<button class="btn-light adm" data-act="hide" data-id="'.(int)$n['id'].'">Hide</button>';
                  echo '<button class="btn-light adm" data-act="delete" data-id="'.(int)$n['id'].'">Delete</button>';
                  echo '</div>';
                }
                echo '</div>';
                echo '<div class="cmtBody">'.nl2br(h($n['content'])).'</div>';
                echo '<div class="cmtActions"><button class="btn-light replyBtn" data-id="'.(int)$n['id'].'">Reply</button></div>';
                if (!empty($n['children'])) {
                  echo '<div class="replies">';
                  foreach ($n['children'] as $ch) render_comment_node($ch, $isAdmin);
                  echo '</div>';
                }
              echo '</div>';
            }
            $isAdmin = (!empty($_SESSION['role']) && $_SESSION['role']==='admin');
            foreach ($commentsTree as $node) render_comment_node($node, $isAdmin);
            if (empty($commentsTree)) echo '<p class="cMeta">No comments yet — be the first to ask!</p>';
          ?>
        </div>
      </div>

      <!-- Comments + RSVP JS -->
      <script>
      (function(){
        // comment JS
        const csrfCmt = <?= json_encode($_SESSION['csrf_cmt'] ?? '') ?>;
        const csrfAdm = <?= json_encode($_SESSION['csrf_cmt_admin'] ?? '') ?>;
        const eventId = <?= (int)$event['id'] ?>;

        const cForm    = document.getElementById('cForm');
        const cContent = document.getElementById('cContent');
        const cParent  = document.getElementById('cParent');
        const cSubmit  = document.getElementById('cSubmit');
        const cList    = document.getElementById('cList');

        function bindReplyButtons(scope){
          (scope || document).querySelectorAll('.replyBtn').forEach(btn=>{
            btn.onclick = ()=>{
              const id = btn.dataset.id;
              cParent.value = id;
              cContent.placeholder = "Replying…";
              cContent.focus();
            };
          });
        }
        function bindAdminButtons(scope){
          (scope || document).querySelectorAll('.adm').forEach(btn=>{
            btn.onclick = ()=>{
              const act = btn.dataset.act, id = btn.dataset.id;
              if (act==='delete' && !confirm('Delete this comment?')) return;
              fetch('comment_action.php', {
                method:'POST',
                headers:{'Accept':'application/json'},
                body:new URLSearchParams({csrf: csrfAdm, action: act, id})
              }).then(r=>r.json()).then(j=>{
                if(!j.ok) throw new Error(j.message||'Error');
                if (act==='approve'){ btn.closest('.cmt').querySelector('.badge-wait')?.remove(); }
                if (act==='hide'){ btn.closest('.cmt').style.opacity = .5; }
                if (act==='delete'){ btn.closest('.cmt').remove(); }
              }).catch(e=>alert(e.message||'Error'));
            };
          });
        }
        if (cForm){
          cSubmit.onclick = ()=>{
            const content = (cContent.value||'').trim();
            if (!content) return alert('Type something first.');
            cSubmit.disabled = true;
            fetch('comment_add.php', {
              method:'POST',
              headers:{'Accept':'application/json'},
              body:new URLSearchParams({
                csrf: csrfCmt,
                event_id: eventId,
                parent_id: cParent.value,
                content
              })
            }).then(r=>r.json()).then(j=>{
              if (!j.ok) throw new Error(j.message||'Failed');
              if (j.status==='visible' && j.html){
                if (cParent.value){
                  const parent = cList.querySelector('.cmt[data-id="'+cParent.value+'"]');
                  let rep = parent.querySelector('.replies');
                  if (!rep){ rep = document.createElement('div'); rep.className='replies'; parent.appendChild(rep); }
                  const wrapper = document.createElement('div'); wrapper.innerHTML = j.html;
                  rep.appendChild(wrapper.firstChild);
                  bindReplyButtons(rep); bindAdminButtons(rep);
                } else {
                  const wrapper = document.createElement('div'); wrapper.innerHTML = j.html;
                  cList.appendChild(wrapper.firstChild);
                  bindReplyButtons(cList); bindAdminButtons(cList);
                }
              } else {
                alert(j.message || 'Submitted for moderation.');
              }
              cContent.value=''; cParent.value=''; cContent.placeholder='Ask a question or share a thought...';
            }).catch(e=>alert(e.message||'Error')).finally(()=>{ cSubmit.disabled=false; });
          };
        }
        bindReplyButtons(); bindAdminButtons();

        // RSVP JS
        const csrf = <?= json_encode($_SESSION['csrf_rsvp'] ?? '') ?>;
        const chip = document.getElementById('attChip');
        const toggle = document.getElementById('remToggle');
        const state  = document.getElementById('remState');

        function getCount() { return parseInt((chip?.textContent||'0').replace(/\D+/g,'')) || 0; }
        function setCount(n){ if(chip) chip.textContent = '👥 ' + n; }

        function send(action, eventId, btn){
          if(!csrf){ alert("Security token missing. Refresh the page and try again."); return; }
          btn.disabled = true;
          fetch('rsvp.php', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: new URLSearchParams({ action, event_id: eventId, csrf })
          })
          .then(r => r.json())
          .then(j => {
            if(!j.ok) throw new Error(j.message || 'Failed');
            const isReg = action === 'register';
            const current = getCount();
            setCount(isReg ? current + 1 : Math.max(0, current - 1));
            if(isReg){
              btn.outerHTML = '<button class="btn btn-danger" id="rsvpCancelBtn" data-event="'+eventId+'">Cancel RSVP</button>';
            } else {
              btn.outerHTML = '<button class="btn btn-primary" id="rsvpBtn" data-event="'+eventId+'">RSVP: I’m Going</button>';
            }
          })
          .catch(e => alert(e.message || 'Error'))
          .finally(() => bindRSVP());
        }

        function bindRSVP(){
          const go = document.getElementById('rsvpBtn');
          if(go) go.onclick = () => send('register', go.dataset.event, go);
          const cancel = document.getElementById('rsvpCancelBtn');
          if(cancel) cancel.onclick = () => {
            if(confirm('Cancel your RSVP for this event?')) send('cancel', cancel.dataset.event, cancel);
          };
        }
        bindRSVP();

        // Reminder toggle
        if (toggle && state) {
          toggle.addEventListener('change', ()=>{
            fetch('rsvp_pref.php', {
              method: 'POST',
              headers: {'Accept':'application/json'},
              body: new URLSearchParams({ csrf, event_id: <?= (int)$event['id'] ?>, opt_in: toggle.checked ? 1 : 0 })
            })
            .then(r=>r.json())
            .then(j=>{
              if(!j.ok) throw new Error(j.message||'Failed to update');
              state.textContent = j.opt_in ? 'On' : 'Off';
            })
            .catch(e=>{
              alert(e.message||'Error'); toggle.checked = !toggle.checked;
            });
          });
        }
      })();
      </script>

    </div>
  </div>

<?php else: ?>
  <!-- UPCOMING LIST + FILTERS -->
  <form class="toolbar" method="get" action="event.php">
    <div class="search">
      <input type="text" name="q" placeholder="Search title, location, description..." value="<?= h($searchQ ?? '') ?>">
      <select name="month" title="Filter by month">
        <?php
          $now = new DateTime('first day of this month');
          for ($i=0; $i<8; $i++) {
            $m = clone $now; $m->modify("+$i month");
            $val = $m->format('Y-m');
            $label = $m->format('M Y');
            $sel = ($monthQ === $val) ? 'selected' : '';
            echo "<option value=\"".h($val)."\" $sel>$label</option>";
          }
        ?>
      </select>
    </div>
    <div>
      <button class="btn btn-primary" type="submit">Apply</button>
      <a class="btn btn-ghost" href="event.php">Reset</a>
    </div>
  </form>

  <?php if (!empty($upcoming)): ?>
    <div class="grid">
      <?php foreach ($upcoming as $e): ?>
        <div class="card">
          <img class="banner" src="<?= h($e['banner'] ?: 'assets/img/event-placeholder.jpg') ?>" alt="">
          <div class="body">
            <h3 class="title"><?= h($e['title']) ?></h3>
            <div class="meta">
              📅 <?= date('D, d M Y • h:i A', strtotime($e['event_date'])) ?>
              <?php if (!empty($e['location'])): ?> &nbsp; • &nbsp; 📍 <?= h($e['location']) ?><?php endif; ?>
            </div>
            <p class="desc"><?= h(mb_strimwidth(strip_tags($e['description']), 0, 140, '…')) ?></p>
          </div>
          <div class="actions" style="padding:16px;display:flex;gap:10px;flex-wrap:wrap">
            <a class="btn btn-primary" href="event.php?id=<?= (int)$e['id'] ?>">View</a>
            <?php if (!empty($e['registration_url'])): ?>
              <a class="btn btn-ghost" target="_blank" rel="noopener" href="<?= h($e['registration_url']) ?>">Register</a>
            <?php else: ?>
              <a class="btn btn-ghost" href="event.php?ics=<?= urlencode((string)$e['id']) ?>">Add .ics</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p style="text-align:center;color:var(--muted);margin:20px 0">No upcoming events found.</p>
  <?php endif; ?>
<?php endif; ?>
</div>

<footer>
  <div class="smallmuted">© <?= date('Y') ?> E-Learning Platform • Events</div>
</footer>

<script>
  // Theme toggle
  const themeBtn = document.getElementById('themeBtn');
  function setBtnIcon(){ themeBtn.textContent = document.body.getAttribute('data-theme')==='dark' ? '☀️' : '🌙'; }
  function toggleTheme(){
    const b=document.body;
    if(b.getAttribute("data-theme")==="dark"){ b.removeAttribute("data-theme"); localStorage.removeItem("theme"); }
    else { b.setAttribute("data-theme","dark"); localStorage.setItem("theme","dark"); }
    setBtnIcon();
  }
  if(localStorage.getItem("theme")==="dark")){ document.body.setAttribute("data-theme","dark"); }
  setBtnIcon();
</script>
</body>
</html>
