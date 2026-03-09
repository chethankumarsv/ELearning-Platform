<?php
// /elearningplatform/my_rsvps.php
session_start();
require_once __DIR__ . '/includes/config.php';
if (function_exists('date_default_timezone_set')) date_default_timezone_set('Asia/Kolkata');

if (empty($_SESSION['user_id'])) {
  header("Location: auth.php");
  exit;
}
$userId = (int)$_SESSION['user_id'];
if (empty($_SESSION['csrf_rsvp'])) $_SESSION['csrf_rsvp'] = bin2hex(random_bytes(16));

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Query RSVPs (no dependency on a 'banner' column)
$sql = "SELECT e.id, e.title, e.event_date, COALESCE(e.location,'') AS location, '' AS banner, r.status
        FROM event_rsvps r
        JOIN events e ON e.id = r.event_id
        WHERE r.user_id=? AND r.status='going'
        ORDER BY e.event_date ASC";
$stmt = $conn->prepare($sql);
if (!$stmt) { die('SQL prepare failed: ' . $conn->error); }
$stmt->bind_param("i", $userId);
$stmt->execute();
$rows = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>My RSVPs — E-Learning</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --brand:#2563eb; --brand2:#60a5fa; --accent:#ff7e5f; --accent2:#fbbf24;
      --text:#111827; --muted:#6b7280; --bg:#f0f4ff; --card:#ffffff; --glass:#fffffff2;
      --shadow:0 14px 40px rgba(0,0,0,.12); --border:#e5e7eb; --ring:rgba(37,99,235,.18);
      --danger:#ef4444; --success:#10b981;
    }
    [data-theme="dark"]{
      --bg:#0f172a; --text:#f9fafb; --muted:#cbd5e1; --card:#0b1220; --glass:#0b1220e6;
      --shadow:0 14px 40px rgba(0,0,0,.45); --border:#1f2937;
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:Poppins,system-ui,-apple-system,Segoe UI,Roboto;background:var(--bg);color:var(--text);}

    /* NAVBAR */
    header{position:sticky;top:0;z-index:50;background:linear-gradient(90deg,var(--brand),var(--brand2));color:#fff;box-shadow:0 6px 20px rgba(0,0,0,.15)}
    .nav{max-width:1200px;margin:0 auto;padding:14px 18px;display:flex;align-items:center;justify-content:space-between}
    .brand{font-weight:800;display:flex;gap:8px;align-items:center}
    .nav a{color:#fff;text-decoration:none;font-weight:600;margin-left:14px;padding:6px 12px;border-radius:10px}
    .nav a:hover{background:rgba(255,255,255,.18)}
    .toggle{background:#fff;color:#0f172a;border:none;border-radius:10px;padding:6px 10px;cursor:pointer;font-weight:700}

    /* HERO */
    .hero{
      background:linear-gradient(120deg,#667eea,#764ba2);
      color:#fff;text-align:center;padding:80px 16px 110px;
      border-bottom-left-radius:45% 10%;border-bottom-right-radius:45% 10%;
      box-shadow:0 12px 30px rgba(0,0,0,.18)
    }
    .hero h1{margin:0 0 6px;font-size:40px}
    .hero p{margin:0;color:#e5e7eb}

    /* WRAP */
    .wrap{
      max-width:1200px;margin:-60px auto 40px;background:var(--glass);backdrop-filter:blur(12px);
      border-radius:24px;box-shadow:var(--shadow);padding:24px 22px
    }
    h1{margin:0 0 8px}
    .muted{color:var(--muted)}

    /* GRID & CARDS */
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px;margin-top:14px}
    .card{
      background:linear-gradient(135deg,var(--card),#f9fafb);
      border:1px solid var(--border);border-radius:20px;box-shadow:0 10px 26px rgba(0,0,0,.08);
      overflow:hidden;display:flex;flex-direction:column;transition:transform .15s ease, box-shadow .15s ease
    }
    .card:hover{transform:translateY(-4px);box-shadow:0 16px 36px rgba(0,0,0,.12)}
    .banner{height:150px;width:100%;object-fit:cover;background:#e5e7eb}
    .body{padding:16px}
    .title{font-weight:800;margin:0 0 6px;color:var(--accent)}
    .meta{font-size:13px;color:var(--muted);margin-bottom:10px}
    .chip{
      display:inline-block;background:var(--success);color:#fff;font-size:11px;font-weight:800;
      padding:4px 10px;border-radius:999px;box-shadow:0 4px 10px rgba(16,185,129,.3)
    }
    .actions{display:flex;gap:10px;padding:14px;flex-wrap:wrap}
    .btn{
      appearance:none;border:1px solid var(--border);background:var(--card);
      border-radius:12px;padding:10px 16px;font-weight:800;cursor:pointer;transition:.15s;
      text-decoration:none;color:var(--text)
    }
    .btn:hover{box-shadow:0 0 0 4px var(--ring)}
    .btn-ghost{background:transparent}
    .btn-danger{background:var(--danger);border-color:transparent;color:#fff}
    [data-theme="dark"] .btn-ghost{border-color:#334155;color:#e5e7eb}

    /* EMPTY STATE */
    .empty{
      margin-top:16px;display:grid;place-items:center;text-align:center;padding:22px;
      border:1px dashed var(--border);border-radius:20px;background:linear-gradient(135deg,var(--card),#f9fafb)
    }
    .empty a{display:inline-block;margin-top:10px;padding:10px 16px;border-radius:12px;background:var(--accent);color:#fff;font-weight:800}

    /* FOOTER (optional minimal spacing on this page) */
    @media (prefers-reduced-motion: reduce){
      .card:hover{transform:none;box-shadow:0 10px 26px rgba(0,0,0,.08)}
      .btn:hover{box-shadow:none}
    }
  </style>
</head>
<body>

<header>
  <div class="nav">
    <div class="brand">🎓 <span>E-Learning</span></div>
    <div>
      <a href="index.php">Home</a>
      <a href="event.php">Events</a>
      <a href="events_calendar.php">Calendar</a>
      <button class="toggle" id="themeBtn" onclick="toggleTheme()">🌙</button>
    </div>
  </div>
</header>

<div class="hero">
  <h1>My RSVPs</h1>
  <p>All the events you’re going to—organized and easy to manage.</p>
</div>

<div class="wrap">
  <h2 style="margin:0 0 6px">Upcoming registrations</h2>
  <div class="muted">You can view details or cancel your RSVP anytime.</div>

  <?php if ($rows && $rows->num_rows): ?>
    <div class="grid" id="grid">
      <?php while($e = $rows->fetch_assoc()): ?>
        <div class="card" data-id="<?= (int)$e['id'] ?>">
          <img class="banner" src="<?= h($e['banner'] ?: 'assets/img/event-placeholder.jpg') ?>" alt="">
          <div class="body">
            <h3 class="title"><?= h($e['title']) ?></h3>
            <div class="meta">
              <span class="chip">✔︎ Going</span>
              &nbsp; • &nbsp; 📅 <?= date('D, d M Y • h:i A', strtotime($e['event_date'])) ?>
              <?php if (!empty($e['location'])): ?> &nbsp; • &nbsp; 📍 <?= h($e['location']) ?><?php endif; ?>
            </div>
          </div>
          <div class="actions">
            <a class="btn btn-ghost" href="event.php?id=<?= (int)$e['id'] ?>" target="_blank" rel="noopener">View</a>
            <button class="btn btn-danger cancel" data-event="<?= (int)$e['id'] ?>">Cancel RSVP</button>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <div class="empty">
      <div class="muted">You haven’t registered for any events yet.</div>
      <a href="event.php">Browse Events</a>
    </div>
  <?php endif; ?>
</div>

<script>
  // Theme toggle (consistent with other pages)
  const themeBtn = document.getElementById('themeBtn');
  function setBtnIcon(){ themeBtn.textContent = document.body.getAttribute('data-theme')==='dark' ? '☀️' : '🌙'; }
  function toggleTheme(){
    const b=document.body;
    if(b.getAttribute("data-theme")==="dark"){ b.removeAttribute("data-theme"); localStorage.removeItem("theme"); }
    else { b.setAttribute("data-theme","dark"); localStorage.setItem("theme","dark"); }
    setBtnIcon();
  }
  if (localStorage.getItem("theme")==="dark")) {  // <-- remove extra )
    document.body.setAttribute("data-theme","dark");
  }
  setBtnIcon();

  // Cancel RSVP inline
  (function(){
    const csrf = <?= json_encode($_SESSION['csrf_rsvp'] ?? '') ?>;
    document.querySelectorAll('.cancel').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        if(!confirm('Cancel your RSVP for this event?')) return;
        btn.disabled = true;
        fetch('rsvp.php', {
          method:'POST',
          headers:{'Accept':'application/json'},
          body:new URLSearchParams({action:'cancel', event_id: btn.dataset.event, csrf})
        })
        .then(r=>r.json()).then(j=>{
          if(!j.ok) throw new Error(j.message||'Failed');
          const card = btn.closest('.card'); if(card) card.remove();
          if(!document.querySelector('.card')) {
            const grid = document.getElementById('grid');
            if (grid) grid.remove();
            const wrap = document.querySelector('.wrap');
            const box = document.createElement('div');
            box.className='empty';
            box.innerHTML = '<div class="muted">You haven’t registered for any events yet.</div><a href="event.php">Browse Events</a>';
            wrap.appendChild(box);
          }
        }).catch(e=>alert(e.message||'Error')).finally(()=>btn.disabled=false);
      });
    });
  })();
</script>
</body>
</html>
