<?php
ob_start();
// leave_management.php (Modern Light Premium redesign)
session_start(); // Ensure session is started if not in header.php

// Restrict access: Only logged-in users (attendance_id in session) can access. Redirect to login.php if not logged in.
if (!isset($_SESSION['attendance_id'])) { header('Location: login.php'); exit; }

$page_title = "Leave Management";
$show_back_btn = true;


require_once '../admin/database.php';
// ...existing code...
// Move header.php include after form processing
// Helper function definition for safe output
if (!function_exists('s')) {
    function s($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

include 'header.php';

// Submit Leave
$success_message = null;
$error_message = null;

// NOTE: Using prepared statements is highly recommended instead of direct injection for security.
// For modernization, I will keep the existing syntax but add a warning.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_leave']) && $conn instanceof mysqli) {
    // SECURITY WARNING: Use prepared statements instead of mysqli_real_escape_string for robust security.
    $leave_type = mysqli_real_escape_string($conn, $_POST['leave_type']);
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date   = mysqli_real_escape_string($conn, $_POST['end_date']);
    $reason     = mysqli_real_escape_string($conn, $_POST['reason']);

    try {
        $start = new DateTime($start_date);
        $end   = new DateTime($end_date);
        // Calculate days including both start and end date
        $days  = $start->diff($end)->days + 1;

        $insert_query = "INSERT INTO worker_leaves 
            (worker_id, leave_type, start_date, end_date, days, reason, status, applied_date) 
            VALUES 
            ($worker_id, '$leave_type', '$start_date', '$end_date', $days, '$reason', 'Pending', NOW())";

        if ($conn->query($insert_query)) {
            // Redirect to avoid duplicate submit on refresh
            header("Location: leave.php?success=1");
            exit();
        } else {
            // Redirect with error
            header("Location: leave.php?error=1");
            exit();
        }
    } catch (Exception $e) {
        header("Location: leave.php?error=1");
        exit();
    }
}

// Ensure $conn is available and is a mysqli object before proceeding
if (!$conn instanceof mysqli) {
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'error',title:'Database Error',text:'Database connection failed.',showConfirmButton:false,timer:2000});</script>";
    include 'footer.php';
    exit();
}

// Worker Info (using prepared statement for safety)
$worker = null;
$stmt_worker = $conn->prepare("SELECT * FROM workers WHERE id = ?");
$stmt_worker->bind_param('i', $worker_id);
$stmt_worker->execute();
$worker_result = $stmt_worker->get_result();
$worker = $worker_result->fetch_assoc();
$stmt_worker->close();

if (!$worker) {
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'error',title:'Worker Not Found',text:'Worker not found!',showConfirmButton:false,timer:2000});</script>";
    include 'footer.php';
    exit();
}

// Create Leave Table (Ensure table exists - keeping existing DDL logic)
$conn->query("CREATE TABLE IF NOT EXISTS worker_leaves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    leave_type VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    days INT NOT NULL,
    reason TEXT,
    status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    applied_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    approved_date DATETIME NULL,
    approved_by INT NULL,
    admin_remarks TEXT NULL
)");

// Fetch Leave Data (using prepared statement for safety)
$leaves = null;
$stmt_leaves = $conn->prepare("SELECT * FROM worker_leaves WHERE worker_id = ? ORDER BY applied_date DESC");
$stmt_leaves->bind_param('i', $worker_id);
$stmt_leaves->execute();
$leaves_result = $stmt_leaves->get_result();

// Leave Summary Calculation
$leave_summary = [
    'total_applied' => 0,
    'approved' => 0,
    'pending' => 0,
    'rejected' => 0,
    'days_taken' => 0
];
$leave_data = []; // Store data for display

while ($leave = $leaves_result->fetch_assoc()) {
    $leave_data[] = $leave;
    $leave_summary['total_applied']++;
    if ($leave['status'] == 'Approved') {
        $leave_summary['approved']++;
        $leave_summary['days_taken'] += $leave['days'];
    } elseif ($leave['status'] == 'Pending') {
        $leave_summary['pending']++;
    } else {
        $leave_summary['rejected']++;
    }
}
$stmt_leaves->close();

$leave_quota = 28; // Example Quota
$remaining_leaves = max(0, $leave_quota - $leave_summary['days_taken']);
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --primary: #4f46e5;
        --accent: #d63384;
        --bg-body: #f1f5f9;
        --card-bg: #ffffff;
        --text-dark: #1e293b;
    }

    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background-color: var(--bg-body);
        color: var(--text-dark);
    }

    .leave-container {
        max-width: 1000px;
        margin: 30px auto;
        padding: 0 15px;
    }

    /* Hero Header */
    .leave-hero-header {
        background: linear-gradient(135deg, var(--primary), var(--accent));
        color: #fff;
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 30px;
        box-shadow: 0 10px 25px rgba(79, 70, 229, 0.2);
    }
    .leave-hero-header h4 { font-weight: 700; margin: 0; font-size: 1.8rem; }
    .leave-hero-header small { opacity: 0.9; }

    /* Summary Cards */
    .summary-card-modern {
        background: var(--card-bg);
        border: 1px solid rgba(0,0,0,0.05);
        border-radius: 14px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        transition: transform .2s ease;
    }
    .summary-card-modern:hover { transform: translateY(-3px); }
    .summary-card-modern h6 { color: #64748b; font-size: .85rem; font-weight: 600; margin-bottom: 5px; }
    .summary-card-modern .value { font-size: 1.8rem; font-weight: 700; }

    /* Leave Form Card */
    .apply-card, .history-card {
        background: var(--card-bg);
        border: none;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
    }
    .apply-card .card-header {
        background: linear-gradient(90deg, var(--primary), #818cf8);
        border-top-left-radius: 16px;
        border-top-right-radius: 16px;
        color: white;
        padding: 15px 20px;
        font-weight: 600;
        font-size: 1.1rem;
    }
    
    /* Form Styling */
    .form-control, .form-select { border-radius: 8px; border-color: #e2e8f0; }
    .btn-primary { background: var(--primary); border-color: var(--primary); font-weight: 600; }
    
    /* Table Styling */
    .history-card .card-header {
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        border-top-left-radius: 16px;
        border-top-right-radius: 16px;
        color: var(--text-dark);
        font-weight: 600;
        font-size: 1.1rem;
    }

    .leave-table thead th {
        color: #64748b;
        font-size: .8rem;
        text-transform: uppercase;
        font-weight: 600;
        background: #f8fafc;
    }

    /* Status Badges */
    .badge-status {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: .8rem;
        font-weight: 600;
    }
    .status-pending { background: #fff3cd; color: #b88b02; } /* Yellow/Warning */
    .status-approved { background: #d4edda; color: #155724; } /* Green/Success */
    .status-rejected { background: #f8d7da; color: #721c24; } /* Red/Danger */

    .leave-type-pill {
        background: #e0f2fe;
        color: #0369a1;
        padding: 4px 10px;
        border-radius: 14px;
        font-weight: 500;
        font-size: .85rem;
        display: inline-block;
    }
</style>

<div class="leave-container">

    <?php
    if (isset($_GET['success'])) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
        Swal.fire({icon:'success',title:'Success!',text:'Leave application submitted successfully!',showConfirmButton:true, timer:2500}).then(function(){
            if(window.history.replaceState){
                var url = window.location.href.split('?')[0];
                window.history.replaceState({}, document.title, url);
            }
        });
        </script>";
    }
    if (isset($_GET['error'])) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
        Swal.fire({icon:'error',title:'Error!',text:'Error submitting leave application.',showConfirmButton:true, timer:3500}).then(function(){
            if(window.history.replaceState){
                var url = window.location.href.split('?')[0];
                window.history.replaceState({}, document.title, url);
            }
        });
        </script>";
    }
    ?>

    <div class="leave-hero-header text-center">
        <h4><i class="bi bi-calendar-week me-2"></i>Leave Management Portal</h4>
        <small>Total Leave Quota: <strong><?= $leave_quota ?> Days</strong></small>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="summary-card-modern">
                <h6>Quota Remaining</h6>
                <div class="value text-info"><?= $remaining_leaves ?></div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="summary-card-modern">
                <h6>Days Used</h6>
                <div class="value text-success"><?= $leave_summary['days_taken'] ?></div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="summary-card-modern">
                <h6>Pending Requests</h6>
                <div class="value text-warning"><?= $leave_summary['pending'] ?></div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="summary-card-modern">
                <h6>Total Approved</h6>
                <div class="value text-primary"><?= $leave_summary['approved'] ?></div>
            </div>
        </div>
    </div>

    <div class="card apply-card mb-4">
        <div class="card-header">
            <i class="bi bi-plus-circle me-2"></i>Apply for Leave
        </div>

        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Leave Type</label>
                    <select class="form-select" name="leave_type" required>
                        <option value="">Select Leave Type</option>
                        <option value="Sick Leave">Sick Leave</option>
                        <option value="Casual Leave">Casual Leave</option>
                        <option value="Emergency Leave">Emergency Leave</option>
                        <option value="Personal Leave">Personal Leave</option>
                        <option value="Festival Leave">Festival Leave</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Reason (Brief Explanation)</label>
                    <textarea class="form-control" name="reason" rows="3" required placeholder="e.g., Attending a family function, mild fever, etc."></textarea>
                </div>

                <div class="col-12 text-end pt-3">
                    <button type="submit" class="btn btn-primary px-4 py-2" name="apply_leave">
                        <i class="bi bi-send-check me-1"></i> Submit Application
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card history-card">
        <div class="card-header bg-white border-bottom">
            <i class="bi bi-clock-history me-2"></i><span class="fw-semibold">Recent Leave History</span>
        </div>
        <div class="card-body p-0">
            <!-- Debug: Worker ID -->
            <div style="display:none;">Worker ID: <?= $worker_id ?></div>
            <?php if (!empty($leave_data)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 leave-table align-middle">
                        <thead class="table-light">
                        <tr>
                            <th class="text-center" width="50">#</th>
                            <th>Type</th>
                            <th>Duration</th>
                            <th>Days</th>
                            <th>Applied On</th>
                            <th>Status</th>
                            <th class="text-center" width="80">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $i=1; foreach ($leave_data as $leave): ?>
                            <tr>
                                <td class="text-center text-muted fw-bold">#<?= $i++ ?></td>
                                <td><span class="badge bg-info text-dark px-2 py-1 rounded-pill shadow-sm"><?= s($leave['leave_type']) ?></span></td>
                                <td><span class="text-nowrap"><?= date("d M Y", strtotime($leave['start_date'])) ?> → <?= date("d M Y", strtotime($leave['end_date'])) ?></span></td>
                                <td><span class="badge bg-secondary rounded-pill px-2"><?= $leave['days'] ?></span></td>
                                <td><span class="text-muted small"><?= date("d M Y", strtotime($leave['applied_date'])) ?></span></td>
                                <td>
                                    <?php if ($leave['status']=="Pending"): ?>
                                        <span class="badge bg-warning text-dark rounded-pill px-3 py-1 shadow-sm">Pending</span>
                                    <?php elseif ($leave['status']=="Approved"): ?>
                                        <span class="badge bg-success rounded-pill px-3 py-1 shadow-sm">Approved</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger rounded-pill px-3 py-1 shadow-sm">Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3"
                                            data-bs-toggle="modal"
                                            data-bs-target="#leaveModal<?= $leave['id'] ?>">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            <div class="modal fade" id="leaveModal<?= $leave['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content rounded-3">
                                        <div class="modal-header bg-light border-bottom">
                                            <strong class="modal-title">Leave Details (ID: <?= $leave['id'] ?>)</strong>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>Type:</strong> <?= s($leave['leave_type']) ?></p>
                                            <p><strong>Duration:</strong> <?= date("d M Y", strtotime($leave['start_date'])) ?> to <?= date("d M Y", strtotime($leave['end_date'])) ?></p>
                                            <p><strong>Days:</strong> <span class="badge bg-primary px-2"><?= $leave['days'] ?> Days</span></p>
                                            <p><strong>Status:</strong> <span class="badge bg-<?= ($leave['status']=='Approved'?'success':($leave['status']=='Pending'?'warning':'danger')) ?> rounded-pill px-3 py-1 shadow-sm"><?= s($leave['status']) ?></span></p>
                                            <p><strong>Applied On:</strong> <?= date("d M Y h:i A", strtotime($leave['applied_date'])) ?></p>
                                            <hr>
                                            <p class="mb-1"><strong>Reason:</strong></p>
                                            <div class="alert alert-secondary py-2 small mb-2"><?= nl2br(s($leave['reason'])) ?></div>
                                            <?php if ($leave['admin_remarks']): ?>
                                                <p class="mb-1 text-danger"><strong>Admin Remarks:</strong></p>
                                                <div class="alert alert-danger py-2 small mb-2"><?= nl2br(s($leave['admin_remarks'])) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="py-5 text-center text-muted">
                    <i class="bi bi-calendar-x fs-1 d-block mb-2 text-primary opacity-50"></i>
                    <p class="mt-2 mb-0 fw-semibold">No leave applications found yet.</p>
                    <small>Use the form above to apply for your first leave.</small>
                    <div class="alert alert-warning mt-3">Debug: No leave found for Worker ID <b><?= $worker_id ?></b>. If you just applied, check database for worker_leaves table.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Date validation: Ensure End Date is not before Start Date
    document.getElementById('start_date').addEventListener('change', function() {
        let endDateInput = document.getElementById('end_date');
        endDateInput.min = this.value;
        if (endDateInput.value < this.value) {
            endDateInput.value = this.value;
        }
    });

    // Ensure initial min date is set
    document.addEventListener('DOMContentLoaded', () => {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('start_date').min = today;
        document.getElementById('end_date').min = today;
    });
</script>

<?php 
// Helper function definition is often needed if not in header.php
if (!function_exists('s')) {
    function s($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
include 'footer.php'; 
?>