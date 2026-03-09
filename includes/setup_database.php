<?php
// setup_database.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "elearningplatform"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "Database '$dbname' created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select database
$conn->select_db($dbname);

// Create tables
$tables = [
    "newsletter" => "CREATE TABLE IF NOT EXISTS newsletter (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "mentor_bookings" => "CREATE TABLE IF NOT EXISTS mentor_bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mentor_id INT NOT NULL,
        user_id INT NOT NULL,
        student_name VARCHAR(255) NOT NULL,
        slot VARCHAR(255) NOT NULL,
        status VARCHAR(50) DEFAULT 'booked',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "courses" => "CREATE TABLE IF NOT EXISTS courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        short_description TEXT,
        icon VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "mentors" => "CREATE TABLE IF NOT EXISTS mentors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        branch VARCHAR(50),
        rating DECIMAL(2,1),
        headline VARCHAR(255),
        skills TEXT,
        available_slots TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    // AI Assistant Tables
    "ai_knowledge_base" => "CREATE TABLE IF NOT EXISTS ai_knowledge_base (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question TEXT NOT NULL,
        answer TEXT NOT NULL,
        category VARCHAR(50) NOT NULL,
        tags TEXT,
        relevance_score FLOAT DEFAULT 1.0,
        last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_category (category),
        FULLTEXT idx_question_answer (question, answer)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "ai_response_cache" => "CREATE TABLE IF NOT EXISTS ai_response_cache (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question_hash VARCHAR(32) UNIQUE NOT NULL,
        original_question TEXT NOT NULL,
        response JSON NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_hash (question_hash),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "ai_conversation_logs" => "CREATE TABLE IF NOT EXISTS ai_conversation_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        question TEXT NOT NULL,
        response TEXT NOT NULL,
        confidence_score FLOAT,
        response_source VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($tables as $table_name => $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Table '$table_name' created successfully<br>";
    } else {
        echo "Error creating table '$table_name': " . $conn->error . "<br>";
    }
}

// Insert sample data
$sample_courses = [
    ["Web Development", "HTML, CSS, JavaScript & frameworks", "💻"],
    ["Data Science", "Python, pandas, ML basics", "📊"],
    ["AI & ML", "Neural nets & ML pipelines", "🤖"],
    ["Mobile App Development", "Build Android & iOS apps", "📱"],
    ["Cloud Computing", "AWS, Azure, and cloud services", "☁️"],
    ["Cyber Security", "Ethical hacking and security fundamentals", "🔒"],
    ["Blockchain Development", "Smart contracts and DApps", "⛓️"],
    ["IoT & Embedded Systems", "Hardware programming and IoT projects", "🔌"]
];

foreach ($sample_courses as $course) {
    $sql = "INSERT IGNORE INTO courses (title, short_description, icon) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $course[0], $course[1], $course[2]);
    $stmt->execute();
}

// Insert sample mentors
$sample_mentors = [
    ["Prof. Anil Kumar", "CSE", 4.8, "DSA & Interview Coach", '["DSA","Java","System Design"]', '["2025-10-08 10:00","2025-10-09 16:00"]'],
    ["Dr. Priya Sharma", "ECE", 4.7, "Electronics & VLSI Mentor", '["VLSI","Embedded"]', '["2025-10-07 14:00","2025-10-10 09:00"]'],
    ["Mr. Ravi Patel", "ME", 4.6, "Mechanical Design Coach", '["CAD","Thermodynamics"]', '["2025-10-09 12:00","2025-10-12 15:30"]'],
    ["Dr. Sneha Verma", "CSE", 4.9, "AI Research Mentor", '["AI/ML","Python","Research"]', '["2025-10-08 14:00","2025-10-10 11:00"]']
];

foreach ($sample_mentors as $mentor) {
    $sql = "INSERT IGNORE INTO mentors (name, branch, rating, headline, skills, available_slots) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdsss", $mentor[0], $mentor[1], $mentor[2], $mentor[3], $mentor[4], $mentor[5]);
    $stmt->execute();
}

// Insert sample AI knowledge base
$sample_knowledge = [
    [
        "What is object-oriented programming?",
        "Object-Oriented Programming (OOP) is a programming paradigm based on the concept of objects, which can contain data and code. The four main principles are: 1) Encapsulation - bundling data and methods, 2) Inheritance - creating new classes from existing ones, 3) Polymorphism - objects of different types being accessed through the same interface, 4) Abstraction - hiding complex implementation details.",
        "programming",
        "OOP, programming, computer science, concepts",
        0.95
    ],
    [
        "How to implement binary search in Python?",
        "Binary search is an efficient algorithm for finding an item in a sorted list. Here's the Python implementation:\n\n```python\ndef binary_search(arr, target):\n    low, high = 0, len(arr) - 1\n    while low <= high:\n        mid = (low + high) // 2\n        if arr[mid] == target:\n            return mid\n        elif arr[mid] < target:\n            low = mid + 1\n        else:\n            high = mid - 1\n    return -1\n```\nTime complexity: O(log n), Space complexity: O(1)",
        "programming",
        "algorithms, python, binary search, coding",
        0.9
    ]
];

foreach ($sample_knowledge as $knowledge) {
    $sql = "INSERT IGNORE INTO ai_knowledge_base (question, answer, category, tags, relevance_score) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssd", $knowledge[0], $knowledge[1], $knowledge[2], $knowledge[3], $knowledge[4]);
    $stmt->execute();
}

echo "Database setup completed successfully!<br>";
echo "You can now access your application at: http://localhost/elearningplatform/<br>";
$conn->close();
?>