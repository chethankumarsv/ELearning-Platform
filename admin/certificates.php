<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['admin_id']) && !isset($_SESSION['admin_loggedin'])) {
    header("Location: login.php");
    exit();
}

// Fetch all certificates with student and course information
$certificates = [];
$total_certificates = 0;
$recent_certificates = 0;

$certificates_query = "
    SELECT 
        c.id as certificate_id,
        c.title as certificate_title,
        c.issued_on,
        c.download_url,
        u.username as student_name,
        u.email as student_email,
        cr.title as course_title,
        cr.code as course_code
    FROM certificates c
    LEFT JOIN users u ON c.user_id = u.id
    LEFT JOIN courses cr ON c.course_id = cr.id
    ORDER BY c.issued_on DESC
";

$certificates_result = mysqli_query($conn, $certificates_query);

if ($certificates_result) {
    while ($certificate = mysqli_fetch_assoc($certificates_result)) {
        $certificates[] = $certificate;
    }
    $total_certificates = count($certificates);
    
    // Count recent certificates (last 30 days)
    $recent_query = "SELECT COUNT(*) as recent_count FROM certificates WHERE issued_on >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $recent_result = mysqli_query($conn, $recent_query);
    if ($recent_result) {
        $recent_data = mysqli_fetch_assoc($recent_result);
        $recent_certificates = $recent_data['recent_count'];
    }
} else {
    error_log("SQL Error in certificates.php: " . mysqli_error($conn));
}

// Handle certificate actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_certificate'])) {
        // Handle certificate generation logic
        $user_id = $_POST['user_id'] ?? '';
        $course_id = $_POST['course_id'] ?? '';
        
        // Add your certificate generation logic here
        $_SESSION['message'] = "Certificate generated successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: certificates.php");
        exit();
    }
    
    if (isset($_POST['delete_certificate'])) {
        $certificate_id = $_POST['certificate_id'] ?? '';
        
        // Add your certificate deletion logic here
        $_SESSION['message'] = "Certificate deleted successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: certificates.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Management - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .certificate-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        
        .certificate-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-issued {
            background: rgba(6, 214, 160, 0.1);
            color: var(--success);
        }
        
        .status-pending {
            background: rgba(255, 209, 102, 0.1);
            color: #92400e;
        }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid var(--border);
        }
        
        .filter-group {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-select {
            padding: 10px 15px;
            border: 2px solid var(--border);
            border-radius: 8px;
            background: white;
            font-size: 14px;
            min-width: 200px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-graduation-cap"></i> E-Learning</h2>
                <p>Admin Panel</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="students.php"><i class="fas fa-users"></i> Students</a></li>
                <li><a href="mentors.php"><i class="fas fa-chalkboard-teacher"></i> Mentors</a></li>
                <li><a href="courses.php"><i class="fas fa-book"></i> Courses</a></li>
                <li><a href="certificates.php" class="active"><i class="fas fa-certificate"></i> Certificates</a></li>
                <li><a href="enrollments.php"><i class="fas fa-list-alt"></i> Enrollments</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1><i class="fas fa-certificate"></i> Certificate Management</h1>
                <div class="user-info">
                    <i class="fas fa-user-shield"></i> Welcome, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>
                </div>
            </div>

            <div class="content">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['message_type'] === 'error' ? 'error' : 'success'; ?>">
                        <i class="fas <?php echo $_SESSION['message_type'] === 'error' ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?>"></i>
                        <?php 
                        echo $_SESSION['message']; 
                        unset($_SESSION['message']);
                        unset($_SESSION['message_type']);
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Certificate Overview -->
                <div class="certificate-card">
                    <div class="d-flex justify-between align-center">
                        <div>
                            <h2 style="color: white; margin-bottom: 10px;">📜 Certificate Center</h2>
                            <p style="color: rgba(255, 255, 255, 0.9); margin-bottom: 15px;">
                                Manage and track all student certificates issued through the platform.
                            </p>
                            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                <span class="certificate-badge">
                                    <i class="fas fa-certificate"></i> Total: <?php echo $total_certificates; ?>
                                </span>
                                <span class="certificate-badge">
                                    <i class="fas fa-clock"></i> Recent: <?php echo $recent_certificates; ?>
                                </span>
                                <span class="certificate-badge">
                                    <i class="fas fa-check-circle"></i> All Verified
                                </span>
                            </div>
                        </div>
                        <div>
                            <button class="btn btn-warning">
                                <i class="fas fa-download"></i> Export Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_certificates; ?></div>
                        <div class="stat-label">Total Certificates</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $recent_certificates; ?></div>
                        <div class="stat-label">Last 30 Days</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">100%</div>
                        <div class="stat-label">Verified</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">0</div>
                        <div class="stat-label">Pending</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filters">
                    <h3 style="margin-bottom: 15px;">Filter Certificates</h3>
                    <div class="filter-group">
                        <select class="filter-select">
                            <option value="">All Courses</option>
                            <option value="web-dev">Web Development</option>
                            <option value="data-science">Data Science</option>
                        </select>
                        <select class="filter-select">
                            <option value="">All Students</option>
                            <option value="john">John Doe</option>
                            <option value="jane">Jane Smith</option>
                        </select>
                        <select class="filter-select">
                            <option value="">All Time</option>
                            <option value="7days">Last 7 Days</option>
                            <option value="30days">Last 30 Days</option>
                            <option value="90days">Last 90 Days</option>
                        </select>
                        <button class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <button class="btn btn-outline" style="background: transparent; border: 2px solid var(--border); color: var(--gray);">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                    </div>
                </div>

                <!-- Certificates List -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> All Certificates (<?php echo $total_certificates; ?>)</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($certificates)): ?>
                            <div class="empty-state">
                                <i class="fas fa-certificate"></i>
                                <h3>No Certificates Issued Yet</h3>
                                <p>Certificates will appear here once they are generated for students who complete courses.</p>
                                <a href="courses.php" class="btn btn-primary mt-20">
                                    <i class="fas fa-book"></i> Manage Courses
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Certificate ID</th>
                                            <th>Student</th>
                                            <th>Course</th>
                                            <th>Certificate Title</th>
                                            <th>Issued Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($certificates as $cert): ?>
                                        <tr>
                                            <td>
                                                <strong>#<?php echo $cert['certificate_id']; ?></strong>
                                            </td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.9rem;">
                                                        <?php echo strtoupper(substr($cert['student_name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($cert['student_name']); ?></div>
                                                        <div style="font-size: 0.8rem; color: var(--gray);"><?php echo htmlspecialchars($cert['student_email']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-weight: 600;"><?php echo htmlspecialchars($cert['course_title']); ?></div>
                                                <div style="font-size: 0.8rem; color: var(--gray);"><?php echo htmlspecialchars($cert['course_code']); ?></div>
                                            </td>
                                            <td><?php echo htmlspecialchars($cert['certificate_title']); ?></td>
                                            <td>
                                                <span class="badge badge-primary">
                                                    <?php echo date('M j, Y', strtotime($cert['issued_on'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge status-issued">
                                                    <i class="fas fa-check-circle"></i> Issued
                                                </span>
                                            </td>
                                            <td class="action-buttons">
                                                <a href="<?php echo htmlspecialchars($cert['download_url'] ?? '#'); ?>" class="btn btn-success btn-sm" target="_blank">
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                                <button class="btn btn-primary btn-sm">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="certificate_id" value="<?php echo $cert['certificate_id']; ?>">
                                                    <button type="submit" name="delete_certificate" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this certificate?')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid var(--border);">
                                <div style="color: var(--gray); font-size: 0.9rem;">
                                    Showing <?php echo count($certificates); ?> of <?php echo $total_certificates; ?> certificates
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <button class="btn btn-outline" style="padding: 8px 16px;" disabled>
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </button>
                                    <button class="btn btn-outline" style="padding: 8px 16px;">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex gap-15" style="flex-wrap: wrap;">
                            <button class="btn btn-primary">
                                <i class="fas fa-plus"></i> Generate New Certificate
                            </button>
                            <button class="btn btn-success">
                                <i class="fas fa-envelope"></i> Bulk Email Certificates
                            </button>
                            <button class="btn btn-warning">
                                <i class="fas fa-cog"></i> Certificate Settings
                            </button>
                            <button class="btn btn-primary">
                                <i class="fas fa-file-export"></i> Export All Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add any JavaScript functionality here
            console.log('Certificate management loaded');
            
            // Example: Filter functionality
            const filterButtons = document.querySelectorAll('.filter-select');
            filterButtons.forEach(select => {
                select.addEventListener('change', function() {
                    // Add filter logic here
                    console.log('Filter changed:', this.value);
                });
            });
        });
    </script>
</body>
</html>