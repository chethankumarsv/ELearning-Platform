<?php
// leaderboard.php
session_start();
require_once __DIR__ . '/includes/config.php';

// =====================
// Pagination Settings
// =====================
$limit = 10; // students per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// =====================
// Search Filter
// =====================
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$where = "WHERE u.role='student'";
if (!empty($search)) {
    $search_esc = $conn->real_escape_string($search);
    $where .= " AND u.name LIKE '%$search_esc%'";
}

// =====================
// Total Count
// =====================
$total_result = $conn->query("
  SELECT COUNT(DISTINCT u.id) as total
  FROM users u
  LEFT JOIN certificates cert ON u.id = cert.user_id
  $where
") or die("SQL Error (count): " . $conn->error);
$total_students = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_students / $limit);

// =====================
// Fetch Leaderboard
// =====================
$leaders = $conn->query("
  SELECT u.id, u.name, COALESCE(COUNT(cert.id), 0) as certs
  FROM users u
  LEFT JOIN certificates cert ON u.id = cert.user_id
  $where
  GROUP BY u.id, u.name
  ORDER BY certs DESC, u.name ASC
  LIMIT $limit OFFSET $offset
") or die("SQL Error (leaderboard): " . $conn->error);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Leaderboard - E-Learning Platform</title>
  <style>
    body{font-family:Arial, sans-serif; background:#f9fafb; margin:0; padding:20px;}
    h2{color:#2563eb; text-align:center; margin-bottom:20px;}
    form{text-align:center; margin-bottom:20px;}
    input[type=text]{padding:8px; width:250px; border:1px solid #ccc; border-radius:6px;}
    button{padding:8px 14px; background:#2563eb; color:#fff; border:none; border-radius:6px; cursor:pointer;}
    button:hover{background:#1e3a8a;}
    table{width:80%; margin:0 auto; border-collapse:collapse; background:#fff; box-shadow:0 4px 10px rgba(0,0,0,0.08);}
    th, td{padding:12px 15px; border-bottom:1px solid #ddd; text-align:center;}
    th{background:#2563eb; color:#fff;}
    tr:hover{background:#f1f5f9;}
    .rank{font-weight:bold; color:#2563eb;}
    .pagination{text-align:center; margin-top:20px;}
    .pagination a{margin:0 5px; padding:8px 12px; text-decoration:none; border-radius:6px; background:#e5e7eb; color:#111;}
    .pagination a.active{background:#2563eb; color:#fff; font-weight:bold;}
    .pagination a:hover{background:#93c5fd;}
    .back{display:block; width:120px; margin:20px auto; padding:10px; text-align:center; background:#2563eb; color:#fff; border-radius:8px; text-decoration:none;}
    .back:hover{background:#1e3a8a;}
  </style>
</head>
<body>

  <h2>🏆 Full Leaderboard</h2>

  <!-- Search Form -->
  <form method="get">
    <input type="text" name="search" placeholder="Search by name" value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
  </form>

  <!-- Leaderboard Table -->
  <table>
    <tr>
      <th>Rank</th>
      <th>Student Name</th>
      <th>Certificates Earned</th>
    </tr>
    <?php
    $rank = $offset + 1;
    if($leaders->num_rows > 0){
      while($row = $leaders->fetch_assoc()){
        echo "<tr>
                <td class='rank'>#{$rank}</td>
                <td>".htmlspecialchars($row['name'])."</td>
                <td>{$row['certs']}</td>
              </tr>";
        $rank++;
      }
    } else {
      echo "<tr><td colspan='3'>No students found</td></tr>";
    }
    ?>
  </table>

  <!-- Pagination -->
  <div class="pagination">
    <?php
    if ($total_pages > 1) {
      for ($i = 1; $i <= $total_pages; $i++) {
        $active = ($i == $page) ? "active" : "";
        echo "<a class='$active' href='?page=$i&search=" . urlencode($search) . "'>$i</a>";
      }
    }
    ?>
  </div>

  <a href="index.php" class="back">⬅ Back Home</a>

</body>
</html>
