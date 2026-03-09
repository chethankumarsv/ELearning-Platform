<?php
// start_path.php
session_start();

// Get the course ID from URL parameters
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 1;

// Define learning paths data
$learning_paths = [
    1 => [
        'title' => 'Full Stack Development',
        'duration' => '6 months',
        'level' => 'Beginner to Advanced',
        'courses_count' => 8,
        'description' => 'Complete curriculum covering fundamentals to advanced topics with hands-on projects and industry case studies.',
        'courses' => [
            'HTML5 & CSS3 Fundamentals',
            'JavaScript & DOM Manipulation',
            'React.js Frontend Development',
            'Node.js & Express Backend',
            'Database Design with MySQL',
            'RESTful API Development',
            'Git & Deployment Strategies',
            'Final Capstone Project'
        ],
        'color' => 'primary',
        'icon' => '🌐',
        'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
    ],
    2 => [
        'title' => 'Data Science & AI',
        'duration' => '8 months',
        'level' => 'Intermediate',
        'courses_count' => 10,
        'description' => 'Complete curriculum covering fundamentals to advanced topics with hands-on projects and industry case studies.',
        'courses' => [
            'Python for Data Science',
            'Statistics & Probability',
            'Data Analysis with Pandas',
            'Data Visualization with Matplotlib & Seaborn',
            'Machine Learning Fundamentals',
            'Deep Learning with TensorFlow',
            'Natural Language Processing',
            'Big Data Technologies',
            'AI Model Deployment',
            'Industry Case Studies & Capstone Project'
        ],
        'color' => 'danger',
        'icon' => '🧠',
        'gradient' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)'
    ],
    3 => [
        'title' => 'Cyber Security',
        'duration' => '5 months',
        'level' => 'Beginner to Intermediate',
        'courses_count' => 6,
        'description' => 'Complete curriculum covering fundamentals to advanced topics with hands-on projects and industry case studies.',
        'courses' => [
            'Cyber Security Fundamentals',
            'Network Security & Protocols',
            'Ethical Hacking Techniques',
            'Cryptography & Encryption',
            'Security Assessment & Penetration Testing',
            'Incident Response & Digital Forensics'
        ],
        'color' => 'info',
        'icon' => '🔐',
        'gradient' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'
    ],
    4 => [
        'title' => 'Mobile App Development',
        'duration' => '4 months',
        'level' => 'Beginner',
        'courses_count' => 5,
        'description' => 'Learn mobile app development for iOS and Android platforms with hands-on projects.',
        'courses' => [
            'Mobile Development Fundamentals',
            'React Native Basics & Components',
            'UI/UX Design for Mobile Applications',
            'API Integration & Data Management',
            'App Deployment to App Store & Play Store'
        ],
        'color' => 'success',
        'icon' => '📱',
        'gradient' => 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)'
    ]
];

// Get current path data
$current_path = isset($learning_paths[$course_id]) ? $learning_paths[$course_id] : $learning_paths[1];

// small helper for escaping in templates
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($current_path['title']); ?> - Learning Path</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--darker);
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            line-height: 1.2;
        }

        .container {
            max-width: var(--max-width);
            margin: 0 auto;
            padding: 0 20px;
        }

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
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            color: white;
            box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4);
            color: white;
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
            color: var(--text);
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 0.85rem;
        }

        /* Header Styles */
        .path-header {
            background: <?php echo $current_path['gradient']; ?>;
            color: white;
            padding: 4rem 0 3rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .path-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.1);
        }

        .path-header > .container > .row {
            position: relative;
            z-index: 2;
        }

        .path-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            display: block;
        }

        /* Progress Section */
        .progress-container {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
        }

        .progress {
            background: var(--dark-light);
            border-radius: 10px;
            overflow: hidden;
            height: 20px;
        }

        .progress-bar {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transition: width 0.6s ease;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
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

        /* Course Cards */
        .course-card {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            transition: var(--transition);
            backdrop-filter: blur(10px);
            height: 100%;
            border-left: 4px solid;
            border-left-color: 
            <?php 
            switch($course_id) {
                case 1: echo '#007bff'; break;
                case 2: echo '#dc3545'; break;
                case 3: echo '#17a2b8'; break;
                case 4: echo '#28a745'; break;
                default: echo '#007bff';
            }
            ?>;
        }

        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--glass-shadow);
            border-color: var(--primary-light);
        }

        .course-badge {
            background: rgba(124, 58, 237, 0.1);
            color: var(--primary-light);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-badge {
            background: rgba(107, 114, 128, 0.2);
            color: var(--text-muted);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        /* Resources Section */
        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .resource-card {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            transition: var(--transition);
            backdrop-filter: blur(10px);
            text-align: center;
        }

        .resource-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--glass-shadow);
        }

        .resource-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }

        /* Action Card */
        .action-card {
            background: var(--glass-strong);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 2rem;
            backdrop-filter: blur(10px);
            text-align: center;
            height: 100%;
        }

        /* Navigation */
        .back-nav {
            margin-bottom: 2rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .path-header {
                padding: 3rem 0 2rem;
            }
            
            .path-icon {
                font-size: 3rem;
            }
            
            .display-4 {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="path-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <span class="path-icon"><?php echo h($current_path['icon']); ?></span>
                    <h1 class="display-4 fw-bold"><?php echo h($current_path['title']); ?></h1>
                    <p class="lead mb-0"><?php echo h($current_path['description']); ?></p>
                </div>
                <div class="col-md-4 text-md-end">
                    <span class="badge bg-light text-dark fs-6 me-2"><?php echo h($current_path['duration']); ?></span>
                    <span class="badge bg-light text-dark fs-6"><?php echo h($current_path['level']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Back Navigation -->
        <div class="back-nav">
            <a href="index.php" class="btn btn-secondary btn-small">
                ← Back to All Paths
            </a>
        </div>

        <!-- Path Overview -->
        <div class="row mb-5">
            <div class="col-md-8">
                <div class="progress-container">
                    <h3 class="mb-4">Your Learning Journey</h3>
                    <div class="progress mb-4">
                        <div class="progress-bar" role="progressbar" style="width: 0%" 
                             aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            <span class="progress-text">0% Complete</span>
                        </div>
                    </div>
                    <div class="row text-center">
                        <div class="col-6 col-md-3">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo h($current_path['courses_count']); ?></span>
                                <span class="stat-label">Total Courses</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="stat-item">
                                <span class="stat-number" style="color: var(--success)">0</span>
                                <span class="stat-label">Completed</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="stat-item">
                                <span class="stat-number" style="color: var(--accent)"><?php echo h($current_path['courses_count']); ?></span>
                                <span class="stat-label">Remaining</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="stat-item">
                                <span class="stat-number" style="color: var(--secondary)"><?php echo h($current_path['duration']); ?></span>
                                <span class="stat-label">Duration</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="action-card">
                    <h5>Ready to Start?</h5>
                    <p class="text-muted mb-3">Begin your learning journey today</p>
                    <button class="btn btn-primary btn-lg w-100 mb-3" onclick="startLearning()">
                        Start First Course
                    </button>
                    <div class="d-grid gap-2">
                        <button class="btn btn-secondary btn-sm" onclick="saveProgress()">
                            Save Progress
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="sharePath()">
                            Share Path
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Curriculum -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="mb-4">Course Curriculum</h2>
                <div class="row">
                    <?php foreach ($current_path['courses'] as $index => $course): ?>
                    <div class="col-lg-6 mb-3">
                        <div class="course-card">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="course-badge">
                                    Course <?php echo $index + 1; ?>
                                </span>
                                <span class="status-badge">Not Started</span>
                            </div>
                            <h5 class="mb-2"><?php echo h($course); ?></h5>
                            <p class="text-muted small mb-3">
                                This course covers essential concepts and practical applications with hands-on projects.
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">~2 weeks • 15-20 hours</small>
                                <button class="btn btn-outline-primary btn-sm" 
                                        onclick="startCourse(<?php echo $index + 1; ?>, '<?php echo h($course); ?>')">
                                    Start Course
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Learning Resources -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="mb-4">Learning Resources</h2>
                <div class="resources-grid">
                    <div class="resource-card">
                        <div class="resource-icon">📚</div>
                        <h5>Study Materials</h5>
                        <p class="text-muted small">PDFs, Slides, and comprehensive notes</p>
                        <button class="btn btn-secondary btn-sm" onclick="openResources('materials')">
                            Access Materials
                        </button>
                    </div>
                    
                    <div class="resource-card">
                        <div class="resource-icon">🎥</div>
                        <h5>Video Lectures</h5>
                        <p class="text-muted small">Recorded sessions and tutorials</p>
                        <button class="btn btn-secondary btn-sm" onclick="openResources('videos')">
                            Watch Videos
                        </button>
                    </div>
                    
                    <div class="resource-card">
                        <div class="resource-icon">💻</div>
                        <h5>Practice Projects</h5>
                        <p class="text-muted small">Hands-on coding exercises</p>
                        <button class="btn btn-secondary btn-sm" onclick="openResources('projects')">
                            Start Projects
                        </button>
                    </div>
                    
                    <div class="resource-card">
                        <div class="resource-icon">🔄</div>
                        <h5>Quizzes & Tests</h5>
                        <p class="text-muted small">Assess your knowledge</p>
                        <button class="btn btn-secondary btn-sm" onclick="openResources('quizzes')">
                            Take Quiz
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Path Completion -->
        <div class="row">
            <div class="col-12">
                <div class="progress-container text-center">
                    <h3 class="mb-3">Complete the Path to Earn Your Certificate</h3>
                    <p class="text-muted mb-4">Finish all courses and projects to receive your completion certificate</p>
                    <button class="btn btn-primary btn-lg" onclick="viewCertificate()">
                        View Certificate Preview
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function startLearning() {
            const pathTitle = "<?php echo h($current_path['title']); ?>";
            alert(`Starting your learning journey in ${pathTitle}! 🚀\n\nYou'll begin with the first course. Good luck!`);
            // Redirect to first course or implement your logic
            startCourse(1, "<?php echo h($current_path['courses'][0]); ?>");
        }

        function startCourse(courseNumber, courseName) {
            alert(`Starting Course ${courseNumber}: ${courseName}\n\nThis will open the course materials and video lectures.`);
            // Implement course starting logic
            updateProgress(courseNumber);
        }

        function updateProgress(courseNumber) {
            const totalCourses = <?php echo $current_path['courses_count']; ?>;
            const progress = ((courseNumber - 1) / totalCourses) * 100;
            const progressBar = document.querySelector('.progress-bar');
            const progressText = document.querySelector('.progress-text');
            
            progressBar.style.width = progress + '%';
            progressBar.setAttribute('aria-valuenow', progress);
            progressText.textContent = Math.round(progress) + '% Complete';
            
            // Update completed courses count
            if (courseNumber > 1) {
                document.querySelector('.stat-number[style*="color: var(--success)"]').textContent = courseNumber - 1;
                document.querySelector('.stat-number[style*="color: var(--accent)"]').textContent = totalCourses - (courseNumber - 1);
            }
        }

        function saveProgress() {
            alert('Progress saved successfully! ✅\nYour learning progress has been saved to your account.');
        }

        function sharePath() {
            const pathTitle = "<?php echo h($current_path['title']); ?>";
            alert(`Share the "${pathTitle}" learning path with others! 📤\n\nCopy this link to share: ${window.location.href}`);
        }

        function openResources(type) {
            const resources = {
                'materials': 'Study Materials',
                'videos': 'Video Lectures', 
                'projects': 'Practice Projects',
                'quizzes': 'Quizzes & Tests'
            };
            alert(`Opening ${resources[type]}...`);
        }

        function viewCertificate() {
            const pathTitle = "<?php echo h($current_path['title']); ?>";
            alert(`Certificate Preview for ${pathTitle}\n\nComplete all courses to unlock your official certificate! 🎓`);
        }

        // Initialize progress on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateProgress(1);
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>