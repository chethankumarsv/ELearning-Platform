<?php
// /elearningplatform/admin_comments.php
session_start();
require_once __DIR__ . '/includes/config.php';
if (function_exists('date_default_timezone_set')) date_default_timezone_set('Asia/Kolkata');

if (empty($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'admin')) {
  http_response_code(403); echo "Forbidden"; exit;
}
if (empty($_SESSION['csrf_cmt_admin'])) $_SESSION['csrf_cmt_admin'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf_cmt_admin'];

$q = trim($_GET['q'] ?? '');
$sql = "SELECT c.id, c.content, c.created_at, e.title, u.name
        FROM event_comments c
        JOIN events e ON e.id = c.event_id
        JOIN users u   ON u.id = c.user_id
        WHERE c.status='pending'".($q!=='' ? " AND (e.title LIKE ? OR u.name LIKE ? OR c.content LIKE ?)" : "")."
        ORDER BY c.created_at ASC LIMIT 200";
$stmt = $conn->prepare($sql);
if ($q!==''){ $like="%$q%"; $stmt->bind_param("sss", $like,$like,$like); }
$stmt->execute(); $rows = $stmt->get_result();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin • Pending Comments</title>
<style>
  body{margin:20px;font-family:system-ui,Segoe UI,Roboto}
  .row{border:1px solid #ddd;border-radius:10px;padding:12px;margin:10px 0}
  .meta{color:#666;font-size:12px;margin-bottom:6px}
  .act button{margin-right:6px}
  .search{margin-bottom:10px}
</style></head><body>
<h2>Pending Comments</h2>
<form class="search" method="get"><input name="q" value="<?=htmlspecialchars($q)?>" placeholder="Search..."><button>Search</button></form>
<?php if ($rows->num_rows): while($r=$rows->fetch_assoc()): ?>
  <div class="row" data-id="<?=$r['id']?>">
    <div class="meta"><b><?=htmlspecialchars($r['name'])?></b> on <i><?=htmlspecialchars($r['title'])?></i> • <?=date('d M Y, h:i A', strtotime($r['created_at']))?></div>
    <div><?=nl2br(htmlspecialchars($r['content']))?></div>
    <div class="act">
      <button onclick="go('approve',<?=$r['id']?>)">Approve</button>
      <button onclick="go('hide',<?=$r['id']?>)">Hide</button>
      <button onclick="go('delete',<?=$r['id']?>)">Delete</button>
    </div>
  </div>
<?php endwhile; else: ?>
  <p>No pending comments 🎉</p>
<?php endif; ?>
<script>
function go(action,id){
  fetch('comment_action.php',{method:'POST',headers:{'Accept':'application/json'},
    body:new URLSearchParams({csrf:'<?=$csrf?>',action,id})})
  .then(r=>r.json()).then(j=>{
    if(!j.ok) throw new Error(j.message||'Error');
    document.querySelector('[data-id="'+id+'"]')?.remove();
  }).catch(e=>alert(e.message||'Error'));
}
</script>
</body></html>
