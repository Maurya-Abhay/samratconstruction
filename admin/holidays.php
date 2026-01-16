<?php
// holidays.php

require_once 'database.php';
include 'topheader.php';
include 'sidenavbar.php';

// Resolve Admin ID
$admin_email = $_SESSION['email'] ?? '';
$admin_id = 0;

if ($admin_email) {
    $stmt = $conn->prepare("SELECT id FROM admin WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $admin_email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $admin_id = (int)$row['id'];
    }
    $stmt->close();
}

$success_message = '';
$error_message = '';

// Handle Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Add Holiday
    if (isset($_POST['add_holiday'])) {
        $name = trim($_POST['holiday_name'] ?? '');
        $date = trim($_POST['holiday_date'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $is_active = $_POST['is_active'] ?? 'Yes';
        
        if ($name && $date) {
            $stmt = $conn->prepare("INSERT INTO holidays (holiday_name, holiday_date, description, is_active, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $name, $date, $description, $is_active, $admin_id);
            
            if ($stmt->execute()) {
                // Prevent resubmission
                header("Location: holidays.php");
                exit();
            } else {
                $error_message = "Error adding holiday: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = "Holiday Name and Date are required.";
        }
    }
    
    // Update Holiday
    if (isset($_POST['update_holiday'])) {
        $id = (int)($_POST['holiday_id'] ?? 0);
        $name = trim($_POST['holiday_name'] ?? '');
        $date = trim($_POST['holiday_date'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $is_active = $_POST['is_active'] ?? 'Yes';
        
        $stmt = $conn->prepare("UPDATE holidays SET holiday_name=?, holiday_date=?, description=?, is_active=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $date, $description, $is_active, $id);
        
        if ($stmt->execute()) {
            header("Location: holidays.php");
            exit();
        } else {
            $error_message = "Error updating holiday: " . $stmt->error;
        }
        $stmt->close();
    }
    
    // Delete Holiday
    if (isset($_POST['delete_holiday'])) {
        $id = (int)($_POST['holiday_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM holidays WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header("Location: holidays.php");
            exit();
        } else {
            $error_message = "Error deleting holiday.";
        }
        $stmt->close();
    }
    
    // Toggle Status
    if (isset($_POST['toggle_status'])) {
        $id = (int)($_POST['holiday_id'] ?? 0);
        $new_status = ($_POST['new_status'] ?? 'Yes') === 'Yes' ? 'Yes' : 'No';
        $stmt = $conn->prepare("UPDATE holidays SET is_active=? WHERE id=?");
        $stmt->bind_param("si", $new_status, $id);
        if ($stmt->execute()) {
            header("Location: holidays.php");
            exit();
        }
        $stmt->close();
    }
}

// Fetch Data
$holidays = $conn->query("SELECT h.*, a.name as created_by_name FROM holidays h LEFT JOIN admin a ON h.created_by = a.id ORDER BY h.holiday_date DESC");

// Statistics
$total_holidays = (int)($conn->query("SELECT COUNT(*) as count FROM holidays")->fetch_assoc()['count'] ?? 0);
$active_holidays = (int)($conn->query("SELECT COUNT(*) as count FROM holidays WHERE is_active='Yes'")->fetch_assoc()['count'] ?? 0);
$upcoming_holidays = (int)($conn->query("SELECT COUNT(*) as count FROM holidays WHERE holiday_date >= CURDATE() AND is_active='Yes'")->fetch_assoc()['count'] ?? 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holiday Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #4e73df;
            --light-bg: #f8f9fc;
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Modern Card */
        .card-modern {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            background: #fff;
            margin-bottom: 20px;
        }

        /* Summary Cards */
        .summary-box {
            color: white;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .summary-box:hover { transform: translateY(-3px); }
        .bg-gradient-primary { background: linear-gradient(135deg, #4e73df, #224abe); }
        .bg-gradient-success { background: linear-gradient(135deg, #1cc88a, #13855c); }
        .bg-gradient-info { background: linear-gradient(135deg, #36b9cc, #258391); }

        /* Table */
        .table-responsive {
            border-radius: 12px;
            overflow: auto !important;
            max-height: 600px;
            min-height: 200px;
            width: 100%;
        }
        .table {
            min-width: 900px;
        }
        .table thead th {
            background-color: #f8f9fc;
            color: #858796;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            border-bottom: 2px solid #e3e6f0;
        }
        .date-badge {
            display: inline-block;
            text-align: center;
            background: #f8f9fc;
            border: 1px solid #e3e6f0;
            border-radius: 8px;
            padding: 5px 10px;
            min-width: 80px;
        }
        .date-day { font-size: 1.2rem; font-weight: bold; line-height: 1; display: block; }
        .date-month { font-size: 0.75rem; text-transform: uppercase; color: #858796; }

        /* Forms */
        .form-floating > .form-control { height: 3.5rem; }
        .form-floating > label { padding-top: 0.6rem; }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-dark fw-bold m-0"><i class="bi bi-calendar-event text-primary me-2"></i>Holiday Management</h3>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addHolidayModal">
            <i class="bi bi-plus-lg me-1"></i> Add Holiday
        </button>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($success_message) ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error_message) ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="summary-box bg-gradient-primary">
                <div>
                    <h6 class="text-uppercase mb-1" style="opacity:0.9">Total Holidays</h6>
                    <h2 class="mb-0 fw-bold"><?= $total_holidays ?></h2>
                </div>
                <div class="fs-1" style="opacity:0.3"><i class="bi bi-calendar4-week"></i></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="summary-box bg-gradient-success">
                <div>
                    <h6 class="text-uppercase mb-1" style="opacity:0.9">Active Holidays</h6>
                    <h2 class="mb-0 fw-bold"><?= $active_holidays ?></h2>
                </div>
                <div class="fs-1" style="opacity:0.3"><i class="bi bi-check-circle"></i></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="summary-box bg-gradient-info">
                <div>
                    <h6 class="text-uppercase mb-1" style="opacity:0.9">Upcoming</h6>
                    <h2 class="mb-0 fw-bold"><?= $upcoming_holidays ?></h2>
                </div>
                <div class="fs-1" style="opacity:0.3"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
    </div>

    <div class="card-modern">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 600px;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="sticky-top">
                        <tr>
                            <th width="5%" class="ps-4">#</th>
                            <th width="15%">Date</th>
                            <th width="25%">Holiday Name</th>
                            <th width="30%">Description</th>
                            <th width="10%">Status</th>
                            <th width="15%" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($holidays && $holidays->num_rows > 0): ?>
                            <?php $i=1; while($row = $holidays->fetch_assoc()): 
                                $hDate = new DateTime($row['holiday_date']);
                                $isUpcoming = $row['holiday_date'] >= date('Y-m-d');
                            ?>
                            <tr class="<?= $isUpcoming ? 'bg-light' : '' ?>">
                                <td class="ps-4 text-muted"><?= $i++ ?></td>
                                <td>
                                    <div class="date-badge">
                                        <span class="date-day"><?= $hDate->format('d') ?></span>
                                        <span class="date-month"><?= $hDate->format('M Y') ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['holiday_name']) ?></div>
                                    <small class="text-muted"><?= $hDate->format('l') ?></small>
                                    <?php if ($row['holiday_date'] == date('Y-m-d')): ?>
                                        <span class="badge bg-warning text-dark ms-1">Today</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="text-muted small text-truncate" style="max-width: 300px;">
                                        <?= htmlspecialchars($row['description'] ?? '-') ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($row['is_active'] == 'Yes'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success px-2">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary px-2">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="editHoliday(<?= $row['id'] ?>, '<?= addslashes($row['holiday_name']) ?>', '<?= $row['holiday_date'] ?>', '<?= addslashes($row['description']) ?>', '<?= $row['is_active'] ?>')"
                                                data-bs-toggle="modal" data-bs-target="#editHolidayModal" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="holiday_id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="new_status" value="<?= $row['is_active'] == 'Yes' ? 'No' : 'Yes' ?>">
                                            <button type="submit" name="toggle_status" class="btn btn-sm btn-outline-warning" title="Toggle Status">
                                                <i class="bi bi-<?= $row['is_active'] == 'Yes' ? 'pause-fill' : 'play-fill' ?>"></i>
                                            </button>
                                        </form>

                                        <form method="POST" style="display:inline;" onsubmit="event.preventDefault(); showDeleteHolidaySwal(this);">
                                            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                                            <script>
                                            function showDeleteHolidaySwal(form) {
                                                Swal.fire({
                                                    title: 'Delete Holiday?',
                                                    text: 'Delete this holiday?',
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#d33',
                                                    cancelButtonColor: '#aaa',
                                                    confirmButtonText: 'Yes, delete',
                                                    cancelButtonText: 'Cancel',
                                                    reverseButtons: true
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        form.submit();
                                                    }
                                                });
                                            }
                                            </script>
                                            <input type="hidden" name="holiday_id" value="<?= $row['id'] ?>">
                                            <button type="submit" name="delete_holiday" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted">No holidays found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="addHolidayModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add New Holiday</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="add_holiday" value="1">
                    
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="holiday_name" placeholder="Name" required>
                        <label>Holiday Name *</label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="date" class="form-control" name="holiday_date" required>
                        <label>Date *</label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <select class="form-select" name="is_active">
                            <option value="Yes">Active</option>
                            <option value="No">Inactive</option>
                        </select>
                        <label>Status</label>
                    </div>
                    
                    <div class="form-floating">
                        <textarea class="form-control" name="description" style="height: 100px" placeholder="Desc"></textarea>
                        <label>Description (Optional)</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary px-4">Save Holiday</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editHolidayModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Holiday</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="update_holiday" value="1">
                    <input type="hidden" name="holiday_id" id="edit_holiday_id">
                    
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="holiday_name" id="edit_holiday_name" placeholder="Name" required>
                        <label>Holiday Name *</label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="date" class="form-control" name="holiday_date" id="edit_holiday_date" required>
                        <label>Date *</label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <select class="form-select" name="is_active" id="edit_is_active">
                            <option value="Yes">Active</option>
                            <option value="No">Inactive</option>
                        </select>
                        <label>Status</label>
                    </div>
                    
                    <div class="form-floating">
                        <textarea class="form-control" name="description" id="edit_description" style="height: 100px" placeholder="Desc"></textarea>
                        <label>Description (Optional)</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-warning px-4">Update Holiday</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'downfooter.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editHoliday(id, name, date, description, isActive) {
    document.getElementById('edit_holiday_id').value = id;
    document.getElementById('edit_holiday_name').value = name;
    document.getElementById('edit_holiday_date').value = date;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_is_active').value = isActive;
}
</script>

</body>
</html>