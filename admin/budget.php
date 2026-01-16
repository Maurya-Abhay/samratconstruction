<?php
// budget.php

include "lib_common.php";

// --- 1. Handle Add Budget ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_budget'])) {
    $project_name = trim($_POST['project_name']);
    $customer_name = trim($_POST['customer_name']);
    $budget_amount = floatval($_POST['budget_amount']);
    $spent_amount = floatval($_POST['spent_amount']);

    $stmt = $conn->prepare("INSERT INTO budgets (project_name, customer_name, budget_amount, spent_amount) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssdd", $project_name, $customer_name, $budget_amount, $spent_amount);
    
    if ($stmt->execute()) {
        header("Location: budget.php");
        exit();
    }
    $stmt->close();
}

// --- 2. Handle Delete Budget ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM budgets WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: budget.php");
    exit();
}

// --- 3. Fetch Data & Calculate Stats ---
$query = $conn->query("SELECT * FROM budgets ORDER BY id DESC");
$budgets = [];
$total_budget = 0;
$total_spent = 0;

if ($query && $query->num_rows > 0) {
    while ($row = $query->fetch_assoc()) {
        $budgets[] = $row;
        $total_budget += $row['budget_amount'];
        $total_spent += $row['spent_amount'];
    }
}
$total_remaining = $total_budget - $total_spent;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Management</title>
    
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
        .bg-gradient-danger { background: linear-gradient(135deg, #e74a3b, #c0392b); }
        .bg-gradient-success { background: linear-gradient(135deg, #1cc88a, #13855c); }

        .stats-icon { font-size: 2.5rem; opacity: 0.3; }

        /* Table */
        .table-responsive { border-radius: 12px; overflow: hidden; }
        .table thead th {
            background-color: #f8f9fc;
            color: #858796;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            border-bottom: 2px solid #e3e6f0;
        }

        /* Progress Bar */
        .progress { height: 8px; border-radius: 4px; background-color: #eaecf4; margin-top: 5px;}
        .progress-bar { border-radius: 4px; }

        /* Forms */
        .form-floating > .form-control { height: 3.5rem; }
        .form-floating > label { padding-top: 0.6rem; }
    </style>
</head>
<body>

<?php include "topheader.php" ?>
<?php include "sidenavbar.php" ?>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-dark fw-bold m-0"><i class="bi bi-wallet2 text-primary me-2"></i>Project Budgets</h3>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addBudgetModal">
            <i class="bi bi-plus-lg me-1"></i> Add Budget
        </button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="summary-box bg-gradient-primary">
                <div>
                    <h6 class="text-uppercase mb-1" style="opacity:0.9">Total Budget</h6>
                    <h3 class="mb-0 fw-bold">₹<?= number_format($total_budget, 2) ?></h3>
                </div>
                <div class="stats-icon"><i class="bi bi-cash-stack"></i></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="summary-box bg-gradient-danger">
                <div>
                    <h6 class="text-uppercase mb-1" style="opacity:0.9">Total Spent</h6>
                    <h3 class="mb-0 fw-bold">₹<?= number_format($total_spent, 2) ?></h3>
                </div>
                <div class="stats-icon"><i class="bi bi-graph-down-arrow"></i></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="summary-box bg-gradient-success">
                <div>
                    <h6 class="text-uppercase mb-1" style="opacity:0.9">Remaining</h6>
                    <h3 class="mb-0 fw-bold">₹<?= number_format($total_remaining, 2) ?></h3>
                </div>
                <div class="stats-icon"><i class="bi bi-piggy-bank"></i></div>
            </div>
        </div>
    </div>

    <div class="card-modern">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 600px;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="sticky-top">
                        <tr>
                            <th width="5%" class="ps-4">ID</th>
                            <th width="20%">Project</th>
                            <th width="15%">Allocated</th>
                            <th width="15%">Spent</th>
                            <th width="15%">Remaining</th>
                            <th width="20%">Usage</th>
                            <th width="10%" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($budgets)): ?>
                            <?php foreach ($budgets as $row): 
                                $remaining = $row['budget_amount'] - $row['spent_amount'];
                                $percent = ($row['budget_amount'] > 0) ? ($row['spent_amount'] / $row['budget_amount']) * 100 : 0;
                                $percent = min(100, max(0, $percent)); // Clamp between 0-100
                                
                                // Color logic
                                $barColor = 'success';
                                if($percent > 70) $barColor = 'warning';
                                if($percent > 90) $barColor = 'danger';
                            ?>
                            <tr>
                                <td class="ps-4 text-muted">#<?= $row['id'] ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['project_name']) ?></div>
                                    <small class="text-muted"><i class="bi bi-person me-1"></i><?= htmlspecialchars($row['customer_name']) ?></small>
                                </td>
                                <td class="fw-bold text-dark">₹<?= number_format($row['budget_amount']) ?></td>
                                <td class="text-danger">₹<?= number_format($row['spent_amount']) ?></td>
                                <td class="text-success fw-bold">₹<?= number_format($remaining) ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="small me-2 fw-bold"><?= round($percent) ?>%</span>
                                        <div class="progress flex-grow-1">
                                            <div class="progress-bar bg-<?= $barColor ?>" role="progressbar" style="width: <?= $percent ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <form method="POST" onsubmit="event.preventDefault(); showDeleteBudgetSwal(this);">
                                        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                                        <script>
                                        function showDeleteBudgetSwal(form) {
                                            Swal.fire({
                                                title: 'Delete Budget Entry?',
                                                text: 'Delete this budget entry?',
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
                                        <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">No budget records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="addBudgetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Project Budget</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="add_budget" value="1">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="project_name" placeholder="Project" required>
                                <label>Project Name *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="customer_name" placeholder="Customer" required>
                                <label>Customer Name *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="number" step="0.01" class="form-control" name="budget_amount" placeholder="Budget" required>
                                <label>Total Budget (₹) *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="number" step="0.01" class="form-control" name="spent_amount" placeholder="Spent" required value="0">
                                <label>Amount Spent (₹) *</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary px-4">Save Budget</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include "downfooter.php" ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>