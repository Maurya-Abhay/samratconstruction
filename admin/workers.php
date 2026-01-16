<?php
include "database.php";

date_default_timezone_set('Asia/Kolkata');

// -----------------------------------------------------------------------------
// ## Attendance Logic
// -----------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'mark_attendance') {
    $worker_id = intval($_POST['worker_id'] ?? 0);
    $date = $_POST['date'] ?? date('Y-m-d');
    $status = $_POST['status'] ?? 'Present';
    $notes = trim($_POST['notes'] ?? '');

    if ($worker_id && $date && in_array($status, ['Present', 'Absent', 'Leave'])) {
        // 1. Check if attendance already exists for the worker on this date
        $stmt = $conn->prepare("SELECT id FROM worker_attendance WHERE worker_id=? AND date=?");
        $stmt->bind_param("is", $worker_id, $date);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Update existing attendance
            $stmt2 = $conn->prepare("UPDATE worker_attendance SET status=?, notes=? WHERE worker_id=? AND date=?");
            $stmt2->bind_param("ssis", $status, $notes, $worker_id, $date);
            $stmt2->execute();
            $message = "Attendance updated!";
        } else {
            // Insert new attendance record
            $stmt2 = $conn->prepare("INSERT INTO worker_attendance (worker_id, date, status, notes) VALUES (?, ?, ?, ?)");
            $stmt2->bind_param("isss", $worker_id, $date, $status, $notes);
            $stmt2->execute();
            $message = $stmt2->affected_rows > 0 ? "Attendance marked!" : "Failed to mark attendance.";
        }
        
        // Redirect to clear POST data and prevent resubmission
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        exit();
    } else {
        $message = "Invalid attendance data.";
    }
}

// -----------------------------------------------------------------------------
// ## AJAX Realtime Validation (Email/Phone Uniqueness)
// -----------------------------------------------------------------------------

if (isset($_GET['check_field']) && isset($_GET['value'])) {
    $field = $_GET['check_field'];
    $value = trim($_GET['value']);
    $id = intval($_GET['id'] ?? 0);
    
    // Only allow 'email' or 'phone' to be checked
    if (!in_array($field, ['email', 'phone'])) {
        echo json_encode(['valid' => false]);
        exit;
    }
    
    // Check for existing worker with the same field value, excluding the current worker (if $id > 0)
    $stmt = $conn->prepare("SELECT id FROM workers WHERE $field=? AND id!=?");
    $stmt->bind_param("si", $value, $id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        echo json_encode(['valid' => false, 'message' => ucfirst($field) . " already exists"]);
    } else {
        echo json_encode(['valid' => true, 'message' => ucfirst($field) . " available"]);
    }
    exit;
}

// -----------------------------------------------------------------------------
// ## Add Worker Logic
// -----------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $aadhaar = trim($_POST['aadhaar']);
    $joining_date = trim($_POST['joining_date']);
    $salary = trim($_POST['salary']);
    $status = trim($_POST['status']);
    $password = trim($_POST['password']);
    $photo = $_FILES['photo'];
    $message = '';
    $uploadOk = 1;

        if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($photo['name'])) {
            $message = "All required fields (Name, Email, Phone, Password, Photo) are required!";
        } else {
            // Image validation
            $file_extension = strtolower(pathinfo($photo["name"], PATHINFO_EXTENSION));
            $uploadOk = 1;
            if (getimagesize($photo["tmp_name"]) === false) {
                $message = "File is not an image.";
                $uploadOk = 0;
            }
            if ($photo["size"] > 2000000) { $message = "Sorry, your file is too large (max 2MB)."; $uploadOk = 0; }
            if (!in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) { $message = "Sorry, only JPG, JPEG, PNG & GIF files are allowed."; $uploadOk = 0; }

            if ($uploadOk == 0) {
                $message = "Sorry, your file was not uploaded. " . $message;
            } else {
                require_once __DIR__ . '/lib_common.php';
                $cloud_url = upload_to_cloudinary($photo["tmp_name"], $photo["type"], $photo["name"]);
                if ($cloud_url) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO workers (name, email, phone, address, aadhaar, joining_date, salary, status, password, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $j_date = !empty($joining_date) ? $joining_date : null;
                    $sal = !empty($salary) ? $salary : null;
                    $stmt->bind_param("ssssssssss", $name, $email, $phone, $address, $aadhaar, $j_date, $sal, $status, $hashed_password, $cloud_url);
                    if ($stmt->execute()) {
                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit();
                    } else {
                        $message = "Error: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $message = "Sorry, there was an error uploading to Cloudinary.";
                }
            }
    }
}

// -----------------------------------------------------------------------------
// ## Edit Worker Logic
// -----------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $aadhaar = trim($_POST['aadhaar']);
    $joining_date = trim($_POST['joining_date']);
    $salary = trim($_POST['salary']);
    $status = trim($_POST['status']);
    $password_raw = trim($_POST['password']);
    $photo = $_FILES['photo'];
    $error = '';

    // Basic validation
    if (!$name || !$email || !$phone) {
        $error = 'Name, Email, and Phone are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
        $error = 'Invalid phone number format.';
    } else {
        // Check for email/phone uniqueness (excluding current worker)
        $stmt_check = $conn->prepare("SELECT id FROM workers WHERE (email = ? OR phone = ?) AND id != ?");
        $stmt_check->bind_param("ssi", $email, $phone, $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            $error = 'Email or phone number already exists for another worker.';
        }
        $stmt_check->close();
    }

    if (empty($error)) {
        $target_file = null;
        
        // Handle photo upload if a new file is provided
        if (!empty($photo['name'])) {
            $file_extension = strtolower(pathinfo($photo["name"], PATHINFO_EXTENSION));
            // Image validation
            if (getimagesize($photo["tmp_name"]) === false) {
                $error = "File is not an image.";
            } elseif ($photo["size"] > 2000000) {
                $error = "File too large.";
            } elseif (!in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                $error = "Invalid file type.";
            } else {
                require_once __DIR__ . '/lib_common.php';
                $cloud_url = upload_to_cloudinary($photo["tmp_name"], $photo["type"], $photo["name"]);
                if ($cloud_url) {
                    $target_file = $cloud_url;
                } else {
                    $error = "Error uploading to Cloudinary.";
                }
            }
        }

        if (empty($error)) {
            // Build the SQL UPDATE statement dynamically
            $sql = "UPDATE workers SET name=?, email=?, phone=?, address=?, aadhaar=?, joining_date=?, salary=?, status=?";
            $params = [$name, $email, $phone, $address, $aadhaar, $joining_date ?: null, $salary ?: null, $status];
            $types = "ssssssss";

            if (!empty($password_raw)) {
                $hashed_password = password_hash($password_raw, PASSWORD_DEFAULT);
                $sql .= ", password=?";
                $params[] = $hashed_password;
                $types .= "s";
            }
            if ($target_file) {
                $sql .= ", photo=?";
                $params[] = $target_file;
                $types .= "s";
            }
            
            $sql .= " WHERE id=?";
            $params[] = $id;
            $types .= "i";

            $stmt = $conn->prepare($sql);
            
            // Using the splat operator (...) for dynamic parameter binding
            $stmt->bind_param($types, ...$params); 
            
            if ($stmt->execute()) {
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $error = "Error updating worker: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    $message = $error ?? ''; 
}

// -----------------------------------------------------------------------------
// ## Delete Worker Logic
// -----------------------------------------------------------------------------

if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    
    // 1. Get photo path and delete file
    $stmt = $conn->prepare("SELECT photo FROM workers WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $photo_path = $row['photo'];
        if (file_exists($photo_path)) {
            unlink($photo_path); // Delete the physical file
        }
    }
    $stmt->close();
    
    // 2. Delete worker record from database
    $stmt = $conn->prepare("DELETE FROM workers WHERE id=?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $message = "Error deleting record: " . $conn->error;
    }
    $stmt->close();
}


// -----------------------------------------------------------------------------
// ## Fetch All Workers & Summary Data
// -----------------------------------------------------------------------------
$sql = "SELECT * FROM workers ORDER BY id DESC";
$result = $conn->query($sql);
$workers = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $workers[] = $row;
    }
}

$today = date('Y-m-d');
// Summary Queries
$total_workers_count = count($workers);

$present_query = $conn->query("SELECT COUNT(*) as count FROM worker_attendance WHERE date='$today' AND status='Present'");
$present_count = $present_query->fetch_assoc()['count'] ?? 0;

// Absent/Not Marked logic: (Total Active Workers) - (Marked as Present/Leave/Absent today)
// Or simply use logic for "Not Marked" vs "Absent". 
// Here we follow your original logic: Absent + Null status
$not_marked_query = $conn->query("SELECT COUNT(*) as count FROM workers w LEFT JOIN worker_attendance a ON w.id=a.worker_id AND a.date='$today' WHERE a.status IS NULL OR a.status!='Present'");
$not_marked_count = $not_marked_query->fetch_assoc()['count'] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-bg: #f8f9fc;
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Summary Cards */
        .summary-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.2s;
            overflow: hidden;
        }
        .summary-card:hover { transform: translateY(-3px); }
        .summary-icon {
            font-size: 2rem;
            opacity: 0.8;
        }
        
        /* Table Styles */
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            padding: 20px;
            margin-top: 20px;
        }
        .worker-thumb {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #eaecf4;
        }
        .table thead th {
            background-color: #f8f9fc;
            border-bottom: 2px solid #e3e6f0;
            color: #858796;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        
        /* Action Buttons */
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 6px;
        }

        /* Floating Labels Adjustment */
        .form-floating > .form-control { height: 3.5rem; }
        .form-floating > label { padding-top: 0.6rem; }
    </style>
</head>
<body>

<?php include "topheader.php"; ?>
<?php include "sidenavbar.php"; ?>

<div class="container-fluid px-4 py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-dark fw-bold m-0"><i class="bi bi-people-fill text-primary me-2"></i>Worker Management</h3>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addWorkerModal">
            <i class="bi bi-plus-lg me-1"></i> Add New Worker
        </button>
    </div>

    <?php if (isset($message) && !empty($message)): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4 col-sm-6">
            <div class="summary-card bg-white p-3 d-flex align-items-center justify-content-between h-100 border-start border-4 border-primary">
                <div>
                    <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Workers</div>
                    <div class="h3 mb-0 fw-bold text-gray-800"><?= $total_workers_count ?></div>
                </div>
                <div class="summary-icon text-gray-300"><i class="bi bi-people text-primary"></i></div>
            </div>
        </div>
        
        <div class="col-md-4 col-sm-6">
            <div class="summary-card bg-white p-3 d-flex align-items-center justify-content-between h-100 border-start border-4 border-success">
                <div>
                    <div class="text-xs fw-bold text-success text-uppercase mb-1">Present Today</div>
                    <div class="h3 mb-0 fw-bold text-gray-800"><?= $present_count ?></div>
                </div>
                <div class="summary-icon text-gray-300"><i class="bi bi-calendar-check text-success"></i></div>
            </div>
        </div>
        
        <div class="col-md-4 col-sm-12">
            <div class="summary-card bg-white p-3 d-flex align-items-center justify-content-between h-100 border-start border-4 border-danger">
                <div>
                    <div class="text-xs fw-bold text-danger text-uppercase mb-1">Absent / Not Marked</div>
                    <div class="h3 mb-0 fw-bold text-gray-800"><?= $not_marked_count ?></div>
                </div>
                <div class="summary-icon text-gray-300"><i class="bi bi-exclamation-circle text-danger"></i></div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <div class="table-responsive" style="max-height: 600px;">
            <table class="table table-hover align-middle mb-0">
                <thead class="sticky-top">
                    <tr>
                        <th width="5%">ID</th>
                        <th width="20%">Worker Info</th>
                        <th width="15%">Contact</th>
                        <th width="10%">Status</th>
                        <th width="10%">Salary</th>
                        <th width="40%" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="workersTableBody">
                    <?php if (empty($workers)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No workers found. Add one to get started.</td></tr>
                    <?php else: ?>
                        <?php foreach ($workers as $worker): ?>
                        <tr data-id="<?= $worker['id'] ?>">
                            <td class="fw-bold text-secondary">#<?= $worker['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php 
                                        $photo_url = !empty($worker['photo']) ? htmlspecialchars($worker['photo']) : 'assets/default-avatar.png'; 
                                    ?>
                                    <img src="<?= $photo_url ?>" class="worker-thumb me-3" alt="Avatar">
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($worker['name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($worker['address'] ?? 'No Address') ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small"><i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($worker['email']) ?></div>
                                <div class="small"><i class="bi bi-phone me-1"></i> <?= htmlspecialchars($worker['phone']) ?></div>
                            </td>
                            <td>
                                <?php if ($worker['status'] == 'Active'): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success px-2 py-1">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary px-2 py-1">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="fw-bold text-dark">₹<?= htmlspecialchars($worker['salary'] ?? '0') ?></span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group" role="group">
                                    <a href="worker_detail.php?id=<?= $worker['id'] ?>" class="btn btn-light btn-action text-primary" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-light btn-action text-success" 
                                            onclick="openAttendanceModal(<?= $worker['id'] ?>, '<?= htmlspecialchars(addslashes($worker['name'])) ?>')" 
                                            data-bs-toggle="modal" data-bs-target="#attendanceModal" title="Mark Attendance">
                                        <i class="bi bi-calendar-check"></i>
                                    </button>
                                    <a href="worker_make_payment.php?worker_id=<?= $worker['id'] ?>" class="btn btn-light btn-action text-info" title="Make Payment">
                                        <i class="bi bi-wallet2"></i>
                                    </a>
                                    <button type="button" class="btn btn-light btn-action text-warning" 
                                            onclick="populateEditForm(<?= $worker['id'] ?>)" 
                                            data-bs-toggle="modal" data-bs-target="#editWorkerModal" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                                <a href="?delete_id=<?= $worker['id'] ?>" class="btn btn-light btn-action text-danger" 
                                                    onclick="event.preventDefault(); showDeleteWorkerSwal(this);" title="Delete">
                                        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                                        <script>
                                        function showDeleteWorkerSwal(el) {
                                            Swal.fire({
                                                title: 'Delete Worker?',
                                                text: 'Are you sure you want to delete this worker?',
                                                icon: 'warning',
                                                showCancelButton: true,
                                                confirmButtonColor: '#d33',
                                                cancelButtonColor: '#aaa',
                                                confirmButtonText: 'Yes, delete',
                                                cancelButtonText: 'Cancel',
                                                reverseButtons: true
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    window.location.href = el.getAttribute('href');
                                                }
                                            });
                                        }
                                        </script>
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php
// Get next auto-increment value for workers table
$next_worker_id = 1;
$auto_inc_res = $conn->query("SHOW TABLE STATUS LIKE 'workers'");
if ($auto_inc_res && $auto_inc_res->num_rows > 0) {
    $auto_inc_row = $auto_inc_res->fetch_assoc();
    $next_worker_id = $auto_inc_row['Auto_increment'] ?? 1;
}
?>
<div class="modal fade" id="addWorkerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-person-plus-fill me-2"></i>Add New Worker</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <span class="badge bg-info text-dark">Next Worker ID: <?= $next_worker_id ?></span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="name" placeholder="Name" required>
                                <label>Full Name *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="email" class="form-control email-field" name="email" placeholder="Email" required>
                                <label>Email Address *</label>
                            </div>
                            <div id="email-msg" class="small mt-1 ps-1"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="tel" class="form-control phone-field" name="phone" placeholder="Phone" required>
                                <label>Phone Number *</label>
                            </div>
                            <div id="phone-msg" class="small mt-1 ps-1"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="aadhaar" placeholder="Aadhaar">
                                <label>Aadhaar Number</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="address" placeholder="Address">
                                <label>Full Address</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="date" class="form-control" name="joining_date">
                                <label>Joining Date</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="number" class="form-control" name="salary" placeholder="Salary">
                                <label>Salary (₹)</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <select class="form-select" name="status">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                                <label>Status</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="password" class="form-control" name="password" placeholder="Password" required>
                                <label>Login Password *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Upload Photo *</label>
                            <input class="form-control form-control-lg" type="file" name="photo" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Worker</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editWorkerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Worker</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editWorkerId">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="editWorkerName" name="name" placeholder="Name" required>
                                <label>Full Name *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="email" class="form-control email-field" id="editWorkerEmail" name="email" placeholder="Email" required>
                                <label>Email Address *</label>
                            </div>
                            <div id="edit-email-msg" class="small mt-1 ps-1"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="tel" class="form-control phone-field" id="editWorkerPhone" name="phone" placeholder="Phone" required>
                                <label>Phone Number *</label>
                            </div>
                            <div id="edit-phone-msg" class="small mt-1 ps-1"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="editWorkerAadhaar" name="aadhaar" placeholder="Aadhaar">
                                <label>Aadhaar Number</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="editWorkerAddress" name="address" placeholder="Address">
                                <label>Full Address</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="date" class="form-control" id="editWorkerJoiningDate" name="joining_date">
                                <label>Joining Date</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="number" class="form-control" id="editWorkerSalary" name="salary" placeholder="Salary">
                                <label>Salary (₹)</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <select class="form-select" id="editWorkerStatus" name="status">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                                <label>Status</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="password" class="form-control" name="password" placeholder="New Password">
                                <label>New Password (Leave blank to keep current)</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Update Photo (Optional)</label>
                            <input class="form-control form-control-lg" type="file" name="photo">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Update Worker</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="attendanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-calendar-check-fill me-2"></i>Mark Attendance</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="mark_attendance">
                    <input type="hidden" name="worker_id" id="attendanceWorkerId">
                    
                    <h5 class="text-center mb-4 text-dark">Worker: <span class="fw-bold text-success" id="attendanceWorkerName"></span></h5>
                    
                    <div class="form-floating mb-3">
                        <input type="date" class="form-control" name="date" id="attendanceDate" value="<?= $today ?>" required>
                        <label>Attendance Date</label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <select class="form-select" name="status" id="attendanceStatus">
                            <option value="Present">Present</option>
                            <option value="Absent">Absent</option>
                            <option value="Leave">Leave</option>
                        </select>
                        <label>Status</label>
                    </div>
                    
                    <div class="form-floating">
                        <textarea class="form-control" name="notes" id="attendanceNotes" style="height: 100px" placeholder="Notes"></textarea>
                        <label>Notes (Optional)</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Save Attendance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include "downfooter.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    // --- Data Management ---
    // Inject PHP array into JS safely
    const workerData = <?php echo json_encode($workers); ?>;

    function openAttendanceModal(workerId, workerName) {
        document.getElementById('attendanceWorkerId').value = workerId;
        document.getElementById('attendanceWorkerName').innerText = workerName;
    }

    function populateEditForm(id) {
        // Find worker object from the data array
        const worker = workerData.find(w => w.id == id);
        
        if (worker) {
            document.getElementById('editWorkerId').value = worker.id;
            document.getElementById('editWorkerName').value = worker.name;
            document.getElementById('editWorkerEmail').value = worker.email;
            document.getElementById('editWorkerPhone').value = worker.phone;
            document.getElementById('editWorkerAddress').value = worker.address || '';
            document.getElementById('editWorkerAadhaar').value = worker.aadhaar || '';
            document.getElementById('editWorkerJoiningDate').value = worker.joining_date || '';
            document.getElementById('editWorkerSalary').value = worker.salary || '';
            document.getElementById('editWorkerStatus').value = worker.status;
        }
    }

    // --- Validation Logic ---
    function checkField($input, field, msgId, id=0) {
        let val = $input.val().trim();
        let msgBox = $(msgId);
        
        if (val.length < 3) {
            msgBox.text('');
            return;
        }
        
        msgBox.html('<span class="text-muted"><i class="bi bi-hourglass-split"></i> Checking...</span>');

        $.get('workers.php', {check_field: field, value: val, id: id}, function(res) { 
            try {
                let data = JSON.parse(res);
                if (data.valid) {
                    msgBox.html('<span class="text-success"><i class="bi bi-check-circle-fill"></i> ' + data.message + '</span>');
                    $input.removeClass('is-invalid').addClass('is-valid');
                } else {
                    msgBox.html('<span class="text-danger"><i class="bi bi-x-circle-fill"></i> ' + data.message + '</span>');
                    $input.removeClass('is-valid').addClass('is-invalid');
                }
            } catch(e) {
                // ignore errors
            }
        });
    }

    // Attach Validation Events
    $(document).on('blur', '.email-field', function() {
        let id = $(this).attr('id') === 'editWorkerEmail' ? $('#editWorkerId').val() : 0;
        let msgId = $(this).attr('id') === 'editWorkerEmail' ? '#edit-email-msg' : '#email-msg';
        checkField($(this), 'email', msgId, id);
    });

    $(document).on('blur', '.phone-field', function() {
        let id = $(this).attr('id') === 'editWorkerPhone' ? $('#editWorkerId').val() : 0;
        let msgId = $(this).attr('id') === 'editWorkerPhone' ? '#edit-phone-msg' : '#phone-msg';
        checkField($(this), 'phone', msgId, id);
    });

    // Auto-refresh every 1 minute for admin panel
    setInterval(function() {
        location.reload();
    }, 60000);
</script>

</body>
</html>