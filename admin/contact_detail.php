<?php
// contact_detail.php

require_once 'database.php';
include 'topheader.php';
include 'sidenavbar.php';

$id = intval($_GET['id'] ?? 0);
$contact = null;

// --- Handle Status Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $progress = intval($_POST['progress_percent'] ?? 0);
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $current_stage = $_POST['current_stage'] ?? null;
    $est_completion = !empty($_POST['estimated_completion']) ? $_POST['estimated_completion'] : null;
    $status = $_POST['status'] ?? 'Pending';

    $stmt = $conn->prepare("UPDATE contacts SET progress_percent=?, start_date=?, current_stage=?, estimated_completion=?, status=? WHERE id=?");
    $stmt->bind_param("issssi", $progress, $start_date, $current_stage, $est_completion, $status, $id);
    $success = $stmt->execute();
    $stmt->close();
    if ($success) {
        echo "<script>localStorage.setItem('statusUpdateSuccess', '1'); window.location.href='contact_detail.php?id=$id';</script>";
        exit();
    } else {
        echo "<script>localStorage.setItem('statusUpdateFail', '1'); window.location.href='contact_detail.php?id=$id';</script>";
        exit();
    }
}

// --- Fetch Contact ---
if ($id) {
    $stmt = $conn->prepare("SELECT * FROM contacts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows) {
        $contact = $result->fetch_assoc();
    }
    $stmt->close();
}

if (!$contact) {
    echo '<div class="container py-5 text-center"><div class="alert alert-danger">Contact not found. <a href="contact_manager.php" class="alert-link">Go back</a></div></div>';
    include 'downfooter.php';
    exit;
}

// --- Fetch Payments ---
$payments = $conn->query("SELECT * FROM contact_payments WHERE contact_id = $id ORDER BY payment_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Details</title>
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

        .profile-header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            padding: 30px;
            border-radius: 12px 12px 0 0;
            position: relative;
        }

        .profile-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            background-color: #fff;
        }

        .info-label {
            font-size: 0.85rem;
            color: #858796;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 0.2rem;
        }
        
        .info-value {
            font-size: 1rem;
            color: #333;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .progress { height: 10px; border-radius: 5px; background-color: #eaecf4; }
        .progress-bar { border-radius: 5px; }

        /* Forms */
        .form-floating > .form-control { height: 3.5rem; }
        .form-floating > label { padding-top: 0.6rem; }
    </style>
</head>
<body>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (localStorage.getItem('statusUpdateSuccess')) {
            Swal.fire({
                icon: 'success',
                title: 'Status Updated!',
                text: 'Project status updated successfully.',
                timer: 1800,
                showConfirmButton: false
            });
            localStorage.removeItem('statusUpdateSuccess');
        }
        if (localStorage.getItem('statusUpdateFail')) {
            Swal.fire({
                icon: 'error',
                title: 'Update Failed',
                text: 'Could not update project status. Please try again.',
                timer: 2200,
                showConfirmButton: false
            });
            localStorage.removeItem('statusUpdateFail');
        }
    });
</script>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="customers.php" class="text-decoration-none text-muted small"><i class="bi bi-arrow-left me-1"></i> Back to List</a>
            <h3 class="text-dark fw-bold m-0 mt-1">Contact Details</h3>
        </div>
        <div>
            <span class="badge bg-<?php echo ($contact['status'] == 'Active') ? 'success' : 'secondary'; ?> fs-6">
                <?= htmlspecialchars($contact['status']) ?>
            </span>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card-modern">
                <div class="card-body text-center p-4">
                    <?php if (!empty($contact['photo'])): ?>
                        <img src="<?= htmlspecialchars($contact['photo']) ?>" class="profile-img mb-3">
                    <?php else: ?>
                        <div class="profile-img d-flex align-items-center justify-content-center mx-auto mb-3 text-primary bg-light display-4">
                            <?= strtoupper(substr($contact['name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    
                    <h4 class="fw-bold text-dark"><?= htmlspecialchars($contact['name']) ?></h4>
                    <p class="text-muted small mb-3">Client / Contact</p>
                    
                    <hr>
                    
                    <div class="text-start">
                        <div class="mb-3">
                            <div class="info-label"><i class="bi bi-envelope me-1"></i> Email</div>
                            <div class="info-value"><?= htmlspecialchars($contact['email']) ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="info-label"><i class="bi bi-phone me-1"></i> Phone</div>
                            <div class="info-value"><?= htmlspecialchars($contact['phone']) ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="info-label"><i class="bi bi-card-heading me-1"></i> Aadhaar</div>
                            <div class="info-value"><?= htmlspecialchars($contact['aadhaar'] ?? 'N/A') ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="info-label"><i class="bi bi-geo-alt me-1"></i> Address</div>
                            <div class="info-value"><?= nl2br(htmlspecialchars($contact['address'] ?? 'N/A')) ?></div>
                        </div>
                        <div class="mb-0">
                            <div class="info-label"><i class="bi bi-calendar-event me-1"></i> Joining Date</div>
                            <div class="info-value"><?= $contact['joining_date'] ? date('d M, Y', strtotime($contact['joining_date'])) : 'N/A' ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($contact['notes'])): ?>
            <div class="card-modern">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-sticky me-2 text-warning"></i>Notes</h6>
                    <p class="mb-0 text-muted small bg-light p-3 rounded fst-italic">
                        <?= nl2br(htmlspecialchars($contact['notes'])) ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-8">
            
            <div class="card-modern">
                <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold m-0 text-primary"><i class="bi bi-kanban me-2"></i>Project Status</h5>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editStatusModal">
                        <i class="bi bi-pencil-square me-1"></i> Update
                    </button>
                </div>
                <div class="card-body px-4 pb-4 pt-0">
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-bold text-muted">Progress</span>
                            <span class="small fw-bold text-primary"><?= (int)$contact['progress_percent'] ?>%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?= (int)$contact['progress_percent'] ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded h-100">
                                <div class="info-label">Current Stage</div>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($contact['current_stage'] ?? 'Not Started') ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded h-100">
                                <div class="info-label">Overall Status</div>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($contact['status'] ?? 'Pending') ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Start Date</div>
                            <div class="text-dark"><?= $contact['start_date'] ? date('d M, Y', strtotime($contact['start_date'])) : '-' ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Est. Completion</div>
                            <div class="text-dark"><?= $contact['estimated_completion'] ? date('d M, Y', strtotime($contact['estimated_completion'])) : '-' ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-modern">
                <div class="card-header bg-white border-0 py-3 px-4">
                    <h5 class="fw-bold m-0 text-success"><i class="bi bi-wallet2 me-2"></i>Financial Overview</h5>
                </div>
                <div class="card-body px-4 pb-4 pt-0">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center">
                                <div class="small text-muted mb-1">Total Contract</div>
                                <div class="h5 fw-bold text-dark mb-0">₹<?= number_format($contact['contract_amount'], 2) ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center bg-success bg-opacity-10 border-success">
                                <div class="small text-success mb-1">Paid Amount</div>
                                <div class="h5 fw-bold text-success mb-0">₹<?= number_format($contact['amount_paid'], 2) ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center bg-danger bg-opacity-10 border-danger">
                                <div class="small text-danger mb-1">Due Amount</div>
                                <div class="h5 fw-bold text-danger mb-0">₹<?= number_format($contact['contract_amount'] - $contact['amount_paid'], 2) ?></div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-muted small text-uppercase mb-3">Payment History</h6>
                    <div class="table-responsive border rounded">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="small text-muted ps-3">Date</th>
                                    <th class="small text-muted text-end">Amount</th>
                                    <th class="small text-muted pe-3">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($payments && $payments->num_rows > 0): ?>
                                    <?php while($row = $payments->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-3"><?= date('d M, Y', strtotime($row['payment_date'])) ?></td>
                                        <td class="text-end fw-bold text-success">₹<?= number_format($row['amount'], 2) ?></td>
                                        <td class="small text-muted pe-3"><?= htmlspecialchars($row['notes'] ?? '-') ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center py-3 text-muted small">No payment records found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<div class="modal fade" id="editStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Update Project Status</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="update_status" value="1">
                    
                    <label class="form-label small fw-bold text-muted">Progress (%)</label>
                    <div class="d-flex align-items-center mb-3">
                        <input type="range" class="form-range flex-grow-1 me-3" name="progress_percent" min="0" max="100" value="<?= (int)$contact['progress_percent'] ?>" oninput="this.nextElementSibling.value = this.value + '%'">
                        <output class="fw-bold text-primary"><?= (int)$contact['progress_percent'] ?>%</output>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="current_stage" value="<?= htmlspecialchars($contact['current_stage'] ?? '') ?>" placeholder="Stage">
                        <label>Current Stage</label>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="form-floating">
                                <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($contact['start_date'] ?? '') ?>">
                                <label>Start Date</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating">
                                <input type="date" class="form-control" name="estimated_completion" value="<?= htmlspecialchars($contact['estimated_completion'] ?? '') ?>">
                                <label>Est. Completion</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-floating">
                        <select class="form-select" name="status">
                            <option value="Pending" <?= ($contact['status'] == 'Pending') ? 'selected' : '' ?>>Pending</option>
                            <option value="Active" <?= ($contact['status'] == 'Active') ? 'selected' : '' ?>>Active</option>
                            <option value="On Hold" <?= ($contact['status'] == 'On Hold') ? 'selected' : '' ?>>On Hold</option>
                            <option value="Completed" <?= ($contact['status'] == 'Completed') ? 'selected' : '' ?>>Completed</option>
                        </select>
                        <label>Overall Status</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include "downfooter.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>