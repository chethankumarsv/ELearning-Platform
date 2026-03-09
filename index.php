<?php
// index.php — Enhanced single-file version with AI Assistant
session_start();

// ==================== CRITICAL FIX: ADD AUTHENTICATION CHECK ====================
// Check if user is logged in properly
$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// If user is logged in, fetch user data
$userData = [];
if ($isLoggedIn && isset($conn)) {
    $stmt = $conn->prepare("SELECT name, email, profile_pic FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $userData = $result->fetch_assoc();
    }
    $stmt->close();
}

require_once __DIR__ . '/includes/config.php'; // $conn expected but file works with fallback

// Include the AI Assistant class
if (file_exists(__DIR__ . '/includes/ai_assistant.php')) {
    require_once __DIR__ . '/includes/ai_assistant.php';
}

header('X-Frame-Options: SAMEORIGIN'); // small security header

// --- Simple AJAX API handlers (subscribe, book_mentor, recommend, chat) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    header('Content-Type: application/json; charset=utf-8');

    // Helper
    function respond($ok, $data = []) {
        echo json_encode(array_merge(['success' => $ok], (array)$data));
        exit;
    }

    // Newsletter subscribe
    if ($action === 'subscribe') {
        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respond(false, ['msg' => 'Invalid email']);

        if (isset($conn) && $conn) {
            // Try insert into newsletter table (if exists)
            $stmt = @$conn->prepare("INSERT INTO newsletter (email, created_at) VALUES (?, NOW())");
            if ($stmt) {
                $stmt->bind_param("s", $email);
                if ($stmt->execute()) respond(true, ['msg' => 'Subscribed successfully']);
                else respond(false, ['msg' => 'DB insert failed: ' . $stmt->error]);
            } else {
                // table might not exist, fallback
                @file_put_contents(__DIR__ . '/data/newsletter.txt', $email . PHP_EOL, FILE_APPEND);
                respond(true, ['msg' => 'Subscribed (fallback)']);
            }
        } else {
            // fallback: file store
            @file_put_contents(__DIR__ . '/data/newsletter.txt', $email . PHP_EOL, FILE_APPEND);
            respond(true, ['msg' => 'Subscribed (local fallback)']);
        }
    }

    // Mentor booking - REQUIRES LOGIN
    if ($action === 'book_mentor') {
        // Check login status
        if (!$isLoggedIn) {
            respond(false, ['msg' => 'Please login to book a mentor', 'redirect' => 'auth.php']);
        }
        
        $user_id = $_SESSION['user_id'];
        $mentor_id = intval($_POST['mentor_id'] ?? 0);
        $student_name = trim($_POST['student_name'] ?? '');
        $slot = trim($_POST['slot'] ?? '');
        if (!$mentor_id || !$student_name || !$slot) respond(false, ['msg' => 'Missing fields']);

        if (isset($conn) && $conn) {
            // Try to insert into mentor_bookings
            $stmt = @$conn->prepare("INSERT INTO mentor_bookings (mentor_id, user_id, student_name, slot, status, created_at) VALUES (?, ?, ?, ?, 'booked', NOW())");
            if ($stmt) {
                $stmt->bind_param("iiss", $mentor_id, $user_id, $student_name, $slot);
                if ($stmt->execute()) respond(true, ['msg' => 'Booking confirmed']);
                else respond(false, ['msg' => 'Booking failed: ' . $stmt->error]);
            } else {
                respond(false, ['msg' => 'Database prepare failed']);
            }
        } else {
            // fallback: append to file
            $line = json_encode(['mentor_id' => $mentor_id, 'user_id' => $user_id, 'name' => $student_name, 'slot' => $slot, 'ts' => date('c')]) . PHP_EOL;
            @file_put_contents(__DIR__ . '/data/bookings.txt', $line, FILE_APPEND);
            respond(true, ['msg' => 'Booking saved locally (no DB)']);
        }
    }

    // Simple AI Recommender (stub) - you can replace with real AI call
    if ($action === 'recommend') {
        $branch = $_POST['branch'] ?? ($_SESSION['branch'] ?? 'CSE');
        $year = intval($_POST['year'] ?? ($_SESSION['year'] ?? 3));
        // Very basic rule-based suggestions as placeholder
        $suggestions = [];
        if (stripos($branch, 'CSE') !== false) {
            $suggestions = [
                ['title' => 'Advanced Data Structures', 'why' => 'Must for placements and higher CS topics'],
                ['title' => 'Operating Systems Projects', 'why' => 'Strong systems understanding'],
                ['title' => 'Machine Learning Basics', 'why' => 'High industry demand']
            ];
        } elseif (stripos($branch, 'ECE') !== false) {
            $suggestions = [
                ['title' => 'VLSI Design Fundamentals', 'why' => 'Core for ECE hardware roles'],
                ['title' => 'Embedded Systems Projects', 'why' => 'IoT & firmware development']
            ];
        } else {
            $suggestions = [
                ['title' => 'Fundamentals Refresher', 'why' => 'Strengthen basics for any branch']
            ];
        }
        // adapt for year
        if ($year <= 2) array_unshift($suggestions, ['title' => 'Programming Essentials', 'why' => 'Build a solid base early']);

        respond(true, ['recommendations' => $suggestions]);
    }

    // ENHANCED AI CHAT ASSISTANT - UPDATED VERSION
    if ($action === 'chat') {
        $q = trim($_POST['q'] ?? '');
        $user_context = [
            'branch' => $_SESSION['branch'] ?? 'CSE',
            'year' => $_SESSION['year'] ?? 3,
            'user_id' => $_SESSION['user_id'] ?? null
        ];
        
        if ($q === '') respond(false, ['msg' => 'Empty question']);
        
        // Check if AI Assistant class exists
        if (class_exists('EduHubAIAssistant')) {
            $ai_assistant = new EduHubAIAssistant($conn);
            $response = $ai_assistant->processQuery($q, $user_context);
            
            // Log the interaction
            if ($isLoggedIn && isset($conn) && $conn) {
                $stmt = $conn->prepare("INSERT INTO ai_conversation_logs (user_id, question, response, confidence_score, response_source) VALUES (?, ?, ?, ?, ?)");
                $user_id = $_SESSION['user_id'];
                $stmt->bind_param("issds", $user_id, $q, $response['answer'], $response['confidence'], $response['source']);
                $stmt->execute();
            }
            
            respond(true, [
                'reply' => $response['answer'],
                'confidence' => $response['confidence'],
                'source' => $response['source'],
                'suggestions' => $response['suggestions'] ?? [],
                'references' => $response['references'] ?? []
            ]);
        } else {
            // Fallback simple response
            respond(true, [
                'reply' => "I'm your AI assistant. I can help with engineering topics, programming questions, and career guidance. Please ask me anything!",
                'confidence' => 0.7,
                'source' => 'fallback',
                'suggestions' => ['Try asking about: DSA, Web Development, Machine Learning']
            ]);
        }
    }

    // Unknown action
    respond(false, ['msg' => 'Unknown action']);
}

// --- End AJAX handlers. Continue with page rendering ---
// Basic session defaults (only set if not already set)
if (!isset($_SESSION['branch'])) $_SESSION['branch'] = 'CSE';
if (!isset($_SESSION['year'])) $_SESSION['year'] = 3;

// Set student name based on login status
$student_name = $isLoggedIn ? ($userData['name'] ?? 'Student') : 'Guest';

// Fetch dynamic data (courses, mentors) from DB if available
$courses = [];
if (isset($conn) && $conn) {
    $q = "SELECT id, title, short_description, icon FROM courses WHERE status = 'active' ORDER BY id DESC LIMIT 8";
    if ($res = @$conn->query($q)) {
        while ($r = $res->fetch_assoc()) $courses[] = $r;
        $res->free();
    }
}
if (empty($courses)) {
    $courses = [
      ["id"=>201,"title"=>"Web Development","short_description"=>"HTML, CSS, JavaScript & frameworks","icon"=>"💻"],
      ["id"=>202,"title"=>"Data Science","short_description"=>"Python, pandas, ML basics","icon"=>"📊"],
      ["id"=>203,"title"=>"AI & ML","short_description"=>"Neural nets & ML pipelines","icon"=>"🤖"],
      ["id"=>204,"title"=>"Mobile App Development","short_description"=>"Build Android & iOS apps","icon"=>"📱"],
      ["id"=>205,"title"=>"Cloud Computing","short_description"=>"AWS, Azure, and cloud services","icon"=>"☁️"],
      ["id"=>206,"title"=>"Cyber Security","short_description"=>"Ethical hacking and security fundamentals","icon"=>"🔒"],
      ["id"=>207,"title"=>"Blockchain Development","short_description"=>"Smart contracts and DApps","icon"=>"⛓️"],
      ["id"=>208,"title"=>"IoT & Embedded Systems","short_description"=>"Hardware programming and IoT projects","icon"=>"🔌"],
    ];
}

// mentors fallback
$mentors_fallback = [
  ["id"=>101,"name"=>"Prof. Anil Kumar","branch"=>"CSE","rating"=>"4.8","skills"=>["DSA","Java","System Design"],"headline"=>"DSA & Interview Coach","available_slots"=>["2025-10-08 10:00","2025-10-09 16:00","2025-10-11 11:30"]],
  ["id"=>102,"name"=>"Dr. Priya Sharma","branch"=>"ECE","rating"=>"4.7","skills"=>["VLSI","Embedded"],"headline"=>"Electronics & VLSI Mentor","available_slots"=>["2025-10-07 14:00","2025-10-10 09:00"]],
  ["id"=>103,"name"=>"Mr. Ravi Patel","branch"=>"ME","rating"=>"4.6","skills"=>["CAD","Thermodynamics"],"headline"=>"Mechanical Design Coach","available_slots"=>["2025-10-09 12:00","2025-10-12 15:30"]],
  ["id"=>104,"name"=>"Dr. Sneha Verma","branch"=>"CSE","rating"=>"4.9","skills"=>["AI/ML","Python","Research"],"headline"=>"AI Research Mentor","available_slots"=>["2025-10-08 14:00","2025-10-10 11:00"]],
];

// Try to pull mentors from DB
$mentors = [];
if (isset($conn) && $conn) {
    $qr = "SELECT id, name, branch, rating, headline, skills, available_slots FROM mentors WHERE status = 'active' ORDER BY rating DESC LIMIT 8";
    if ($res = @$conn->query($qr)) {
        while ($r = $res->fetch_assoc()) {
            // normalize skills & slots if stored as JSON/text
            $r['skills'] = json_decode($r['skills'] ?? '[]', true) ?: (is_string($r['skills']) ? explode(',', $r['skills']) : []);
            $r['available_slots'] = json_decode($r['available_slots'] ?? '[]', true) ?: (is_string($r['available_slots']) ? explode(',', $r['available_slots']) : []);
            $mentors[] = $r;
        }
        $res->free();
    }
}
if (empty($mentors)) $mentors = $mentors_fallback;

// Student projects — add or modify URLs here
$student_projects = [
    ["id"=>1,"title"=>"E-Learning Platform","desc"=>"Online learning platform with courses, quizzes, mentors and project hub.","tags"=>["PHP","MySQL","HTML","CSS"],"code_url"=>"#","demo_url"=>"#"],
    ["id"=>2,"title"=>"AI Crop Disease Detection","desc"=>"AI model to detect crop diseases from leaf images (Flask + TensorFlow).","tags"=>["Python","Flask","ML"],"code_url"=>"#","demo_url"=>"#"],
    ["id"=>3,"title"=>"Library Management System","desc"=>"Complete LMS with borrow/return operations and admin panel (PHP & MySQL).","tags"=>["PHP","MySQL"],"code_url"=>"#","demo_url"=>"#"],
    ["id"=>4,"title"=>"Online Voting System","desc"=>"Secure online voting system with role-based access.","tags"=>["PHP","MySQL","Security"],"code_url"=>"#","demo_url"=>"#"],
    ["id"=>5,"title"=>"Student Record Management","desc"=>"CRUD application for student records, authentication and PDF reports.","tags"=>["PHP","MySQL","JS"],"code_url"=>"#","demo_url"=>"#"],
    ["id"=>6,"title"=>"Forza-like Racing (Unity)","desc"=>"Open-world 3D racing prototype built in Unity.","tags"=>["Unity","C#","3D"],"code_url"=>"#","demo_url"=>"#"],
];

// Learning paths data with proper course IDs
$learning_paths = [
    ["id" => 1, "title" => "Full Stack Development", "duration" => "6 months", "level" => "Beginner to Advanced", "courses" => 8, "icon" => "🌐"],
    ["id" => 2, "title" => "Data Science & AI", "duration" => "8 months", "level" => "Intermediate", "courses" => 10, "icon" => "🧠"],
    ["id" => 3, "title" => "Cyber Security", "duration" => "5 months", "level" => "Beginner to Intermediate", "courses" => 6, "icon" => "🔐"],
    ["id" => 4, "title" => "Mobile App Development", "duration" => "4 months", "level" => "Beginner", "courses" => 5, "icon" => "📱"],
];

// small helper for escaping in templates
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>EduHub - All-in-One Learning Platform for Engineering Students</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* ==========================
       CSS RESET & VARIABLES
       ========================== */
    :root {
      --primary: #7c3aed;
      --primary-light: #8b5cf6;
      --secondary: #06b6d4;
      --accent: #f59e0b;
      --accent-alt: #ef4444;
      --success: #10b981;
      --dark: #0f172a;
      --dark-light: #1e293b;
      --darker: #020617;
      --text: #f8fafc;
      --text-muted: #cbd5e1;
      --text-light: #64748b;
      --glass: rgba(255, 255, 255, 0.05);
      --glass-border: rgba(255, 255, 255, 0.08);
      --glass-strong: rgba(255, 255, 255, 0.1);
      --glass-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
      --max-width: 1200px;
      --border-radius: 16px;
      --border-radius-sm: 8px;
      --transition: all 0.3s ease;
      --transition-fast: all 0.15s ease;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--darker);
      color: var(--text);
      line-height: 1.6;
      overflow-x: hidden;
      position: relative;
    }

    h1, h2, h3, h4, h5, h6 {
      font-family: 'Poppins', sans-serif;
      font-weight: 700;
      line-height: 1.2;
    }

    a {
      color: inherit;
      text-decoration: none;
    }

    img {
      max-width: 100%;
      height: auto;
    }

    .container {
      max-width: var(--max-width);
      margin: 0 auto;
      padding: 0 20px;
    }

    .section {
      padding: 100px 0;
      position: relative;
    }

    .section-title {
      font-size: 2.5rem;
      text-align: center;
      margin-bottom: 1rem;
      background: linear-gradient(90deg, var(--primary), var(--secondary));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .section-subtitle {
      text-align: center;
      color: var(--text-muted);
      font-size: 1.1rem;
      max-width: 600px;
      margin: 0 auto 3rem;
    }

    /* ==========================
       PARTICLE BACKGROUND FOR HERO
       ========================== */
    .particles-bg {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 1;
    }

    #particles-js {
      position: absolute;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, var(--darker) 0%, #1e1b4b 100%);
    }

    /* ==========================
       PROGRESS BAR STYLES
       ========================== */
    .progress-box {
      background: var(--glass);
      border: 1px solid var(--glass-border);
      border-radius: var(--border-radius-sm);
      padding: 1rem;
      margin: 1.5rem 0;
      display: flex;
      align-items: center;
      gap: 1rem;
      backdrop-filter: blur(10px);
    }
    
    .progress-bar {
      flex: 1;
      height: 8px;
      background: var(--dark-light);
      border-radius: 4px;
      overflow: hidden;
    }
    
    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, var(--primary), var(--secondary));
      border-radius: 4px;
      width: 0%;
      transition: width 0.5s ease;
    }
    
    #progressText {
      font-weight: 600;
      color: var(--primary-light);
      min-width: 60px;
      text-align: right;
    }

    /* ==========================
       ENHANCED CHAT STYLES
       ========================== */
    .user-message { 
        margin: 10px 0; 
        padding: 10px; 
        background: rgba(59, 130, 246, 0.1); 
        border-radius: 10px; 
        border-left: 3px solid #3b82f6; 
    }
    .ai-message { 
        margin: 10px 0; 
        padding: 10px; 
        background: rgba(16, 185, 129, 0.1); 
        border-radius: 10px; 
        border-left: 3px solid #10b981; 
    }
    .typing-indicator { 
        margin: 10px 0; 
        padding: 10px; 
        background: rgba(107, 114, 128, 0.1); 
        border-radius: 10px; 
        font-style: italic; 
        color: var(--text-muted);
    }
    .typing-dots::after { 
        content: ''; 
        animation: dots 1.5s steps(4, end) infinite; 
    }
    @keyframes dots { 
        0%, 20% { content: '.'; } 
        40% { content: '..'; } 
        60% { content: '...'; } 
        80%, 100% { content: ''; } 
    }
    .confidence-high { 
        color: #10b981; 
        font-size: 0.8em; 
        margin-left: 10px; 
        font-weight: 500;
    }
    .confidence-medium { 
        color: #f59e0b; 
        font-size: 0.8em; 
        margin-left: 10px; 
        font-weight: 500;
    }
    .confidence-low { 
        color: #ef4444; 
        font-size: 0.8em; 
        margin-left: 10px; 
        font-weight: 500;
    }
    .suggestions { 
        margin-top: 8px; 
        padding: 8px; 
        background: rgba(139, 92, 246, 0.1); 
        border-radius: 6px; 
        font-size: 0.9em; 
        border-left: 2px solid #8b5cf6;
    }
    .error-message { 
        margin: 10px 0; 
        padding: 10px; 
        background: rgba(239, 68, 68, 0.1); 
        border-radius: 10px; 
        border-left: 3px solid #ef4444; 
    }

    /* Enhanced chat modal */
    #chatModal .modal {
        max-width: 700px;
        max-height: 80vh;
    }
    #chatLog {
        height: 300px;
        min-height: 300px;
        overflow-y: auto;
        padding: 10px;
        background: var(--dark-light);
        border-radius: 8px;
        border: 1px solid var(--glass-border);
    }

    /* ==========================
       HEADER & NAVIGATION
       ========================== */
    header {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 1000;
      padding: 1rem 0;
      transition: var(--transition);
      background: rgba(2, 6, 23, 0.8);
      backdrop-filter: blur(10px);
    }

    header.scrolled {
      background: rgba(2, 6, 23, 0.95);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 1.5rem;
      font-weight: 800;
    }

    .logo-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 900;
    }

    .nav-links {
      display: flex;
      gap: 2rem;
      list-style: none;
    }

    .nav-links a {
      font-weight: 500;
      transition: var(--transition);
      position: relative;
    }

    .nav-links a:hover {
      color: var(--primary-light);
    }

    .nav-links a::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: 0;
      width: 0;
      height: 2px;
      background: var(--primary);
      transition: var(--transition);
    }

    .nav-links a:hover::after {
      width: 100%;
    }

    .nav-actions {
      display: flex;
      gap: 1rem;
      align-items: center;
    }

    .user-menu {
      display: flex;
      align-items: center;
      gap: 10px;
      color: var(--text-muted);
    }

    .user-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      color: white;
      font-size: 0.9rem;
    }

    /* ==========================
       BUTTONS
       ========================== */
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 24px;
      border-radius: var(--border-radius-sm);
      font-weight: 600;
      font-size: 0.95rem;
      transition: var(--transition);
      cursor: pointer;
      border: none;
      outline: none;
    }

    .btn-primary {
      background: linear-gradient(90deg, var(--primary), var(--primary-light));
      color: white;
      box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4);
    }

    .btn-secondary {
      background: transparent;
      color: var(--text);
      border: 1px solid var(--glass-border);
      backdrop-filter: blur(10px);
    }

    .btn-secondary:hover {
      background: var(--glass);
      border-color: var(--primary-light);
    }

    .btn-outline {
      background: transparent;
      color: var(--text);
      border: 1px solid var(--glass-border);
    }

    .btn-outline:hover {
      background: var(--glass);
      border-color: var(--primary-light);
    }

    .btn-small {
      padding: 8px 16px;
      font-size: 0.85rem;
    }

    /* ==========================
       HERO SECTION
       ========================== */
    .hero {
      min-height: 100vh;
      display: flex;
      align-items: center;
      position: relative;
      padding-top: 80px;
      overflow: hidden;
    }

    .hero-content {
      max-width: 800px;
      margin: 0 auto;
      text-align: center;
      position: relative;
      z-index: 10;
    }

    .hero-badge {
      display: inline-block;
      background: var(--glass);
      border: 1px solid var(--glass-border);
      padding: 8px 20px;
      border-radius: 50px;
      font-size: 0.9rem;
      margin-bottom: 1.5rem;
      color: var(--primary-light);
      font-weight: 500;
      backdrop-filter: blur(10px);
    }

    .hero-title {
      font-size: 3.5rem;
      margin-bottom: 1.5rem;
      line-height: 1.1;
    }

    .hero-title span {
      background: linear-gradient(90deg, var(--primary), var(--secondary));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .hero-description {
      font-size: 1.2rem;
      color: var(--text-muted);
      max-width: 600px;
      margin: 0 auto 2.5rem;
    }

    .hero-actions {
      display: flex;
      gap: 1rem;
      justify-content: center;
      margin-bottom: 3rem;
      flex-wrap: wrap;
    }

    .hero-stats {
      display: flex;
      justify-content: center;
      gap: 2rem;
      flex-wrap: wrap;
    }

    .stat-item {
      text-align: center;
    }

    .stat-number {
      font-size: 2rem;
      font-weight: 800;
      background: linear-gradient(90deg, var(--primary), var(--secondary));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      display: block;
    }

    .stat-label {
      color: var(--text-muted);
      font-size: 0.9rem;
    }

    /* ==========================
       FEATURES SECTION - GRADIENT BACKGROUND
       ========================== */
    .features {
      background: linear-gradient(135deg, #1e1b4b 0%, #0f172a 100%);
      position: relative;
    }

    .features::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="%237c3aed" fill-opacity="0.03" points="0,1000 1000,0 1000,1000"/></svg>');
      background-size: cover;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 2rem;
      position: relative;
      z-index: 2;
    }

    .feature-card {
      background: var(--glass);
      border: 1px solid var(--glass-border);
      border-radius: var(--border-radius);
      padding: 2rem;
      transition: var(--transition);
      backdrop-filter: blur(10px);
    }

    .feature-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--glass-shadow);
      border-color: var(--primary-light);
    }

    .feature-icon {
      width: 60px;
      height: 60px;
      border-radius: 12px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1.5rem;
      font-size: 1.5rem;
    }

    .feature-title {
      font-size: 1.3rem;
      margin-bottom: 1rem;
    }

    .feature-description {
      color: var(--text-muted);
    }

    /* ==========================
       COURSES SECTION - DARK BACKGROUND
       ========================== */
    .courses {
      background: var(--dark);
    }

    .courses-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
    }

    .course-card {
      background: var(--glass);
      border: 1px solid var(--glass-border);
      border-radius: var(--border-radius);
      padding: 1.5rem;
      transition: var(--transition);
      backdrop-filter: blur(10px);
    }

    .course-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--glass-shadow);
      border-color: var(--primary-light);
    }

    .course-icon {
      width: 50px;
      height: 50px;
      border-radius: 10px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1rem;
      font-size: 1.2rem;
    }

    .course-title {
      font-size: 1.2rem;
      margin-bottom: 0.5rem;
    }

    .course-description {
      color: var(--text-muted);
      font-size: 0.9rem;
      margin-bottom: 1.5rem;
    }

    .course-actions {
      display: flex;
      gap: 0.5rem;
    }

    /* ==========================
       LEARNING PATHS SECTION - GRADIENT
       ========================== */
    .learning-paths {
      background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
      position: relative;
    }

    .learning-paths::after {
      content: '';
      position: absolute;
      bottom: 0;
      right: 0;
      width: 300px;
      height: 300px;
      background: radial-gradient(circle, var(--primary) 0%, transparent 70%);
      opacity: 0.1;
      border-radius: 50%;
    }

    .paths-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
    }

    .path-card {
      background: var(--glass-strong);
      border: 1px solid var(--glass-border);
      border-radius: var(--border-radius);
      padding: 2rem;
      transition: var(--transition);
      backdrop-filter: blur(10px);
      position: relative;
      overflow: hidden;
    }

    .path-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, var(--primary), var(--secondary));
    }

    .path-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--glass-shadow);
    }

    .path-icon {
      width: 50px;
      height: 50px;
      border-radius: 10px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1rem;
      font-size: 1.2rem;
    }

    .path-title {
      font-size: 1.3rem;
      margin-bottom: 1rem;
    }

    .path-meta {
      display: flex;
      justify-content: space-between;
      margin-bottom: 1rem;
      color: var(--text-muted);
      font-size: 0.9rem;
    }

    .path-courses {
      background: rgba(124, 58, 237, 0.1);
      color: var(--primary-light);
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.8rem;
    }

    .path-description {
      color: var(--text-muted);
      margin-bottom: 1.5rem;
    }

    /* ==========================
       MENTORS SECTION - DARK
       ========================== */
    .mentors {
      background: var(--dark);
    }

    .mentors-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
    }

    .mentor-card {
      background: var(--glass);
      border: 1px solid var(--glass-border);
      border-radius: var(--border-radius);
      padding: 1.5rem;
      transition: var(--transition);
      backdrop-filter: blur(10px);
    }

    .mentor-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--glass-shadow);
      border-color: var(--primary-light);
    }

    .mentor-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1rem;
    }

    .mentor-avatar {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      color: white;
    }

    .mentor-info h3 {
      margin-bottom: 0.25rem;
    }

    .mentor-specialty {
      color: var(--text-muted);
      font-size: 0.9rem;
    }

    .mentor-rating {
      display: flex;
      align-items: center;
      gap: 0.25rem;
      color: var(--accent);
      margin-bottom: 1rem;
    }

    .mentor-skills {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      margin-bottom: 1.5rem;
    }

    .skill-tag {
      background: rgba(124, 58, 237, 0.1);
      color: var(--primary-light);
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.8rem;
    }

    .mentor-actions {
      display: flex;
      gap: 0.5rem;
    }

    /* ==========================
       PROJECTS SECTION - GRADIENT
       ========================== */
    .projects {
      background: linear-gradient(135deg, #1e1b4b 0%, #0f172a 100%);
    }

    .projects-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 1.5rem;
    }

    .project-card {
      background: var(--glass);
      border: 1px solid var(--glass-border);
      border-radius: var(--border-radius);
      padding: 1.5rem;
      transition: var(--transition);
      backdrop-filter: blur(10px);
    }

    .project-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--glass-shadow);
      border-color: var(--primary-light);
    }

    .project-title {
      font-size: 1.2rem;
      margin-bottom: 0.5rem;
    }

    .project-description {
      color: var(--text-muted);
      font-size: 0.9rem;
      margin-bottom: 1rem;
    }

    .project-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      margin-bottom: 1.5rem;
    }

    .project-tag {
      background: rgba(6, 182, 212, 0.1);
      color: var(--secondary);
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.8rem;
    }

    .project-actions {
      display: flex;
      gap: 0.5rem;
    }

    /* ==========================
       TESTIMONIALS SECTION - DARK
       ========================== */
    .testimonials {
      background: var(--dark);
    }

    .testimonials-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 1.5rem;
    }

    .testimonial-card {
      background: var(--glass);
      border: 1px solid var(--glass-border);
      border-radius: var(--border-radius);
      padding: 1.5rem;
      transition: var(--transition);
      backdrop-filter: blur(10px);
    }

    .testimonial-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--glass-shadow);
      border-color: var(--primary-light);
    }

    .testimonial-content {
      margin-bottom: 1.5rem;
      font-style: italic;
      color: var(--text-muted);
    }

    .testimonial-author {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .author-avatar {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      color: white;
    }

    .author-info h4 {
      margin-bottom: 0.25rem;
    }

    .author-role {
      color: var(--text-muted);
      font-size: 0.9rem;
    }

    /* ==========================
       FOOTER
       ========================== */
    footer {
      background: var(--darker);
      padding: 4rem 0 2rem;
      border-top: 1px solid var(--glass-border);
    }

    .footer-content {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 2rem;
      margin-bottom: 3rem;
    }

    .footer-column h3 {
      margin-bottom: 1.5rem;
      font-size: 1.2rem;
    }

    .footer-links {
      list-style: none;
    }

    .footer-links li {
      margin-bottom: 0.75rem;
    }

    .footer-links a {
      color: var(--text-muted);
      transition: var(--transition);
    }

    .footer-links a:hover {
      color: var(--primary-light);
    }

    .footer-bottom {
      border-top: 1px solid var(--glass-border);
      padding-top: 2rem;
      text-align: center;
      color: var(--text-muted);
    }

    /* ==========================
       MODALS
       ========================== */
    .modal-backdrop {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.7);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 2000;
      padding: 20px;
    }

    .modal {
      background: var(--dark-light);
      border-radius: var(--border-radius);
      padding: 2rem;
      max-width: 600px;
      width: 100%;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      border: 1px solid var(--glass-border);
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
    }

    .modal-title {
      font-size: 1.5rem;
    }

    .modal-close {
      background: none;
      border: none;
      color: var(--text-muted);
      font-size: 1.5rem;
      cursor: pointer;
      transition: var(--transition);
    }

    .modal-close:hover {
      color: var(--text);
    }

    /* ==========================
       CHAT FAB
       ========================== */
    .chat-fab {
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 20px rgba(124, 58, 237, 0.4);
      cursor: pointer;
      z-index: 100;
      transition: var(--transition);
    }

    .chat-fab:hover {
      transform: scale(1.1);
    }

    /* ==========================
       ANIMATIONS
       ========================== */
    .fade-in {
      opacity: 0;
      transform: translateY(30px);
      transition: opacity 0.6s ease, transform 0.6s ease;
    }

    .fade-in.visible {
      opacity: 1;
      transform: translateY(0);
    }

    /* ==========================
       RESPONSIVE DESIGN
       ========================== */
    @media (max-width: 768px) {
      .hero-title {
        font-size: 2.5rem;
      }

      .section-title {
        font-size: 2rem;
      }

      .nav-links {
        display: none;
      }

      .hero-actions {
        flex-direction: column;
        align-items: center;
      }

      .hero-actions .btn {
        width: 100%;
        max-width: 300px;
        justify-content: center;
      }

      .section {
        padding: 60px 0;
      }
    }

    @media (max-width: 480px) {
      .hero-title {
        font-size: 2rem;
      }

      .container {
        padding: 0 15px;
      }
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header id="header">
    <div class="container">
      <nav class="navbar">
        <div class="logo">
          <div class="logo-icon">EL</div>
          <span>ELearningPlatform</span>
        </div>
        
        <ul class="nav-links">
          <li><a href="#home">Home</a></li>
          <li><a href="#features">Features</a></li>
          <li><a href="#courses">Courses</a></li>
          <li><a href="#paths">Learning Paths</a></li>
          <li><a href="#mentors">Mentors</a></li>
          <li><a href="#projects">Projects</a></li>
          <li><a href="materials.php">Materials</a></li>
          <li><a href="events_calendar.php">Calendar</a></li>
        </ul>
        
        <div class="nav-actions">
          <?php if ($isLoggedIn): ?>
            <div class="user-menu">
              <div class="user-avatar">
                <?php 
                  if (!empty($userData['name'])) {
                    $nameParts = explode(' ', $userData['name']);
                    $initials = '';
                    foreach($nameParts as $part) {
                      $initials .= strtoupper(substr($part, 0, 1));
                      if(strlen($initials) >= 2) break;
                    }
                    echo $initials;
                  } else {
                    echo 'U';
                  }
                ?>
              </div>
              <span><?php echo h($userData['name'] ?? 'User'); ?></span>
            </div>
            <a class="btn btn-secondary" href="dashboard.php">Dashboard</a>
            <a class="btn btn-outline" href="logout.php">Logout</a>
          <?php else: ?>
            <button class="btn btn-primary" onclick="location.href='auth.php'">Login / Signup</button>
          <?php endif; ?>
        </div>
      </nav>
    </div>
  </header>

  <!-- Hero Section with Particle Background -->
  <section class="hero" id="home">
    <div class="particles-bg">
      <div id="particles-js"></div>
    </div>
    <div class="container">
      <div class="hero-content">
        <div class="hero-badge">🚀 All-in-One Learning Platform</div>
        <h1 class="hero-title">Master Engineering with <span>Hands-On Learning</span></h1>
        <p class="hero-description">ELearning Platform brings together courses, projects, mentorship, and tools designed specifically for engineering students. Build practical skills, work on real projects, and get career-ready.</p>
        
        <div class="hero-actions">
          <button class="btn btn-primary" onclick="location.href='courses.php'">
            <span>Explore Courses</span>
            <span>→</span>
          </button>
          <button class="btn btn-secondary" onclick="location.href='mentors.php'">Find Mentors</button>
          <button class="btn btn-outline" onclick="openTools()">Learning Tools</button>
        </div>
        
        <?php if ($isLoggedIn): ?>
        <div class="progress-box">
          <div class="progress-bar">
            <div class="progress-fill" id="progressFill"></div>
          </div>
          <span id="progressText">Loading...</span>
        </div>
        <?php endif; ?>
        
        <div class="hero-stats">
          <div class="stat-item">
            <span class="stat-number">15k+</span>
            <span class="stat-label">Active Learners</span>
          </div>
          <div class="stat-item">
            <span class="stat-number">4.8</span>
            <span class="stat-label">Average Rating</span>
          </div>
          <div class="stat-item">
            <span class="stat-number"><?php echo count($courses); ?>+</span>
            <span class="stat-label">Courses</span>
          </div>
          <div class="stat-item">
            <span class="stat-number">500+</span>
            <span class="stat-label">Projects Completed</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section class="section features" id="features">
    <div class="container">
      <h2 class="section-title">Why Choose EduHub?</h2>
      <p class="section-subtitle">Our platform is designed specifically for engineering students to bridge the gap between theory and practice</p>
      
      <div class="features-grid">
        <div class="feature-card fade-in">
          <div class="feature-icon">📚</div>
          <h3 class="feature-title">Industry-Relevant Courses</h3>
          <p class="feature-description">Learn from courses designed with industry input, focusing on practical skills that employers value. Updated regularly with latest technologies.</p>
        </div>
        

        <div class="feature-card fade-in">
          <div class="feature-icon">👨‍🏫</div>
          <h3 class="feature-title">1:1 Mentor Sessions</h3>
          <p class="feature-description">Get personalized guidance from experienced professionals and academic experts in your field. Book sessions based on your schedule.</p>
        </div>
        
        <div class="feature-card fade-in">
          <div class="feature-icon">💼</div>
          <h3 class="feature-title">Project Portfolio Building</h3>
          <p class="feature-description">Build a strong portfolio with real-world projects that showcase your skills to potential employers. Get feedback from mentors.</p>
        </div>
        
        <div class="feature-card fade-in">
          <div class="feature-icon">🎯</div>
          <h3 class="feature-title">Placement Preparation</h3>
          <p class="feature-description">Comprehensive resources for DSA practice, mock interviews, and company-specific preparation. Track your progress with analytics.</p>
        </div>
        
        <div class="feature-card fade-in">
          <div class="feature-icon">🤖</div>
          <h3 class="feature-title">AI-Powered Assistance</h3>
          <p class="feature-description">Get instant help with our AI study assistant for doubts, explanations, and learning recommendations. Available 24/7.</p>
        </div>
        
        <div class="feature-card fade-in">
          <div class="feature-icon">📊</div>
          <h3 class="feature-title">Progress Tracking</h3>
          <p class="feature-description">Monitor your learning journey with detailed analytics and personalized progress reports. Set goals and achieve them.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Courses Section -->
  <section class="section courses" id="courses">
    <div class="container">
      <h2 class="section-title">Popular Courses</h2>
      <p class="section-subtitle">Hand-picked courses to boost your engineering career with practical skills</p>
      
      <div class="courses-grid">
        <?php foreach($courses as $c): ?>
          <div class="course-card fade-in">
            <div class="course-icon"><?php echo h($c['icon'] ?? '📘'); ?></div>
            <h3 class="course-title"><?php echo h($c['title']); ?></h3>
            <p class="course-description"><?php echo h(substr($c['short_description'] ?? '', 0, 140)); ?></p>
            <div class="course-actions">
              <a class="btn btn-outline btn-small" href="course.php?id=<?php echo (int)$c['id']; ?>">Details</a>
              <?php if ($isLoggedIn): ?>
                <a class="btn btn-primary btn-small" href="enroll.php?course_id=<?php echo (int)$c['id']; ?>">Enroll</a>
              <?php else: ?>
                <a class="btn btn-primary btn-small" href="auth.php?redirect=course.php?id=<?php echo (int)$c['id']; ?>">Login to Enroll</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Learning Paths Section -->
  <section class="section learning-paths" id="paths">
    <div class="container">
      <h2 class="section-title">Structured Learning Paths</h2>
      <p class="section-subtitle">Follow curated paths designed by industry experts to master specific domains</p>
      
      <div class="paths-grid">
        <?php foreach($learning_paths as $path): ?>
          <div class="path-card fade-in">
            <div class="path-icon"><?php echo h($path['icon']); ?></div>
            <h3 class="path-title"><?php echo h($path['title']); ?></h3>
            <div class="path-meta">
              <span>🕒 <?php echo h($path['duration']); ?></span>
              <span>📊 <?php echo h($path['level']); ?></span>
            </div>
            <div class="path-courses"><?php echo h($path['courses']); ?> courses</div>
            <p class="path-description">Complete curriculum covering fundamentals to advanced topics with hands-on projects and industry case studies.</p>
            
            <?php if ($isLoggedIn): ?>
              <a href="start_path.php?path_id=<?php echo h($path['id']); ?>" class="btn btn-primary btn-small">Start Path</a>
            <?php else: ?>
              <a href="auth.php?redirect=start_path.php?path_id=<?php echo h($path['id']); ?>" class="btn btn-primary btn-small">Login to Start</a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Mentors Section -->
  <section class="section mentors" id="mentors">
    <div class="container">
      <h2 class="section-title">Expert Mentors</h2>
      <p class="section-subtitle">Learn from industry professionals and experienced academic mentors</p>
      
      <div class="mentors-grid">
        <?php foreach($mentors as $m): ?>
          <div class="mentor-card fade-in">
            <div class="mentor-header">
              <div class="mentor-avatar">
                <?php 
                  $initials = '';
                  $nameParts = explode(' ', $m['name']);
                  foreach($nameParts as $part) {
                    $initials .= strtoupper(substr($part, 0, 1));
                    if(strlen($initials) >= 2) break;
                  }
                  echo $initials;
                ?>
              </div>
              <div class="mentor-info">
                <h3><?php echo h($m['name']); ?></h3>
                <p class="mentor-specialty"><?php echo h($m['headline']); ?></p>
              </div>
            </div>
            
            <div class="mentor-rating">
              <span>⭐</span>
              <span><?php echo h($m['rating']); ?></span>
              <span class="mentor-branch" style="margin-left: auto; color: var(--text-muted); font-size: 0.8rem;"><?php echo h($m['branch']); ?></span>
            </div>
            
            <div class="mentor-skills">
              <?php foreach(array_slice($m['skills'], 0, 3) as $skill): ?>
                <span class="skill-tag"><?php echo h($skill); ?></span>
              <?php endforeach; ?>
              <?php if(count($m['skills']) > 3): ?>
                <span class="skill-tag">+<?php echo count($m['skills']) - 3; ?> more</span>
              <?php endif; ?>
            </div>
            
            <div class="mentor-actions">
              <?php if ($isLoggedIn): ?>
                <button class="btn btn-outline btn-small" onclick="openBookingModal(<?php echo h($m['id']); ?>, '<?php echo h($m['name']); ?>')">Book Session</button>
                <a class="btn btn-primary btn-small" href="mentors.php?mentor_id=<?php echo h($m['id']); ?>">View Profile</a>
              <?php else: ?>
                <a class="btn btn-outline btn-small" href="auth.php?redirect=mentors.php?mentor_id=<?php echo h($m['id']); ?>">Login to Book</a>
                <a class="btn btn-primary btn-small" href="mentors.php?mentor_id=<?php echo h($m['id']); ?>">View Profile</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Projects Section -->
  <section class="section projects" id="projects">
    <div class="container">
      <h2 class="section-title">Student Projects</h2>
      <p class="section-subtitle">Real projects built by students like you to showcase practical skills</p>
      
      <div class="projects-grid">
        <?php foreach($student_projects as $p): ?>
          <div class="project-card fade-in">
            <h3 class="project-title"><?php echo h($p['title']); ?></h3>
            <p class="project-description"><?php echo h($p['desc']); ?></p>
            <div class="project-tags">
              <?php foreach($p['tags'] as $tag): ?>
                <span class="project-tag"><?php echo h($tag); ?></span>
              <?php endforeach; ?>
            </div>
            <div class="project-actions">
              <button class="btn btn-outline btn-small" onclick="showProjectCode(<?php echo $p['id']; ?>)">View Code</button>
              <button class="btn btn-primary btn-small" onclick="showProjectDemo(<?php echo $p['id']; ?>)">View Demo</button>
              <a class="btn btn-secondary btn-small" href="<?php echo h($p['code_url']); ?>" target="_blank">GitHub</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Testimonials Section -->
  <section class="section testimonials" id="testimonials">
    <div class="container">
      <h2 class="section-title">Student Success Stories</h2>
      <p class="section-subtitle">Hear from students who transformed their careers with EduHub</p>
      
      <div class="testimonials-grid">
        <div class="testimonial-card fade-in">
          <div class="testimonial-content">
            "EduHub helped me bridge the gap between academic knowledge and industry requirements. The project-based approach was exactly what I needed to land my dream job at Google. The mentor guidance was invaluable!"
          </div>
          <div class="testimonial-author">
            <div class="author-avatar">RS</div>
            <div class="author-info">
              <h4>Rahul Sharma</h4>
              <p class="author-role">Software Engineer at Google</p>
            </div>
          </div>
        </div>
        
        <div class="testimonial-card fade-in">
          <div class="testimonial-content">
            "The mentorship program was incredible! My mentor helped me navigate complex data science concepts and provided guidance that was crucial for my placement at Amazon. The projects gave me real-world experience."
          </div>
          <div class="testimonial-author">
            <div class="author-avatar">PS</div>
            <div class="author-info">
              <h4>Priya Singh</h4>
              <p class="author-role">Data Scientist at Amazon</p>
            </div>
          </div>
        </div>
        
        <div class="testimonial-card fade-in">
          <div class="testimonial-content">
            "As an ECE student, I found the specialized courses and projects extremely valuable. The platform helped me secure an internship at Intel during my third year. The learning paths made it easy to follow a structured approach."
          </div>
          <div class="testimonial-author">
            <div class="author-avatar">AK</div>
            <div class="author-info">
              <h4>Ankit Kumar</h4>
              <p class="author-role">ECE Student at IIT Delhi</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer>
    <div class="container">
      <div class="footer-content">
        <div class="footer-column">
          <h3>EduHub</h3>
          <p>Practical, project-first learning with mentor support and industry-aligned outcomes for engineering students.</p>
        </div>
        
        <div class="footer-column">
          <h3>Resources</h3>
          <ul class="footer-links">
            <li><a href="courses.php">All Courses</a></li>
            <li><a href="projects.php">Student Projects</a></li>
            <li><a href="mentors.php">Find Mentors</a></li>
            <li><a href="placements.php">Placement Hub</a></li>
          </ul>
        </div>
        
        <div class="footer-column">
          <h3>Support</h3>
          <ul class="footer-links">
            <li><a href="help.php">Help Center</a></li>
            <li><a href="forum.php">Community Forum</a></li>
            <li><a href="contact.php">Contact Us</a></li>
            <li><a href="privacy.php">Privacy Policy</a></li>
          </ul>
        </div>
        
        <div class="footer-column">
          <h3>Subscribe</h3>
          <p>Stay updated with new courses and learning resources.</p>
          <form id="newsletterForm" onsubmit="return submitNewsletter(event)" style="margin-top: 1rem;">
            <input id="newsEmail" type="email" placeholder="Your email" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--glass-border); background: transparent; color: var(--text); margin-bottom: 10px;" />
            <button class="btn btn-primary btn-small" type="submit" style="width: 100%;">Subscribe</button>
          </form>
        </div>
      </div>
      
      <div class="footer-bottom">
        <p>© <?php echo date('Y'); ?> EduHub Learning Platform. All rights reserved.</p>
      </div>
    </div>
  </footer>

  <!-- Chat FAB -->
  <div class="chat-fab" id="chatFab" onclick="openChat()">💬</div>

  <!-- Chat Modal -->
  <div id="chatModal" class="modal-backdrop" role="dialog" aria-hidden="true">
    <div class="modal">
      <div class="modal-header">
        <h3 class="modal-title">AI Study Assistant</h3>
        <button class="modal-close" onclick="closeChat()">✕</button>
      </div>
      <div style="margin-top:12px">
        <div id="chatLog" style="height:300px;overflow:auto;border:1px solid var(--glass-border);padding:12px;border-radius:8px;background:transparent;color:var(--text); margin-bottom: 1rem;"></div>
        <div style="display:flex;gap:8px">
          <input id="chatInput" placeholder="Ask a question about engineering, programming, or career..." style="flex:1;padding:10px;border-radius:8px;border:1px solid var(--glass-border);background:transparent;color:var(--text)" />
          <button class="btn btn-primary" onclick="sendChat()">Send</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Booking Modal -->
  <div id="bookingModal" class="modal-backdrop" role="dialog" aria-hidden="true" style="display:none;">
    <div class="modal">
      <div class="modal-header">
        <h3 class="modal-title" id="bookingModalTitle">Book Mentor Session</h3>
        <button class="modal-close" onclick="closeBookingModal()">✕</button>
      </div>
      <div style="margin-top:12px">
        <form id="bookingForm" onsubmit="return submitBooking(event)">
          <input type="hidden" id="bookingMentorId" value="">
          <div style="margin-bottom: 1rem;">
            <label style="display:block; margin-bottom: 0.5rem; color: var(--text-muted);">Your Name</label>
            <input type="text" id="bookingStudentName" value="<?php echo h($student_name); ?>" required style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--glass-border); background:transparent; color:var(--text);">
          </div>
          <div style="margin-bottom: 1rem;">
            <label style="display:block; margin-bottom: 0.5rem; color: var(--text-muted);">Select Time Slot</label>
            <select id="bookingSlot" required style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--glass-border); background:transparent; color:var(--text);">
              <option value="">Choose a time slot</option>
              <option value="2025-10-08 10:00">Oct 8, 2025 - 10:00 AM</option>
              <option value="2025-10-09 16:00">Oct 9, 2025 - 4:00 PM</option>
              <option value="2025-10-11 11:30">Oct 11, 2025 - 11:30 AM</option>
            </select>
          </div>
          <button class="btn btn-primary" type="submit" style="width:100%;">Confirm Booking</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Toast Notification -->
  <div id="toast" style="position:fixed;right:20px;bottom:20px;background:var(--dark-light);color:var(--text);padding:12px 20px;border-radius:8px;display:none;z-index:2000;box-shadow:0 4px 20px rgba(0,0,0,0.3);border:1px solid var(--glass-border)"></div>

  <!-- JavaScript -->
  <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
  <script>
    // Initialize particles.js
    document.addEventListener('DOMContentLoaded', function() {
      particlesJS('particles-js', {
        particles: {
          number: {
            value: 80,
            density: {
              enable: true,
              value_area: 800
            }
          },
          color: {
            value: "#4a69bd"
          },
          shape: {
            type: "circle",
            stroke: {
              width: 0,
              color: "#000000"
            }
          },
          opacity: {
            value: 0.5,
            random: true,
            anim: {
              enable: true,
              speed: 1,
              opacity_min: 0.1,
              sync: false
            }
          },
          size: {
            value: 3,
            random: true,
            anim: {
              enable: true,
              speed: 2,
              size_min: 0.1,
              sync: false
            }
          },
          line_linked: {
            enable: true,
            distance: 150,
            color: "#4a69bd",
            opacity: 0.4,
            width: 1
          },
          move: {
            enable: true,
            speed: 2,
            direction: "none",
            random: true,
            straight: false,
            out_mode: "out",
            bounce: false,
            attract: {
              enable: false,
              rotateX: 600,
              rotateY: 1200
            }
          }
        },
        interactivity: {
          detect_on: "canvas",
          events: {
            onhover: {
              enable: true,
              mode: "grab"
            },
            onclick: {
              enable: true,
              mode: "push"
            },
            resize: true
          },
          modes: {
            grab: {
              distance: 140,
              line_linked: {
                opacity: 1
              }
            },
            push: {
              particles_nb: 4
            }
          }
        },
        retina_detect: true
      });

      // Load progress if user is logged in
      <?php if ($isLoggedIn): ?>
      loadUserProgress();
      <?php endif; ?>
    });

    // Load user progress
    async function loadUserProgress() {
      try {
        // You can implement this function to fetch actual progress from your API
        // For now, we'll use a demo progress
        const progress = Math.floor(Math.random() * 100);
        document.getElementById('progressFill').style.width = progress + '%';
        document.getElementById('progressText').innerText = progress + '% completed';
      } catch (err) {
        console.error('Error loading progress:', err);
      }
    }

    // Header scroll effect
    window.addEventListener('scroll', function() {
      const header = document.getElementById('header');
      if (window.scrollY > 50) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
    });

    // Fade-in animation on scroll
    const fadeElements = document.querySelectorAll('.fade-in');
    
    const fadeInOnScroll = function() {
      fadeElements.forEach(element => {
        const elementTop = element.getBoundingClientRect().top;
        const elementVisible = 150;
        
        if (elementTop < window.innerHeight - elementVisible) {
          element.classList.add('visible');
        }
      });
    };
    
    window.addEventListener('scroll', fadeInOnScroll);
    // Initial check
    fadeInOnScroll();

    // Toast notification
    const toastEl = document.getElementById('toast');
    function showToast(txt, t = 3000) { 
      toastEl.textContent = txt; 
      toastEl.style.display = 'block'; 
      setTimeout(() => toastEl.style.display = 'none', t); 
    }

    // ENHANCED CHAT FUNCTIONALITY
    function openChat(){ 
      document.getElementById('chatModal').style.display = 'flex'; 
      document.getElementById('chatInput').focus();
    }
    
    function closeChat(){ 
      document.getElementById('chatModal').style.display = 'none'; 
    }
    
    async function sendChat(){
      const q = document.getElementById('chatInput').value.trim();
      if(!q) return;
      
      const log = document.getElementById('chatLog');
      log.innerHTML += `<div class="user-message"><strong>You:</strong> ${q}</div>`;
      document.getElementById('chatInput').value = '';
      
      // Show typing indicator
      const typingId = showTypingIndicator();
      
      const fd = new FormData(); 
      fd.append('action','chat'); 
      fd.append('q', q);
      
      try {
        const res = await fetch(location.href, {method:'POST', body: fd});
        const data = await res.json();
        
        // Remove typing indicator
        removeTypingIndicator(typingId);
        
        if(data.success) {
          let replyHtml = `<div class="ai-message"><strong>Assistant:</strong> ${data.reply}`;
          
          // Add confidence indicator
          if(data.confidence > 0.8) {
            replyHtml += `<span class="confidence-high"> ✓ High Confidence</span>`;
          } else if(data.confidence > 0.6) {
            replyHtml += `<span class="confidence-medium"> ~ Medium Confidence</span>`;
          } else {
            replyHtml += `<span class="confidence-low"> ? Learning Mode</span>`;
          }
          
          // Add suggestions if available
          if(data.suggestions && data.suggestions.length > 0) {
            replyHtml += `<div class="suggestions"><strong>Suggestions:</strong> ${data.suggestions.join(', ')}</div>`;
          }
          
          replyHtml += `</div>`;
          log.innerHTML += replyHtml;
        } else {
          log.innerHTML += `<div class="error-message"><strong>Assistant:</strong> ${data.msg}</div>`;
        }
        log.scrollTop = log.scrollHeight;
      } catch (err) {
        removeTypingIndicator(typingId);
        log.innerHTML += `<div class="error-message"><strong>Assistant:</strong> Network error. Please try again.</div>`;
      }
    }

    function showTypingIndicator() {
      const log = document.getElementById('chatLog');
      const id = 'typing-' + Date.now();
      log.innerHTML += `<div id="${id}" class="typing-indicator"><strong>Assistant:</strong> <span class="typing-dots">...</span></div>`;
      log.scrollTop = log.scrollHeight;
      return id;
    }

    function removeTypingIndicator(id) {
      const element = document.getElementById(id);
      if(element) element.remove();
    }

    // Newsletter subscription
    async function submitNewsletter(e){
      e.preventDefault();
      const email = document.getElementById('newsEmail').value.trim();
      if(!email) return showToast('Please enter an email address');
      
      const form = new FormData();
      form.append('action','subscribe');
      form.append('email', email);
      
      try {
        const res = await fetch(location.href, {method:'POST', body: form});
        const data = await res.json();
        if(data.success) {
          showToast(data.msg || 'Subscribed successfully!');
          document.getElementById('newsletterForm').reset();
        } else {
          showToast(data.msg || 'Subscription failed');
        }
      } catch (err) { 
        showToast('Network error'); 
      }
      return false;
    }

    // Booking functionality
    function openBookingModal(mentorId, mentorName) {
      document.getElementById('bookingMentorId').value = mentorId;
      document.getElementById('bookingModalTitle').textContent = `Book Session with ${mentorName}`;
      document.getElementById('bookingModal').style.display = 'flex';
    }
    
    function closeBookingModal() {
      document.getElementById('bookingModal').style.display = 'none';
    }
    
    async function submitBooking(e) {
      e.preventDefault();
      
      const mentorId = document.getElementById('bookingMentorId').value;
      const studentName = document.getElementById('bookingStudentName').value.trim();
      const slot = document.getElementById('bookingSlot').value;
      
      if (!studentName || !slot) {
        showToast('Please fill all fields');
        return false;
      }
      
      const form = new FormData();
      form.append('action', 'book_mentor');
      form.append('mentor_id', mentorId);
      form.append('student_name', studentName);
      form.append('slot', slot);
      
      try {
        const res = await fetch(location.href, {method:'POST', body: form});
        const data = await res.json();
        
        if(data.success) {
          showToast(data.msg || 'Booking confirmed!');
          closeBookingModal();
        } else {
          if (data.redirect) {
            // Redirect to login if not logged in
            window.location.href = data.redirect;
          } else {
            showToast(data.msg || 'Booking failed');
          }
        }
      } catch (err) {
        showToast('Network error');
      }
      
      return false;
    }

    // Modal functions
    function openTools() {
      showToast('Tools panel will open here');
    }

    function showProjectCode(id) {
      showToast(`Code for project ${id} will open here`);
    }

    function showProjectDemo(id) {
      showToast(`Demo for project ${id} will open here`);
    }

    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
      if (e.target.classList.contains('modal-backdrop')) {
        e.target.style.display = 'none';
      }
    });

    // Enter key for chat
    document.getElementById('chatInput').addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        sendChat();
      }
    });
  </script>
</body>
</html>