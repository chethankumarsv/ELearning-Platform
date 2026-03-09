<?php
// /elearningplatform/careers.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Career Roadmaps - E-Learning Platform</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --brand:#2563eb; --brand2:#60a5fa; --accent:#ff7e5f;
      --text:#222; --muted:#555; --bg:#f0f2f5; --white:#fff;
    }
    [data-theme="dark"] {
      --bg:#1f2937; --text:#f9fafb; --muted:#d1d5db; --white:#111827;
    }
    body {
      margin:0;
      font-family:Poppins,system-ui,-apple-system,Segoe UI,Roboto;
      background:var(--bg);
      color:var(--text);
      transition:background .3s,color .3s;
    }
    header{background:linear-gradient(90deg,var(--brand),var(--brand2));padding:14px 20px;color:#fff;display:flex;align-items:center;justify-content:space-between}
    .brand{font-weight:700;font-size:20px}
    nav ul{list-style:none;margin:0;padding:0;display:flex;gap:18px}
    nav a{color:#fff;font-weight:600;padding:6px 12px;border-radius:6px}
    nav a:hover{background:rgba(255,255,255,.2)}

    main{max-width:1100px;margin:30px auto;padding:20px;background:var(--white);border-radius:14px;box-shadow:0 8px 24px rgba(0,0,0,.1)}
    h1{text-align:center;color:var(--accent);margin-bottom:20px}

    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px}
    .card{background:#fff;border-radius:12px;padding:18px;box-shadow:0 6px 16px rgba(0,0,0,.08);transition:.25s}
    .card:hover{transform:translateY(-4px)}
    .card h2{margin:0 0 10px;font-size:20px;color:var(--brand)}
    .steps{list-style:decimal inside;padding-left:0;margin:0}
    .steps li{margin:8px 0;font-size:14px;color:var(--muted)}

    footer{margin-top:40px;background:#111827;color:#9ca3af;padding:20px;text-align:center;font-size:13px;border-radius:0 0 14px 14px}
  </style>
</head>
<body>

<header>
  <div class="brand">🎯 Career Roadmaps</div>
  <nav>
    <ul>
      <li><a href="index.php">Home</a></li>
      <li><a href="projects.php">Projects</a></li>
      <li><a href="tools.php">Tools</a></li>
    </ul>
  </nav>
</header>

<main>
  <h1>Career Roadmaps for Engineers</h1>
  <div class="grid">

    <!-- Software Engineer -->
    <div class="card">
      <h2>💻 Software Engineer</h2>
      <ul class="steps">
        <li>Learn Programming (C, Java, Python, etc.)</li>
        <li>Master Data Structures & Algorithms</li>
        <li>Understand Databases & SQL</li>
        <li>Learn Web Development (HTML, CSS, JS, PHP/Node)</li>
        <li>Build Projects & Internship Experience</li>
        <li>Practice Problem-Solving (LeetCode, GFG)</li>
      </ul>
    </div>

    <!-- Data Scientist -->
    <div class="card">
      <h2>📊 Data Scientist</h2>
      <ul class="steps">
        <li>Strong Python & Statistics foundation</li>
        <li>Learn Data Analysis (NumPy, Pandas, Excel)</li>
        <li>Master Data Visualization (Matplotlib, Power BI)</li>
        <li>Machine Learning (Scikit-learn, TensorFlow)</li>
        <li>Work on Real-World Data Projects</li>
        <li>Understand Big Data Tools (Hadoop, Spark)</li>
      </ul>
    </div>

    <!-- AI Engineer -->
    <div class="card">
      <h2>🤖 AI / ML Engineer</h2>
      <ul class="steps">
        <li>Learn Python, Linear Algebra, Probability</li>
        <li>Understand ML Algorithms (Regression, SVM, Decision Trees)</li>
        <li>Neural Networks & Deep Learning (Keras, PyTorch)</li>
        <li>Natural Language Processing (NLP)</li>
        <li>Computer Vision (OpenCV, CNNs)</li>
        <li>Deploy AI Models (Flask/Django APIs)</li>
      </ul>
    </div>

    <!-- Mechanical Engineer -->
    <div class="card">
      <h2>⚙️ Mechanical Engineer</h2>
      <ul class="steps">
        <li>Master Engineering Drawing & CAD (AutoCAD, SolidWorks)</li>
        <li>Understand Thermodynamics & Fluid Mechanics</li>
        <li>Hands-on with Manufacturing & Design projects</li>
        <li>Learn MATLAB / ANSYS for simulations</li>
        <li>Work on Industry Internships</li>
        <li>Prepare for GATE / higher studies</li>
      </ul>
    </div>

    <!-- Civil Engineer -->
    <div class="card">
      <h2>🏗️ Civil Engineer</h2>
      <ul class="steps">
        <li>Learn CAD Tools (AutoCAD, Revit)</li>
        <li>Structural Analysis & RCC Design</li>
        <li>Surveying & Geotechnical Engineering</li>
        <li>Project Management & Estimation</li>
        <li>Site Internships & Construction Projects</li>
        <li>Prepare for Competitive Exams</li>
      </ul>
    </div>

  </div>
</main>

<footer>
  © <?= date("Y") ?> E-Learning Platform | Career Roadmaps
</footer>

</body>
</html>
