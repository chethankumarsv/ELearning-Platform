<?php
// /elearningplatform/tools.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Engineering Tools - E-Learning Platform</title>
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

    /* Tabs */
    .tabs{display:flex;justify-content:center;margin-bottom:20px;flex-wrap:wrap}
    .tabs button{background:var(--brand);color:#fff;border:none;padding:10px 18px;margin:6px;border-radius:20px;font-weight:600;cursor:pointer;transition:.25s}
    .tabs button:hover{background:var(--accent)}
    .tabcontent{display:none}
    .tabcontent.active{display:block;animation:fade .4s}
    @keyframes fade{from{opacity:0}to{opacity:1}}

    /* Tool Cards */
    .tool-box{background:#fff;padding:20px;border-radius:12px;box-shadow:0 6px 16px rgba(0,0,0,.08);margin-bottom:20px}
    .tool-box h2{margin-top:0;color:var(--brand)}
    label{font-size:14px;font-weight:600}
    input, select{padding:8px;margin:6px 0;width:100%;border:1px solid #ccc;border-radius:6px}
    .btn{display:inline-block;background:var(--accent);color:#fff;padding:8px 16px;border-radius:6px;font-size:14px;font-weight:600;cursor:pointer;margin-top:10px}
    .btn:hover{background:#f87171}
    .result{margin-top:12px;font-weight:600;color:var(--brand)}

    footer{margin-top:40px;background:#111827;color:#9ca3af;padding:20px;text-align:center;font-size:13px;border-radius:0 0 14px 14px}
  </style>
</head>
<body>

<header>
  <div class="brand">🧮 Engineering Tools</div>
  <nav>
    <ul>
      <li><a href="index.php">Home</a></li>
      <li><a href="materials.php">Materials</a></li>
      <li><a href="projects.php">Projects</a></li>
    </ul>
  </nav>
</header>

<main>
  <h1>Engineering Tools</h1>

  <!-- Tabs -->
  <div class="tabs">
    <button onclick="openTab('gpa')">GPA Calculator</button>
    <button onclick="openTab('unit')">Unit Converter</button>
    <button onclick="openTab('resistor')">Resistor Color Code</button>
  </div>

  <!-- GPA Calculator -->
  <div id="gpa" class="tabcontent active">
    <div class="tool-box">
      <h2>🎓 GPA / CGPA Calculator</h2>
      <label>Enter Grades (comma separated, e.g. 8,7,9,10):</label>
      <input type="text" id="grades" placeholder="Enter grades...">
      <button class="btn" onclick="calcGPA()">Calculate GPA</button>
      <div class="result" id="gpaResult"></div>
    </div>
  </div>

  <!-- Unit Converter -->
  <div id="unit" class="tabcontent">
    <div class="tool-box">
      <h2>📏 Unit Converter</h2>
      <label>Value:</label>
      <input type="number" id="unitValue" placeholder="Enter value">
      <label>From:</label>
      <select id="fromUnit">
        <option value="m">Meters</option>
        <option value="cm">Centimeters</option>
        <option value="km">Kilometers</option>
      </select>
      <label>To:</label>
      <select id="toUnit">
        <option value="cm">Centimeters</option>
        <option value="m">Meters</option>
        <option value="km">Kilometers</option>
      </select>
      <button class="btn" onclick="convertUnit()">Convert</button>
      <div class="result" id="unitResult"></div>
    </div>
  </div>

  <!-- Resistor Code -->
  <div id="resistor" class="tabcontent">
    <div class="tool-box">
      <h2>⚡ Resistor Color Code</h2>
      <label>Band 1:</label>
      <select id="band1">
        <option value="1">Brown</option><option value="2">Red</option>
        <option value="3">Orange</option><option value="4">Yellow</option>
        <option value="5">Green</option><option value="6">Blue</option>
        <option value="7">Violet</option><option value="8">Gray</option>
        <option value="9">White</option><option value="0">Black</option>
      </select>
      <label>Band 2:</label>
      <select id="band2">
        <option value="1">Brown</option><option value="2">Red</option>
        <option value="3">Orange</option><option value="4">Yellow</option>
        <option value="5">Green</option><option value="6">Blue</option>
        <option value="7">Violet</option><option value="8">Gray</option>
        <option value="9">White</option><option value="0">Black</option>
      </select>
      <label>Multiplier:</label>
      <select id="multiplier">
        <option value="1">x1 (Black)</option><option value="10">x10 (Brown)</option>
        <option value="100">x100 (Red)</option><option value="1000">x1k (Orange)</option>
        <option value="10000">x10k (Yellow)</option><option value="100000">x100k (Green)</option>
      </select>
      <button class="btn" onclick="calcResistor()">Calculate</button>
      <div class="result" id="resistorResult"></div>
    </div>
  </div>
</main>

<footer>
  © <?= date("Y") ?> E-Learning Platform | Engineering Tools
</footer>

<script>
  // Tabs
  function openTab(tabId) {
    document.querySelectorAll('.tabcontent').forEach(t => t.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
  }

  // GPA Calculator
  function calcGPA() {
    let input = document.getElementById('grades').value.split(',');
    let grades = input.map(x => parseFloat(x.trim())).filter(x => !isNaN(x));
    if(grades.length === 0) {
      document.getElementById('gpaResult').innerText = "Please enter valid grades.";
      return;
    }
    let sum = grades.reduce((a,b)=>a+b,0);
    let gpa = (sum / grades.length).toFixed(2);
    document.getElementById('gpaResult').innerText = "Your GPA: " + gpa;
  }

  // Unit Converter
  function convertUnit() {
    let val = parseFloat(document.getElementById('unitValue').value);
    if(isNaN(val)) { document.getElementById('unitResult').innerText="Enter valid value."; return; }
    let from = document.getElementById('fromUnit').value;
    let to = document.getElementById('toUnit').value;

    let meters;
    if(from==="m") meters=val;
    if(from==="cm") meters=val/100;
    if(from==="km") meters=val*1000;

    let result;
    if(to==="m") result=meters;
    if(to==="cm") result=meters*100;
    if(to==="km") result=meters/1000;

    document.getElementById('unitResult').innerText = val+" "+from+" = "+result+" "+to;
  }

  // Resistor Code
  function calcResistor() {
    let b1 = parseInt(document.getElementById('band1').value);
    let b2 = parseInt(document.getElementById('band2').value);
    let mul = parseInt(document.getElementById('multiplier').value);
    let value = ((b1*10)+b2)*mul;
    document.getElementById('resistorResult').innerText = "Resistance: " + value + " Ω";
  }
</script>

</body>
</html>
