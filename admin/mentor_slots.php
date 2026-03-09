<?php
// admin/mentor_slots.php - Mentor Time Slots Management
session_start();
require_once __DIR__ . '/../includes/config.php';

// Check if user is admin
if (empty($_SESSION['admin_id']) && empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_slot'])) {
        $mentor_id = intval($_POST['mentor_id']);
        $start_at = $conn->real_escape_string($_POST['start_at']);
        $duration = intval($_POST['duration']);
        
        // First, ensure the mentor_slots table exists
        $create_table_sql = "CREATE TABLE IF NOT EXISTS mentor_slots (
            id INT PRIMARY KEY AUTO_INCREMENT,
            mentor_id INT,
            start_at DATETIME NOT NULL,
            duration_minutes INT DEFAULT 30,
            is_booked BOOLEAN DEFAULT 0,
            booked_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (mentor_id) REFERENCES mentors(id)
        )";
        
        if ($conn->query($create_table_sql)) {
            // Now insert the time slot
            $sql = "INSERT INTO mentor_slots (mentor_id, start_at, duration_minutes, is_booked, created_at) 
                    VALUES ($mentor_id, '$start_at', $duration, 0, NOW())";
            
            if ($conn->query($sql)) {
                $success = "Time slot added successfully!";
            } else {
                $error = "Error adding time slot: " . $conn->error;
            }
        } else {
            $error = "Error creating mentor_slots table: " . $conn->error;
        }
    }
    
    if (isset($_POST['delete_slot'])) {
        $slot_id = intval($_POST['slot_id']);
        $sql = "DELETE FROM mentor_slots WHERE id = $slot_id";
        
        if ($conn->query($sql)) {
            $success = "Time slot deleted successfully!";
        } else {
            $error = "Error deleting time slot: " . $conn->error;
        }
    }
}

// Fetch all mentors for dropdown
$mentors = [];
$table_check = $conn->query("SHOW TABLES LIKE 'mentors'");
if ($table_check && $table_check->num_rows > 0) {
    $sql = "SELECT id, name, branch FROM mentors WHERE is_active = 1 ORDER BY name";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $mentors[] = $row;
        }
    }
}

// Fetch all time slots with mentor info
$slots = [];
$table_check = $conn->query("SHOW TABLES LIKE 'mentor_slots'");
if ($table_check && $table_check->num_rows > 0) {
    $sql = "SELECT ms.*, m.name as mentor_name, m.branch 
            FROM mentor_slots ms 
            JOIN mentors m ON ms.mentor_id = m.id 
            WHERE ms.start_at > NOW() 
            ORDER BY ms.start_at ASC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $slots[] = $row;
        }
    }
}

// Include admin header
require_once 'header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mt-4">Manage Mentor Time Slots</h1>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-calendar-plus"></i>
                    Add New Time Slot
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Select Mentor *</label>
                                    <select name="mentor_id" class="form-control" required>
                                        <option value="">Select Mentor</option>
                                        <?php foreach ($mentors as $mentor): ?>
                                            <option value="<?php echo $mentor['id']; ?>">
                                                <?php echo htmlspecialchars($mentor['name']); ?> (<?php echo $mentor['branch']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Start Date & Time *</label>
                                    <input type="datetime-local" name="start_at" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Duration (minutes) *</label>
                                    <select name="duration" class="form-control" required>
                                        <option value="30">30 minutes</option>
                                        <option value="45">45 minutes</option>
                                        <option value="60">60 minutes</option>
                                        <option value="90">90 minutes</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="add_slot" class="btn btn-primary">
                            <i class="fas fa-save"></i> Add Time Slot
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-calendar-alt"></i>
                    Upcoming Time Slots (<?php echo count($slots); ?>)
                </div>
                <div class="card-body">
                    <?php if (empty($slots)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h4>No Time Slots Found</h4>
                            <p class="text-muted">Add time slots using the form above.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Mentor</th>
                                        <th>Date & Time</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($slots as $slot): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($slot['mentor_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo $slot['branch']; ?></small>
                                            </td>
                                            <td>
                                                <?php echo date('M j, Y g:i A', strtotime($slot['start_at'])); ?>
                                            </td>
                                            <td><?php echo $slot['duration_minutes']; ?> minutes</td>
                                            <td>
                                                <?php if ($slot['is_booked']): ?>
                                                    <span class="badge badge-danger">Booked</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Available</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                                    <button type="submit" name="delete_slot" class="btn btn-danger btn-sm" 
                                                            onclick="return confirm('Are you sure you want to delete this time slot?')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>