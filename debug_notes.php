<?php
require_once __DIR__ . '/includes/config.php';
$res = $conn->query("SELECT id, semester, subject, original_filename, file_path, uploaded_on FROM notes ORDER BY uploaded_on DESC LIMIT 20");
echo "<pre>";
if (!$res) { echo "DB error: " . $conn->error; exit; }
while($r = $res->fetch_assoc()){
    $path = __DIR__ . '/' . ltrim($r['file_path'],'/');
    echo "ID: {$r['id']} | sem: {$r['semester']} | subject: {$r['subject']} | file_path: {$r['file_path']} | exists: " . (file_exists($path) ? 'YES' : 'NO') . "\n";
}
echo "</pre>";
