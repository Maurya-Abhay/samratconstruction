<?php
// worker_make_payment.php

include 'topheader.php';
include 'sidenavbar.php';

// --- Database Setup (Keep as is, but structure better) ---
$conn->query("CREATE TABLE IF NOT EXISTS worker_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    method VARCHAR(50) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_worker_date (worker_id, payment_date),
    CONSTRAINT fk_wp_worker FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS worker_payment_settings (
    worker_id INT PRIMARY KEY,
    upi_vpa VARCHAR(120) NULL,
    upi_payee VARCHAR(120) NULL,
    upi_mobile VARCHAR(20) NULL,
    upi_qr_path VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_wps_worker FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$ok = null; $err = null; $receipt_id = null;
$worker_id = isset($_GET['worker_id']) ? (int)$_GET['worker_id'] : 0;

// Load workers for select
$workers = $conn->query("SELECT id, name FROM workers ORDER BY name ASC");

// --- Handle Payment Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_payment'])) {
    $worker_id = (int)($_POST['worker_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $date = trim($_POST['payment_date'] ?? date('Y-m-d'));
    $method = trim($_POST['method'] ?? 'Cash');
    $notes = trim($_POST['notes'] ?? '');

    if ($worker_id <= 0) { 
        $err = 'Please select a worker.'; 
    } elseif (!($amount > 0)) { 
        $err = 'Amount must be greater than 0.'; 
    } else {
        $stmt = $conn->prepare('INSERT INTO worker_payments (worker_id, amount, payment_date, method, notes) VALUES (?,?,?,?,?)');
        $stmt->bind_param('idsss', $worker_id, $amount, $date, $method, $notes);
        if ($stmt->execute()) {
            $receipt_id = $stmt->insert_id;
            $ok = 'Payment recorded successfully!';
            $_POST = [];
            // POST-Redirect-GET: prevent double payment on refresh
            header("Location: worker_make_payment.php?worker_id=$worker_id&status=ok&receipt=$receipt_id");
            exit;
        } else { 
            $err = 'Failed to save payment: ' . $stmt->error; 
        }
        $stmt->close();
    }
}

// Check for successful redirection status
if (isset($_GET['status']) && $_GET['status'] == 'ok' && isset($_GET['receipt'])) {
    $ok = 'Payment recorded successfully!';
    $receipt_id = (int)$_GET['receipt'];
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: "success",
                title: "Success!",
                text: "' . $ok . '",
                confirmButtonText: "OK",
                allowOutsideClick: false
            }).then(function() {
                window.location.href = "worker_make_payment.php?worker_id=' . $worker_id . '";
            });
        });
    </script>';
}


// --- Load Selected Worker Details and Summary ---
$sel = null; $wps = null;
if ($worker_id > 0) {
    $res = $conn->query("SELECT * FROM workers WHERE id=$worker_id");
    $sel = $res ? $res->fetch_assoc() : null;

    $res2 = $conn->query("SELECT * FROM worker_payment_settings WHERE worker_id=$worker_id");
    $wps = $res2 ? $res2->fetch_assoc() : null;
}

$present_days = 0; $daily_wage = 0.0; $total_earned = 0.0; $total_paid_sum = 0.0; $remaining_due = 0.0;

if ($sel) {
    $daily_wage = (float)($sel['salary'] ?? 0);
    
    // Total Present Days
    if ($r = $conn->query("SELECT COUNT(*) FROM worker_attendance WHERE worker_id=".(int)$sel['id']." AND status='Present'")) {
        $row = $r->fetch_row(); $present_days = (int)($row[0] ?? 0); $r->free();
    }
    
    // Total Paid Sum
    if ($r = $conn->query("SELECT COALESCE(SUM(amount),0) FROM worker_payments WHERE worker_id=".(int)$sel['id'])) {
        $row = $r->fetch_row(); $total_paid_sum = (float)($row[0] ?? 0); $r->free();
    }

    $total_earned = $present_days * $daily_wage;
    $remaining_due = $total_earned - $total_paid_sum;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Payment | Worker Pay</title>
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
        }

        /* Payment Summary Card */
        .summary-card-body {
            background: #f0f3fa;
            border-radius: 0 0 12px 12px;
        }

        .summary-item {
            padding: 8px 0;
            border-bottom: 1px dashed #e3e6f0;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .badge-due {
            font-size: 1em;
            padding: 0.5em 1em;
            border-radius: 8px;
        }

        .upi-qr-box {
            display: inline-block;
            padding: 10px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-dark fw-bold m-0"><i class="bi bi-currency-rupee text-success me-2"></i>Record Worker Payment</h3>
        <div>
            <a href="worker_payments_history.php" class="btn btn-outline-secondary me-2"><i class="bi bi-clock-history"></i> History</a>
            <a href="dashboard.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left"></i> Back</a>
        </div>
    </div>

    <?php if ($ok): ?><div class="alert alert-success d-flex align-items-center"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($ok) ?> <?php if ($receipt_id): ?><a class="btn btn-sm btn-outline-success ms-3" target="_blank" href="worker_payment_receipt.php?id=<?= (int)$receipt_id ?>"><i class="bi bi-receipt"></i> View Receipt</a><?php endif; ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-danger d-flex align-items-center"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <div class="card card-modern shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">1. Select Worker</div>
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-8">
                    <label for="workerSelect" class="form-label visually-hidden">Worker</label>
                    <select id="workerSelect" name="worker_id" class="form-select form-select-lg" required>
                        <option value="">-- Select Worker --</option>
                        <?php if ($workers && $workers->num_rows): while($w=$workers->fetch_assoc()): ?>
                            <option value="<?= (int)$w['id'] ?>" <?= $worker_id==(int)$w['id']?'selected':'' ?>><?= htmlspecialchars($w['name']) ?> (#<?= (int)$w['id'] ?>)</option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary btn-lg w-100"><i class="bi bi-box-arrow-in-right"></i> Load Details</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($sel): ?>
    <div class="row g-4">
        
        <div class="col-lg-5">
            <div class="card card-modern h-100">
                <div class="card-header bg-white fw-bold">Worker Details & Summary</div>
                <div class="card-body p-3">
                    <h5 class="fw-bold text-primary"><?= htmlspecialchars($sel['name']) ?> <span class="text-muted small">#<?= (int)$sel['id'] ?></span></h5>
                    <p class="text-muted small mb-3">Daily Wage: **₹<?= number_format($daily_wage, 2) ?>**</p>
                    
                    <div class="summary-card-body p-3">
                        <div class="summary-item d-flex justify-content-between align-items-center">
                            <span>Total Present Days</span>
                            <span class="fw-bold text-dark"><?= (int)$present_days ?></span>
                        </div>
                        <div class="summary-item d-flex justify-content-between align-items-center">
                            <span>Total Earned (Wage x Days)</span>
                            <span class="fw-bold text-success">₹<?= number_format($total_earned, 2) ?></span>
                        </div>
                        <div class="summary-item d-flex justify-content-between align-items-center">
                            <span>Total Paid To Date</span>
                            <span class="fw-bold text-danger">₹<?= number_format($total_paid_sum, 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-3">
                            <span class="fw-bold">CURRENT BALANCE</span>
                            <div class="text-end">
                                <?php if ($remaining_due > 0): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger badge-due">Due: ₹<?= number_format($remaining_due, 2) ?></span>
                                <?php elseif ($remaining_due < 0): ?>
                                    <span class="badge bg-info-subtle text-info border border-info badge-due">Advance: ₹<?= number_format(abs($remaining_due), 2) ?></span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success border border-success badge-due"><i class="bi bi-check2"></i> Settled</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($wps && (!empty($wps['upi_vpa']) || !empty($wps['upi_mobile']))): ?>
                        <h6 class="mt-4 mb-2"><i class="bi bi-phone me-1 text-primary"></i> Payment Instructions</h6>
                        <div class="alert alert-light border p-3 small">
                            <?php if (!empty($wps['upi_vpa'])): ?>
                                <div class="mb-1"><strong>UPI VPA:</strong> <span class="text-success"><?= htmlspecialchars($wps['upi_vpa']) ?></span></div>
                            <?php endif; ?>
                            <?php if (!empty($wps['upi_mobile'])): ?>
                                <div class="mb-1"><strong>Mobile:</strong> <a href="tel:<?= htmlspecialchars($wps['upi_mobile']) ?>"><?= htmlspecialchars($wps['upi_mobile']) ?></a></div>
                            <?php endif; ?>
                            <div class="mb-1"><strong>Payee:</strong> <?= htmlspecialchars($wps['upi_payee'] ?: $sel['name']) ?></div>
                            <?php if (!empty($wps['upi_qr_path'])): ?>
                                <button type="button" class="btn btn-outline-secondary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#workerQRModal">
                                    <i class="bi bi-qr-code"></i> View Full QR
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-7">
            <div class="card card-modern h-100">
                <div class="card-header bg-white fw-bold">2. Record Transaction</div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="worker_id" value="<?= (int)$sel['id'] ?>" />
                        
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Amount (₹)</label>
                            <div class="input-group">
                                <input type="number" id="payAmount" name="amount" min="1" step="0.01" class="form-control form-control-lg" required />
                                <?php if ($remaining_due > 0): ?>
                                    <button type="button" class="btn btn-outline-danger" id="useDueBtn" title="Set amount to remaining due (₹<?= number_format($remaining_due, 2) ?>)"><i class="bi bi-wallet"></i> Max Due</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Payment Date</label>
                            <input type="date" id="payDate" name="payment_date" value="<?= date('Y-m-d') ?>" class="form-control form-control-lg" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Method</label>
                            <select id="payMethod" name="method" class="form-select form-select-lg">
                                <option>Cash</option>
                                <option>UPI</option>
                                <option>Bank Transfer</option>
                                <option>Cheque</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes (optional)</label>
                            <input type="text" id="payNotes" name="notes" class="form-control" placeholder="e.g., Monthly salary, advance for materials, etc." />
                        </div>
                        <div class="col-12 text-end pt-3">
                            <button class="btn btn-success btn-lg" name="make_payment" value="1"><i class="bi bi-cloud-arrow-up-fill me-2"></i> Save Payment Record</button>
                        </div>
                    </form>

                    <?php if ($wps && !empty($wps['upi_vpa'])): ?>
                        <hr class="mt-4 mb-3"/>
                        <div id="upiPayBox" class="border rounded p-3 bg-light d-none">
                            <h5 class="mb-3 text-primary"><i class="bi bi-upc-scan me-2"></i>Live UPI Payment Details (Dynamic QR)</h5>
                            <div class="row g-3 align-items-start">
                                <div class="col-md-4 text-center">
                                    <div id="genQR" class="upi-qr-box mx-auto"></div>
                                    <p class="small text-muted mt-2">Scan this QR to pay directly using the amount entered above.</p>
                                </div>
                                <div class="col-md-8">
                                    <table class="table table-sm table-borderless small">
                                        <tr><th width="30%">To Pay:</th><td><span id="upiAmtShow" class="fw-bold text-success">₹0.00</span></td></tr>
                                        <tr><th>VPA:</th><td><?= htmlspecialchars($wps['upi_vpa']) ?></td></tr>
                                        <tr><th>Payee:</th><td><?= htmlspecialchars($wps['upi_payee'] ?: $sel['name']) ?></td></tr>
                                        <tr class="d-none" id="upiNoteRow"><th>Note:</th><td id="upiNoteShow"></td></tr>
                                    </table>
                                    
                                    <a id="upiOpenBtn" class="btn btn-primary me-2" href="#" target="_blank"><i class="bi bi-phone me-1"></i> Open in UPI App</a>
                                    
                                    <div class="alert alert-warning mt-3 mb-0" role="alert">
                                        <strong>IMPORTANT:</strong> After completing the payment in the UPI app, you must return here and click **'Save Payment Record'** to officially log the transaction in the system.
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="workerQRModal" tabindex="-1" aria-labelledby="workerQRModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content card-modern">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="workerQRModalLabel"><i class="bi bi-qr-code me-2"></i>Worker UPI QR (Static)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <?php if (!empty($wps['upi_qr_path'])): ?>
                        <img src="<?= htmlspecialchars($wps['upi_qr_path']) ?>" alt="Worker QR" class="img-fluid border rounded" style="max-width:300px;" />
                        <p class="text-muted small mt-3 mb-0">This is the static QR code uploaded by the worker.</p>
                    <?php else: ?>
                        <div class="text-muted py-3">No static QR image has been uploaded by the worker.</div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
    (function(){
        const methodSel = document.getElementById('payMethod');
        const amtInput = document.getElementById('payAmount');
        const notesInput = document.getElementById('payNotes');
        const useDueBtn = document.getElementById('useDueBtn');
        const box = document.getElementById('upiPayBox');
        const amtShow = document.getElementById('upiAmtShow');
        const noteRow = document.getElementById('upiNoteRow');
        const noteShow = document.getElementById('upiNoteShow');
        const link = document.getElementById('upiOpenBtn');
        const qrBox = document.getElementById('genQR');
        const vpa = <?= json_encode($wps['upi_vpa'] ?? '') ?>;
        const payee = <?= json_encode(($wps['upi_payee'] ?? '') ?: ($sel['name'] ?? '')) ?>;
        const due = <?= json_encode($remaining_due) ?>;

        function buildUpiUrl(vpa, payee, amount, note){
            const p = new URLSearchParams({ pa:vpa, pn:payee, am:amount.toFixed(2), cu:'INR' });
            if (note) p.set('tn', note.replace(/\s/g, '%20')); // Replace spaces for safe URL
            return 'upi://pay?' + p.toString();
        }

        let qrCodeInstance = null;

        function refreshUpiBox(){
            if (!methodSel || !amtInput || !box) return;

            const isUpi = (methodSel.value === 'UPI');
            const amt = parseFloat(amtInput.value || '0');
            const note = (notesInput?.value || '').trim();
            const ok = isUpi && vpa && (amt > 0);

            if (ok){
                // Show box
                box.classList.remove('d-none');
                
                // Update text
                if (amtShow) amtShow.textContent = '₹' + amt.toFixed(2);
                if (note) { 
                    noteRow?.classList.remove('d-none'); 
                    if (noteShow) noteShow.textContent = note; 
                } else { 
                    noteRow?.classList.add('d-none'); 
                }
                
                // Build UPI URL
                const url = buildUpiUrl(vpa, payee, amt, note);
                if (link) link.href = url;

                // Generate QR Code dynamically
                if (qrBox && window.QRCode){ 
                    qrBox.innerHTML = ''; 
                    qrCodeInstance = new QRCode(qrBox, { 
                        text: url, 
                        width: 180, 
                        height: 180, 
                        colorDark: '#000', 
                        colorLight: '#fff', 
                        correctLevel: QRCode.CorrectLevel.H 
                    }); 
                }
            } else {
                box.classList.add('d-none');
            }
        }

        methodSel?.addEventListener('change', refreshUpiBox);
        amtInput?.addEventListener('input', refreshUpiBox);
        notesInput?.addEventListener('input', refreshUpiBox);
        
        useDueBtn?.addEventListener('click', function(){ 
            if (typeof due === 'number' && due > 0 && amtInput){ 
                amtInput.value = due.toFixed(2); 
                // Force triggering input event to update UPI box
                amtInput.dispatchEvent(new Event('input')); 
            } 
        });
        
        // Initial state
        refreshUpiBox();
    })();
    </script>
</div>

<?php include 'downfooter.php'; ?>
</body>
</html>