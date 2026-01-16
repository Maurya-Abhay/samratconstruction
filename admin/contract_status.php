<?php
// contract_status.php

require_once 'database.php';
include 'topheader.php';
include 'sidenavbar.php';

// Fetch Contracts
$contracts = $conn->query("SELECT * FROM contacts ORDER BY id DESC");

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = (int)$_POST['edit_id'];
    $progress = (int)($_POST['progress_percent'] ?? 0);
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $current_stage = $_POST['current_stage'] ?? '';
    $est_completion = !empty($_POST['estimated_completion']) ? $_POST['estimated_completion'] : null;
    $status = $_POST['status'] ?? '';
    
    $stmt = $conn->prepare("UPDATE contacts SET progress_percent=?, start_date=?, current_stage=?, estimated_completion=?, status=? WHERE id=?");
    $stmt->bind_param("issssi", $progress, $start_date, $current_stage, $est_completion, $status, $id);
    
    if ($stmt->execute()) {
        // Redirect to refresh
        echo "<script>window.location.href='contract_status.php';</script>";
        exit();
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract Status</title>
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

        /* Table */
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }
        .table thead th {
            background-color: #f8f9fc;
            color: #858796;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            border-bottom: 2px solid #e3e6f0;
        }

        /* Progress Bar */
        .progress {
            height: 10px;
            border-radius: 5px;
            background-color: #eaecf4;
        }
        .progress-bar {
            border-radius: 5px;
        }

        /* Status Badges */
        .badge-status { font-size: 0.75rem; padding: 5px 10px; border-radius: 20px; }
        .status-Pending { background-color: #fff3cd; color: #856404; }
        .status-Active { background-color: #d1e7dd; color: #0f5132; }
        .status-Completed { background-color: #d4edda; color: #155724; }
        .status-OnHold { background-color: #f8d7da; color: #721c24; }

        /* Forms */
        .form-floating > .form-control { height: 3.5rem; }
        .form-floating > label { padding-top: 0.6rem; }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-dark fw-bold m-0"><i class="bi bi-kanban text-primary me-2"></i>Contract Status</h3>
    </div>

    <div class="card-modern">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 600px;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="sticky-top">
                        <tr>
                            <th width="5%" class="ps-4">ID</th>
                            <th width="20%">Project Name</th>
                            <th width="20%">Progress</th>
                            <th width="15%">Timeline</th>
                            <th width="15%">Current Stage</th>
                            <th width="10%">Status</th>
                            <th width="15%" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($contracts && $contracts->num_rows > 0): ?>
                            <?php while($c = $contracts->fetch_assoc()): 
                                $progress = (int)($c['progress_percent'] ?? 0);
                                $statusClass = 'status-' . str_replace(' ', '', $c['status'] ?? 'Pending');
                            ?>
                            <tr>
                                <td class="ps-4 text-muted">#<?= $c['id'] ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($c['name']) ?></div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2">
                                            <div class="progress-bar bg-<?php echo $progress == 100 ? 'success' : ($progress > 50 ? 'primary' : 'warning'); ?>" 
                                                 role="progressbar" style="width: <?= $progress ?>%"></div>
                                        </div>
                                        <span class="small fw-bold"><?= $progress ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="small text-muted">Start: <span class="text-dark"><?= $c['start_date'] ? date('d M Y', strtotime($c['start_date'])) : '-' ?></span></div>
                                    <div class="small text-muted">Est: <span class="text-dark"><?= $c['estimated_completion'] ? date('d M Y', strtotime($c['estimated_completion'])) : '-' ?></span></div>
                                </td>
                                <td>
                                    <span class="small text-dark"><?= htmlspecialchars($c['current_stage'] ?? '-') ?></span>
                                </td>
                                <td>
                                    <span class="badge-status <?= $statusClass ?>">
                                        <?= htmlspecialchars($c['status'] ?? 'Pending') ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal<?= $c['id'] ?>">
                                        <i class="bi bi-pencil-square me-1"></i> Update
                                    </button>
                                </td>
                            </tr>

                            <div class="modal fade" id="editModal<?= $c['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title">Update Status: <?= htmlspecialchars($c['name']) ?></h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body p-4">
                                                <input type="hidden" name="edit_id" value="<?= $c['id'] ?>">
                                                
                                                <label class="form-label small fw-bold text-muted">Progress</label>
                                                <div class="d-flex align-items-center mb-3">
                                                    <input type="range" class="form-range flex-grow-1 me-3" name="progress_percent" min="0" max="100" value="<?= $progress ?>" oninput="this.nextElementSibling.value = this.value + '%'">
                                                    <output class="fw-bold"><?= $progress ?>%</output>
                                                </div>

                                                <div class="row g-2 mb-3">
                                                    <div class="col-6">
                                                        <div class="form-floating">
                                                            <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($c['start_date'] ?? '') ?>">
                                                            <label>Start Date</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-floating">
                                                            <input type="date" class="form-control" name="estimated_completion" value="<?= htmlspecialchars($c['estimated_completion'] ?? '') ?>">
                                                            <label>Est. Completion</label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-floating mb-3">
                                                    <input type="text" class="form-control" name="current_stage" value="<?= htmlspecialchars($c['current_stage'] ?? '') ?>" placeholder="Stage">
                                                    <label>Current Stage (e.g. Foundation)</label>
                                                </div>

                                                <div class="form-floating">
                                                    <select class="form-select" name="status">
                                                        <option value="Pending" <?= ($c['status'] == 'Pending') ? 'selected' : '' ?>>Pending</option>
                                                        <option value="Active" <?= ($c['status'] == 'Active') ? 'selected' : '' ?>>Active</option>
                                                        <option value="On Hold" <?= ($c['status'] == 'On Hold') ? 'selected' : '' ?>>On Hold</option>
                                                        <option value="Completed" <?= ($c['status'] == 'Completed') ? 'selected' : '' ?>>Completed</option>
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
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">No contracts found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include 'downfooter.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>