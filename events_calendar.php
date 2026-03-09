<?php
// /elearningplatform/events_calendar.php
session_start();
require_once __DIR__ . '/includes/config.php';

if (function_exists('date_default_timezone_set')) date_default_timezone_set('Asia/Kolkata');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Inputs: year, month
$y = isset($_GET['year'])  ? max(1970, min(2100, (int)$_GET['year'])) : (int)date('Y');
$m = isset($_GET['month']) ? max(1, min(12, (int)$_GET['month']))    : (int)date('n');

// Calc month bounds
$firstOfMonth = DateTime::createFromFormat('Y-n-j', "$y-$m-1");
$monthStart   = clone $firstOfMonth;
$monthEnd     = (clone $firstOfMonth)->modify('last day of this month')->setTime(23,59,59);

// Query events in this calendar grid range (show neighbors to fill weeks)
$gridStart = (clone $firstOfMonth)->modify('monday this week'); // week starts Monday
$gridEnd   = (clone $monthEnd)->modify('sunday this week')->setTime(23,59,59);

// Fetch events in [gridStart, gridEnd] (no banner column needed)
$sql = "SELECT id, title, event_date, COALESCE(location,'') AS location, '' AS banner
        FROM events
        WHERE event_date BETWEEN ? AND ?
        ORDER BY event_date ASC";
$stmt = $conn->prepare($sql);
if (!$stmt) { die("SQL prepare failed: " . $conn->error); }

$startStr = $gridStart->format('Y-m-d H:i:s');
$endStr   = $gridEnd->format('Y-m-d H:i:s');
$stmt->bind_param("ss", $startStr, $endStr);
$stmt->execute();
$res = $stmt->get_result();

// Group by Y-m-d for quick access
$eventsByDay = [];
if ($res) {
  while ($row = $res->fetch_assoc()) {
    $key = date('Y-m-d', strtotime($row['event_date']));
    $eventsByDay[$key][] = $row;
  }
}
$stmt->close();

// Prev/Next month
$prev = (clone $firstOfMonth)->modify('-1 month');
$next = (clone $firstOfMonth)->modify('+1 month');

$todayKey = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en" data-theme="">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Calendar — <?= h($firstOfMonth->format('F Y')) ?> • E-Learning</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --brand:#2563eb; --brand2:#60a5fa; --accent:#ff7e5f; --accent2:#fbbf24;
      --text:#111827; --muted:#6b7280; --bg:#f0f4ff; --card:#ffffff; --glass:#fffffff2;
      --shadow:0 14px 40px rgba(0,0,0,.12); --ring:rgba(37,99,235,.18);
      --today:#10b981; --border:#e5e7eb;
    }
    [data-theme="dark"]{
      --bg:#0f172a; --text:#f9fafb; --muted:#cbd5e1; --card:#0b1220; --glass:#0b1220e6;
      --shadow:0 14px 40px rgba(0,0,0,.45); --border:#1f2937;
    }
    *{box-sizing:border-box}
    body{
      margin:0;font-family:Poppins,system-ui,-apple-system,Segoe UI,Roboto;
      background:var(--bg);color:var(--text);
    }
    a{text-decoration:none;color:inherit}

    /* NAVBAR */
    header{
      position:sticky;top:0;z-index:50;
      background:linear-gradient(90deg,var(--brand),var(--brand2));
      color:#fff;box-shadow:0 6px 20px rgba(0,0,0,.15)
    }
    .nav{max-width:1200px;margin:0 auto;padding:14px 18px;display:flex;align-items:center;justify-content:space-between}
    .brand{font-weight:800;display:flex;gap:8px;align-items:center}
    .nav a{color:#fff;font-weight:600;margin-left:14px;padding:6px 12px;border-radius:10px}
    .nav a:hover{background:rgba(255,255,255,.18)}
    .toggle{background:#fff;color:#0f172a;border:none;border-radius:10px;padding:6px 10px;cursor:pointer;font-weight:700}

    /* HERO */
    .hero{
      background:linear-gradient(120deg,#667eea,#764ba2);
      color:#fff;text-align:center;padding:80px 16px 110px;
      border-bottom-left-radius:45% 10%;border-bottom-right-radius:45% 10%;
      box-shadow:0 12px 30px rgba(0,0,0,.18);
    }
    .hero h1{margin:0 0 6px;font-size:42px}
    .hero p{margin:0;color:#e5e7eb}

    /* MAIN WRAP (glassy) */
    .wrap{
      max-width:1200px;margin:-60px auto 40px;background:var(--glass);backdrop-filter:blur(12px);
      border-radius:24px;box-shadow:var(--shadow);padding:22px
    }

    /* TOOLBAR */
    .toolbar{display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;margin-bottom:14px}
    .title{font-size:20px;font-weight:800}
    .controls{display:flex;gap:8px;align-items:center}
    .btn{
      appearance:none;border:1px solid var(--border);background:var(--card);
      border-radius:12px;padding:10px 14px;font-weight:800;cursor:pointer;transition:.15s;
      box-shadow:0 4px 12px rgba(0,0,0,.06)
    }
    .btn:hover{box-shadow:0 0 0 4px var(--ring)}
    .select{
      padding:10px 12px;border-radius:12px;border:1px solid var(--border);
      background:var(--card);color:var(--text);font-weight:600
    }
    [data-theme="dark"] .btn,[data-theme="dark"] .select{border-color:#334155}

    /* CALENDAR GRID */
    .grid{display:grid;grid-template-columns:repeat(7,1fr);gap:10px}
    .dow{color:var(--muted);text-align:center;font-weight:700;margin:6px 0}
    .cell{
      background:linear-gradient(135deg,var(--card),#f9fafb);
      border:1px solid var(--border);border-radius:18px;min-height:120px;
      box-shadow:0 10px 28px rgba(0,0,0,.08);
      padding:12px;position:relative;display:flex;flex-direction:column;
      transition:transform .15s ease, box-shadow .15s ease
    }
    .cell:hover{transform:translateY(-3px);box-shadow:0 16px 36px rgba(0,0,0,.12)}
    .date{font-weight:800;font-size:14px}
    .mutedDate{opacity:.5}
    .todayBadge{
      display:inline-block;background:var(--today);color:#fff;font-size:10px;font-weight:800;
      padding:2px 6px;border-radius:999px;margin-left:6px;box-shadow:0 4px 10px rgba(16,185,129,.35)
    }
    .dots{display:flex;gap:6px;flex-wrap:wrap;margin-top:auto}
    .dot{width:9px;height:9px;border-radius:999px;background:var(--accent);opacity:.9;animation:pulse 3s infinite}
    .dot.secondary{background:var(--brand);animation-delay:.4s}
    .dot.tertiary{background:#f59e0b;animation-delay:.8s}
    @keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.2)}}

    /* SIDE PANEL */
    .side{
      position:fixed;inset:0 0 0 auto;width:440px;max-width:96vw;background:var(--glass);color:var(--text);
      box-shadow:-14px 0 40px rgba(0,0,0,.18);transform:translateX(100%);transition:transform .25s ease;z-index:60;display:flex;flex-direction:column;backdrop-filter:blur(12px)
    }
    .side.open{transform:translateX(0)}
    .sideHead{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid var(--border)}
    .sideTitle{font-weight:800}
    .close{background:transparent;border:0;font-size:20px;cursor:pointer}
    .sideBody{padding:14px 16px;overflow:auto}
    .eventItem{
      display:flex;gap:12px;padding:12px;border:1px solid var(--border);border-radius:16px;margin-bottom:12px;align-items:center;
      background:linear-gradient(135deg,var(--card),#f9fafb);box-shadow:0 8px 22px rgba(0,0,0,.08)
    }
    .thumb{width:72px;height:52px;object-fit:cover;border-radius:12px;background:#e5e7eb}
    .ename{font-weight:800;color:var(--accent);margin:0}
    .meta{font-size:12px;color:var(--muted)}
    .searchbar{display:flex;gap:8px;margin:8px 0 12px}
    .searchbar input{
      flex:1;padding:12px;border-radius:12px;border:1px solid var(--border);background:var(--card);color:var(--text)
    }

    /* FOOTER */
    footer{background:#0f172a;color:#e5e7eb;margin-top:10px;padding:24px 16px;text-align:center;font-size:14px}
    .smallmuted{color:#94a3b8;font-size:12px;margin-top:8px}

    @media (max-width:900px){
      .hero h1{font-size:34px}
      .cell{min-height:100px}
    }
    @media (prefers-reduced-motion: reduce){
      .cell:hover{transform:none;box-shadow:0 10px 28px rgba(0,0,0,.08)}
      .dot{animation:none}
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
      <?php if(!empty($_SESSION['user_id'])): ?>
        <a href="my_rsvps.php">My RSVPs</a>
      <?php endif; ?>
      <button class="toggle" id="themeBtn" onclick="toggleTheme()">🌙</button>
    </div>
  </div>
</header>

<div class="hero">
  <h1>Events Calendar</h1>
  <p>Glance over the month and open any day to see its events.</p>
</div>

<div class="wrap">
  <div class="toolbar">
    <div class="title"><?= h($firstOfMonth->format('F Y')) ?></div>
    <div class="controls">
      <a class="btn" href="?year=<?= $prev->format('Y') ?>&month=<?= $prev->format('n') ?>" title="Previous month">←</a>
      <a class="btn" href="?year=<?= $next->format('Y') ?>&month=<?= $next->format('n') ?>" title="Next month">→</a>
      <form method="get" action="events_calendar.php" style="display:flex;gap:8px;margin-left:8px">
        <select class="select" name="month" aria-label="Select month">
          <?php for($i=1;$i<=12;$i++): ?>
            <option value="<?= $i ?>" <?= $i===$m?'selected':'' ?>><?= date('M', mktime(0,0,0,$i,1)) ?></option>
          <?php endfor; ?>
        </select>
        <select class="select" name="year" aria-label="Select year">
          <?php for($yy=$y-2;$yy<=$y+2;$yy++): ?>
            <option value="<?= $yy ?>" <?= $yy===$y?'selected':'' ?>><?= $yy ?></option>
          <?php endfor; ?>
        </select>
        <button class="btn" type="submit">Go</button>
      </form>
    </div>
  </div>

  <!-- Days of week -->
  <div class="grid" role="grid" aria-label="Calendar">
    <?php
      $dows = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
      foreach($dows as $dow) echo '<div class="dow">'.$dow.'</div>';

      $cursor = clone $gridStart;
      while ($cursor <= $gridEnd) {
        $key = $cursor->format('Y-m-d');
        $inMonth = ($cursor->format('n') == $m);
        $isToday = ($key === $todayKey);
        $events  = $eventsByDay[$key] ?? [];
        echo '<div class="cell" data-date="'.$key.'" role="button" tabindex="0" aria-pressed="false">';
          echo '<div class="date'.($inMonth?'':' mutedDate').'">'.(int)$cursor->format('j');
          if ($isToday) echo '<span class="todayBadge">Today</span>';
          echo '</div>';
          if (!empty($events)) {
            echo '<div class="dots" aria-label="'.count($events).' events">';
            $c=0; foreach($events as $ev){
              $cls = $c===0?'':($c===1?' secondary':($c===2?' tertiary':''));
              echo '<span class="dot'.$cls.'"></span>';
              if(++$c>=5) break;
            }
            echo '</div>';
          } else {
            echo '<div style="margin-top:auto;height:8px"></div>';
          }
        echo '</div>';
        $cursor->modify('+1 day');
      }
    ?>
  </div>
</div>

<!-- Side panel -->
<aside id="side" class="side" aria-hidden="true">
  <div class="sideHead">
    <div>
      <div class="sideTitle" id="sideTitle">Events</div>
      <div class="meta" id="sideSub"></div>
    </div>
    <button class="close" id="closeSide" aria-label="Close">✕</button>
  </div>
  <div class="sideBody">
    <div class="searchbar">
      <input id="filter" type="text" placeholder="Filter by title or location...">
    </div>
    <div id="list"></div>
  </div>
</aside>

<footer>
  <div>© <?= date('Y') ?> E-Learning Platform • Calendar</div>
  <div class="smallmuted">Tip: Use ← / → keys to navigate months. Press Enter on a day to open it.</div>
</footer>

<script>
  // Theme toggle (match other pages)
  const themeBtn = document.getElementById('themeBtn');
  function setBtnIcon(){ themeBtn.textContent = document.body.getAttribute('data-theme')==='dark' ? '☀️' : '🌙'; }
  function toggleTheme(){
    const b=document.body;
    if(b.getAttribute("data-theme")==="dark"){ b.removeAttribute("data-theme"); localStorage.removeItem("theme"); }
    else { b.setAttribute("data-theme","dark"); localStorage.setItem("theme","dark"); }
    setBtnIcon();
  }
  if (localStorage.getItem("theme")==="dark") {
    document.body.setAttribute("data-theme","dark");
  }
  setBtnIcon();

  // Data bootstrap (for instantaneous open without extra fetch)
  const eventsByDay = <?= json_encode($eventsByDay, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

  // Side panel logic
  const side    = document.getElementById('side');
  const closeBt = document.getElementById('closeSide');
  const list    = document.getElementById('list');
  const titleEl = document.getElementById('sideTitle');
  const subEl   = document.getElementById('sideSub');
  const filter  = document.getElementById('filter');

  function openDay(dateKey){
    const arr = eventsByDay[dateKey] || [];
    titleEl.textContent = new Date(dateKey+"T00:00:00").toLocaleDateString(undefined,{weekday:'long', year:'numeric', month:'long', day:'numeric'});
    subEl.textContent = arr.length ? (arr.length + ' event' + (arr.length>1?'s':'')) : 'No events';
    renderList(arr);
    side.classList.add('open'); side.setAttribute('aria-hidden','false');
    filter.value=''; filter.focus();
  }
  function closeSide(){ side.classList.remove('open'); side.setAttribute('aria-hidden','true'); }
  closeBt.addEventListener('click', closeSide);
  document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') closeSide(); });

  function renderList(items){
    list.innerHTML = '';
    if(!items.length){
      const p = document.createElement('p'); p.className='meta'; p.textContent='No events on this day.'; list.appendChild(p); return;
    }
    items.forEach(ev=>{
      const wrap = document.createElement('div'); wrap.className='eventItem';
      wrap.dataset.title=(ev.title||'').toLowerCase(); wrap.dataset.loc=(ev.location||'').toLowerCase();

      const img  = document.createElement('img'); img.className='thumb'; img.src = ev.banner || 'assets/img/event-placeholder.jpg'; img.alt='';
      const box  = document.createElement('div'); box.style.flex='1';

      const a    = document.createElement('a'); a.href = 'event.php?id='+encodeURIComponent(ev.id); a.target = '_blank'; a.rel='noopener';
      const h3   = document.createElement('h3'); h3.className='ename'; h3.textContent = ev.title || 'Event';
      a.appendChild(h3);

      const meta = document.createElement('div'); meta.className='meta';
      const dt   = new Date((ev.event_date||'').replace(' ','T'));
      if (!isNaN(dt)) {
        meta.textContent = '🕒 '+ dt.toLocaleTimeString(undefined,{hour:'2-digit',minute:'2-digit'}) + (ev.location ? '  •  📍 '+ ev.location : '');
      } else {
        meta.textContent = (ev.location ? '📍 '+ ev.location : '');
      }

      box.appendChild(a); box.appendChild(meta);
      wrap.appendChild(img); wrap.appendChild(box);
      list.appendChild(wrap);
    });
  }

  // Click / keyboard on day cells
  document.querySelectorAll('.cell').forEach(cell=>{
    cell.addEventListener('click', ()=> openDay(cell.dataset.date));
    cell.addEventListener('keydown', (e)=>{ if(e.key==='Enter' || e.key===' ') { e.preventDefault(); openDay(cell.dataset.date); }});
  });

  // Filter in panel
  filter.addEventListener('input', ()=>{
    const q = filter.value.trim().toLowerCase();
    list.querySelectorAll('.eventItem').forEach(it=>{
      const ok = !q || it.dataset.title.includes(q) || it.dataset.loc.includes(q);
      it.style.display = ok ? '' : 'none';
    });
  });

  // Keyboard month navigation
  document.addEventListener('keydown', (e)=>{
    if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.isContentEditable)) return;
    if (e.key === 'ArrowLeft') window.location.href = '?year=<?= $prev->format('Y') ?>&month=<?= $prev->format('n') ?>';
    if (e.key === 'ArrowRight') window.location.href = '?year=<?= $next->format('Y') ?>&month=<?= $next->format('n') ?>';
  });
</script>
</body>
</html>
