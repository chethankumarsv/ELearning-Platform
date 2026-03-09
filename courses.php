<?php
session_start();
require_once("includes/config.php");

// -------------------------
// Apply Filters
// -------------------------
$where = [];

if (!empty($_GET['branch'])) {
    $branch = $conn->real_escape_string($_GET['branch']);
    $where[] = "branch = '$branch'";
}
if (!empty($_GET['semester_id'])) {
    $semester_id = intval($_GET['semester_id']);
    $where[] = "semester_id = $semester_id";
}

$sql = "SELECT * FROM courses";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY id DESC";
$result = $conn->query($sql);

// Fetch semesters for dropdown
$semesters = $conn->query("SELECT * FROM semesters");

// ✅ Helper: convert any YouTube link to embed format with autoplay
function getEmbedUrl($url) {
    $embedUrl = $url;
    
    if (strpos($url, "watch?v=") !== false) {
        $embedUrl = str_replace("watch?v=", "embed/", $url);
    }
    elseif (strpos($url, "youtu.be/") !== false) {
        $videoId = substr(parse_url($url, PHP_URL_PATH), 1);
        $embedUrl = "https://www.youtube.com/embed/" . $videoId;
    }
    elseif (strpos($url, "embed/") === false && strpos($url, 'youtube.com') !== false) {
        // Handle other YouTube URL formats
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        if (isset($params['v'])) {
            $embedUrl = "https://www.youtube.com/embed/" . $params['v'];
        }
    }
    
    // Add autoplay parameter
    if (strpos($embedUrl, '?') !== false) {
        $embedUrl .= '&autoplay=1&mute=1';
    } else {
        $embedUrl .= '?autoplay=1&mute=1';
    }
    
    // Add additional parameters for better experience
    $embedUrl .= '&rel=0&modestbranding=1&playsinline=1';
    
    return $embedUrl;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Courses | E-Learning Platform</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
:root {
  --primary: #6366f1;
  --primary-dark: #4f46e5;
  --secondary: #10b981;
  --accent: #f59e0b;
  --danger: #ef4444;
  --dark: #1e293b;
  --darker: #0f172a;
  --light: #f8fafc;
  --gray: #64748b;
  --glass: rgba(255, 255, 255, 0.08);
  --glass-border: rgba(255, 255, 255, 0.12);
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(135deg, var(--darker) 0%, var(--dark) 100%);
  color: var(--light);
  min-height: 100vh;
  padding-top: 80px;
  line-height: 1.6;
}

/* Navbar */
.navbar {
  position: fixed;
  top: 0;
  width: 100%;
  background: rgba(30, 41, 59, 0.95);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--glass-border);
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 2rem;
  z-index: 1000;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.logo i {
  font-size: 1.8rem;
  color: var(--accent);
}

.logo h2 {
  font-weight: 700;
  font-size: 1.5rem;
  background: linear-gradient(135deg, var(--accent), var(--primary));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.nav-links {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.nav-link {
  color: var(--light);
  text-decoration: none;
  font-weight: 500;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  transition: all 0.3s ease;
  position: relative;
}

.nav-link:hover {
  color: var(--accent);
  background: var(--glass);
}

.nav-link.active {
  color: var(--accent);
  background: var(--glass);
}

.nav-link.active::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 1rem;
  right: 1rem;
  height: 2px;
  background: var(--accent);
  border-radius: 2px;
}

.logout-btn {
  background: linear-gradient(135deg, var(--danger), #dc2626);
  color: white;
  border: none;
  padding: 0.6rem 1.2rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
}

.logout-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
}

/* Main Content */
.main-content {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
}

.page-header {
  text-align: center;
  margin-bottom: 3rem;
}

.page-title {
  font-size: 3rem;
  font-weight: 700;
  background: linear-gradient(135deg, var(--accent), var(--primary));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 1rem;
}

.page-subtitle {
  color: var(--gray);
  font-size: 1.1rem;
  font-weight: 400;
}

/* Filter Section */
.filter-section {
  background: var(--glass);
  backdrop-filter: blur(20px);
  border: 1px solid var(--glass-border);
  border-radius: 16px;
  padding: 2rem;
  margin-bottom: 3rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.filter-form {
  display: flex;
  gap: 1.5rem;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-label {
  font-size: 0.9rem;
  color: var(--gray);
  font-weight: 500;
}

.filter-select {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid var(--glass-border);
  border-radius: 10px;
  padding: 0.75rem 1rem;
  color: var(--light);
  font-size: 0.95rem;
  min-width: 180px;
  backdrop-filter: blur(10px);
  transition: all 0.3s ease;
}

.filter-select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
}

.filter-btn {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  border: none;
  padding: 0.75rem 2rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-top: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filter-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
}

/* Course Grid */
.course-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
  gap: 2rem;
  margin-top: 2rem;
}

.course-card {
  background: var(--glass);
  backdrop-filter: blur(20px);
  border: 1px solid var(--glass-border);
  border-radius: 20px;
  padding: 2rem;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.course-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
  transition: left 0.6s ease;
}

.course-card:hover::before {
  left: 100%;
}

.course-card:hover {
  transform: translateY(-8px);
  border-color: rgba(99, 102, 241, 0.3);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.course-header {
  margin-bottom: 1.5rem;
}

.course-title {
  font-size: 1.4rem;
  font-weight: 600;
  color: var(--light);
  margin-bottom: 0.5rem;
  line-height: 1.3;
}

.course-meta {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
}

.course-tag {
  background: rgba(99, 102, 241, 0.2);
  color: var(--primary);
  padding: 0.3rem 0.8rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
  border: 1px solid rgba(99, 102, 241, 0.3);
}

.course-video {
  width: 100%;
  height: 220px;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  border: none;
  background: var(--darker);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
}

.course-description {
  color: var(--gray);
  font-size: 0.95rem;
  line-height: 1.6;
  margin-bottom: 2rem;
  flex-grow: 1;
}

.course-actions {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.action-row {
  display: flex;
  gap: 0.75rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.9rem;
  transition: all 0.3s ease;
  border: none;
  cursor: pointer;
  flex: 1;
  text-align: center;
}

.btn-primary {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
}

.btn-secondary {
  background: linear-gradient(135deg, var(--secondary), #059669);
  color: white;
}

.btn-accent {
  background: linear-gradient(135deg, var(--accent), #d97706);
  color: white;
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
}

.btn-primary:hover {
  box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
}

.btn-secondary:hover {
  box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

.btn-accent:hover {
  box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: var(--gray);
}

.empty-state i {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.empty-state h3 {
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
  color: var(--light);
}

/* Responsive Design */
@media (max-width: 768px) {
  body {
    padding-top: 70px;
  }
  
  .navbar {
    padding: 1rem;
    flex-direction: column;
    gap: 1rem;
  }
  
  .nav-links {
    gap: 1rem;
  }
  
  .main-content {
    padding: 1rem;
  }
  
  .page-title {
    font-size: 2.2rem;
  }
  
  .filter-form {
    flex-direction: column;
    align-items: stretch;
  }
  
  .filter-group {
    width: 100%;
  }
  
  .filter-select {
    min-width: auto;
    width: 100%;
  }
  
  .course-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .course-card {
    padding: 1.5rem;
  }
  
  .action-row {
    flex-direction: column;
  }
}

@media (max-width: 480px) {
  .nav-links {
    flex-direction: column;
    width: 100%;
  }
  
  .nav-link {
    width: 100%;
    text-align: center;
  }
  
  .logout-btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
  <div class="logo">
    <i class="fas fa-graduation-cap"></i>
    <h2>E-Learning Platform</h2>
  </div>
  <div class="nav-links">
    <a href="index.php" class="nav-link">Home</a>
    <a href="courses.php" class="nav-link active">Courses</a>
    <a href="index.php" class="logout-btn">
      <i class="fas fa-sign-out-alt"></i>
      Logout
    </a>
  </div>
</nav>

<!-- Main Content -->
<div class="main-content">
  <div class="page-header">
    <h1 class="page-title">Explore Our Courses</h1>
    <p class="page-subtitle">Discover a wide range of courses tailored to your learning needs</p>
  </div>

  <!-- Filter Section -->
  <div class="filter-section">
    <form method="GET" class="filter-form">
      <div class="filter-group">
        <label class="filter-label">Branch</label>
        <select name="branch" class="filter-select">
          <option value="">All Branches</option>
          <option value="CSE" <?php if(isset($_GET['branch']) && $_GET['branch']=='CSE') echo 'selected'; ?>>CSE</option>
          <option value="MECH" <?php if(isset($_GET['branch']) && $_GET['branch']=='MECH') echo 'selected'; ?>>MECH</option>
          <option value="CIVIL" <?php if(isset($_GET['branch']) && $_GET['branch']=='CIVIL') echo 'selected'; ?>>CIVIL</option>
          <option value="ECE" <?php if(isset($_GET['branch']) && $_GET['branch']=='ECE') echo 'selected'; ?>>ECE</option>
          <option value="AI-ML" <?php if(isset($_GET['branch']) && $_GET['branch']=='AI-ML') echo 'selected'; ?>>AI-ML</option>
        </select>
      </div>

      <div class="filter-group">
        <label class="filter-label">Semester</label>
        <select name="semester_id" class="filter-select">
          <option value="">All Semesters</option>
          <?php while($sem = $semesters->fetch_assoc()){ ?>
            <option value="<?php echo $sem['id']; ?>" <?php if(isset($_GET['semester_id']) && $_GET['semester_id']==$sem['id']) echo 'selected'; ?>>
              <?php echo $sem['sem_name']; ?>
            </option>
          <?php } ?>
        </select>
      </div>

      <button type="submit" class="filter-btn">
        <i class="fas fa-filter"></i>
        Apply Filters
      </button>
    </form>
  </div>

  <!-- Course Grid -->
  <div class="course-grid">
    <?php 
    if ($result && $result->num_rows > 0) { 
        while ($row = $result->fetch_assoc()) { ?>
            <div class="course-card">
                <div class="course-header">
                    <h3 class="course-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                    <div class="course-meta">
                        <?php if (!empty($row['branch'])): ?>
                            <span class="course-tag"><?php echo htmlspecialchars($row['branch']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($row['semester_id'])): ?>
                            <span class="course-tag">Semester <?php echo htmlspecialchars($row['semester_id']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($row['video_url'])): ?>
                    <iframe 
                        class="course-video"
                        src="<?php echo getEmbedUrl($row['video_url']); ?>" 
                        allow="autoplay; encrypted-media" 
                        allowfullscreen
                        loading="lazy">
                    </iframe>
                <?php endif; ?>
                
                <?php if (!empty($row['description'])): ?>
                    <p class="course-description"><?php echo htmlspecialchars($row['description']); ?></p>
                <?php endif; ?>
                
                <!-- Action Buttons -->
                <div class="course-actions">
                    <div class="action-row">
                        <a href="quizzes.php?course_id=<?php echo $row['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-play-circle"></i>
                            Start Learning
                        </a>
                    </div>
                    <div class="action-row">
                        <a href="quizzes.php?course_id=<?php echo $row['id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-question-circle"></i>
                            Take Quiz
                        </a>
                        <a href="certificates.php?course_id=<?php echo $row['id']; ?>" class="btn btn-accent">
                            <i class="fas fa-certificate"></i>
                            Get Certificate
                        </a>
                    </div>
                </div>
            </div>
    <?php 
        }
    } else { ?>
        <div class="empty-state">
            <i class="fas fa-book-open"></i>
            <h3>No Courses Found</h3>
            <p>No courses match your current filter criteria. Try adjusting your filters.</p>
        </div>
    <?php } ?>
  </div>
</div>

<script>
// Enhance user experience
document.addEventListener('DOMContentLoaded', function() {
    // Smooth loading for iframes
    const iframes = document.querySelectorAll('.course-video');
    iframes.forEach(iframe => {
        iframe.addEventListener('load', function() {
            this.style.opacity = '1';
        });
        iframe.style.opacity = '0';
        iframe.style.transition = 'opacity 0.5s ease';
    });

    // Add subtle animation to cards on load
    const cards = document.querySelectorAll('.course-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.style.animation = 'fadeInUp 0.6s ease forwards';
    });
});

// Add CSS animation
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(style);
</script>

</body>
</html>