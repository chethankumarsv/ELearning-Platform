<?php
require_once __DIR__ . '/includes/config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$internship = $conn->query("SELECT * FROM internships WHERE id=$id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Apply for <?= htmlspecialchars($internship['title'] ?? 'Internship') ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: #f0f4f9;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 650px;
      margin: 60px auto;
      background: #fff;
      padding: 30px;
      border-radius: 14px;
      box-shadow: 0 10px 25px rgba(0,0,0,.1);
    }
    h1 {
      color: #2563eb;
      text-align: center;
    }
    p {
      color: #444;
      text-align: center;
    }
    form {
      display: flex;
      flex-direction: column;
      gap: 15px;
      margin-top: 25px;
    }
    input, textarea {
      padding: 12px;
      border: 1px solid #ccc;
      border-radius: 10px;
      font-size: 14px;
    }
    button {
      background: #2563eb;
      color: white;
      padding: 12px;
      border: none;
      border-radius: 10px;
      font-weight: 600;
      cursor: pointer;
    }
    button:hover {
      background: #1d4ed8;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Apply for <?= htmlspecialchars($internship['title'] ?? 'Internship') ?></h1>
    <p><strong>Company:</strong> <?= htmlspecialchars($internship['company'] ?? 'Unknown') ?></p>
    <form method="POST" action="submit_application.php" enctype="multipart/form-data">
      <input type="hidden" name="internship_id" value="<?= $id ?>">
      <label>Your Full Name:</label>
      <input type="text" name="name" required>
      <label>Email Address:</label>
      <input type="email" name="email" required>
      <label>Why should we select you?</label>
      <textarea name="message" rows="5" required></textarea>
      <label>Upload Resume (PDF only):</label>
      <input type="file" name="resume" accept=".pdf" required>
      <button type="submit">Submit Application</button>
    </form>
  </div>
</body>
</html>
