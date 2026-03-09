<?php
// admin/users.php
// Admin users listing: search + pagination + safe session & DB usage.
// Save as: C:\xampp\htdocs\elearningplatform\admin\users.php

if (session_status() === PHP_SESSION_NONE) session_start();

// include DB connection
require_once __DIR__ . '/../includes/config.php';

// safe escape helper
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// --- Admin authentication check (accepts admin_id or is_admin) ---
$admin_ok = false;
if (!empty($_SESSION['admin_id'])) $admin_ok = true;
if (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) $admin_ok = true;

if (!$admin_ok) {
    if (!headers_sent()) {
        header('Location: login.php');
        exit;
    } else {
        echo '<script>location.href="login.php"</script>';
        exit;
    }
}

// include admin header if exists (it also checks auth typically)
if (file_exists(__DIR__ . '/header.php')) {
    include __DIR__ . '/header.php';
}

// --- Read filters ---
$search = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$page   = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// --- Build query with search (prepared statements) ---
$whereSql = "";
$params = [];
$types = "";

if ($search !== '') {
    $whereSql = " WHERE username LIKE ? OR email LIKE ?";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

// count total
$total = 0;
$countSql = "SELECT COUNT(*) AS cnt FROM users" . $whereSql;
if ($stmt = $conn->prepare($countSql)) {
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        $row = $res->fetch_assoc();
        $total = intval($row['cnt'] ?? 0);
    }
    $stmt->close();
}

// fetch page rows
$users = [];
$selectSql = "SELECT id, username, email, role, created_at FROM users" . $whereSql . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
if ($stmt = $conn->prepare($selectSql)) {
    // bind params: search params (if any) then two ints (i,i)
    if ($types) {
        // build combined types string
        $bindTypes = $types . 'ii';
        $bindValues = array_merge($params, [$perPage, $offset]);
        // bind dynamically
        $bind_names = [];
        $bind_names[] = $bindTypes;
        for ($i = 0; $i < count($bindValues); $i++) {
            $bind_name = 'b' . $i;
            $$bind_name = $bindValues[$i];
            $bind_names[] = &$$bind_name;
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);
    } else {
        $stmt->bind_param('ii', $perPage, $offset);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($r = $res->fetch_assoc()) $users[] = $r;
    }
    $stmt->close();
}

// pagination helpers
$totalPages = ($perPage > 0) ? (int)ceil($total / $perPage) : 1;
$baseUrl = strtok($_SERVER["REQUEST_URI"], '?');
$queryParams = $_GET;
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin — Users</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#f7f9fc;margin:0;padding:22px}
    .wrap{max-width:1100px;margin:0 auto}
    .card{background:#fff;padding:18px;border-radius:10px;box-shadow:0 6px 18px rgba(0,0,0,0.04)}
    .controls{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:12px}
    .controls form{display:flex;gap:8px}
    input[type="text"]{padding:8px;border:1px solid #ddd;border-radius:6px}
    button.btn{padding:8px 12px;border-radius:6px;background:#2563eb;color:#fff;border:0;cursor:pointer}
    table{width:100%;border-collapse:collapse;margin-top:8px}
    th,td{padding:10px;border-bottom:1px solid #eef2f6;text-align:left}
    th{background:#fafbfd}
    .meta{font-size:13px;color:#657083}
    .empty{padding:24px;color:#6b7280}
    .pager{margin-top:12px;display:flex;gap:6px;align-items:center}
    .pager a{padding:6px 10px;background:#fff;border:1px solid #e6e9ef;border-radius:6px;text-decoration:none;color:#111}
    .pager strong{padding:6px 10px}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Admin — Users</h1>

    <div class="card">
      <div class="controls">
        <div style="flex:1">
          <form method="get" action="">
            <input type="text" name="q" placeholder="Search by username or email" value="<?php echo h($search); ?>">
            <button type="submit" class="btn">Search</button>
            <a href="users.php" style="margin-left:8px;text-decoration:none;color:#2563eb">Reset</a>
          </form>
        </div>
        <div>
          <a href="dashboard.php" class="btn">Back to Dashboard</a>
        </div>
      </div>

      <?php if (empty($users)): ?>
        <div class="empty">No users found.</div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Username</th>
              <th>Email</th>
              <th>Role</th>
              <th>Registered</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($users as $u): ?>
              <tr>
                <td><?php echo h($u['id']); ?></td>
                <td><?php echo h($u['username']); ?></td>
                <td><?php echo h($u['email'] ?? '-'); ?></td>
                <td><?php echo h($u['role'] ?? '-'); ?></td>
                <td class="meta"><?php echo h($u['created_at'] ?? '-'); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
          <div class="pager">
            <?php
              // build page links preserving search param
              $qp = $_GET;
              for ($p = 1; $p <= $totalPages; $p++):
                $qp['page'] = $p;
                $link = htmlspecialchars($baseUrl . '?' . http_build_query($qp));
            ?>
              <?php if ($p === $page): ?>
                <strong><?php echo $p; ?></strong>
              <?php else: ?>
                <a href="<?php echo $link; ?>"><?php echo $p; ?></a>
              <?php endif; ?>
            <?php endfor; ?>
          </div>
        <?php endif; ?>

      <?php endif; ?>
    </div>
  </div>
</body>
</html>
