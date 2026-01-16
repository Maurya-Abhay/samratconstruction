<?php
// worker_detail.php

// --- 1. Logic: Redirect & Processing (Must be at the top) ---
require_once 'database.php';
require_once 'lib_common.php';
date_default_timezone_set('Asia/Kolkata');

// Validate ID
if (!isset($_GET['id']) || !($worker_id = intval($_GET['id']))) {
    header("Location: workers.php");
    exit();
}

// Handle Payment Submission
$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    $amount = floatval($_POST['amount']);
    $payment_date = $_POST['payment_date'];
    $remarks = trim($_POST['remarks']);

    if ($amount > 0 && $payment_date) {
        $stmt = $conn->prepare("INSERT INTO worker_payments (worker_id, amount, payment_date, notes) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("idss", $worker_id, $amount, $payment_date, $remarks);
        
        if ($stmt->execute()) {
            header("Location: worker_detail.php?id=$worker_id&status=success");
            exit();
        } else {
            $msg = "Error: " . $stmt->error;
            $msg_type = "danger";
        }
        $stmt->close();
    } else {
        $msg = "Invalid data provided.";
        $msg_type = "warning";
    }
}

if (isset($_GET['status']) && $_GET['status'] === 'success') {
    $msg = "Payment recorded successfully!";
    $msg_type = "success";
}

// Fetch Worker Data
$stmt = $conn->prepare("SELECT * FROM workers WHERE id=?");
$stmt->bind_param("i", $worker_id);
$stmt->execute();
$res = $stmt->get_result();
$worker = $res->fetch_assoc();
$stmt->close();

if (!$worker) {
    header("Location: workers.php");
    exit;
}

// --- Calculations ---
// Payments
$pay_res = $conn->query("SELECT * FROM worker_payments WHERE worker_id=$worker_id ORDER BY payment_date DESC");
$payments = [];
$total_paid = 0;
if ($pay_res) {
    while ($p = $pay_res->fetch_assoc()) {
        $payments[] = $p;
        $total_paid += $p['amount'];
    }
}

// Attendance & Earnings
$daily_wage = floatval($worker['salary'] ?? 0);
$att_res = $conn->query("SELECT * FROM worker_attendance WHERE worker_id=$worker_id ORDER BY date DESC");
$attendance_records = [];
$present_days = 0;
$half_days = 0;
$absent_days = 0;

if ($att_res) {
    while ($a = $att_res->fetch_assoc()) {
        $attendance_records[] = $a;
        if ($a['status'] === 'Present') $present_days++;
        elseif ($a['status'] === 'Half Day') $half_days++;
        elseif ($a['status'] === 'Absent') $absent_days++;
    }
}

$total_earned = ($present_days * $daily_wage) + ($half_days * ($daily_wage / 2));
$balance_due = $total_earned - $total_paid;

// --- HTML Output ---
include 'topheader.php';
include 'sidenavbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Details | Admin</title>
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
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            background: #fff;
            margin-bottom: 24px;
            overflow: hidden;
        }

        /* Profile Header */
        .profile-cover {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            height: 120px;
            position: relative;
        }
        
        .profile-avatar-container {
            position: absolute;
            bottom: -40px;
            left: 30px;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid #fff;
            object-fit: cover;
            background-color: #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .profile-body {
            padding-top: 50px; /* Space for avatar */
            padding-left: 30px;
            padding-right: 30px;
            padding-bottom: 30px;
        }

        /* Info Items */
        .info-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #858796;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .info-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: #333;
        }

        /* Stats Boxes */
        .stat-card {
            padding: 20px;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-left: 5px solid transparent;
            height: 100%;
        }
        .stat-card.earnings { border-left-color: #4e73df; }
        .stat-card.paid { border-left-color: #1cc88a; }
        .stat-card.due { border-left-color: #e74a3b; }
        
        .stat-title { font-size: 0.8rem; color: #888; text-transform: uppercase; font-weight: 700; }
        .stat-num { font-size: 1.5rem; font-weight: 700; margin-top: 5px; }

        /* Custom Tabs */
        .nav-tabs { border-bottom: 2px solid #f1f1f1; }
        .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 600;
            padding: 12px 20px;
            background: transparent;
        }
        .nav-link.active {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            background: transparent;
        }
        .nav-link:hover { color: var(--primary-color); }

        /* Table */
        .table thead th {
            background-color: #f8f9fc;
            color: #858796;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            border-bottom: none;
            padding: 12px 15px;
        }
        .table td { padding: 12px 15px; vertical-align: middle; }
        
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .status-Present { background-color: #d1e7dd; color: #0f5132; }
        .status-Absent { background-color: #f8d7da; color: #842029; }
        .status-HalfDay { background-color: #fff3cd; color: #856404; }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="workers.php" class="text-decoration-none text-muted small fw-bold"><i class="bi bi-arrow-left me-1"></i> WORKERS LIST</a>
            <h3 class="fw-bold text-dark m-0 mt-1">Worker Dashboard</h3>
        </div>
        <div class="d-flex gap-2">
            <a href="worker_pdf_selector.php?id=<?= $worker_id ?>" class="btn btn-outline-danger shadow-sm fw-bold">
                <i class="bi bi-file-earmark-pdf me-1"></i> Report
            </a>
            <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                <i class="bi bi-wallet2 me-1"></i> Add Payment
            </button>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm mb-4 border-0" role="alert">
            <i class="bi bi-<?= $msg_type == 'success' ? 'check-circle' : 'exclamation-triangle' ?>-fill me-2"></i>
            <?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        
        <div class="col-lg-4">
            <div class="card-modern p-0">
                <div class="profile-cover">
                    <div class="profile-avatar-container">
                        <?php $img = !empty($worker['photo']) ? $worker['photo'] : 'assets/default-avatar.png'; ?>
                        <img src="<?= htmlspecialchars($img) ?>" alt="Avatar" class="profile-avatar">
                    </div>
                </div>
                <div class="profile-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h4 class="fw-bold m-0"><?= htmlspecialchars($worker['name']) ?></h4>
                            <p class="text-muted small mb-0"><?= htmlspecialchars($worker['status'] ?? 'Active') ?></p>
                        </div>
                        <span class="badge bg-light text-primary border">ID: #<?= $worker_id ?></span>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="info-label">Phone</div>
                            <div class="info-value"><?= htmlspecialchars($worker['phone']) ?></div>
                        </div>
                        <div class="col-6">
                            <div class="info-label">Joined</div>
                            <div class="info-value"><?= $worker['joining_date'] ? date('d M, Y', strtotime($worker['joining_date'])) : '-' ?></div>
                        </div>
                        <div class="col-12">
                            <div class="info-label">Address</div>
                            <div class="info-value"><?= htmlspecialchars($worker['address'] ?? 'N/A') ?></div>
                        </div>
                        <div class="col-12">
                            <div class="info-label">Aadhaar</div>
                            <div class="info-value"><?= htmlspecialchars($worker['aadhaar'] ?? 'N/A') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <div class="stat-card earnings">
                        <div>
                            <div class="stat-title">Total Earned</div>
                            <div class="stat-num text-primary">₹<?= number_format($total_earned) ?></div>
                            <small class="text-muted">Based on attendance</small>
                        </div>
                        <div class="bg-light rounded-circle p-3 text-primary"><i class="bi bi-graph-up-arrow fs-4"></i></div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="stat-card paid">
                        <div>
                            <div class="stat-title">Total Paid</div>
                            <div class="stat-num text-success">₹<?= number_format($total_paid) ?></div>
                            <small class="text-muted"><?= count($payments) ?> transactions</small>
                        </div>
                        <div class="bg-light rounded-circle p-3 text-success"><i class="bi bi-check-circle fs-4"></i></div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="stat-card due">
                        <div>
                            <div class="stat-title">Balance Due</div>
                            <div class="stat-num text-danger">₹<?= number_format($balance_due) ?></div>
                            <small class="text-muted">Outstanding amount</small>
                        </div>
                        <div class="bg-light rounded-circle p-3 text-danger"><i class="bi bi-exclamation-circle fs-4"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card-modern">
                <div class="card-header bg-white pt-3 px-4">
                    <ul class="nav nav-tabs card-header-tabs" id="historyTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance" type="button">
                                <i class="bi bi-calendar-check me-2"></i>Attendance (<?= count($attendance_records) ?>)
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button">
                                <i class="bi bi-cash-coin me-2"></i>Payments (<?= count($payments) ?>)
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-0">
                    <div class="tab-content" id="historyTabsContent">
                        
                        <div class="tab-pane fade show active" id="attendance" role="tabpanel">
                            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                                <table class="table table-hover mb-0">
                                    <thead class="sticky-top">
                                        <tr>
                                            <th class="ps-4">Date</th>
                                            <th>Status</th>
                                            <th>Time In/Out</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($attendance_records)): ?>
                                            <?php foreach ($attendance_records as $att): 
                                                $statusClass = 'status-' . str_replace(' ', '', $att['status']);
                                            ?>
                                            <tr>
                                                <td class="ps-4 fw-semibold"><?= date('d M, Y', strtotime($att['date'])) ?></td>
                                                <td><span class="status-badge <?= $statusClass ?>"><?= $att['status'] ?></span></td>
                                                <td class="small text-muted">
                                                    <?= $att['check_in'] ? date('h:i A', strtotime($att['check_in'])) : '--' ?> 
                                                    - 
                                                    <?= $att['check_out'] ? date('h:i A', strtotime($att['check_out'])) : '--' ?>
                                                </td>
                                                <td class="small text-muted"><?= htmlspecialchars($att['notes'] ?? '-') ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center py-5 text-muted">No attendance records found.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="payments" role="tabpanel">
                            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                                <table class="table table-hover mb-0">
                                    <thead class="sticky-top">
                                        <tr>
                                            <th class="ps-4">Date</th>
                                            <th>Amount</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($payments)): ?>
                                            <?php foreach ($payments as $pay): ?>
                                            <tr>
                                                <td class="ps-4 fw-semibold"><?= date('d M, Y', strtotime($pay['payment_date'])) ?></td>
                                                <td class="text-success fw-bold">₹<?= number_format($pay['amount'], 2) ?></td>
                                                <td class="small text-muted"><?= htmlspecialchars($pay['notes'] ?? '-') ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="3" class="text-center py-5 text-muted">No payment records found.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-cash me-2"></i>Record Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="add_payment" value="1">
                    
                    <div class="form-floating mb-3">
                        <input type="number" step="0.01" class="form-control" name="amount" placeholder="Amount" required>
                        <label>Payment Amount (₹)</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="date" class="form-control" name="payment_date" value="<?= date('Y-m-d') ?>" required>
                        <label>Date</label>
                    </div>

                    <div class="form-floating">
                        <textarea class="form-control" name="remarks" style="height: 100px" placeholder="Remarks"></textarea>
                        <label>Notes (Optional)</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4">Save Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'downfooter.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>