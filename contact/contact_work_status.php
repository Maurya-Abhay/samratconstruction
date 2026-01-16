<?php
require_once '../admin/database.php';
require_once '../admin/lib_common.php';
include 'header.php';
session_start();

$contact_id = isset($_GET['id']) ? intval($_GET['id']) : ($_SESSION['contact_id'] ?? 0);

if ($contact_id <= 0) {
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'error',title:'Invalid Contact',text:'Invalid contact ID.',showConfirmButton:false,timer:2000});</script>";
    include 'footer.php';
    exit;
}

$stmt = $conn->prepare("SELECT id, name, status, contract_amount, amount_paid FROM contacts WHERE id=? LIMIT 1");
$stmt->bind_param('i', $contact_id);
$stmt->execute();
$res = $stmt->get_result();
$contact = $res->fetch_assoc();
$stmt->close();

if (!$contact) {
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'error',title:'Contact Not Found',text:'Contact not found.',showConfirmButton:false,timer:2000});</script>";
    include 'footer.php';
    exit;
}

$contract_amount = $contact['contract_amount'] ?? 0;
$amount_paid = $contact['amount_paid'] ?? 0;
$due = max(0, $contract_amount - $amount_paid);

$progress_percent = ($contract_amount > 0)
    ? min(100, round(($amount_paid / $contract_amount) * 100))
    : 0;

$status = $contact['status'] ?: 'Unknown';

switch (strtolower($status)) {
    case 'active': $status_color = 'success'; break;
    case 'inactive': $status_color = 'danger'; break;
    case 'pending': $status_color = 'warning'; break;
    default: $status_color = 'secondary';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Contact Work Status</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
    body {
        background: linear-gradient(135deg, #eef2ff 0%, #f8faff 100%);
        font-family: 'Inter', sans-serif;
    }
    .dashboard-card {
        border: none;
        border-radius: 18px;
        background: #fff;
        transition: 0.3s;
        box-shadow: 0 4px 18px rgba(0,0,0,0.08);
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }
    .header-title {
        font-weight: 700;
        font-size: 28px;
        color: #1b2a4e;
    }
    .stat-box {
        border-radius: 16px;
        padding: 25px;
        color: #fff;
    }
    .stat-1 { background: linear-gradient(135deg, #4f46e5, #6366f1); }
    .stat-2 { background: linear-gradient(135deg, #059669, #10b981); }
    .stat-3 { background: linear-gradient(135deg, #dc2626, #ef4444); }
    .progress {
        height: 20px;
        border-radius: 30px;
        overflow: hidden;
        background: #e7e9f5;
    }
    .progress-bar {
        border-radius: 30px;
        font-weight: 600;
    }
</style>
</head>

<body>

<div class="container py-5">

    <div class="mb-5">
        <h3 class="header-title">
            <i class="bi bi-speedometer2 me-2 text-primary"></i>
            Work Status: <?= htmlspecialchars($contact['name']) ?>
        </h3>
    </div>

    <!-- Big Contact Card -->
    <div class="dashboard-card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-1 text-dark">
                    <?= htmlspecialchars($contact['name']) ?> 
                    <span class="text-muted">(ID: <?= $contact['id'] ?>)</span>
                </h4>
                <p class="text-muted small mb-0">Current Working Status</p>
            </div>
            <span class="badge bg-<?= $status_color ?> px-4 py-2 fs-6">
                <?= htmlspecialchars($status) ?>
            </span>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-box stat-1 shadow-lg">
                <div class="small">Contract Value</div>
                <div class="fs-4 fw-bold">₹<?= number_format($contract_amount, 2) ?></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-box stat-2 shadow-lg">
                <div class="small">Amount Paid</div>
                <div class="fs-4 fw-bold">₹<?= number_format($amount_paid, 2) ?></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-box stat-3 shadow-lg">
                <div class="small">Amount Due</div>
                <div class="fs-4 fw-bold">₹<?= number_format($due, 2) ?></div>
            </div>
        </div>
    </div>

    <!-- Progress Card -->
    <div class="dashboard-card p-4">
        <h5 class="fw-bold mb-3 text-dark">
            <i class="bi bi-bar-chart-steps me-2 text-primary"></i> Payment Progress
        </h5>

        <div class="d-flex justify-content-between mb-2">
            <span class="fw-bold">Progress</span>
            <span class="fw-bold text-primary"><?= $progress_percent ?>%</span>
        </div>

        <div class="progress mb-3">
            <div class="progress-bar bg-success" style="width: <?= $progress_percent ?>%;">
                <?= $progress_percent ?>%
            </div>
        </div>

        <?php if ($progress_percent == 100): ?>
            <div class="alert alert-success text-center">
                <i class="bi bi-check-circle-fill me-2"></i> Fully Paid!
            </div>
        <?php elseif ($due > 0): ?>
            <div class="alert alert-warning text-center">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> ₹<?= number_format($due, 2) ?> still due.
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Auto-refresh every 1 minute
setInterval(function() {
    location.reload();
}, 60000);
</script>

</body>
</html>
