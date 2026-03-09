<?php
// setup/setup_ai_tables.php
require_once '../includes/config.php';

$ai_tables = [
    "ai_knowledge_base" => "
        CREATE TABLE IF NOT EXISTS ai_knowledge_base (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question TEXT NOT NULL,
            answer TEXT NOT NULL,
            category VARCHAR(50) NOT NULL,
            tags TEXT,
            relevance_score FLOAT DEFAULT 1.0,
            last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_category (category),
            INDEX idx_tags (tags(255)),
            FULLTEXT idx_question_answer (question, answer)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    "ai_response_cache" => "
        CREATE TABLE IF NOT EXISTS ai_response_cache (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question_hash VARCHAR(32) UNIQUE NOT NULL,
            original_question TEXT NOT NULL,
            response JSON NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_hash (question_hash),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    "ai_conversation_logs" => "
        CREATE TABLE IF NOT EXISTS ai_conversation_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            question TEXT NOT NULL,
            response TEXT NOT NULL,
            confidence_score FLOAT,
            response_source VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    "
];

foreach ($ai_tables as $table_name => $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Table '$table_name' created successfully<br>";
    } else {
        echo "Error creating table '$table_name': " . $conn->error . "<br>";
    }
}

// Populate initial knowledge base
$initial_data = [
    [
        'question' => 'What is object-oriented programming?',
        'answer' => 'Object-Oriented Programming (OOP) is a programming paradigm based on the concept of objects, which can contain data and code. The four main principles are: 1) Encapsulation - bundling data and methods, 2) Inheritance - creating new classes from existing ones, 3) Polymorphism - objects of different types being accessed through the same interface, 4) Abstraction - hiding complex implementation details.',
        'category' => 'programming',
        'tags' => 'OOP, programming, computer science, concepts',
        'relevance_score' => 0.95
    ],
    // Add more initial questions as needed
];

foreach ($initial_data as $data) {
    $sql = "INSERT IGNORE INTO ai_knowledge_base (question, answer, category, tags, relevance_score) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssd", $data['question'], $data['answer'], $data['category'], $data['tags'], $data['relevance_score']);
    $stmt->execute();
}

echo "AI tables setup completed!";
?>