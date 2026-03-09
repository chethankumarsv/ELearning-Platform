<?php
$branch = $_SESSION['branch'] ?? 'CSE';
$year = $_SESSION['year'] ?? 1;

$recommendations = [];

// =======================
// COMPUTER SCIENCE (CSE)
// =======================
if ($branch == 'CSE') {
    switch ($year) {
        case 1:
            $recommendations = [
                "Programming in C — Learn the basics of coding and problem-solving.",
                "Computer Fundamentals — Understand hardware and software basics.",
                "Mathematics for Computing — Strengthen logical and analytical skills."
            ];
            break;
        case 2:
            $recommendations = [
                "Data Structures — Core for efficient coding.",
                "Database Management Systems — Learn SQL and database design.",
                "Object-Oriented Programming — Master C++ or Java concepts."
            ];
            break;
        case 3:
            $recommendations = [
                "Operating Systems — Learn how OS manages resources.",
                "Computer Networks — Understand network protocols and communication.",
                "Machine Learning — Introduction to AI-driven technologies."
            ];
            break;
        case 4:
            $recommendations = [
                "Cloud Computing — Learn AWS and Azure fundamentals.",
                "AI & Deep Learning — Build intelligent applications.",
                "Capstone Project — Apply all your knowledge practically."
            ];
            break;
    }

// =======================
// INFORMATION SCIENCE (ISE)
// =======================
} elseif ($branch == 'ISE') {
    switch ($year) {
        case 1:
            $recommendations = [
                "Introduction to Programming — Learn logic building.",
                "Mathematics for Information Science — Focus on discrete math.",
                "Basics of Information Systems — Learn data flow and architecture."
            ];
            break;
        case 2:
            $recommendations = [
                "Data Structures — Enhance coding efficiency.",
                "Web Technologies — Build dynamic web pages using HTML, CSS, JS.",
                "Database Systems — Learn MySQL and database normalization."
            ];
            break;
        case 3:
            $recommendations = [
                "Data Analytics — Learn to analyze and visualize data.",
                "Software Engineering — Understand SDLC and documentation.",
                "Computer Networks — Learn communication protocols."
            ];
            break;
        case 4:
            $recommendations = [
                "AI & ML — Build intelligent systems using Python.",
                "Cloud & Big Data — Learn Hadoop, AWS, and GCP basics.",
                "Project Work — Integrate all learned skills into real projects."
            ];
            break;
    }

// =======================
// ELECTRONICS & COMMUNICATION (ECE)
// =======================
} elseif ($branch == 'ECE') {
    switch ($year) {
        case 1:
            $recommendations = [
                "Basic Electrical Engineering — Learn fundamentals of circuits.",
                "Engineering Mathematics — Build analytical foundation.",
                "Electronics Fundamentals — Study semiconductors and diodes."
            ];
            break;
        case 2:
            $recommendations = [
                "Digital Electronics — Learn logic gates and flip-flops.",
                "Analog Circuits — Understand op-amps and amplifiers.",
                "Signals & Systems — Introduction to signal analysis."
            ];
            break;
        case 3:
            $recommendations = [
                "Embedded Systems — Learn microcontrollers (Arduino, 8051).",
                "Communication Systems — Understand modulation techniques.",
                "VLSI Design — Introduction to chip design."
            ];
            break;
        case 4:
            $recommendations = [
                "IoT Applications — Design connected electronic devices.",
                "Wireless Communication — Learn 4G/5G basics.",
                "Project Design — Integrate hardware and software for real-world use."
            ];
            break;
    }

// =======================
// ELECTRICAL & ELECTRONICS (EEE)
// =======================
} elseif ($branch == 'EEE') {
    switch ($year) {
        case 1:
            $recommendations = [
                "Basic Electrical Engineering — Learn voltage, current & resistance.",
                "Mathematics for Engineers — Foundation for circuit analysis.",
                "Engineering Physics — Understand electromagnetism."
            ];
            break;
        case 2:
            $recommendations = [
                "Electrical Machines — Study transformers and motors.",
                "Network Analysis — Learn mesh and nodal analysis.",
                "Control Systems — Introduction to automation and feedback."
            ];
            break;
        case 3:
            $recommendations = [
                "Power Electronics — Learn converters and inverters.",
                "Microcontrollers — Study embedded control systems.",
                "Renewable Energy Systems — Learn solar and wind systems."
            ];
            break;
        case 4:
            $recommendations = [
                "Power System Protection — Learn circuit breakers and relays.",
                "Smart Grids — Future of energy networks.",
                "Major Project — Apply power electronics concepts practically."
            ];
            break;
    }

// =======================
// MECHANICAL ENGINEERING (ME)
// =======================
} elseif ($branch == 'ME') {
    switch ($year) {
        case 1:
            $recommendations = [
                "Engineering Drawing — Learn design and drafting.",
                "Basic Mechanics — Understand forces and equilibrium.",
                "Workshop Practice — Learn hands-on manufacturing processes."
            ];
            break;
        case 2:
            $recommendations = [
                "Thermodynamics — Fundamental mechanical subject.",
                "Fluid Mechanics — Learn properties of fluids and flow.",
                "Material Science — Study structure and strength of materials."
            ];
            break;
        case 3:
            $recommendations = [
                "Machine Design — Design gears, bearings, and shafts.",
                "Manufacturing Processes — Learn CNC and 3D printing.",
                "Heat Transfer — Understand conduction and convection."
            ];
            break;
        case 4:
            $recommendations = [
                "Automobile Engineering — Learn vehicle systems and design.",
                "Robotics — Introduction to automation and sensors.",
                "Project Work — Develop real-world mechanical prototypes."
            ];
            break;
    }

// =======================
// CIVIL ENGINEERING
// =======================
} elseif ($branch == 'CIVIL') {
    switch ($year) {
        case 1:
            $recommendations = [
                "Engineering Mechanics — Learn structural basics.",
                "Basic Surveying — Understand measurement and mapping.",
                "Environmental Studies — Learn sustainability concepts."
            ];
            break;
        case 2:
            $recommendations = [
                "Strength of Materials — Study stress and strain in materials.",
                "Fluid Mechanics — Learn water flow and pressure principles.",
                "Building Materials — Understand cement, steel, and concrete."
            ];
            break;
        case 3:
            $recommendations = [
                "Structural Analysis — Learn beam and truss design.",
                "Geotechnical Engineering — Study soil mechanics.",
                "Transportation Engineering — Understand highway design."
            ];
            break;
        case 4:
            $recommendations = [
                "Environmental Engineering — Design waste management systems.",
                "Construction Management — Learn planning and costing.",
                "Major Project — Apply design and analysis knowledge."
            ];
            break;
    }

// =======================
// ARTIFICIAL INTELLIGENCE & DATA SCIENCE (AI & DS)
// =======================
} elseif ($branch == 'AI&DS' || $branch == 'AI') {
    switch ($year) {
        case 1:
            $recommendations = [
                "Introduction to AI — Learn the basics of intelligent systems.",
                "Python Programming — Build a foundation for data science.",
                "Mathematics for AI — Focus on linear algebra and probability."
            ];
            break;
        case 2:
            $recommendations = [
                "Data Structures — Optimize data handling for AI.",
                "Data Visualization — Learn tools like Power BI and Tableau.",
                "Statistics for Data Science — Analyze and interpret data."
            ];
            break;
        case 3:
            $recommendations = [
                "Machine Learning — Supervised and unsupervised learning.",
                "Deep Learning — Neural networks and image processing.",
                "Natural Language Processing — AI for text and speech."
            ];
            break;
        case 4:
            $recommendations = [
                "AI in Cloud — Deploy models using AWS/GCP.",
                "AI Ethics & Security — Responsible AI development.",
                "Major Project — Build end-to-end AI application."
            ];
            break;
    }

// =======================
// DEFAULT
// =======================
} else {
    $recommendations = ["No specific recommendations available for this branch."];
}
?>

<!-- HTML Display -->
<div class="recommendation-box">
  <h3>Recommended for <?= htmlspecialchars($branch) ?> (Year <?= $year ?>)</h3>
  <ul>
    <?php foreach ($recommendations as $rec): ?>
      <li><?= htmlspecialchars($rec) ?></li>
    <?php endforeach; ?>
  </ul>
  <button onclick="this.parentElement.style.display='none'" class="close-btn">Close</button>
</div>

<style>
.recommendation-box {
  background: rgba(255,255,255,0.1);
  backdrop-filter: blur(12px);
  border-radius: 16px;
  padding: 25px;
  color: #fff;
  width: 100%;
  max-width: 600px;
  margin: 20px auto;
  box-shadow: 0 0 15px rgba(0,0,0,0.3);
}
.recommendation-box h3 {
  text-align: center;
  color: #00ffe0;
}
.recommendation-box ul {
  list-style: none;
  padding: 0;
}
.recommendation-box li {
  background: rgba(0,0,0,0.3);
  margin: 8px 0;
  padding: 10px;
  border-radius: 8px;
}
.close-btn {
  display: block;
  margin: 15px auto 0;
  background: #00ffe0;
  color: #000;
  padding: 8px 15px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: bold;
}
</style>
