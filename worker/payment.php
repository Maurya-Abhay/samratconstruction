<?php
// payments.php (Modern Light Premium redesign)
session_start();

$page_title = "Payments Overview";
$show_back_btn = true;

// Define helper function for output safety
if (!function_exists('s')) {
    function s($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

if (!isset($_SESSION['worker_id'])) {
    header('Location: login.php');
    exit();
}
$worker_id = $_SESSION['worker_id'];

include 'header.php'; // Includes required files

require_once '../admin/database.php';

if (!$conn instanceof mysqli) {
    echo '<div class="alert alert-danger">Database connection failed.</div>';
    include 'footer.php';
    exit();
}

// Fetch worker (using prepared statement for safety)
$worker = null;
$stmt_worker = $conn->prepare("SELECT id, name, salary FROM workers WHERE id = ?");
$stmt_worker->bind_param('i', $worker_id);
$stmt_worker->execute();
$worker_result = $stmt_worker->get_result();
$worker = $worker_result->fetch_assoc();
$stmt_worker->close();

if (!$worker) {
    echo '<div class="alert alert-danger">Worker not found!</div>';
    include 'footer.php';
    exit();
}

// Payment history
$payments = $conn->query("SELECT * FROM worker_payments WHERE worker_id=$worker_id ORDER BY payment_date DESC");

// Total paid
$total_paid = 0.00;
$payment_data = [];
if ($payments && $payments->num_rows > 0) {
    while($prow = $payments->fetch_assoc()){
        $total_paid += floatval($prow['amount']);
        $payment_data[] = $prow;
    }
}

// Attendance summary
$attendance = $conn->query("SELECT status FROM worker_attendance WHERE worker_id=$worker_id");
$summary = ['Present'=>0, 'Absent'=>0, 'Leave'=>0];

if ($attendance && $attendance->num_rows > 0) {
    foreach ($attendance as $row) {
        if (isset($summary[$row['status']])) $summary[$row['status']]++;
    }
}

$daily_wage = floatval($worker['salary'] ?? 0);
$total_present_payment = $summary['Present'] * $daily_wage;
$remaining_due = $total_present_payment - $total_paid;
?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --primary: #4f46e5;
        --secondary: #64748b;
        --bg-body: #f1f5f9;
        --card-bg: #ffffff;
        --text-dark: #1e293b;
    }
    
    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background-color: var(--bg-body);
        color: var(--text-dark);
    }

    .payment-container {
        padding: 12px 10px;
        margin: 30px auto;
        padding: 0 15px;
    }

    /* Primary Card Style */
    
    @media (max-width: 600px) {
        .summary-card-modern .value { font-size: 1.1rem; }
        .summary-card-modern { padding: 8px 6px; }
    }
    .page-card {
        background: var(--card-bg);
        border-radius: 16px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.07);
    .summary-card-modern .value { font-size: 1.3rem; font-weight: 700; word-break: break-word; }
        overflow: hidden;
    }

    /* Header Box */
    .header-box {
        background: linear-gradient(135deg, var(--primary), #818cf8);
        padding: 30px;
        color: #fff;
        font-size: 1.1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .header-box h3 { font-weight: 700; margin: 0; font-size: 1.8rem; }
    .header-box small { opacity: 0.8; }

    /* Summary Cards */
    .summary-card-modern {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        transition: transform .2s ease;
        height: 100%;
    }
    .summary-card-modern:hover { transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08); }
    .summary-card-modern i { font-size: 2rem; color: var(--primary); }
    .summary-card-modern h6 { color: var(--secondary); font-size: .85rem; font-weight: 600; margin-top: 8px; }
    .summary-card-modern .value { font-size: 1.8rem; font-weight: 700; }
    
    /* Detailed Calculation Box */
    .calc-box {
        border-radius: 12px;
        padding: 20px;
        border: 1px solid #e2e8f0;
        background-color: #f8fafc;
        height: 100%;
    }
    .calc-box h6 { color: var(--primary); font-weight: 600; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 15px; }
    .calc-box span { font-size: 0.95rem; }
    .calc-box strong { font-size: 1.1rem; }

    /* History Table */
    .history-header {
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        color: var(--text-dark);
        font-weight: 600;
        padding: 15px 20px;
        border-top-left-radius: 16px;
        border-top-right-radius: 16px;
    }
    .table-responsive { border-radius: 16px; }
    .table thead th {
        color: #64748b;
        font-size: .8rem;
        text-transform: uppercase;
        font-weight: 600;
        background: #f8fafc;
    }
    .table-hover tbody tr:hover { background-color: #f1f5f9; }
    .status-badge-paid {
        background: #d1fae5; 
        color: #047857; 
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 600;
    }
</style>

<div class="payment-container">

    <div class="page-card mb-4">
        <div class="header-box">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-wallet2 me-2"></i>Payments Overview</h3>
                <small>Worker ID: <?= s($worker['id']) ?> | Name: <?= s($worker['name']) ?></small>
            </div>
            <div class="ms-3">
                <span class="badge bg-white text-primary fw-bold p-3 rounded-pill shadow-sm">
                    Daily Wage: ₹<?= number_format($daily_wage, 2) ?>
                </span>
            </div>
        </div>

        <div class="p-4 row g-3">
            
            <div class="col-6 col-md-3">
                <div class="summary-card-modern">
                    <i class="bi bi-currency-rupee text-success"></i>
                    <h6>Total Earned (Till Date)</h6>
                    <div class="value text-success">₹<?= number_format($total_present_payment, 2) ?></div>
                </div>
            </div>
            
            <div class="col-6 col-md-3">
                <div class="summary-card-modern">
                    <i class="bi bi-cash-coin text-primary"></i>
                    <h6>Total Paid</h6>
                    <div class="value text-primary">₹<?= number_format($total_paid, 2) ?></div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="summary-card-modern">
                    <i class="bi bi-calendar-check text-info"></i>
                    <h6>Present Days Count</h6>
                    <div class="value text-info"><?= $summary['Present'] ?></div>
                </div>
            </div>
            
            <div class="col-6 col-md-3">
                <div class="summary-card-modern">
                    <i class="bi bi-clipboard-data text-danger"></i>
                    <h6>Amount Due / Advance</h6>
                    <div class="value <?= $remaining_due > 0 ? 'text-danger' : ($remaining_due < 0 ? 'text-warning' : 'text-success') ?>">
                        ₹<?= number_format(abs($remaining_due), 2) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php 
    $status_class = 'alert-success';
    $status_icon = 'bi-check-circle-fill';
    $status_message = 'All Payments Clear';

    if ($remaining_due > 0) {
        $status_class = 'alert-danger';
        $status_icon = 'bi-exclamation-triangle-fill';
        $status_message = "Pending Payment: ₹" . number_format($remaining_due, 2) . ". Contact admin for settlement.";
    } elseif ($remaining_due < 0) {
        $status_class = 'alert-info';
        $status_icon = 'bi-info-circle';
        $status_message = "Advance Paid: ₹" . number_format(abs($remaining_due), 2) . ". This will be deducted from future earnings.";
    }
    ?>
    <div class="alert <?= $status_class ?> d-flex align-items-center mb-4 rounded-3 shadow-sm py-3" role="alert">
        <i class="bi <?= $status_icon ?> fs-4 me-3"></i>
        <div class="fw-semibold"><?= $status_message ?></div>
    </div>

    <div class="page-card mb-4 p-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-calculator me-2 text-primary"></i>Detailed Calculation</h5>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="calc-box">
                    <h6>Earnings Summary</h6>
                    <div class="d-flex justify-content-between mb-2"><span>Present Days:</span><strong class="text-primary"><?= $summary['Present'] ?></strong></div>
                    <div class="d-flex justify-content-between mb-2"><span>Daily Wage:</span><strong>₹<?= number_format($daily_wage, 2) ?></strong></div>
                    <hr class="my-3">
                    <div class="d-flex justify-content-between"><span class="fw-bold fs-5">TOTAL EARNED:</span><strong class="text-success fs-5">₹<?= number_format($total_present_payment, 2) ?></strong></div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="calc-box">
                    <h6>Balance Summary</h6>
                    <div class="d-flex justify-content-between mb-2"><span>Total Earned:</span><strong class="text-success">₹<?= number_format($total_present_payment, 2) ?></strong></div>
                    <div class="d-flex justify-content-between mb-2"><span>(-) Total Paid:</span><strong class="text-primary">₹<?= number_format($total_paid, 2) ?></strong></div>
                    <hr class="my-3">
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold fs-5">NET BALANCE:</span>
                        <strong class="fs-5 <?= $remaining_due > 0 ? 'text-danger' : ($remaining_due < 0 ? 'text-warning' : 'text-success') ?>">
                            ₹<?= number_format($remaining_due, 2) ?>
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-card mb-4">
        <div class="history-header">
            <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Transaction History</h5>
        </div>

        <div class="table-responsive">
            <?php if (!empty($payment_data)): ?>
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Method</th>
                            <th>Notes</th>
                            <th width="100">Receipt</th>
                            <th width="100">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        foreach($payment_data as $row):
                        $d = new DateTime($row['payment_date']);
                        $days = (new DateTime())->diff($d)->days;
                        ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td class="fw-bold text-success">₹<?= number_format($row['amount'], 2) ?></td>
                            <td>
                                <?= $d->format('d M Y') ?><br>
                                <small class="text-muted">
                                <?= $days==0?'Today':($days==1?'Yesterday':$days.' days ago') ?>
                                </small>
                            </td>
                            <td><i class="bi bi-credit-card me-1 text-info"></i><?= s($row['method'] ?? 'Cash') ?></td>
                            <td><?= s($row['notes'] ?: '-') ?></td>
                            <td>
                                <button class="btn btn-outline-primary btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-href="../admin/worker_payment_receipt.php?id=<?= (int)$row['id'] ?>&embed=1" 
                                        data-bs-target="#receiptModal">
                                    <i class="bi bi-receipt"></i> View
                                </button>
                            </td>
                            <td><span class="status-badge-paid"><i class="bi bi-check2 me-1"></i> Paid</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th class="fw-bold">TOTAL PAID:</th>
                            <th class="text-primary fw-bold fs-5">₹<?= number_format($total_paid, 2) ?></th>
                            <th colspan="5"></th>
                        </tr>
                    </tfoot>
                </table>
            <?php else: ?>
                <div class="text-center p-5 text-muted">
                    <i class="bi bi-wallet-x fs-1 text-primary opacity-50 d-block mb-3"></i>
                    <h5>No Payment Records Found</h5>
                    <p>Your payment transactions will appear here once processed by the admin.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="payment_settings.php" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">
            <i class="bi bi-bank me-2"></i> Update Bank/UPI Details
        </a>

        <div class="alert alert-light border mb-0 p-3 rounded-pill text-secondary fw-semibold">
            <i class="bi bi-headset me-2"></i> For payment queries, please contact HR/Admin.
        </div>
    </div>
</div>

<div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow-lg border-0">
            <div class="modal-header bg-gradient" style="background:linear-gradient(135deg,#4f46e5,#818cf8);color:#fff;">
                <h5 class="modal-title" id="receiptModalLabel"><i class="bi bi-receipt me-2"></i> Payment Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="receiptCard" class="mx-auto" style="max-width:400px;">
                    <!-- Receipt details will be injected here -->
                </div>
            </div>
            <div class="modal-footer">
                <button id="printReceiptBtn" class="btn btn-primary">
                    <i class="bi bi-printer"></i> Print Receipt
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Payment data for modal (from PHP array)
const paymentData = {};
<?php foreach($payment_data as $row): ?>
paymentData[<?= (int)$row['id'] ?>] = {
    amount: "₹<?= number_format($row['amount'],2) ?>",
    status: "Paid",
    method: "<?= s($row['method'] ?? 'Cash') ?>",
    notes: "<?= s($row['notes'] ?: '-') ?>",
    date: "<?= date('d M Y, h:i A', strtotime($row['payment_date'])) ?>",
    transaction_id: "<?= s($row['transaction_id'] ?? '-') ?>",
    sender: "<?= s($worker['name']) ?>",
    receiver: "Admin",
};
<?php endforeach; ?>

document.addEventListener('DOMContentLoaded', function() {
    const receiptModal = document.getElementById('receiptModal');
    const receiptCard = document.getElementById('receiptCard');
    let currentId = null;
    document.querySelectorAll('button[data-bs-target="#receiptModal"]').forEach(btn => {
        btn.addEventListener('click', function(){
            const id = this.closest('tr').querySelector('td').textContent.trim();
            currentId = Object.keys(paymentData)[id-1];
            const data = paymentData[currentId];
            if(data){
                receiptCard.innerHTML = `
                <div class="card shadow-lg border-0 rounded-4 p-4" style="background:#f8fafc;">
                    <div class="d-flex align-items-center mb-3">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($worker['name']) ?>&background=4f46e5&color=fff" class="rounded-circle border border-2 me-3" style="width:54px;height:54px;object-fit:cover;" alt="Avatar">
                        <div>
                            <div class="fw-bold fs-5 text-primary">${data.sender}</div>
                            <div class="text-muted small">Worker ID: <?= s($worker['id']) ?> | Wage: ₹<?= number_format($daily_wage,2) ?></div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h4 class="fw-bold mb-0"><i class="bi bi-currency-rupee text-success me-2"></i> ${data.amount}</h4>
                        <span class="badge bg-success px-3 py-2 rounded-pill fs-6"><i class="bi bi-check-circle me-1"></i> ${data.status}</span>
                    </div>
                    <hr>
                    <div class="mb-2"><strong>Transaction ID:</strong> <span class="text-secondary">${data.transaction_id}</span></div>
                    <div class="mb-2"><strong>Payment Method:</strong> <span>${data.method}</span></div>
                    <div class="mb-2"><strong>Notes:</strong> <span>${data.notes}</span></div>
                    <div class="mb-2"><strong>Payment Time:</strong> <span>${data.date}</span></div>
                    <hr>
                    <div class="d-flex align-items-center justify-content-between mt-3">
                        <span class="badge bg-primary px-3 py-2 rounded-pill"><i class="bi bi-person-badge me-1"></i> Sent by Admin</span>
                        <span class="text-muted small">Receiver: Admin</span>
                    </div>
                </div>`;
            }
        });
    });
    document.getElementById('printReceiptBtn').addEventListener('click', function(){
        window.print();
    });
});
</script>

<?php include 'footer.php'; ?>