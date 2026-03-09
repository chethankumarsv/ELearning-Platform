<?php
// placements.php - improved, defensive implementation
// Place this file in your project root (same location as your previous placements.php)

ini_set('display_errors', '0'); // keep off by default; set to '1' temporarily for debugging
error_reporting(E_ALL);

// -- Basic helpers and session --
if (session_status() === PHP_SESSION_NONE) session_start();

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// -- Check includes/config.php and DB connection --
$configPath = __DIR__ . '/includes/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo "<h2>Configuration file missing</h2>";
    echo "<p>Expected <code>includes/config.php</code> at: <code>" . e($configPath) . "</code></p>";
    echo "<p>Create that file and make sure it sets <code>\$conn = new mysqli(...)</code>.</p>";
    exit;
}

require_once $configPath;

// Validate $conn
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo "<h2>Database connection error</h2>";
    echo "<p><code>\$conn</code> is not defined as a <code>mysqli</code> instance in <code>includes/config.php</code>.</p>";
    exit;
}

// Try to ping the DB
if (!$conn->ping()) {
    http_response_code(500);
    echo "<h2>Database unreachable</h2>";
    echo "<p>Check DB credentials and that MySQL is running. Error: " . e($conn->connect_error) . "</p>";
    exit;
}

// -- Admin check (adjust to your auth system) --
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    // Non-admin users are redirected to index. If you want students to see placements, create a public view.
    header('Location: index.php');
    exit;
}

// -- CSRF token --
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// -- Upload paths --
$uploadDirFS = __DIR__ . '/uploads/logos/'; // filesystem path
$uploadWebPath = 'uploads/logos/';          // web path relative to project root

if (!is_dir($uploadDirFS)) {
    // attempt to create, but don't fail hard if it can't be created
    @mkdir($uploadDirFS, 0755, true);
}

// ensure upload dir is writable; if not, set a flag and allow operations without file upload
$uploadWritable = is_dir($uploadDirFS) && is_writable($uploadDirFS);

// -- messages --
$errors = [];
$success = '';

// -- Handle POST actions: add, edit, delete --
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF early
    if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf'])) {
        $errors[] = 'Invalid CSRF token. Try reloading the page.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'add') {
            $company = trim($_POST['company_name'] ?? '');
            $role    = trim($_POST['role'] ?? '');
            $package = trim($_POST['package'] ?? '');
            $drive_date = trim($_POST['drive_date'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $eligibility = trim($_POST['eligibility'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($company === '' || $role === '') {
                $errors[] = 'Company name and role are required.';
            }

            $logoPathDB = null;
            if (!empty($_FILES['logo']['name']) && $uploadWritable) {
                $f = $_FILES['logo'];
                if ($f['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                    $allowed = ['png','jpg','jpeg','gif','webp'];
                    if (!in_array($ext, $allowed)) {
                        $errors[] = 'Logo must be an image (png,jpg,jpeg,gif,webp).';
                    } else {
                        $newName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                        $target = $uploadDirFS . $newName;
                        if (move_uploaded_file($f['tmp_name'], $target)) {
                            $logoPathDB = $uploadWebPath . $newName;
                        } else {
                            $errors[] = 'Failed to move uploaded file.';
                        }
                    }
                } elseif ($f['error'] !== UPLOAD_ERR_NO_FILE) {
                    $errors[] = 'Logo upload error code: ' . intval($f['error']);
                }
            }

            if (empty($errors)) {
                $stmt = $conn->prepare("INSERT INTO placements (company_name, role, package, drive_date, location, eligibility, description, logo_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if (!$stmt) {
                    $errors[] = 'DB prepare failed: ' . $conn->error;
                } else {
                    $drive_date_db = $drive_date === '' ? null : $drive_date;
                    $stmt->bind_param('ssssssss', $company, $role, $package, $drive_date_db, $location, $eligibility, $description, $logoPathDB);
                    if ($stmt->execute()) {
                        $success = 'Placement drive added.';
                    } else {
                        $errors[] = 'DB execute failed: ' . $stmt->error;
                        // clean up uploaded file on DB failure
                        if ($logoPathDB && file_exists(__DIR__ . '/' . $logoPathDB)) {
                            @unlink(__DIR__ . '/' . $logoPathDB);
                        }
                    }
                    $stmt->close();
                }
            }
        } elseif ($action === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            $company = trim($_POST['company_name'] ?? '');
            $role    = trim($_POST['role'] ?? '');
            $package = trim($_POST['package'] ?? '');
            $drive_date = trim($_POST['drive_date'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $eligibility = trim($_POST['eligibility'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($id <= 0 || $company === '' || $role === '') {
                $errors[] = 'Invalid input for edit.';
            } else {
                // fetch existing logo path
                $oldLogo = null;
                $q = $conn->prepare("SELECT logo_path FROM placements WHERE id = ?");
                if ($q) {
                    $q->bind_param('i', $id);
                    $q->execute();
                    $q->bind_result($oldLogo);
                    $q->fetch();
                    $q->close();
                }

                $logoPathDB = $oldLogo;

                if (!empty($_FILES['logo']['name']) && $uploadWritable) {
                    $f = $_FILES['logo'];
                    if ($f['error'] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                        $allowed = ['png','jpg','jpeg','gif','webp'];
                        if (!in_array($ext, $allowed)) {
                            $errors[] = 'Logo must be an image (png,jpg,jpeg,gif,webp).';
                        } else {
                            $newName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                            $target = $uploadDirFS . $newName;
                            if (move_uploaded_file($f['tmp_name'], $target)) {
                                $logoPathDB = $uploadWebPath . $newName;
                                // delete old logo file if exists
                                if ($oldLogo && file_exists(__DIR__ . '/' . $oldLogo)) {
                                    @unlink(__DIR__ . '/' . $oldLogo);
                                }
                            } else {
                                $errors[] = 'Failed to move uploaded file.';
                            }
                        }
                    } elseif ($f['error'] !== UPLOAD_ERR_NO_FILE) {
                        $errors[] = 'Logo upload error code: ' . intval($f['error']);
                    }
                }

                if (empty($errors)) {
                    $stmt = $conn->prepare("UPDATE placements SET company_name=?, role=?, package=?, drive_date=?, location=?, eligibility=?, description=?, logo_path=? WHERE id=?");
                    if (!$stmt) {
                        $errors[] = 'DB prepare failed: ' . $conn->error;
                    } else {
                        $drive_date_db = $drive_date === '' ? null : $drive_date;
                        $stmt->bind_param('ssssssssi', $company, $role, $package, $drive_date_db, $location, $eligibility, $description, $logoPathDB, $id);
                        if ($stmt->execute()) {
                            $success = 'Placement drive updated.';
                        } else {
                            $errors[] = 'DB execute failed: ' . $stmt->error;
                        }
                        $stmt->close();
                    }
                }
            }
        } elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                $errors[] = 'Invalid id to delete.';
            } else {
                // get logo to delete file
                $logo = null;
                $q = $conn->prepare("SELECT logo_path FROM placements WHERE id = ?");
                if ($q) {
                    $q->bind_param('i', $id);
                    $q->execute();
                    $q->bind_result($logo);
                    $q->fetch();
                    $q->close();
                }
                $stmt = $conn->prepare("DELETE FROM placements WHERE id = ?");
                if (!$stmt) {
                    $errors[] = 'DB prepare failed: ' . $conn->error;
                } else {
                    $stmt->bind_param('i', $id);
                    if ($stmt->execute()) {
                        $success = 'Placement drive removed.';
                        if ($logo && file_exists(__DIR__ . '/' . $logo)) {
                            @unlink(__DIR__ . '/' . $logo);
                        }
                    } else {
                        $errors[] = 'DB execute failed: ' . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        } else {
            $errors[] = 'Unknown action.';
        }
    }
}

// -- Fetch placements for display --
$placements = [];
$res = $conn->query("SELECT id, company_name, role, package, drive_date, location, eligibility, description, logo_path, created_at FROM placements ORDER BY drive_date DESC, created_at DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $placements[] = $row;
    }
    $res->free();
} else {
    // table might not exist
    // Do not die — show friendly message in UI
    $errors[] = "Failed to fetch placements: " . $conn->error;
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Placements Management</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .logo-thumb{width:72px;height:72px;object-fit:contain;border:1px solid #eee;padding:6px;background:#fff}
    textarea.form-control{min-height:80px;}
  </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Placement Drives</h2>
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">+ Add Drive</button>
            <a href="index.php" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
            <?php foreach ($errors as $er): ?>
                <li><?= e($er) ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped mb-0">
            <thead class="table-light">
              <tr>
                <th>Logo</th>
                <th>Company</th>
                <th>Role</th>
                <th>Package</th>
                <th>Date</th>
                <th>Location</th>
                <th>Eligibility</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php if (empty($placements)): ?>
              <tr><td colspan="8" class="text-center py-4">No placement drives yet.</td></tr>
            <?php else: foreach ($placements as $p): ?>
              <tr>
                <td>
                    <?php if (!empty($p['logo_path']) && file_exists(__DIR__ . '/' . $p['logo_path'])): ?>
                        <img src="<?= e($p['logo_path']) ?>" alt="logo" class="logo-thumb">
                    <?php else: ?>
                        <div class="logo-thumb d-flex align-items-center justify-content-center text-muted">No Logo</div>
                    <?php endif; ?>
                </td>
                <td><?= e($p['company_name']) ?><br><small class="text-muted"><?= e($p['created_at']) ?></small></td>
                <td><?= e($p['role']) ?></td>
                <td><?= e($p['package']) ?></td>
                <td><?= e($p['drive_date']) ?></td>
                <td><?= e($p['location']) ?></td>
                <td style="max-width:220px;white-space:pre-wrap;"><?= e($p['eligibility']) ?></td>
                <td>
                    <button class="btn btn-sm btn-outline-primary mb-1" data-bs-toggle="modal" data-bs-target="#editModal"
                        data-id="<?= e($p['id']) ?>"
                        data-company="<?= e($p['company_name']) ?>"
                        data-role="<?= e($p['role']) ?>"
                        data-package="<?= e($p['package']) ?>"
                        data-drive_date="<?= e($p['drive_date']) ?>"
                        data-location="<?= e($p['location']) ?>"
                        data-eligibility="<?= e($p['eligibility']) ?>"
                        data-description="<?= e($p['description']) ?>"
                        data-logo="<?= e($p['logo_path']) ?>"
                        >Edit</button>

                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this drive?');">
                      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                      <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="action" value="add">
      <div class="modal-header">
        <h5 class="modal-title">Add Placement Drive</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Company Name *</label>
            <input name="company_name" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Role *</label>
            <input name="role" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Package</label>
            <input name="package" class="form-control" placeholder="e.g. 6 LPA">
          </div>
          <div class="col-md-4">
            <label class="form-label">Drive Date</label>
            <input type="date" name="drive_date" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label">Location</label>
            <input name="location" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Eligibility / Notes</label>
            <textarea name="eligibility" class="form-control"></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Company Logo (optional)</label>
            <?php if (!$uploadWritable): ?>
                <div class="form-text text-danger">Note: server cannot write to uploads/logos/; logo upload disabled.</div>
            <?php endif; ?>
            <input type="file" name="logo" accept="image/*" class="form-control" <?= $uploadWritable ? '' : 'disabled' ?>>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit-id">
      <div class="modal-header">
        <h5 class="modal-title">Edit Placement Drive</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Company Name *</label>
            <input id="edit-company" name="company_name" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Role *</label>
            <input id="edit-role" name="role" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Package</label>
            <input id="edit-package" name="package" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label">Drive Date</label>
            <input id="edit-drive_date" type="date" name="drive_date" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label">Location</label>
            <input id="edit-location" name="location" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Eligibility / Notes</label>
            <textarea id="edit-eligibility" name="eligibility" class="form-control"></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea id="edit-description" name="description" class="form-control"></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Replace Logo (optional)</label>
            <?php if (!$uploadWritable): ?>
                <div class="form-text text-danger">Server cannot write to uploads/logos/; logo replace disabled.</div>
            <?php endif; ?>
            <input type="file" name="logo" accept="image/*" class="form-control" <?= $uploadWritable ? '' : 'disabled' ?>>
            <div id="current-logo" class="mt-2"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" type="submit">Update</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var editModal = document.getElementById('editModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button) return;
            document.getElementById('edit-id').value = button.getAttribute('data-id') || '';
            document.getElementById('edit-company').value = button.getAttribute('data-company') || '';
            document.getElementById('edit-role').value = button.getAttribute('data-role') || '';
            document.getElementById('edit-package').value = button.getAttribute('data-package') || '';
            document.getElementById('edit-drive_date').value = button.getAttribute('data-drive_date') || '';
            document.getElementById('edit-location').value = button.getAttribute('data-location') || '';
            document.getElementById('edit-eligibility').value = button.getAttribute('data-eligibility') || '';
            document.getElementById('edit-description').value = button.getAttribute('data-description') || '';
            var logo = button.getAttribute('data-logo') || '';
            var curLogoDiv = document.getElementById('current-logo');
            if (logo) {
                curLogoDiv.innerHTML = '<img src="'+logo+'" class="logo-thumb" alt="current logo">';
            } else {
                curLogoDiv.innerHTML = '<div class="logo-thumb d-flex align-items-center justify-content-center text-muted">No Logo</div>';
            }
        });
    }
});
</script>
</body>
</html>
