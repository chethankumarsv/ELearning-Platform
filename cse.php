<?php
// roadmaps/cse.php
include('../includes/header.php'); // if you have a header file
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CSE Roadmap</title>
  <link rel="stylesheet" href="../assets/css/style.css"> <!-- Optional -->
  <style>
    body {
      font-family: "Poppins", sans-serif;
      background: #0a0a0a;
      color: #fff;
      text-align: center;
      padding: 50px;
    }
    h1 {
      color: #00d1ff;
      font-size: 2.5rem;
    }
    .roadmap-container {
      margin-top: 40px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 15px;
    }
    .topic {
      background: rgba(255,255,255,0.1);
      border-radius: 12px;
      padding: 15px 25px;
      width: 60%;
      text-align: left;
      transition: all 0.3s;
    }
    .topic:hover {
      background: rgba(255,255,255,0.2);
      transform: scale(1.02);
    }
  </style>
</head>
<body>
  <h1>Computer Science Engineering Roadmap</h1>
  <p>Explore your learning journey step by step.</p>

  <div class="roadmap-container">
    <div class="topic">1️⃣ Programming Basics (C / Python)</div>
    <div class="topic">2️⃣ Data Structures and Algorithms</div>
    <div class="topic">3️⃣ Object-Oriented Programming (Java / C++)</div>
    <div class="topic">4️⃣ Database Management Systems</div>
    <div class="topic">5️⃣ Operating Systems & Computer Networks</div>
    <div class="topic">6️⃣ Web Development (HTML, CSS, JS, PHP)</div>
    <div class="topic">7️⃣ Machine Learning & AI</div>
    <div class="topic">8️⃣ Cloud & DevOps</div>
  </div>

</body>
</html>

<?php
include('../includes/footer.php'); // if you have a footer file
?>
