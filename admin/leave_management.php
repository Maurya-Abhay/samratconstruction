
<?php
// --- Logic: Handle Actions (MOVED TO TOP for header fix) ---
require_once 'database.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Resolve Admin ID
    session_start(); // Ensure session is started before using $_SESSION
    $admin_id = $_SESSION['admin']['id'] ?? 0;
    if (!$admin_id && isset($_SESSION['email'])) {
        $res = $conn->query("SELECT id FROM admin WHERE email='".$_SESSION['email']."' LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) $admin_id = $row['id'];
    }

    // Approve Leave
    if (isset($_POST['approve_leave'])) {
        $leave_id = (int)$_POST['leave_id'];
        $remarks = $conn->real_escape_string($_POST['admin_remarks'] ?? '');
        $sql = "UPDATE worker_leaves SET status='Approved', approved_date=NOW(), approved_by=$admin_id, admin_remarks='$remarks' WHERE id=$leave_id";
        $conn->query($sql);
        header("Location: leave_management.php?msg=approved");
        exit;
    }

    // Reject Leave
    if (isset($_POST['reject_leave'])) {
        $leave_id = (int)$_POST['leave_id'];
        $remarks = $conn->real_escape_string($_POST['admin_remarks'] ?? '');
        $sql = "UPDATE worker_leaves SET status='Rejected', approved_date=NOW(), approved_by=$admin_id, admin_remarks='$remarks' WHERE id=$leave_id";
        $conn->query($sql);
        header("Location: leave_management.php?msg=rejected");
        exit;
    }

    // Delete Leave
    if (isset($_POST['delete_leave'])) {
        $leave_id = (int)$_POST['leave_id'];
        $conn->query("DELETE FROM worker_leaves WHERE id=$leave_id");
        header("Location: leave_management.php?msg=deleted");
        exit;
    }
}

// Now safe to include headers and output
include 'topheader.php';
include 'sidenavbar.php';

// --- Fetch Data ---
$sql = "SELECT wl.*, w.name as worker_name, w.email as worker_email, a.name as approved_by_name
        FROM worker_leaves wl
        LEFT JOIN workers w ON wl.worker_id = w.id 
        LEFT JOIN admin a ON wl.approved_by = a.id
        ORDER BY wl.applied_date DESC";
$leaves = $conn->query($sql);

// --- Stats ---
$total_leaves = $conn->query("SELECT COUNT(*) as count FROM worker_leaves")->fetch_assoc()['count'] ?? 0;
$pending_leaves = $conn->query("SELECT COUNT(*) as count FROM worker_leaves WHERE status='Pending'")->fetch_assoc()['count'] ?? 0;
$approved_leaves = $conn->query("SELECT COUNT(*) as count FROM worker_leaves WHERE status='Approved'")->fetch_assoc()['count'] ?? 0;
$rejected_leaves = $conn->query("SELECT COUNT(*) as count FROM worker_leaves WHERE status='Rejected'")->fetch_assoc()['count'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management</title>
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

        /* Stats Cards */
        .stats-card {
            border: none;
            border-radius: 12px;
            padding: 20px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stats-card:hover { transform: translateY(-3px); }
        .bg-gradient-primary { background: linear-gradient(135deg, #4e73df, #224abe); }
        .bg-gradient-warning { background: linear-gradient(135deg, #f6c23e, #dda20a); }
        .bg-gradient-success { background: linear-gradient(135deg, #1cc88a, #13855c); }
        .bg-gradient-danger { background: linear-gradient(135deg, #e74a3b, #be2617); }
        
        .stats-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2.5rem;
            opacity: 0.3;
        }

        /* Table */
        .table-responsive {
            border-radius: 12px;
            overflow-x: auto;
            overflow-y: visible;
            -webkit-overflow-scrolling: touch;
        }
        @media (max-width: 600px) {
            .table {
                min-width: 700px;
            }
            th, td {
                white-space: nowrap;
                font-size: 0.95rem;
            }
        }
        .table thead th {
            background-color: #f8f9fc;
            color: #858796;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            border-bottom: 2px solid #e3e6f0;
        }
        
        /* Status Badges */
        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 600;
        }
        .badge-pending { background-color: #fff3cd; color: #856404; }
        .badge-approved { background-color: #d1e7dd; color: #0f5132; }
        .badge-rejected { background-color: #f8d7da; color: #842029; }

        /* Forms */
        .form-floating > .form-control { height: 3.5rem; }
        .form-floating > label { padding-top: 0.6rem; }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-dark fw-bold m-0"><i class="bi bi-calendar2-week text-primary me-2"></i>Leave Applications</h3>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success_message) ?>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '<?= htmlspecialchars($error_message) ?>',
                    showConfirmButton: false,
                    timer: 2000
                });
            </script>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stats-card bg-gradient-primary">
                <div>
                    <div class="small fw-bold text-uppercase mb-1" style="opacity:0.8">Total Applications</div>
                    <div class="h2 mb-0 fw-bold"><?= $total_leaves ?></div>
                </div>
                <div class="stats-icon"><i class="bi bi-folder2-open"></i></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stats-card bg-gradient-warning">
                <div>
                    <div class="small fw-bold text-uppercase mb-1" style="opacity:0.8">Pending Review</div>
                    <div class="h2 mb-0 fw-bold"><?= $pending_leaves ?></div>
                </div>
                <div class="stats-icon"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stats-card bg-gradient-success">
                <div>
                    <div class="small fw-bold text-uppercase mb-1" style="opacity:0.8">Approved</div>
                    <div class="h2 mb-0 fw-bold"><?= $approved_leaves ?></div>
                </div>
                <div class="stats-icon"><i class="bi bi-check2-circle"></i></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stats-card bg-gradient-danger">
                <div>
                    <div class="small fw-bold text-uppercase mb-1" style="opacity:0.8">Rejected</div>
                    <div class="h2 mb-0 fw-bold"><?= $rejected_leaves ?></div>
                </div>
                <div class="stats-icon"><i class="bi bi-x-circle"></i></div>
            </div>
        </div>
    </div>

    <div class="card-modern">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 600px;">
                <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="sticky-top">
                        <tr>
                            <th width="5%" class="ps-4">#</th>
                            <th width="20%">Worker Info</th>
                            <th width="15%">Leave Type</th>
                            <th width="20%">Duration</th>
                            <th width="15%">Applied On</th>
                            <th width="10%">Status</th>
                            <th width="15%" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($leaves && $leaves->num_rows > 0): ?>
                            <?php $i=1; while($row = $leaves->fetch_assoc()): 
                                $start = new DateTime($row['start_date']);
                                $end = new DateTime($row['end_date']);
                                $applied = new DateTime($row['applied_date']);
                                $isUrgent = ($row['status'] == 'Pending' && $start <= new DateTime('+2 days'));
                            ?>
                            <tr class="<?= $isUrgent ? 'table-warning' : '' ?>">
                                <td class="ps-4 text-muted"><?= $i++ ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['worker_name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($row['worker_email']) ?></div>
                                    <?php if ($isUrgent): ?>
                                        <span class="badge bg-danger ms-1" style="font-size: 0.6rem;">URGENT</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1">
                                        <?= htmlspecialchars($row['leave_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= $start->format('d M') ?> - <?= $end->format('d M Y') ?></div>
                                    <small class="text-muted"><?= $row['days'] ?> Day(s)</small>
                                </td>
                                <td>
                                    <div class="small"><?= $applied->format('d M Y') ?></div>
                                    <div class="small text-muted"><?= $applied->format('h:i A') ?></div>
                                </td>
                                <td>
                                    <?php if ($row['status'] == 'Pending'): ?>
                                        <span class="status-badge badge-pending"><i class="bi bi-clock me-1"></i>Pending</span>
                                    <?php elseif ($row['status'] == 'Approved'): ?>
                                        <span class="status-badge badge-approved"><i class="bi bi-check-lg me-1"></i>Approved</span>
                                    <?php else: ?>
                                        <span class="status-badge badge-rejected"><i class="bi bi-x-lg me-1"></i>Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#viewLeaveModal<?= $row['id'] ?>" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        
                                        <?php if ($row['status'] == 'Pending'): ?>
                                            <button class="btn btn-sm btn-outline-success" 
                                                    onclick="approveLeave(<?= $row['id'] ?>)" title="Approve">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="rejectLeave(<?= $row['id'] ?>)" title="Reject">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display:inline;" onsubmit="event.preventDefault(); showDeleteLeaveSwal(this);">
                                            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                                            <script>
                                            function showDeleteLeaveSwal(form) {
                                                Swal.fire({
                                                    title: 'Delete Leave?',
                                                    text: 'Delete this record?',
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
                                            <input type="hidden" name="leave_id" value="<?= $row['id'] ?>">
                                            <button type="submit" name="delete_leave" class="btn btn-sm btn-outline-secondary" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="viewLeaveModal<?= $row['id'] ?>" tabindex="-1">
                                  <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header bg-light">
                                            <h5 class="modal-title">Leave Details #<?= $row['id'] ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row g-3">
                                                <div class="col-6">
                                                    <label class="small text-muted fw-bold">Worker</label>
                                                    <div><?= htmlspecialchars($row['worker_name']) ?></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="small text-muted fw-bold">Type</label>
                                                    <div><?= htmlspecialchars($row['leave_type']) ?></div>
                                                </div>
                                                <div class="col-12">
                                                    <label class="small text-muted fw-bold">Reason</label>
                                                    <div class="p-2 bg-light rounded small"><?= nl2br(htmlspecialchars($row['reason'])) ?></div>
                                                </div>
                                                <?php if ($row['status'] != 'Pending'): ?>
                                                    <div class="col-12 border-top pt-2">
                                                        <label class="small text-muted fw-bold">Admin Remarks</label>
                                                        <div class="small"><?= nl2br(htmlspecialchars($row['admin_remarks'] ?? 'No remarks')) ?></div>
                                                        <div class="small text-muted mt-1">
                                                            Processed by <?= htmlspecialchars($row['approved_by_name'] ?? 'Admin') ?> 
                                                            on <?= date('d M Y', strtotime($row['approved_date'])) ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light">
                                            <?php if ($row['status'] == 'Pending'): ?>
                                                <button class="btn btn-success btn-sm" onclick="approveLeave(<?= $row['id'] ?>)">Approve</button>
                                                <button class="btn btn-danger btn-sm" onclick="rejectLeave(<?= $row['id'] ?>)">Reject</button>
                                            <?php endif; ?>
                                            <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">No leave applications found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-check-circle me-2"></i>Approve Leave</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="leave_id" id="approve_leave_id">
                    <div class="form-floating">
                        <textarea class="form-control" name="admin_remarks" style="height: 100px" placeholder="Remarks"></textarea>
                        <label>Admin Remarks (Optional)</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="approve_leave" class="btn btn-success px-4">Confirm Approval</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-x-circle me-2"></i>Reject Leave</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="leave_id" id="reject_leave_id">
                    <div class="form-floating">
                        <textarea class="form-control" name="admin_remarks" style="height: 100px" placeholder="Reason" required></textarea>
                        <label>Reason for Rejection *</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="reject_leave" class="btn btn-danger px-4">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'downfooter.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function approveLeave(id) {
        // Close detail modal if open
        var detailModal = bootstrap.Modal.getInstance(document.getElementById('viewLeaveModal' + id));
        if (detailModal) detailModal.hide();

        document.getElementById('approve_leave_id').value = id;
        new bootstrap.Modal(document.getElementById('approveModal')).show();
    }

    function rejectLeave(id) {
        // Close detail modal if open
        var detailModal = bootstrap.Modal.getInstance(document.getElementById('viewLeaveModal' + id));
        if (detailModal) detailModal.hide();

        document.getElementById('reject_leave_id').value = id;
        new bootstrap.Modal(document.getElementById('rejectModal')).show();
    }
</script>

</body>
</html>